<?php

namespace App\Services\Wallet;

use App\DTOs\Wallet\TopUpData;
use App\Exceptions\MissingAgencyException;
use App\Helpers\ReferenceNumberGenerator;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\WalletTransactionType;
use App\Models\TravelAgency;
use App\Notifications\Admin\NewWalletDepositNotification;
use App\Services\Audit\AuditLogger;
use App\Services\Notification\AdminNotifier;
use App\Services\Pdf\PdfRenderer;
use App\Services\Settings\PointSettingService;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class WalletService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly PdfRenderer $pdfRenderer,
        private readonly AdminNotifier $adminNotifier,
        private readonly PointSettingService $pointSetting,
    ) {
    }

    public function myWallet(User $user): Wallet
    {
        $agency = $user->agencies()->first();

        if (!$agency) {
            throw new MissingAgencyException('No agency associated with user', 404);
        }

        $wallet = $agency->wallet;
        $wallet->load(['transactions' => fn ($q) => $q->with('transactionType')->limit(50)]);
        $wallet->status;

        return $wallet;
    }

    public function find(Wallet $wallet): Wallet
    {
        $wallet->load(['agency', 'transactions' => fn ($q) => $q->with('transactionType')->limit(50)]);

        return $wallet;
    }

    /** Full transaction ledger for the user's own wallet, paginated (not capped like myWallet()'s snapshot). */
    public function paginateMyTransactions(User $user, int $perPage = 20): LengthAwarePaginator
    {
        $wallet = $this->myWallet($user);

        return WalletTransaction::where('wallet_id', $wallet->id)
            ->with('transactionType')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findByAgency(int $agencyId): Wallet
    {
        $wallet = Wallet::where('agency_id', $agencyId)->firstOrFail();
        $wallet->load(['agency', 'transactions' => fn ($q) => $q->with('transactionType')->limit(50)]);

        return $wallet;
    }

    public function topUp(Wallet $wallet, TopUpData $data): Wallet
    {
        DB::transaction(function () use ($wallet, $data) {
            // Lock the wallet row to prevent concurrent top-ups producing wrong balance_after
            $wallet = Wallet::where('id', $wallet->id)->lockForUpdate()->firstOrFail();

            $type = WalletTransactionType::firstOrCreate(['name' => 'topup'], ['label' => 'Top-Up']);
            $balanceBefore = (float) $wallet->balance;
            $points = $this->pointSetting->moneyToPoints((float) $data->amount);
            $newBalance = $wallet->balance + $points;

            $wallet->transactions()->create([
                'uuid' => Str::uuid(),
                'transaction_type_id' => $type->id,
                'amount' => $points,
                'money_equivalent' => (float) $data->amount,
                'balance_after' => $newBalance,
                'description' => $data->description ?? 'Wallet top-up',
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);

            $wallet->update(['balance' => $newBalance]);

            $this->audit->log('wallet.topup', $wallet, ['balance' => $balanceBefore], [
                'points_credited' => $points,
                'balance' => (float) $newBalance,
            ]);
        });

        return $wallet->fresh();
    }

    public function setCreditLimit(Wallet $wallet, float $creditLimit): Wallet
    {
        $previousLimit = (float) $wallet->credit_limit;

        $wallet->update(['credit_limit' => $creditLimit]);

        $this->audit->log('wallet.credit_limit_changed', $wallet, ['credit_limit' => $previousLimit], ['credit_limit' => $creditLimit]);

        return $wallet;
    }

    public function submitDeposit(User $user, float $amount, string $referenceNumber, ?UploadedFile $receipt = null): WalletTransaction
    {
        $wallet = $this->myWallet($user);

        // Generated up front (not from the transaction's auto-increment id, which doesn't exist yet)
        // so the receipt can be stored under its own folder alongside the agency's, per deposit.
        $depositNumber = ReferenceNumberGenerator::make('DEP');

        $receiptPath = null;
        if ($receipt) {
            $extension = $receipt->extension() ?: $receipt->getClientOriginalExtension() ?: 'bin';
            // storeAs creates the "uploads/{agency}/{deposit}" directories automatically if missing.
            $receiptPath = $receipt->storeAs(
                "uploads/{$wallet->agency_id}/{$depositNumber}",
                "receipt.{$extension}",
                'public'
            );
        }

        return DB::transaction(function () use ($wallet, $amount, $referenceNumber, $receiptPath, $depositNumber, $user) {
            $type = WalletTransactionType::firstOrCreate(['name' => 'deposit_pending'], ['label' => 'إيداع قيد المراجعة']);
            // Preview only — the actual points credited are recomputed at approval time (see approveDeposit),
            // since the rate may change between submission and approval.
            $previewPoints = $this->pointSetting->moneyToPoints($amount);

            $tx = $wallet->transactions()->create([
                'uuid' => Str::uuid(),
                'transaction_type_id' => $type->id,
                'amount' => $previewPoints,
                'money_equivalent' => $amount,
                'balance_after' => $wallet->balance, // balance unchanged until approved
                'description' => "مرجع التحويل: {$referenceNumber}",
                'metadata' => [
                    'reference_number' => $referenceNumber,
                    'deposit_number' => $depositNumber,
                    'receipt_path' => $receiptPath,
                    'status' => 'pending',
                ],
                'created_by' => $user->id,
                'created_at' => now(),
            ]);

            $this->audit->log('wallet.deposit_notice_submitted', $wallet, [], [
                'amount' => $amount,
                'reference_number' => $referenceNumber,
                'transaction_id' => $tx->id,
            ]);

            $tx->load('wallet.agency');
            $this->adminNotifier->send(new NewWalletDepositNotification($tx));

            return $tx;
        });
    }

    private function pendingDepositsQuery()
    {
        return \App\Models\WalletTransaction::whereHas('transactionType', function ($q) {
            $q->where('name', 'deposit_pending');
        })
        ->whereJsonContains('metadata->status', 'pending')
        ->with(['wallet.agency', 'createdBy'])
        ->latest('created_at');
    }

    public function listPendingDeposits()
    {
        return $this->pendingDepositsQuery()->get();
    }

    public function paginatePendingDeposits(int $perPage = 20): LengthAwarePaginator
    {
        return $this->pendingDepositsQuery()->paginate($perPage);
    }

    private function myDepositsQuery(User $user, ?string $from = null, ?string $to = null)
    {
        $wallet = $this->myWallet($user);

        return WalletTransaction::where('wallet_id', $wallet->id)
            ->whereHas('transactionType', function ($q) {
                $q->where('name', 'deposit_pending');
            })
            ->when($from, fn ($q) => $q->where('created_at', '>=', Carbon::parse($from)->startOfDay()))
            ->when($to, fn ($q) => $q->where('created_at', '<=', Carbon::parse($to)->endOfDay()))
            ->with('createdBy')
            ->latest('created_at');
    }

    public function listMyDeposits(User $user, ?string $from = null, ?string $to = null)
    {
        return $this->myDepositsQuery($user, $from, $to)->get();
    }

    public function paginateMyDeposits(User $user, ?string $from = null, ?string $to = null, int $perPage = 20): LengthAwarePaginator
    {
        return $this->myDepositsQuery($user, $from, $to)->paginate($perPage);
    }

    private function allDepositsQuery(?string $from = null, ?string $to = null, ?int $agencyId = null)
    {
        return WalletTransaction::whereHas('transactionType', function ($q) {
            $q->where('name', 'deposit_pending');
        })
        ->when($from, fn ($q) => $q->where('created_at', '>=', Carbon::parse($from)->startOfDay()))
        ->when($to, fn ($q) => $q->where('created_at', '<=', Carbon::parse($to)->endOfDay()))
        ->when($agencyId, fn ($q) => $q->whereHas('wallet', fn ($w) => $w->where('agency_id', $agencyId)))
        ->with(['wallet.agency', 'createdBy'])
        ->latest('created_at');
    }

    public function listAllDeposits(?string $from = null, ?string $to = null, ?int $agencyId = null)
    {
        return $this->allDepositsQuery($from, $to, $agencyId)->get();
    }

    public function paginateAllDeposits(?string $from = null, ?string $to = null, ?int $agencyId = null, int $perPage = 20): LengthAwarePaginator
    {
        return $this->allDepositsQuery($from, $to, $agencyId)->paginate($perPage);
    }

    private function depositsReportData($deposits, ?string $from, ?string $to, ?string $agencyName): array
    {
        return [
            'from' => $from,
            'to' => $to,
            'agencyName' => $agencyName,
            'deposits' => $deposits->map(fn (WalletTransaction $dep) => [
                'agency_name' => $dep->wallet?->agency?->name,
                'date' => $dep->created_at->toDateTimeString(),
                'reference_number' => $dep->metadata['reference_number'] ?? '—',
                'points' => (float) $dep->amount,
                'money' => (float) ($dep->money_equivalent ?? $dep->amount),
                'status_label' => match ($dep->metadata['status'] ?? null) {
                    'approved' => 'مقبول',
                    'rejected' => 'مرفوض',
                    default => 'قيد المراجعة',
                },
            ])->values()->all(),
        ];
    }

    public function renderAllDepositsPdf(?string $from, ?string $to, ?int $agencyId)
    {
        $deposits = $this->listAllDeposits($from, $to, $agencyId);
        $agencyName = $agencyId ? TravelAgency::find($agencyId)?->name : null;
        $data = $this->depositsReportData($deposits, $from, $to, $agencyName);

        return $this->pdfRenderer->render('reports.deposits', $data, $this->depositsFilename($from, $to));
    }

    public function renderAllDepositsCsv(?string $from, ?string $to, ?int $agencyId)
    {
        $deposits = $this->listAllDeposits($from, $to, $agencyId);
        $agencyName = $agencyId ? TravelAgency::find($agencyId)?->name : null;

        return $this->depositsCsvResponse($this->depositsReportData($deposits, $from, $to, $agencyName));
    }

    public function renderMyDepositsPdf(User $user, ?string $from, ?string $to)
    {
        $deposits = $this->listMyDeposits($user, $from, $to);
        $agencyName = $user->agencies()->first()?->name;
        $data = $this->depositsReportData($deposits, $from, $to, $agencyName);

        return $this->pdfRenderer->render('reports.deposits', $data, $this->depositsFilename($from, $to));
    }

    public function renderMyDepositsCsv(User $user, ?string $from, ?string $to)
    {
        $deposits = $this->listMyDeposits($user, $from, $to);
        $agencyName = $user->agencies()->first()?->name;

        return $this->depositsCsvResponse($this->depositsReportData($deposits, $from, $to, $agencyName));
    }

    private function depositsCsvResponse(array $data)
    {
        $csv = fopen('php://temp', 'w+');
        fputcsv($csv, ['Agency', 'Date', 'Reference Number', 'Points', 'Amount (SDG)', 'Status']);
        foreach ($data['deposits'] as $dep) {
            fputcsv($csv, [$dep['agency_name'], $dep['date'], $dep['reference_number'], $dep['points'], $dep['money'], $dep['status_label']]);
        }
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return Response::make($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $this->depositsFilename($data['from'], $data['to'], 'csv') . '"',
        ]);
    }

    private function depositsFilename(?string $from, ?string $to, string $ext = 'pdf'): string
    {
        return 'deposits-' . ($from ?: 'all') . '-to-' . ($to ?: 'all') . '.' . $ext;
    }

    public function approveDeposit(int $transactionId): Wallet
    {
        return DB::transaction(function () use ($transactionId) {
            $pendingTx = \App\Models\WalletTransaction::findOrFail($transactionId);
            $wallet = Wallet::where('id', $pendingTx->wallet_id)->lockForUpdate()->firstOrFail();

            $topUpType = WalletTransactionType::firstOrCreate(['name' => 'topup'], ['label' => 'Top-Up']);
            $moneyAmount = (float) ($pendingTx->money_equivalent ?? $pendingTx->amount);
            // Converted at approval time (not submission time) since that's when points actually land
            // and the rate may have changed since the agency submitted the notice.
            $points = $this->pointSetting->moneyToPoints($moneyAmount);
            $newBalance = $wallet->balance + $points;
            $refNum = $pendingTx->metadata['reference_number'] ?? $pendingTx->id;

            // Mark the pending notice as approved via raw DB to bypass immutability guard
            DB::table('wallet_transactions')->where('id', $pendingTx->id)->update([
                'metadata' => json_encode(array_merge($pendingTx->metadata ?? [], [
                    'status' => 'approved',
                    'approved_at' => now()->toDateTimeString(),
                ])),
            ]);

            // Create the actual credit transaction
            $wallet->transactions()->create([
                'uuid' => Str::uuid(),
                'transaction_type_id' => $topUpType->id,
                'amount' => $points,
                'money_equivalent' => $moneyAmount,
                'balance_after' => $newBalance,
                'description' => "موافقة الإيداع (مرجع: {$refNum})",
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);

            $wallet->update(['balance' => $newBalance]);

            $this->audit->log('wallet.deposit_approved', $wallet, [], [
                'points_credited' => $points,
                'new_balance' => $newBalance,
                'ref' => $refNum,
            ]);

            return $wallet->fresh();
        });
    }

    public function rejectDeposit(int $transactionId, ?string $reason = null): \App\Models\WalletTransaction
    {
        $tx = \App\Models\WalletTransaction::findOrFail($transactionId);

        DB::table('wallet_transactions')->where('id', $tx->id)->update([
            'metadata' => json_encode(array_merge($tx->metadata ?? [], [
                'status' => 'rejected',
                'rejection_reason' => $reason ?? 'مرفوض من قبل المحاسبة',
                'rejected_at' => now()->toDateTimeString(),
            ])),
        ]);

        return $tx->fresh();
    }
}

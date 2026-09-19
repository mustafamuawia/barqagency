<?php

namespace App\Services\Report;

use App\DTOs\Report\ReportFilterData;
use App\Models\Order;
use App\Models\TravelAgency;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Pdf\PdfRenderer;
use App\Traits\AuthorizesAgencyOwnership;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Response;

class ReportService
{
    use AuthorizesAgencyOwnership;

    public function __construct(private readonly PdfRenderer $pdfRenderer)
    {
    }

    /** Totals only (no order rows) — this is what the on-screen JSON summary returns. */
    public function summary(User $user, ReportFilterData $filter): array
    {
        $agencyId = $this->resolveAgencyId($user, $filter);

        $totalBookings = abs($this->walletSum('booking_debit', $filter, $agencyId));
        $totalRefunds = abs($this->walletSum('refund_credit', $filter, $agencyId));

        return [
            'from' => $filter->from->toDateString(),
            'to' => $filter->to->toDateString(),
            'agencyName' => $agencyId ? TravelAgency::find($agencyId)?->name : null,
            'totalBookings' => $totalBookings,
            'totalRefunds' => $totalRefunds,
            'net' => $totalBookings - $totalRefunds,
        ];
    }

    /** Paginated order rows for the on-screen booking-history table. */
    public function paginatedOrders(User $user, ReportFilterData $filter, int $perPage = 20): LengthAwarePaginator
    {
        $agencyId = $this->resolveAgencyId($user, $filter);

        return $this->ordersQuery($filter, $agencyId)
            ->paginate($perPage)
            ->through(fn (Order $order) => $this->mapOrder($order));
    }

    public function renderPdf(User $user, ReportFilterData $filter)
    {
        $data = $this->reportData($user, $filter);

        return $this->pdfRenderer->render('reports.financial-summary', $data, "financial-summary-{$data['from']}-to-{$data['to']}.pdf");
    }

    public function renderCsv(User $user, ReportFilterData $filter)
    {
        $data = $this->reportData($user, $filter);

        $csv = fopen('php://temp', 'w+');
        fputcsv($csv, ['Order Number', 'Agency', 'Date', 'Status', 'Amount (SDG)']);
        foreach ($data['orders'] as $order) {
            fputcsv($csv, [$order['order_number'], $order['agency_name'], $order['date'], $order['status_label'], $order['amount']]);
        }
        fputcsv($csv, []);
        fputcsv($csv, ['Total Bookings (SDG)', $data['totalBookings']]);
        fputcsv($csv, ['Total Refunds (SDG)', $data['totalRefunds']]);
        fputcsv($csv, ['Net (SDG)', $data['net']]);
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        $filename = "financial-summary-{$data['from']}-to-{$data['to']}.csv";

        return Response::make($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /** Totals + the full (unpaginated) order set — only for PDF/CSV export, never exposed as JSON. */
    private function reportData(User $user, ReportFilterData $filter): array
    {
        $agencyId = $this->resolveAgencyId($user, $filter);

        $orders = $this->ordersQuery($filter, $agencyId)->get();

        return [
            ...$this->summary($user, $filter),
            'orders' => $orders->map(fn (Order $order) => $this->mapOrder($order))->values()->all(),
        ];
    }

    private function resolveAgencyId(User $user, ReportFilterData $filter): ?int
    {
        return $user->isAgency() ? $this->agencyIdFor($user) : $filter->agencyId;
    }

    private function ordersQuery(ReportFilterData $filter, ?int $agencyId): Builder
    {
        return Order::with(['agency', 'status', 'latestQuote'])
            ->whereBetween('created_at', [$filter->from, $filter->to])
            ->when($agencyId, fn ($q) => $q->where('agency_id', $agencyId))
            ->orderByDesc('created_at');
    }

    private function mapOrder(Order $order): array
    {
        return [
            'order_number' => $order->order_number,
            'agency_name' => $order->agency?->name,
            'date' => $order->created_at->toDateString(),
            'status_label' => $order->status?->label,
            'amount' => (float) ($order->latestQuote?->total_price ?? 0),
        ];
    }

    private function walletSum(string $transactionType, ReportFilterData $filter, ?int $agencyId): float
    {
        return (float) WalletTransaction::whereHas('transactionType', fn ($q) => $q->where('name', $transactionType))
            ->whereBetween('created_at', [$filter->from, $filter->to])
            ->when($agencyId, fn ($q) => $q->whereHas('wallet', fn ($w) => $w->where('agency_id', $agencyId)))
            ->sum('money_equivalent');
    }
}

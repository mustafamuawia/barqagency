<?php

namespace App\Services\Report;

use App\DTOs\Report\ReportFilterData;
use App\Models\Order;
use App\Models\TravelAgency;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Pdf\PdfRenderer;
use App\Traits\AuthorizesAgencyOwnership;
use Illuminate\Support\Facades\Response;

class ReportService
{
    use AuthorizesAgencyOwnership;

    public function __construct(private readonly PdfRenderer $pdfRenderer)
    {
    }

    public function summary(User $user, ReportFilterData $filter): array
    {
        $agencyId = $user->isAgency() ? $this->agencyIdFor($user) : $filter->agencyId;

        $totalBookings = abs($this->walletSum('booking_debit', $filter, $agencyId));
        $totalRefunds = abs($this->walletSum('refund_credit', $filter, $agencyId));

        $orders = Order::with(['agency', 'status', 'latestQuote'])
            ->whereBetween('created_at', [$filter->from, $filter->to])
            ->when($agencyId, fn ($q) => $q->where('agency_id', $agencyId))
            ->orderByDesc('created_at')
            ->get();

        return [
            'from' => $filter->from->toDateString(),
            'to' => $filter->to->toDateString(),
            'agencyName' => $agencyId ? TravelAgency::find($agencyId)?->name : null,
            'totalBookings' => $totalBookings,
            'totalRefunds' => $totalRefunds,
            'net' => $totalBookings - $totalRefunds,
            'orders' => $orders->map(fn (Order $order) => [
                'order_number' => $order->order_number,
                'agency_name' => $order->agency?->name,
                'date' => $order->created_at->toDateString(),
                'status_label' => $order->status?->label,
                'amount' => (float) ($order->latestQuote?->total_price ?? 0),
            ])->values()->all(),
        ];
    }

    public function renderPdf(User $user, ReportFilterData $filter)
    {
        $data = $this->summary($user, $filter);

        return $this->pdfRenderer->render('reports.financial-summary', $data, "financial-summary-{$data['from']}-to-{$data['to']}.pdf");
    }

    public function renderCsv(User $user, ReportFilterData $filter)
    {
        $data = $this->summary($user, $filter);

        $csv = fopen('php://temp', 'w+');
        fputcsv($csv, ['Order Number', 'Agency', 'Date', 'Status', 'Amount']);
        foreach ($data['orders'] as $order) {
            fputcsv($csv, [$order['order_number'], $order['agency_name'], $order['date'], $order['status_label'], $order['amount']]);
        }
        fputcsv($csv, []);
        fputcsv($csv, ['Total Bookings', $data['totalBookings']]);
        fputcsv($csv, ['Total Refunds', $data['totalRefunds']]);
        fputcsv($csv, ['Net', $data['net']]);
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        $filename = "financial-summary-{$data['from']}-to-{$data['to']}.csv";

        return Response::make($content, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function walletSum(string $transactionType, ReportFilterData $filter, ?int $agencyId): float
    {
        return (float) WalletTransaction::whereHas('transactionType', fn ($q) => $q->where('name', $transactionType))
            ->whereBetween('created_at', [$filter->from, $filter->to])
            ->when($agencyId, fn ($q) => $q->whereHas('wallet', fn ($w) => $w->where('agency_id', $agencyId)))
            ->sum('amount');
    }
}

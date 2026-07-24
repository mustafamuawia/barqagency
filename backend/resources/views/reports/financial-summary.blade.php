<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="utf-8">
    @include('reports.partials.style')
</head>
<body>
    <div class="header">
        <div class="brand">Barq Agency</div>
        <div class="subtitle">B2B Travel & Tourism Portal</div>
        <div class="doc-title" dir="rtl">التقرير المالي</div>
        <div class="doc-subtitle" dir="ltr">Financial Report</div>
    </div>

    <div class="card">
        <div class="row">
            <div class="col">
                <div class="label">الفترة</div>
                <div class="value" style="font-size: 11px;">{{ $from }} — {{ $to }}</div>
            </div>
            @if ($agencyName)
                <div class="col">
                    <div class="label">الوكالة</div>
                    <div class="value" style="font-size: 11px;">{{ $agencyName }}</div>
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-title">الملخص المالي</div>
        <div class="row">
            <div class="col">
                <div class="label">إجمالي الحجوزات</div>
                <div class="value">{{ number_format($totalBookings, 3) }}</div>
            </div>
            <div class="col">
                <div class="label">إجمالي الاستردادات</div>
                <div class="value red">{{ number_format($totalRefunds, 3) }}</div>
            </div>
            <div class="col">
                <div class="label">صافي الإيرادات</div>
                <div class="value emerald">{{ number_format($totalBookings - $totalRefunds, 3) }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-title">سجل الحجوزات ({{ count($orders) }})</div>
        <table class="data">
            <thead>
                <tr>
                    <th>رقم الطلب</th>
                    @if (!$agencyName)
                        <th>الوكالة</th>
                    @endif
                    <th>التاريخ</th>
                    <th>الحالة</th>
                    <th>القيمة</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr>
                        <td>{{ $order['order_number'] }}</td>
                        @if (!$agencyName)
                            <td>{{ $order['agency_name'] }}</td>
                        @endif
                        <td>{{ $order['date'] }}</td>
                        <td>{{ $order['status_label'] }}</td>
                        <td>{{ number_format($order['amount'], 3) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $agencyName ? 3 : 4 }}" style="text-align: center; color: #94a3b8;">لا توجد حجوزات في هذه الفترة</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="footer" dir="rtl">
        هذا التقرير تم إنشاؤه آلياً بواسطة نظام <span dir="ltr">Barq Agency</span> &nbsp;|&nbsp; <span dir="ltr">© {{ date('Y') }} Barq Agency</span>
    </div>
</body>
</html>

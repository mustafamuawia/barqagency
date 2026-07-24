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
        <div class="doc-title" dir="rtl">سجل إشعارات الإيداع البنكي</div>
        <div class="doc-subtitle" dir="ltr">Bank Deposits Report</div>
    </div>

    <div class="card">
        <div class="row">
            <div class="col">
                <div class="label">الفترة</div>
                <div class="value" style="font-size: 11px;">{{ $from ?? '—' }} — {{ $to ?? '—' }}</div>
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
        <div class="card-title">سجل الإيداعات ({{ count($deposits) }})</div>
        <table class="data">
            <thead>
                <tr>
                    @if (!$agencyName)
                        <th>الوكالة</th>
                    @endif
                    <th>التاريخ</th>
                    <th>رقم التحويل</th>
                    <th>المبلغ</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($deposits as $dep)
                    <tr>
                        @if (!$agencyName)
                            <td>{{ $dep['agency_name'] }}</td>
                        @endif
                        <td>{{ $dep['date'] }}</td>
                        <td>{{ $dep['reference_number'] }}</td>
                        <td>{{ number_format($dep['amount'], 3) }}</td>
                        <td>{{ $dep['status_label'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $agencyName ? 4 : 5 }}" style="text-align: center; color: #94a3b8;">لا توجد إشعارات إيداع في هذه الفترة</td>
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

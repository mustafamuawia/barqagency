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
        <div class="doc-title" dir="rtl">سند استرداد</div>
        <div class="doc-subtitle" dir="ltr">Credit Note</div>
    </div>

    <div class="card">
        <div class="card-title">تفاصيل السند</div>
        <div class="row">
            <div class="col">
                <div class="label">رقم الاسترداد</div>
                <div class="value">{{ $refund->refund_number }}</div>
            </div>
            <div class="col">
                <div class="label">رقم الطلب</div>
                <div class="value">{{ $refund->order->order_number }}</div>
            </div>
            <div class="col">
                <div class="label">الوكالة</div>
                <div class="value">{{ $refund->agency->name }}</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-title">التفاصيل المالية</div>
        <div class="row">
            <div class="col">
                <div class="label">المبلغ الأصلي</div>
                <div class="value">{{ number_format($refund->original_amount, 3) }} ج.س</div>
            </div>
            <div class="col">
                <div class="label">مبلغ الجزاء</div>
                <div class="value red">{{ number_format($refund->penalty_amount, 3) }} ج.س</div>
            </div>
            <div class="col">
                <div class="label">صافي المبلغ المسترد</div>
                <div class="value emerald">{{ number_format($refund->refund_amount, 3) }} ج.س</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-title">السبب والملاحظات</div>
        <p style="margin: 4px 0;"><span class="label">سبب الاسترداد:</span> {{ $refund->reason }}</p>
        @if ($refund->admin_remarks)
            <p style="margin: 4px 0;"><span class="label">ملاحظات الإدارة:</span> {{ $refund->admin_remarks }}</p>
        @endif
    </div>

    <div class="card">
        <div class="row">
            <div class="col">
                <div class="label">تاريخ الطلب</div>
                <div class="value" style="font-size: 11px;">{{ $refund->requested_at?->format('Y-m-d H:i') }}</div>
            </div>
            <div class="col">
                <div class="label">تاريخ الاعتماد</div>
                <div class="value" style="font-size: 11px;">{{ $refund->decided_at?->format('Y-m-d H:i') }}</div>
            </div>
            <div class="col">
                <div class="label">اعتمد بواسطة</div>
                <div class="value" style="font-size: 11px;">{{ $refund->decidedBy?->name }}</div>
            </div>
        </div>
    </div>

    <div class="footer" dir="rtl">
        هذا المستند تم إنشاؤه آلياً بواسطة نظام <span dir="ltr">Barq Agency</span> ولا يتطلب توقيعاً. &nbsp;|&nbsp; <span dir="ltr">© {{ date('Y') }} Barq Agency</span>
    </div>
</body>
</html>

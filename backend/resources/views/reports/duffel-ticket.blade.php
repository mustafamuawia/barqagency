<!DOCTYPE html>
<html dir="rtl">
<head>
    <meta charset="utf-8">
    @include('reports.partials.style')
    <style>
        .pnr {
            font-family: monospace;
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 3px;
            color: #f59e0b;
        }
        .ticket-num {
            font-family: monospace;
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 1px;
            color: #0f172a;
        }
        .seg-code {
            font-size: 20px;
            font-weight: bold;
            font-family: monospace;
            color: #0f172a;
        }
        .time-val {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
        }
        .time-date {
            font-size: 9px;
            color: #64748b;
        }
        .time-label {
            font-size: 8px;
            color: #94a3b8;
        }
        .airport-name {
            font-size: 9px;
            color: #64748b;
        }
        .flight-badge {
            background: #f1f5f9;
            border-radius: 6px;
            padding: 2px 8px;
            font-size: 10px;
            color: #475569;
            font-family: monospace;
        }
        .segment-box {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 8px;
            page-break-inside: avoid;
        }
        .return-divider {
            border-top: 2px dashed #94a3b8;
            margin: 10px 0 8px 0;
            padding-top: 10px;
            text-align: center;
            font-size: 9px;
            color: #475569;
            font-weight: bold;
        }
        .connection-note {
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
            margin-bottom: 6px;
        }
    </style>
</head>
<body>

@php
    $docLabels = [
        'electronic_ticket'    => ['ar' => 'التذكرة الإلكترونية',  'en' => 'Electronic Ticket'],
        'booking_confirmation' => ['ar' => 'تأكيد الحجز',          'en' => 'Booking Confirmation'],
        'booking_pending'      => ['ar' => 'حجز معلق',             'en' => 'Booking Pending'],
    ];
    $docType = $ticket['doc_type'] ?? 'booking_confirmation';
    $label   = $docLabels[$docType] ?? $docLabels['booking_confirmation'];

    $bookingStatusMap = [
        'confirmed' => ['ar' => 'مؤكد',     'pill' => 'pill-confirmed'],
        'pending'   => ['ar' => 'معلق',      'pill' => 'pill-pending'],
        'cancelled' => ['ar' => 'ملغى',      'pill' => 'pill-cancelled'],
        'unknown'   => ['ar' => 'غير محدد', 'pill' => 'pill-not-issued'],
    ];
    $bookingStatus = $ticket['booking_status'] ?? 'confirmed';
    $bStatus = $bookingStatusMap[$bookingStatus] ?? $bookingStatusMap['unknown'];

    $ticketStatus = $ticket['ticket_status'] ?? 'not_issued';
    $tStatus = $ticketStatus === 'issued'
        ? ['ar' => 'التذكرة صادرة',   'pill' => 'pill-issued']
        : ['ar' => 'التذكرة لم تُصدر', 'pill' => 'pill-not-issued'];

    $typeMap = ['adult' => 'بالغ', 'child' => 'طفل', 'infant' => 'رضيع'];
    $bagTypeMap = [
        'checked'      => 'أمتعة مسجّلة',
        'carry_on'     => 'حقيبة مقصورة',
        'extra_checked' => 'أمتعة إضافية',
    ];
@endphp

{{-- ============================================================ HEADER --}}
@php
    $pillBgBooking = match($bookingStatus) {
        'confirmed' => '#d1fae5', 'pending' => '#fef3c7', 'cancelled' => '#fee2e2', default => '#f1f5f9'
    };
    $pillFgBooking = match($bookingStatus) {
        'confirmed' => '#065f46', 'pending' => '#92400e', 'cancelled' => '#991b1b', default => '#475569'
    };
    $pillBgTicket = $ticketStatus === 'issued' ? '#d1fae5' : '#f1f5f9';
    $pillFgTicket = $ticketStatus === 'issued' ? '#065f46' : '#475569';
@endphp
<div class="header">
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="vertical-align:top; width:50%;">
                <div class="brand">Barq Agency</div>
                <div class="subtitle">B2B Travel &amp; Tourism Portal</div>
            </td>
            <td style="vertical-align:top; text-align:left; width:50%;">
                <div class="doc-title">{{ $label['ar'] }}</div>
                <div class="doc-subtitle" dir="ltr">{{ $label['en'] }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding-top:10px;">
                <table style="border-collapse:collapse;">
                    <tr>
                        <td style="padding:0; padding-left:6px;">
                            <div style="background:{{ $pillBgBooking }}; color:{{ $pillFgBooking }}; border-radius:4px; padding:2px 8px; font-size:9px; font-weight:bold;">الحجز: {{ $bStatus['ar'] }}</div>
                        </td>
                        <td style="padding:0;">
                            <div style="background:{{ $pillBgTicket }}; color:{{ $pillFgTicket }}; border-radius:4px; padding:2px 8px; font-size:9px; font-weight:bold;">{{ $tStatus['ar'] }}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>

{{-- ============================================================ BOOKING INFO --}}
<div class="card">
    <div class="row">
        <div class="col">
            <div class="label">رقم الحجز</div>
            <div style="font-size:8px; color:#94a3b8; letter-spacing:1px;">PNR</div>
            <div class="pnr">{{ $ticket['booking_reference'] ?? '—' }}</div>
        </div>
        <div class="col" style="text-align:center;">
            <div class="label">رقم الطلب الداخلي</div>
            <div class="value" style="font-size:11px; font-family:monospace;">{{ $ticket['order_number'] }}</div>
            @if(!empty($ticket['booked_at']))
            <div class="label" style="margin-top:6px;">تاريخ الحجز</div>
            <div style="font-size:10px; color:#0f172a;">{{ \Carbon\Carbon::parse($ticket['booked_at'])->format('Y-m-d H:i') }}</div>
            @endif
        </div>
        <div class="col" style="text-align:left;">
            <div class="label">تاريخ إصدار الوثيقة</div>
            <div style="font-size:11px; font-weight:bold; color:#0f172a;">{{ now()->format('Y-m-d') }}</div>
        </div>
    </div>
    @if(!empty($ticket['booking_references']))
    <div style="margin-top:8px; border-top:1px solid #f1f5f9; padding-top:6px;">
        @foreach($ticket['booking_references'] as $ref)
        @if(!empty($ref['value']))
        <span style="font-size:9px; color:#64748b; margin-left:12px;">
            {{ $ref['type'] ?? '' }}:
            <span style="font-family:monospace; color:#0f172a;">{{ $ref['value'] }}</span>
        </span>
        @endif
        @endforeach
    </div>
    @endif
</div>

{{-- ============================================================ PASSENGERS --}}
@if(!empty($ticket['passengers']))
<div class="card">
    <div class="card-title">المسافرون</div>
    <table class="data" style="table-layout:fixed; width:100%;">
        <thead>
            <tr>
                <th style="text-align:right; width:8%;">#</th>
                <th style="text-align:right; width:40%;">الاسم</th>
                <th style="text-align:center; width:16%;">النوع</th>
                <th style="text-align:center; width:36%;">رقم التذكرة</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ticket['passengers'] as $i => $p)
            <tr>
                <td style="text-align:right;">{{ $i + 1 }}</td>
                <td style="text-align:right; font-weight:bold;">{{ $p['name'] }}</td>
                <td style="text-align:center;">{{ $typeMap[$p['type']] ?? $p['type'] }}</td>
                <td style="text-align:center;">
                    @if($p['ticket_issued'])
                        <span class="ticket-num">{{ $p['ticket_number'] }}</span>
                    @else
                        <span style="font-size:9px; color:#94a3b8;">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ============================================================ FLIGHT SEGMENTS --}}
@if(!empty($ticket['segments']))
<div style="margin-bottom:14px;">
    <div style="font-size:11px; color:#64748b; font-weight:bold; margin-bottom:8px;">تفاصيل الرحلة</div>
    @php $prevSlice = -1; @endphp
    @foreach($ticket['segments'] as $seg)

    @if($seg['slice_index'] !== $prevSlice)
        @if($seg['slice_index'] > 0)
        <div class="return-divider">✈ رحلة العودة</div>
        @endif
        @php $prevSlice = $seg['slice_index']; @endphp
    @endif

    @if($seg['is_connection'])
    <div class="connection-note">⟳ ترانزيت — رحلة متواصلة</div>
    @endif

    <div class="segment-box">
        {{-- Airline + flight + duration row --}}
        <div class="row" style="margin-bottom:10px;">
            <div class="col">
                <div class="label">شركة الطيران</div>
                <div style="font-size:11px; font-weight:bold; color:#0f172a;">{{ $seg['airline'] ?? '—' }}</div>
                @if(!empty($seg['operating_airline']))
                <div style="font-size:9px; color:#64748b;">تشغيل: {{ $seg['operating_airline'] }}</div>
                @endif
            </div>
            <div class="col" style="text-align:center;">
                <span class="flight-badge">{{ $seg['flight_number'] ?: '—' }}</span>
                @if(!empty($seg['aircraft']))
                <div style="font-size:9px; color:#94a3b8; margin-top:4px;">{{ $seg['aircraft'] }}</div>
                @endif
            </div>
            <div class="col" style="text-align:left;">
                @if($seg['duration_minutes'] > 0)
                @php $h = intdiv($seg['duration_minutes'], 60); $m = $seg['duration_minutes'] % 60; @endphp
                <div class="label">مدة الرحلة</div>
                <div style="font-size:11px; font-weight:bold; color:#0f172a;" dir="ltr">{{ $h }}h {{ $m }}m</div>
                @endif
            </div>
        </div>

        {{-- Origin → Destination --}}
        <div style="display:table; width:100%;">
            <div style="display:table-cell; width:38%; text-align:right; vertical-align:top;">
                <div class="seg-code">{{ $seg['origin_code'] ?? '—' }}</div>
                @if(!empty($seg['origin_city']))
                    <div class="airport-name">{{ $seg['origin_city'] }}</div>
                @elseif(!empty($seg['origin_name']))
                    <div class="airport-name">{{ $seg['origin_name'] }}</div>
                @endif
                @if(!empty($seg['departing_at']))
                <div class="time-label" style="margin-top:4px;">المغادرة</div>
                <div class="time-val">{{ \Carbon\Carbon::parse($seg['departing_at'])->format('H:i') }}</div>
                <div class="time-date">{{ \Carbon\Carbon::parse($seg['departing_at'])->format('D, d M Y') }}</div>
                @endif
            </div>

            <div style="display:table-cell; width:24%; text-align:center; vertical-align:middle;">
                <div style="border-top:2px solid #f59e0b; width:70%; margin:0 auto;"></div>
            </div>

            <div style="display:table-cell; width:38%; text-align:left; vertical-align:top;">
                <div class="seg-code">{{ $seg['destination_code'] ?? '—' }}</div>
                @if(!empty($seg['destination_city']))
                    <div class="airport-name">{{ $seg['destination_city'] }}</div>
                @elseif(!empty($seg['destination_name']))
                    <div class="airport-name">{{ $seg['destination_name'] }}</div>
                @endif
                @if(!empty($seg['arriving_at']))
                <div class="time-label" style="margin-top:4px;">الوصول</div>
                <div class="time-val">{{ \Carbon\Carbon::parse($seg['arriving_at'])->format('H:i') }}</div>
                <div class="time-date">{{ \Carbon\Carbon::parse($seg['arriving_at'])->format('D, d M Y') }}</div>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- ============================================================ BAGGAGE --}}
@if(!empty($ticket['has_baggage']))
<div class="card">
    <div class="card-title">إقرار الأمتعة</div>
    <table class="data">
        <thead>
            <tr>
                <th style="text-align:right;">المسافر</th>
                <th style="text-align:center;">نوع الأمتعة</th>
                <th style="text-align:center;">الكمية</th>
                <th style="text-align:center;">الوزن</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ticket['baggage'] as $bag)
            @if($bag['quantity'] > 0)
            <tr>
                <td style="text-align:right;">{{ $bag['passenger_name'] }}</td>
                <td style="text-align:center;">
                    {{ $bagTypeMap[$bag['type']] ?? $bag['type'] }}
                    @if(!empty($bag['purchased']))
                    <span style="background:#d1fae5; color:#065f46; border-radius:4px; padding:1px 5px; font-size:7px; font-weight:bold;">مشتراة</span>
                    @endif
                </td>
                <td style="text-align:center; font-weight:bold;">{{ $bag['quantity'] }}</td>
                <td style="text-align:center; font-weight:bold;" dir="ltr">
                    @if(!empty($bag['weight_kg']))
                        {{ $bag['weight_kg'] }} kg
                    @else
                        <span style="color:#94a3b8;">—</span>
                    @endif
                </td>
            </tr>
            @endif
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ============================================================ SEATS --}}
@if(!empty($ticket['has_seats']))
<div class="card">
    <div class="card-title">مقاعد المسافرين</div>
    <table class="data">
        <thead>
            <tr>
                <th style="text-align:right;">المسافر</th>
                <th style="text-align:center;">الرحلة</th>
                <th style="text-align:center;">المقعد</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ticket['seats'] as $seat)
            @php
                $pax    = collect($ticket['passengers'])->firstWhere('id', $seat['passenger_id']);
                $seg    = collect($ticket['segments'])->firstWhere('id', $seat['segment_id']);
                $segLbl = $seg ? (($seg['origin_code'] ?? '') . '→' . ($seg['destination_code'] ?? '')) : '—';
            @endphp
            <tr>
                <td style="text-align:right;">{{ $pax ? $pax['name'] : $seat['passenger_id'] }}</td>
                <td style="text-align:center; font-family:monospace; font-size:9px;">{{ $segLbl }}</td>
                <td style="text-align:center; font-weight:bold; font-family:monospace; font-size:14px;">{{ $seat['seat_number'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- ============================================================ FOOTER --}}
<div class="footer">
    @if($docType === 'electronic_ticket')
        <div>يُرجى الاحتفاظ بهذه التذكرة وإبرازها عند المطلوب</div>
        <div style="margin-top:2px;" dir="ltr">
            <span style="font-family:monospace; font-weight:bold; color:#0f172a; font-size:11px; letter-spacing:2px;">{{ $ticket['booking_reference'] ?? '' }}</span>
            <span style="color:#94a3b8;"> — PNR</span>
        </div>
    @elseif($docType === 'booking_confirmation')
        <div>هذه وثيقة تأكيد حجز وليست تذكرة إلكترونية — ستصدر التذكرة بعد اكتمال معالجة الحجز</div>
    @else
        <div>حجزك قيد المعالجة — سيتم إرسال التذكرة عند تأكيد الحجز</div>
    @endif
    <div style="margin-top:4px;" dir="ltr">
        <span style="font-family:monospace;">Barq Agency</span> · {{ now()->format('Y-m-d') }}
    </div>
</div>

</body>
</html>

<template>
  <div class="space-y-6">

    <!-- Summary -->
    <Card class="space-y-4">
      <div class="flex items-center justify-between gap-2">
        <h3 class="font-bold text-sm text-slate-900">معلومات الطلب</h3>
        <Badge :color="statusColor(order.status?.name)">{{ order.status?.label || '—' }}</Badge>
      </div>

      <div class="bg-[#0f172a] rounded-2xl p-5 grid sm:grid-cols-2 gap-x-6 gap-y-3">
        <div class="flex items-center justify-between gap-2">
          <span class="text-[10px] text-slate-400 shrink-0">رقم الطلب</span>
          <span class="font-mono font-bold text-amber-400 text-sm">{{ order.order_number }}</span>
        </div>
        <div class="flex items-center justify-between gap-2">
          <span class="text-[10px] text-slate-400 shrink-0">نوع الرحلة</span>
          <span class="text-sm font-bold text-slate-200">{{ tripTypeLabel }}</span>
        </div>
        <div class="flex items-center justify-between gap-2">
          <span class="text-[10px] text-slate-400 shrink-0">تاريخ المغادرة</span>
          <span class="text-sm font-bold text-slate-200">{{ formatDate(order.departure_date) }}</span>
        </div>
        <div class="flex items-center justify-between gap-2">
          <span class="text-[10px] text-slate-400 shrink-0">تاريخ ووقت العودة</span>
          <span class="text-sm font-bold text-slate-200">{{ formatFlightDateTime(order.return_date) }}</span>
        </div>
        <div class="flex items-center justify-between gap-2">
          <span class="text-[10px] text-slate-400 shrink-0">عدد الركاب</span>
          <span class="text-sm font-bold text-slate-200">{{ order.total_passengers }}</span>
        </div>
        <div v-if="order.weight" class="flex items-center justify-between gap-2">
          <span class="text-[10px] text-slate-400 shrink-0">الوزن</span>
          <span class="text-sm font-bold text-slate-200">{{ order.weight }} كجم</span>
        </div>
        <div v-if="order.metadata?.source !== 'duffel'" class="flex items-center justify-between gap-2">
          <span class="text-[10px] text-slate-400 shrink-0">السعر التقديري</span>
          <span class="text-sm font-bold font-mono text-slate-200">
            {{ order.estimated_price ? formatMoney(order.estimated_price) : '—' }}
          </span>
        </div>
        <div class="flex items-center justify-between gap-2">
          <span class="text-[10px] text-slate-400 shrink-0">تاريخ الإنشاء</span>
          <span class="text-sm font-bold text-slate-200">{{ formatDateTime(order.created_at) }}</span>
        </div>
        <div class="flex items-center justify-between gap-2">
          <span class="text-[10px] text-slate-400 shrink-0">آخر تحديث</span>
          <span class="text-sm font-bold text-slate-200">{{ formatDateTime(order.updated_at) }}</span>
        </div>
      </div>

      <!-- Passenger mix + source -->
      <div class="flex flex-wrap gap-2">
        <span v-for="mix in passengerMix" :key="mix.label"
          class="bg-slate-100 text-slate-700 text-[11px] font-bold px-3 py-1.5 rounded-full">
          {{ mix.label }}: {{ mix.count }}
        </span>
        <span
          :class="order.metadata?.source === 'duffel'
            ? 'bg-sky-100 text-sky-700 text-[11px] font-bold px-3 py-1.5 rounded-full'
            : 'bg-slate-100 text-slate-700 text-[11px] font-bold px-3 py-1.5 rounded-full'"
        >
          المصدر:
          <span v-if="order.metadata?.source === 'ignav'">بحث آلي (Ignav)</span>
          <span v-else-if="order.metadata?.source === 'duffel'">حجز مباشر</span>
          <span v-else>إدخال يدوي</span>
        </span>
        <span v-if="order.metadata?.ignav_id"
          class="bg-slate-100 text-slate-500 text-[11px] font-mono px-3 py-1.5 rounded-full">
          {{ order.metadata.ignav_id }}
        </span>
      </div>
    </Card>

    <!-- Agency -->
    <Card v-if="isAdmin && order.agency" class="space-y-3">
      <h3 class="font-bold text-sm text-slate-900">الوكالة</h3>
      <div class="grid sm:grid-cols-2 gap-x-6 gap-y-2.5">
        <DetailRow label="الاسم" :value="order.agency.name" />
        <DetailRow label="النوع" :value="order.agency.agency_type?.name" />
        <DetailRow label="البريد الإلكتروني" :value="order.agency.email" />
        <DetailRow label="الهاتف" :value="order.agency.phone" />
        <DetailRow label="رقم الترخيص" :value="order.agency.license_number" />
        <DetailRow label="الرقم الضريبي" :value="order.agency.tax_id" />
        <DetailRow label="حد الائتمان" :value="order.agency.credit_limit ? formatMoney(order.agency.credit_limit) : null" />
        <DetailRow label="العنوان" :value="order.agency.address" />
      </div>
    </Card>

    <!-- Segments -->
    <Card v-if="order.segments?.length" class="space-y-3">
      <h3 class="font-bold text-sm text-slate-900">خط السير ({{ order.segments.length }})</h3>

      <div v-for="(seg, index) in order.segments" :key="seg.id"
        class="bg-slate-50 rounded-2xl p-4 border border-slate-200 space-y-3">
        <div class="flex items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <span class="bg-slate-900 text-white text-[10px] font-bold px-2.5 py-1 rounded-full">
              المرحلة {{ seg.sequence || index + 1 }}
            </span>
            <Badge v-if="seg.metadata?.leg" :color="seg.metadata.leg === 'inbound' ? 'purple' : 'blue'">
              {{ seg.metadata.leg === 'inbound' ? 'العودة' : 'الذهاب' }}
            </Badge>
          </div>
          <span class="text-[11px] font-bold text-slate-500 font-mono">
            {{ flightLabel(seg) }}
          </span>
        </div>

        <div class="flex items-start justify-between gap-3">
          <div class="text-right">
            <p class="font-black text-slate-900 text-lg font-mono">{{ seg.departure_airport?.code || seg.departure_airport_name || '—' }}</p>
            <p class="text-[11px] text-slate-500">{{ airportLabel(seg.departure_airport, seg.departure_airport_name) }}</p>
            <p class="text-[11px] font-bold text-slate-700 mt-0.5">{{ formatFlightDateTime(seg.departure_datetime) }}</p>
            <p v-if="seg.departure_terminal" class="text-[11px] font-bold text-amber-700 mt-0.5">
              مبنى المغادرة: {{ seg.departure_terminal }}
            </p>
          </div>

          <div class="flex-1 flex flex-col items-center gap-1">
            <Plane class="w-4 h-4 text-amber-500 -scale-x-100" />
            <div class="w-full border-t border-dashed border-slate-300"></div>
            <span class="text-[10px] text-slate-400">{{ formatDuration(seg.duration_minutes) }}</span>
          </div>

          <div class="text-left">
            <p class="font-black text-slate-900 text-lg font-mono">{{ seg.arrival_airport?.code || seg.arrival_airport_name || '—' }}</p>
            <p class="text-[11px] text-slate-500">{{ airportLabel(seg.arrival_airport, seg.arrival_airport_name) }}</p>
            <p class="text-[11px] font-bold text-slate-700 mt-0.5">{{ formatFlightDateTime(seg.arrival_datetime) }}</p>
          </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-x-6 gap-y-2 pt-3 border-t border-slate-200">
          <DetailRow label="شركة الطيران" :value="seg.airline?.name" />
          <DetailRow label="درجة المقصورة" :value="seg.cabin_class?.name" />
          <DetailRow label="نوع الطائرة" :value="seg.aircraft_type" />
        </div>
      </div>
    </Card>

    <!-- Passengers -->
    <Card v-if="order.passengers?.length" class="space-y-3">
      <h3 class="font-bold text-sm text-slate-900">الركاب ({{ order.passengers.length }})</h3>

      <div v-for="(p, index) in order.passengers" :key="p.id"
        class="bg-slate-50 rounded-2xl p-4 border border-slate-200 space-y-3">
        <div class="flex items-center justify-between gap-2">
          <span class="font-bold text-sm" :class="isPassengerIncomplete(p) ? 'text-slate-400' : 'text-slate-900'">
            {{ index + 1 }}.
            {{ isPassengerIncomplete(p) ? 'بانتظار إدخال البيانات' : [p.title, p.first_name, p.last_name].filter(Boolean).join(' ') }}
          </span>
          <Badge color="slate">{{ passengerTypeLabel(p.passenger_type) }}</Badge>
        </div>
        <div v-if="!isPassengerIncomplete(p)" class="grid sm:grid-cols-2 gap-x-6 gap-y-2">
          <DetailRow label="تاريخ الميلاد" :value="formatDate(p.date_of_birth)" />
          <DetailRow label="الجنسية" :value="p.nationality" />
          <DetailRow label="رقم الجواز" :value="p.passport_number" mono />
          <DetailRow label="انتهاء الجواز" :value="formatDate(p.passport_expiry)" />
        </div>
      </div>
    </Card>

    <!-- Quotes (hidden for Duffel orders — they have no admin quote) -->
    <Card v-if="order.quotes?.length && order.metadata?.source !== 'duffel'" class="space-y-3">
      <h3 class="font-bold text-sm text-slate-900">عروض الأسعار ({{ order.quotes.length }})</h3>

      <div v-for="(quote, index) in sortedQuotes" :key="quote.id"
        class="rounded-2xl p-4 border space-y-3"
        :class="index === 0 ? 'bg-amber-50/60 border-amber-200' : 'bg-slate-50 border-slate-200'">
        <div class="flex items-center justify-between gap-2 flex-wrap">
          <div class="flex items-center gap-2">
            <Badge :color="index === 0 ? 'amber' : 'slate'">
              {{ index === 0 ? 'العرض الحالي' : `عرض #${sortedQuotes.length - index}` }}
            </Badge>
            <Badge v-if="quote.status?.label" color="blue">{{ quote.status.label }}</Badge>
          </div>
          <span class="text-[11px] text-slate-500">{{ formatDateTime(quote.created_at) }}</span>
        </div>

        <div class="grid sm:grid-cols-2 gap-x-6 gap-y-2">
          <DetailRow label="السعر الأساسي" :value="formatMoney(quote.base_price)" mono />
          <DetailRow label="الضرائب" :value="formatMoney(quote.taxes)" mono />
          <DetailRow label="الرسوم" :value="formatMoney(quote.fees)" mono />
          <DetailRow label="العملة" :value="quote.currency_code" />
          <DetailRow label="صالح حتى" :value="formatDateTime(quote.valid_until)" />
          <DetailRow label="بواسطة" :value="quote.created_by?.name" />
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-slate-200">
          <span class="text-xs font-bold text-slate-600">الإجمالي</span>
          <span class="font-mono font-black text-emerald-600 text-xl">{{ formatMoney(quote.total_price) }}</span>
        </div>

        <p v-if="quote.remarks" class="text-xs text-slate-600 bg-white rounded-xl p-3 border border-slate-200">
          {{ quote.remarks }}
        </p>
      </div>
    </Card>

    <!-- Status timeline -->
    <Card v-if="order.status_histories?.length" class="space-y-3">
      <h3 class="font-bold text-sm text-slate-900">سجل الحالة</h3>

      <div class="space-y-3">
        <div v-for="history in order.status_histories" :key="history.id" class="flex items-start gap-3">
          <div class="w-2 h-2 rounded-full bg-amber-400 mt-1.5 shrink-0"></div>
          <div class="flex-1 min-w-0">
            <p class="text-xs font-bold text-slate-800">
              <span v-if="history.from_status?.label" class="text-slate-500">{{ history.from_status.label }} ←</span>
              {{ history.to_status?.label || '—' }}
            </p>
            <p class="text-[11px] text-slate-500">
              {{ formatDateTime(history.created_at) }}
              <span v-if="history.created_by?.name"> • {{ history.created_by.name }}</span>
            </p>
            <p v-if="history.reason" class="text-[11px] text-slate-600 mt-1">{{ history.reason }}</p>
          </div>
        </div>
      </div>
    </Card>

    <!-- Raw itinerary payload (admin reference) -->
    <Card v-if="isAdmin && order.itinerary_details">
      <details class="group">
        <summary class="font-bold text-sm text-slate-900 cursor-pointer flex items-center gap-2">
          <ChevronLeft class="w-4 h-4 text-slate-400 transition group-open:-rotate-90" />
          بيانات خط السير الأصلية
        </summary>
        <pre dir="ltr" class="mt-3 bg-slate-900 text-slate-200 text-[11px] rounded-2xl p-4 overflow-x-auto">{{ JSON.stringify(order.itinerary_details, null, 2) }}</pre>
      </details>
    </Card>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Plane, ChevronLeft } from 'lucide-vue-next'
import Card from '@/components/Card.vue'
import Badge from '@/components/Badge.vue'
import DetailRow from '@/components/DetailRow.vue'
import { formatMoney } from '@/utils/points'
import { passengerTypeLabel, isPassengerIncomplete } from '@/utils/passengers'

const props = defineProps({
  order: { type: Object, required: true },
  isAdmin: { type: Boolean, default: false },
})

const tripTypeLabel = computed(() => {
  const labels = {
    one_way: 'ذهاب فقط',
    round_trip: 'ذهاب وعودة',
    multi_city: 'مدن متعددة',
  }

  return labels[props.order.trip_type] || (props.order.return_date ? 'ذهاب وعودة' : 'ذهاب فقط')
})

const sortedQuotes = computed(() =>
  [...(props.order.quotes || [])].sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
)

// The mix the admin prices against — it is known from the moment the order is requested,
// well before the traveller details are filled in on approval.
const passengerMix = computed(() => {
  const counts = new Map()
  for (const p of props.order.passengers || []) {
    const label = passengerTypeLabel(p.passenger_type)
    counts.set(label, (counts.get(label) || 0) + 1)
  }
  return [...counts].map(([label, count]) => ({ label, count }))
})

const statusColor = (status) => {
  const colors = {
    pending_pricing: 'amber',
    pricing_sent: 'blue',
    approved: 'emerald',
    confirmed: 'purple',
    cancelled: 'red',
    rejected: 'red',
  }
  return colors[status] || 'slate'
}

// Locally added carriers have no IATA code, so fall back to the name rather than
// rendering a leading blank before the flight number.
const flightLabel = (segment) => {
  const prefix = segment.airline?.code || segment.airline?.name
  return [prefix, segment.flight_number].filter(Boolean).join(' ') || '—'
}

const airportLabel = (airport, fallback) => {
  if (!airport) return fallback || '—'
  return [airport.name, airport.city?.name].filter(Boolean).join(' • ') || '—'
}

const formatDate = (date) => (date ? new Date(date).toLocaleDateString('ar-EG') : '—')

/**
 * Flight and travel times are local airport wall-clock, and the API serialises them with a
 * `Z` suffix, so they are read literally — `new Date()` would shift a 10:00 departure by the
 * viewer's UTC offset.
 */
const formatFlightDateTime = (value) => {
  const match = /^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})/.exec(String(value || ''))
  if (!match) return '—'
  const [, year, month, day, hour, minute] = match
  return `${day}/${month}/${year} ${hour}:${minute}`
}

const formatDateTime = (value) => {
  if (!value) return '—'
  return new Date(value).toLocaleString('ar-EG', {
    year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit',
  })
}

const formatDuration = (minutes) => {
  if (!minutes) return ''
  const hours = Math.floor(minutes / 60)
  const mins = minutes % 60
  return [hours ? `${hours}س` : null, mins ? `${mins}د` : null].filter(Boolean).join(' ') || ''
}
</script>

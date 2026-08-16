<template>
  <div class="max-w-7xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <PageHeader :title="pageTitle" :subtitle="pageSubtitle">
      <template #actions>
        <router-link to="/admin/orders" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-4 py-2.5 rounded-xl flex items-center gap-2 transition">
          <ArrowRight class="w-4 h-4" />
          العودة للطلبات
        </router-link>
      </template>
    </PageHeader>

    <LoadingState v-if="loading" :rows="3" />

    <div v-else-if="order" class="grid lg:grid-cols-3 gap-6 items-start">
      <!-- Full order details -->
      <div class="lg:col-span-2">
        <OrderDetailsPanel :order="order" is-admin />
      </div>

      <!-- Pricing Form -->
      <Card class="space-y-5 lg:sticky lg:top-6">
        <div class="flex items-center justify-between gap-2">
          <h3 class="font-bold text-sm text-slate-900">{{ isRevision ? 'تعديل العرض' : 'إدخال السعر' }}</h3>
          <Badge v-if="!canEdit" color="slate">للمراجعة فقط</Badge>
        </div>

        <p v-if="!canEdit" class="text-xs text-slate-500 bg-slate-50 rounded-xl p-3 border border-slate-200">
          هذا الطلب في حالة "{{ order.status?.label }}" ولا يمكن تعديله بعد موافقة الوكيل. التفاصيل معروضة للمراجعة.
        </p>

        <p v-else-if="isRevision" class="text-[11px] text-blue-800 bg-blue-50 rounded-xl p-3 border border-blue-200 leading-relaxed">
          العرض مُرسل وبانتظار موافقة الوكيل، ويمكنك تعديله الآن. سيتم إشعار الوكيل بالتعديل عند الحفظ.
        </p>

        <form v-if="canEdit" @submit.prevent="submitQuote" class="space-y-4">
          <!-- Departure details per flight -->
          <div class="space-y-3 pb-4 border-b border-slate-200">
            <p class="text-xs font-bold text-slate-800">تفاصيل المغادرة</p>

            <div v-for="(flight, index) in flights" :key="flight.id"
              class="bg-slate-50 rounded-xl p-3 border border-slate-200 space-y-2.5">
              <div class="flex items-center justify-between gap-2">
                <span class="text-[11px] font-bold text-slate-700 font-mono">
                  {{ flight.route }}
                </span>
                <span class="text-[10px] text-slate-500">الرحلة {{ index + 1 }}</span>
              </div>

              <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">تاريخ ووقت المغادرة</label>
                <input v-model="flight.departure_datetime" type="datetime-local"
                  class="w-full border border-slate-200 rounded-xl px-3 py-2 text-[11px] bg-white focus:outline-none focus:ring-2 focus:ring-amber-400" />
                <p v-if="fieldErrors[`flights.${index}.departure_datetime`]" class="text-[10px] text-red-600 font-semibold mt-1">
                  {{ fieldErrors[`flights.${index}.departure_datetime`] }}
                </p>
              </div>

              <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">
                  مبنى المغادرة <span class="text-slate-400 font-normal">(اختياري)</span>
                </label>
                <input v-model="flight.departure_terminal" type="text" placeholder="مثال: T1" maxlength="50"
                  class="w-full border border-slate-200 rounded-xl px-3 py-2 text-[11px] bg-white focus:outline-none focus:ring-2 focus:ring-amber-400" />
                <p v-if="fieldErrors[`flights.${index}.departure_terminal`]" class="text-[10px] text-red-600 font-semibold mt-1">
                  {{ fieldErrors[`flights.${index}.departure_terminal`] }}
                </p>
              </div>
            </div>

            <!-- Round trip: return date and time -->
            <div v-if="isRoundTrip" class="bg-slate-50 rounded-xl p-3 border border-slate-200">
              <label class="block text-[11px] font-semibold text-slate-600 mb-1">تاريخ ووقت العودة</label>
              <input v-model="form.return_date" type="datetime-local"
                class="w-full border border-slate-200 rounded-xl px-3 py-2 text-[11px] bg-white focus:outline-none focus:ring-2 focus:ring-amber-400" />
              <p v-if="fieldErrors.return_date" class="text-[10px] text-red-600 font-semibold mt-1">
                {{ fieldErrors.return_date }}
              </p>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">السعر الأساسي (ج.س)</label>
            <input v-model.number="form.base_price" type="number" step="0.01" min="0" required @input="recalcTotal"
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-amber-400" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">الضرائب (ج.س)</label>
            <input v-model.number="form.taxes" type="number" step="0.01" min="0" @input="recalcTotal"
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-amber-400" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">الرسوم (ج.س)</label>
            <input v-model.number="form.fees" type="number" step="0.01" min="0" @input="recalcTotal"
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-amber-400" />
          </div>

          <!-- Total -->
          <div class="bg-[#0f172a] rounded-2xl p-4 flex items-center justify-between">
            <span class="text-xs text-slate-400">الإجمالي</span>
            <span class="text-2xl font-black text-emerald-400 font-mono">{{ formatMoney(form.total_price) }}</span>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">ملاحظات</label>
            <textarea v-model="form.remarks" rows="3"
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400 resize-none"></textarea>
          </div>

          <div v-if="error" class="bg-red-100 text-red-700 text-xs font-semibold p-3 rounded-xl">{{ error }}</div>

          <Button type="submit" variant="amber" size="md" block :disabled="submitting">
            <Send class="w-4 h-4" />
            {{ submitLabel }}
          </Button>
        </form>
      </Card>
    </div>

    <div v-else-if="error" class="bg-red-100 text-red-700 text-xs font-semibold p-4 rounded-2xl">{{ error }}</div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/lib/api'
import { ArrowRight, Send } from 'lucide-vue-next'
import PageHeader from '@/components/PageHeader.vue'
import Card from '@/components/Card.vue'
import Badge from '@/components/Badge.vue'
import LoadingState from '@/components/LoadingState.vue'
import Button from '@/components/Button.vue'
import OrderDetailsPanel from '@/components/OrderDetailsPanel.vue'
import { formatMoney } from '@/utils/points'

const props = defineProps({ id: [String, Number] })
const router = useRouter()

const order = ref(null)
const loading = ref(true)
const submitting = ref(false)
const error = ref('')
const fieldErrors = ref({})

const form = ref({ base_price: 0, taxes: 0, fees: 0, total_price: 0, remarks: '', return_date: '' })
const flights = ref([])

// The admin can still change the offer until the agency acts on it.
const canEdit = computed(() => ['pending_pricing', 'pricing_sent'].includes(order.value?.status?.name))
const isRevision = computed(() => order.value?.status?.name === 'pricing_sent')
const isRoundTrip = computed(() => Boolean(order.value?.return_date))

const pageTitle = computed(() => (isRevision.value ? 'تفاصيل الطلب وتعديل العرض' : 'تفاصيل الطلب والتسعير'))
const pageSubtitle = computed(() =>
  isRevision.value
    ? 'مراجعة تفاصيل الحجز وتعديل العرض المُرسل، وسيتم إشعار الوكيل بالتعديل'
    : 'مراجعة كامل تفاصيل الحجز وإدخال السعر وإرساله للوكيل'
)
const submitLabel = computed(() => {
  if (submitting.value) return 'جارٍ الإرسال...'
  return isRevision.value ? 'حفظ التعديل وإشعار الوكيل' : 'إرسال العرض للوكيل'
})

const latestQuote = computed(() => {
  const quotes = order.value?.quotes
  if (!quotes?.length) return null
  return [...quotes].sort((a, b) => new Date(b.created_at) - new Date(a.created_at))[0]
})

/**
 * A flight time is local airport wall-clock, and the API serialises it with a `Z` suffix,
 * so it is read literally — `new Date()` would shift a 10:00 departure by the browser offset.
 */
const toDateTimeInput = (value) => (value ? String(value).slice(0, 16) : '')

onMounted(async () => {
  try {
    const response = await api.get(`/orders/${props.id}`)
    order.value = response.data

    flights.value = (order.value.segments || []).map((segment) => ({
      id: segment.id,
      route: `${segment.departure_airport?.code || '—'} → ${segment.arrival_airport?.code || '—'}`,
      departure_datetime: toDateTimeInput(segment.departure_datetime),
      departure_terminal: segment.departure_terminal || '',
    }))

    form.value.return_date = toDateTimeInput(order.value.return_date)

    // Editing an offer already sent: start from what the agency is currently looking at.
    if (latestQuote.value) {
      form.value.base_price = Number(latestQuote.value.base_price) || 0
      form.value.taxes = Number(latestQuote.value.taxes) || 0
      form.value.fees = Number(latestQuote.value.fees) || 0
      form.value.remarks = latestQuote.value.remarks || ''
      recalcTotal()
    }
  } catch (err) {
    error.value = 'فشل تحميل الطلب'
  } finally {
    loading.value = false
  }
})

const recalcTotal = () => {
  form.value.total_price = (Number(form.value.base_price) || 0) + (Number(form.value.taxes) || 0) + (Number(form.value.fees) || 0)
}

const submitQuote = async () => {
  submitting.value = true
  error.value = ''
  fieldErrors.value = {}

  try {
    await api.put(`/orders/${props.id}/quote`, {
      ...form.value,
      return_date: isRoundTrip.value ? form.value.return_date || null : null,
      flights: flights.value.map(({ id, departure_datetime, departure_terminal }) => ({
        id,
        departure_datetime: departure_datetime || null,
        departure_terminal: departure_terminal.trim(),
      })),
    })
    router.push('/admin/orders')
  } catch (err) {
    const errors = err.response?.data?.errors
    if (errors) {
      fieldErrors.value = Object.fromEntries(
        Object.entries(errors).map(([path, messages]) => [path, Array.isArray(messages) ? messages[0] : messages])
      )
    }
    error.value = err.response?.data?.message || 'فشل إرسال العرض'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="max-w-7xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <PageHeader title="تفاصيل الطلب" subtitle="عرض تفاصيل الحجز والعرض المقدم من الإدارة">
      <template #actions>
        <router-link to="/agency/orders" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-4 py-2.5 rounded-xl flex items-center gap-2 transition">
          <ArrowRight class="w-4 h-4" />
          العودة للطلبات
        </router-link>
      </template>
    </PageHeader>

    <LoadingState v-if="loading" :rows="3" />

    <div v-else-if="order" class="grid lg:grid-cols-3 gap-6 items-start">
      <!-- Full order details -->
      <div class="lg:col-span-2 space-y-6">
        <OrderDetailsPanel :order="order" />

        <!-- Duffel booking details -->
        <Card v-if="isDuffelOrder" class="space-y-4">
          <div class="flex items-center gap-2">
            <Plane class="w-4 h-4 text-sky-600" />
            <h3 class="font-bold text-sm text-slate-900">تفاصيل الحجز المباشر</h3>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="bg-slate-50 rounded-xl p-3">
              <div class="text-[10px] text-slate-400 mb-1">رقم الحجز (PNR)</div>
              <div class="font-mono font-black text-lg text-amber-600 tracking-widest">
                {{ order.metadata.booking_reference ?? '—' }}
              </div>
            </div>
            <div class="bg-slate-50 rounded-xl p-3 col-span-2">
              <div class="text-[10px] text-slate-400 mb-2">تفاصيل التكلفة</div>
              <div class="space-y-1.5">
                <div v-if="order.metadata.duffel_cost_sar" class="flex items-center justify-between">
                  <span class="text-[11px] text-slate-400">تكلفة التذكرة</span>
                  <span class="font-mono text-[11px] text-slate-400">{{ formatPoints(order.metadata.duffel_cost_sar) }}</span>
                </div>
                <div v-if="order.metadata.agency_commission != null" class="flex items-center justify-between">
                  <span class="text-[11px] text-slate-400">عمولة الوكالة</span>
                  <span class="font-mono text-[11px] text-slate-400">+ {{ formatPoints(order.metadata.agency_commission) }}</span>
                </div>
                <div class="flex items-center justify-between bg-amber-50 rounded-lg px-2 py-1.5">
                  <span class="text-[11px] font-bold text-amber-700">الخصم من المحفظة</span>
                  <span class="font-mono font-black text-amber-700 text-sm">{{ formatPoints(order.metadata.paid_sar) }}</span>
                </div>
                <div v-if="order.metadata.paid_money" class="flex items-center justify-between">
                  <span class="text-[11px] text-slate-400">≈ بالجنيه</span>
                  <span class="font-mono text-[11px] text-slate-400">{{ formatMoney(order.metadata.paid_money) }}</span>
                </div>
              </div>
            </div>
            <div class="bg-slate-50 rounded-xl p-3">
              <div class="text-[10px] text-slate-400 mb-1">سعر التذكرة (Duffel)</div>
              <div class="font-mono font-bold text-slate-700 text-sm">
                {{ order.metadata.duffel_amount }} {{ order.metadata.duffel_currency }}
              </div>
            </div>
          </div>
        </Card>
      </div>

      <!-- Actions -->
      <Card class="space-y-4 lg:sticky lg:top-6">
        <h3 class="font-bold text-sm text-slate-900">الإجراءات</h3>

        <div v-if="latestQuote && !isDuffelOrder" class="bg-[#0f172a] rounded-2xl p-4 space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-400">إجمالي العرض</span>
            <span class="font-mono font-black text-emerald-400 text-xl">{{ formatMoney(latestQuote.total_price) }}</span>
          </div>
          <!-- The wallet ledger is in points, so the money total alone does not tell the
               agency what its balance will actually be charged. -->
          <div v-if="rate.money_amount" class="flex items-center justify-between border-t border-slate-700 pt-2">
            <span class="text-xs text-slate-400">الخصم من المحفظة</span>
            <span class="font-mono font-bold text-amber-400 text-sm">{{ formatPoints(moneyToPoints(latestQuote.total_price)) }}</span>
          </div>
        </div>

        <p v-else-if="!isDuffelOrder" class="text-xs text-slate-500 bg-slate-50 rounded-xl p-3 border border-slate-200">
          لم يتم استلام عرض سعر بعد
        </p>

        <div v-if="error" class="bg-red-100 text-red-700 text-xs font-semibold p-3 rounded-xl">{{ error }}</div>

        <router-link
          v-if="order.status?.name === 'pricing_sent'"
          :to="`/agency/orders/${order.id}/approve`"
          class="w-full bg-amber-400 hover:bg-amber-500 text-slate-900 font-bold py-3 rounded-xl text-xs flex items-center justify-center gap-2 transition"
        >
          <CheckCircle class="w-4 h-4" />
          إدخال بيانات الركاب والموافقة
        </router-link>

        <Button
          v-if="order.ticket_uploaded_at"
          variant="amber"
          size="md"
          block
          @click="downloadTicket"
          :disabled="downloadingTicket"
        >
          <FileDown class="w-4 h-4" />
          {{ downloadingTicket ? 'جارٍ التنزيل...' : 'تنزيل تذكرة السفر' }}
        </Button>

        <Button
          v-if="isDuffelOrder"
          variant="sky"
          size="md"
          block
          @click="downloadDuffelTicket"
          :disabled="downloadingDuffelTicket"
        >
          <Plane class="w-4 h-4" />
          {{ downloadingDuffelTicket ? 'جارٍ التنزيل...' : 'تنزيل تذكرة الحجز المباشر' }}
        </Button>

        <router-link
          v-if="['approved', 'confirmed'].includes(order.status?.name)"
          :to="`/agency/refunds/new?order_id=${order.id}`"
          class="w-full bg-red-500/10 hover:bg-red-500/20 text-red-600 border border-red-300 font-bold py-3 rounded-xl text-xs flex items-center justify-center gap-2 transition"
        >
          <RotateCcw class="w-4 h-4" />
          طلب استرداد لهذا الحجز
        </router-link>
      </Card>
    </div>

    <div v-else-if="error" class="bg-red-100 text-red-700 text-xs font-semibold p-4 rounded-2xl">{{ error }}</div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/lib/api'
import { ArrowRight, CheckCircle, RotateCcw, FileDown, Plane } from 'lucide-vue-next'
import PageHeader from '@/components/PageHeader.vue'
import LoadingState from '@/components/LoadingState.vue'
import Card from '@/components/Card.vue'
import Button from '@/components/Button.vue'
import OrderDetailsPanel from '@/components/OrderDetailsPanel.vue'
import { formatMoney, formatPoints } from '@/utils/points'
import { usePointRate } from '@/composables/usePointRate'
import { downloadFile } from '@/lib/download'

const { rate, loadRate, moneyToPoints } = usePointRate()

const props = defineProps({ id: [String, Number] })

const order = ref(null)
const loading = ref(true)
const downloadingTicket = ref(false)
const downloadingDuffelTicket = ref(false)
const error = ref('')

const latestQuote = computed(() => {
  const quotes = order.value?.quotes
  if (!quotes?.length) return null
  return [...quotes].sort((a, b) => new Date(b.created_at) - new Date(a.created_at))[0]
})

const isDuffelOrder = computed(() => order.value?.metadata?.source === 'duffel')

const loadOrder = async () => {
  try {
    const response = await api.get(`/orders/${props.id}`)
    order.value = response.data
  } catch (err) {
    error.value = 'فشل تحميل الطلب'
  } finally {
    loading.value = false
  }
}

onMounted(() => Promise.all([loadOrder(), loadRate()]))

const downloadTicket = async () => {
  downloadingTicket.value = true
  error.value = ''
  try {
    const response = await api.get(`/orders/${props.id}/ticket`, { responseType: 'blob' })
    downloadFile(response.data, `ticket-${order.value.order_number}.pdf`)
  } catch (err) {
    error.value = 'فشل تنزيل التذكرة'
  } finally {
    downloadingTicket.value = false
  }
}

const downloadDuffelTicket = async () => {
  downloadingDuffelTicket.value = true
  error.value = ''
  try {
    const response = await api.get(`/duffel/orders/${props.id}/ticket/pdf`, { responseType: 'blob' })
    const pnr = order.value.metadata?.booking_reference ?? order.value.order_number
    downloadFile(response.data, `ticket-${pnr}.pdf`)
  } catch (err) {
    error.value = 'فشل تنزيل تذكرة Duffel'
  } finally {
    downloadingDuffelTicket.value = false
  }
}
</script>

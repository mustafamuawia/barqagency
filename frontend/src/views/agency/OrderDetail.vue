<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <PageHeader title="تفاصيل الطلب" subtitle="عرض تفاصيل الحجز والعرض المقدم من الإدارة">
      <template #actions>
        <router-link to="/agency/orders" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-4 py-2.5 rounded-xl flex items-center gap-2 transition">
          <ArrowRight class="w-4 h-4" />
          العودة للطلبات
        </router-link>
      </template>
    </PageHeader>

    <LoadingState v-if="loading" />

    <template v-else-if="order">
      <div class="grid md:grid-cols-2 gap-6">
        <!-- Order Info -->
        <Card class="space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="font-bold text-sm text-slate-900">معلومات الطلب</h3>
            <Badge :color="statusColor(order.status?.name)">{{ order.status?.label }}</Badge>
          </div>

          <div class="bg-[#0f172a] rounded-2xl p-5 space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-[10px] text-slate-400">رقم الطلب</span>
              <span class="font-mono font-bold text-amber-400 text-sm">{{ order.order_number }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-[10px] text-slate-400">تاريخ المغادرة</span>
              <span class="text-sm font-bold text-slate-200">{{ formatDate(order.departure_date) }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-[10px] text-slate-400">عدد الركاب</span>
              <span class="text-sm font-bold text-slate-200">{{ order.total_passengers }}</span>
            </div>
          </div>

          <!-- Segments -->
          <div v-if="order.segments?.length" class="space-y-2">
            <p class="text-xs font-semibold text-slate-500">خط السير</p>
            <div v-for="seg in order.segments" :key="seg.id" class="bg-slate-50 rounded-xl p-3 text-xs text-slate-600 border border-slate-200">
              <span class="font-bold text-slate-800">{{ seg.departure_airport?.code }}</span>
              <span class="mx-2 text-slate-400">←</span>
              <span class="font-bold text-slate-800">{{ seg.arrival_airport?.code }}</span>
              <span class="text-slate-400 mx-2">•</span>
              {{ seg.airline?.name }} • {{ seg.cabin_class?.name }}
            </div>
          </div>

          <!-- Passengers -->
          <div v-if="order.passengers?.length" class="space-y-2">
            <p class="text-xs font-semibold text-slate-500">الركاب</p>
            <div v-for="p in order.passengers" :key="p.id" class="bg-slate-50 rounded-xl p-3 text-xs text-slate-700 border border-slate-200 font-medium">
              {{ p.first_name }} {{ p.last_name }}
            </div>
          </div>
        </Card>

        <!-- Quote Card -->
        <Card v-if="latestQuote" class="space-y-4">
          <h3 class="font-bold text-sm text-slate-900">العرض المقدم من الإدارة</h3>

          <div class="bg-[#0f172a] rounded-2xl p-5 space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-[10px] text-slate-400">السعر الأساسي</span>
              <span class="font-mono font-bold text-slate-200 text-sm">{{ formatCurrency(latestQuote.base_price) }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-[10px] text-slate-400">الضرائب</span>
              <span class="font-mono font-bold text-slate-200 text-sm">{{ formatCurrency(latestQuote.taxes) }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-[10px] text-slate-400">الرسوم</span>
              <span class="font-mono font-bold text-slate-200 text-sm">{{ formatCurrency(latestQuote.fees) }}</span>
            </div>
            <div class="flex items-center justify-between pt-3 border-t border-slate-700">
              <span class="text-xs text-slate-300 font-bold">الإجمالي</span>
              <span class="font-mono font-black text-emerald-400 text-xl">{{ formatCurrency(latestQuote.total_price) }}</span>
            </div>
          </div>

          <p v-if="latestQuote.remarks" class="text-xs text-slate-600 bg-slate-50 rounded-xl p-3 border border-slate-200">
            {{ latestQuote.remarks }}
          </p>

          <Button
            v-if="order.status?.name === 'pricing_sent'"
            variant="amber"
            size="md"
            block
            @click="approveOrder"
            :disabled="approving"
          >
            <CheckCircle class="w-4 h-4" />
            {{ approving ? 'جارٍ المعالجة...' : 'موافقة وتأكيد الحجز' }}
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

        <div v-else class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 flex items-center justify-center text-slate-400 text-sm">
          لم يتم استلام عرض سعر بعد
        </div>
      </div>

      <div v-if="error" class="bg-red-100 text-red-700 text-xs font-semibold p-4 rounded-2xl">{{ error }}</div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/lib/api'
import { ArrowRight, CheckCircle, RotateCcw } from 'lucide-vue-next'
import PageHeader from '@/components/PageHeader.vue'
import LoadingState from '@/components/LoadingState.vue'
import Card from '@/components/Card.vue'
import Badge from '@/components/Badge.vue'
import Button from '@/components/Button.vue'

const props = defineProps({ id: [String, Number] })

const order = ref(null)
const loading = ref(true)
const approving = ref(false)
const error = ref('')

const latestQuote = computed(() => {
  if (!order.value?.quotes?.length) return null
  return order.value.quotes[order.value.quotes.length - 1]
})

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

onMounted(loadOrder)

const approveOrder = async () => {
  approving.value = true
  error.value = ''
  try {
    await api.put(`/orders/${props.id}/approve`)
    await loadOrder()
  } catch (err) {
    error.value = err.response?.data?.message || 'فشل تأكيد الطلب'
  } finally {
    approving.value = false
  }
}

const statusColor = (status) => {
  const colors = {
    pending_pricing: 'amber',
    pricing_sent: 'blue',
    approved: 'emerald',
    confirmed: 'purple',
  }
  return colors[status] || 'slate'
}

const formatCurrency = (value) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value || 0)
const formatDate = (date) => new Date(date).toLocaleDateString('ar-EG')
</script>

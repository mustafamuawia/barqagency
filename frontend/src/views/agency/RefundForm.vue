<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 flex items-center justify-between">
      <div>
        <h2 class="text-lg font-extrabold text-slate-900">طلب استرداد جديد</h2>
        <p class="text-xs text-slate-400 mt-0.5">اختر الحجز المؤكد وأدخل سبب الاسترداد</p>
      </div>
      <router-link to="/agency/refunds" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-4 py-2.5 rounded-xl flex items-center gap-2 transition">
        <ArrowRight class="w-4 h-4" />
        العودة للطلبات
      </router-link>
    </div>

    <div v-if="loading" class="bg-white rounded-3xl p-12 shadow-sm border border-slate-200 text-center text-slate-400 text-sm">
      جارٍ التحميل...
    </div>

    <div v-else-if="eligibleOrders.length === 0" class="bg-white rounded-3xl p-12 shadow-sm border border-slate-200 text-center text-slate-400 text-sm">
      لا توجد حجوزات مؤكدة مؤهلة لطلب استرداد حالياً
    </div>

    <form v-else @submit.prevent="handleSubmit" class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-5">
      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1.5">اختر الحجز</label>
        <select v-model="form.order_id" required
          class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400">
          <option value="">-- اختر رقم الطلب --</option>
          <option v-for="order in eligibleOrders" :key="order.id" :value="order.id">
            {{ order.order_number }} — {{ formatCurrency(order.latest_quote?.total_price) }}
          </option>
        </select>
      </div>

      <div v-if="selectedOrder" class="bg-[#0f172a] rounded-2xl p-5 flex items-center justify-between">
        <span class="text-[10px] text-slate-400">المبلغ الأصلي المدفوع</span>
        <span class="text-2xl font-black text-emerald-400 font-mono">{{ formatCurrency(selectedOrder.latest_quote?.total_price) }}</span>
      </div>

      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1.5">سبب طلب الاسترداد</label>
        <textarea v-model="form.reason" rows="4" required placeholder="مثال: إلغاء الرحلة من قبل العميل، تغيير في الخطة..."
          class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400 resize-none"></textarea>
      </div>

      <p class="text-[10px] text-slate-400 bg-slate-50 border border-slate-200 rounded-xl p-3">
        سيتم مراجعة الطلب من قبل الإدارة، وقد يتم خصم رسوم جزاء أو إلغاء حسب سياسة شركة الطيران قبل إضافة صافي المبلغ إلى محفظتك.
      </p>

      <div v-if="error" class="bg-red-100 text-red-700 text-xs font-semibold p-3 rounded-xl">{{ error }}</div>

      <button type="submit" :disabled="submitting"
        class="w-full bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-slate-950 font-bold py-3 rounded-xl text-xs flex items-center justify-center gap-2 transition shadow-sm">
        <Send class="w-4 h-4" />
        {{ submitting ? 'جارٍ الإرسال...' : 'إرسال طلب الاسترداد' }}
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '@/lib/api'
import { ArrowRight, Send } from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()

const orders = ref([])
const refunds = ref([])
const loading = ref(true)
const submitting = ref(false)
const error = ref('')

const form = ref({ order_id: route.query.order_id || '', reason: '' })

const ELIGIBLE_STATUSES = ['approved', 'confirmed']
const ACTIVE_REFUND_STATUSES = ['pending', 'approved', 'credited']

const eligibleOrders = computed(() => {
  const ordersWithActiveRefund = new Set(
    refunds.value.filter(r => ACTIVE_REFUND_STATUSES.includes(r.status?.name)).map(r => r.order?.id)
  )
  return orders.value.filter(o => ELIGIBLE_STATUSES.includes(o.status?.name) && !ordersWithActiveRefund.has(o.id))
})

const selectedOrder = computed(() => eligibleOrders.value.find(o => o.id == form.value.order_id))

onMounted(async () => {
  try {
    const [ordersRes, refundsRes] = await Promise.all([api.get('/orders'), api.get('/refunds')])
    orders.value = ordersRes.data.data || ordersRes.data
    refunds.value = refundsRes.data
  } catch (err) {
    error.value = 'فشل تحميل بيانات الطلبات'
  } finally {
    loading.value = false
  }
})

const handleSubmit = async () => {
  submitting.value = true
  error.value = ''
  try {
    const response = await api.post('/refunds', form.value)
    router.push('/agency/refunds')
  } catch (err) {
    error.value = err.response?.data?.message || 'فشل إرسال طلب الاسترداد'
  } finally {
    submitting.value = false
  }
}

const formatCurrency = (value) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value || 0)
</script>

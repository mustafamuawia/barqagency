<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 flex items-center justify-between">
      <div>
        <h2 class="text-lg font-extrabold text-slate-900">تسعير الطلب وإرسال العرض</h2>
        <p class="text-xs text-slate-400 mt-0.5">إدخال تفاصيل السعر وإرساله للوكيل للموافقة</p>
      </div>
      <router-link to="/admin/orders" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-4 py-2.5 rounded-xl flex items-center gap-2 transition">
        <ArrowRight class="w-4 h-4" />
        العودة للطلبات
      </router-link>
    </div>

    <div v-if="loading" class="bg-white rounded-3xl p-12 shadow-sm border border-slate-200 text-center text-slate-400 text-sm">
      جارٍ التحميل...
    </div>

    <div v-else-if="order" class="grid md:grid-cols-2 gap-6">
      <!-- Order Info -->
      <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-4">
        <h3 class="font-bold text-sm text-slate-900">تفاصيل الطلب</h3>

        <div class="bg-[#0f172a] rounded-2xl p-5 space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-[10px] text-slate-400">رقم الطلب</span>
            <span class="font-mono font-bold text-amber-400 text-sm">{{ order.order_number }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[10px] text-slate-400">الوكالة</span>
            <span class="text-sm font-bold text-white">{{ order.agency?.name }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[10px] text-slate-400">الركاب</span>
            <span class="text-sm font-bold text-slate-200">{{ order.total_passengers }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[10px] text-slate-400">تاريخ المغادرة</span>
            <span class="text-sm font-bold text-slate-200">{{ formatDate(order.departure_date) }}</span>
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
      </div>

      <!-- Pricing Form -->
      <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-5">
        <h3 class="font-bold text-sm text-slate-900">إدخال السعر</h3>

        <form @submit.prevent="submitQuote" class="space-y-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">السعر الأساسي (USD)</label>
            <input v-model.number="form.base_price" type="number" step="0.01" min="0" required @input="recalcTotal"
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-amber-400" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">الضرائب (USD)</label>
            <input v-model.number="form.taxes" type="number" step="0.01" min="0" @input="recalcTotal"
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-amber-400" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">الرسوم (USD)</label>
            <input v-model.number="form.fees" type="number" step="0.01" min="0" @input="recalcTotal"
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-amber-400" />
          </div>

          <!-- Total -->
          <div class="bg-[#0f172a] rounded-2xl p-4 flex items-center justify-between">
            <span class="text-xs text-slate-400">الإجمالي</span>
            <span class="text-2xl font-black text-emerald-400 font-mono">{{ formatCurrency(form.total_price) }}</span>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">ملاحظات</label>
            <textarea v-model="form.remarks" rows="3"
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400 resize-none"></textarea>
          </div>

          <div v-if="error" class="bg-red-100 text-red-700 text-xs font-semibold p-3 rounded-xl">{{ error }}</div>

          <button type="submit" :disabled="submitting"
            class="w-full bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-slate-950 font-bold py-3 rounded-xl text-xs flex items-center justify-center gap-2 transition shadow-sm">
            <Send class="w-4 h-4" />
            {{ submitting ? 'جارٍ الإرسال...' : 'إرسال العرض للوكيل' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/lib/api'
import { ArrowRight, Send } from 'lucide-vue-next'

const props = defineProps({ id: [String, Number] })
const router = useRouter()

const order = ref(null)
const loading = ref(true)
const submitting = ref(false)
const error = ref('')

const form = ref({ base_price: 0, taxes: 0, fees: 0, total_price: 0, remarks: '' })

onMounted(async () => {
  try {
    const response = await api.get(`/orders/${props.id}`)
    order.value = response.data
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
  try {
    await api.put(`/orders/${props.id}/quote`, form.value)
    router.push('/admin/orders')
  } catch (err) {
    error.value = err.response?.data?.message || 'فشل إرسال العرض'
  } finally {
    submitting.value = false
  }
}

const formatCurrency = (value) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value || 0)
const formatDate = (date) => new Date(date).toLocaleDateString('ar-EG')
</script>

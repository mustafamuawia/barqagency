<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 flex items-center justify-between">
      <div>
        <h2 class="text-lg font-extrabold text-slate-900">طلباتي وحجوزاتي</h2>
        <p class="text-xs text-slate-400 mt-0.5">عرض جميع طلبات الحجز وحالتها</p>
      </div>
      <router-link
        to="/agency/orders/new"
        class="bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 transition shadow-sm"
      >
        <PlusCircle class="w-4 h-4" />
        طلب حجز جديد
      </router-link>
    </div>

    <div v-if="loading" class="bg-white rounded-3xl p-12 shadow-sm border border-slate-200 text-center text-slate-400 text-sm">
      جارٍ التحميل...
    </div>

    <div v-else-if="orders.length === 0" class="bg-white rounded-3xl p-12 shadow-sm border border-slate-200 text-center text-slate-400 text-sm">
      لا توجد طلبات بعد. أنشئ أول حجز لك.
    </div>

    <div v-else class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="font-bold text-sm text-slate-900">قائمة الطلبات</h3>
        <span class="bg-slate-100 text-slate-600 text-[10px] font-bold px-2.5 py-1 rounded-full">{{ orders.length }} طلب</span>
      </div>

      <div class="overflow-x-auto rounded-2xl border border-slate-200">
        <table class="w-full text-right text-xs">
          <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
            <tr>
              <th class="p-3 font-semibold">رقم الطلب</th>
              <th class="p-3 font-semibold">تاريخ الإنشاء</th>
              <th class="p-3 font-semibold">الركاب</th>
              <th class="p-3 font-semibold">المغادرة</th>
              <th class="p-3 font-semibold">العرض المقدم</th>
              <th class="p-3 font-semibold">الحالة</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr
              v-for="order in orders"
              :key="order.id"
              class="hover:bg-slate-50 transition cursor-pointer"
              @click="$router.push(`/agency/orders/${order.id}`)"
            >
              <td class="p-3 font-mono font-bold text-slate-700">{{ order.order_number }}</td>
              <td class="p-3 text-slate-500">{{ formatDate(order.created_at) }}</td>
              <td class="p-3 text-slate-600">{{ order.total_passengers }}</td>
              <td class="p-3 text-slate-600">{{ formatDate(order.departure_date) }}</td>
              <td class="p-3 font-mono font-bold text-emerald-600">
                {{ order.latest_quote ? formatCurrency(order.latest_quote.total_price) : '—' }}
              </td>
              <td class="p-3">
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold" :class="statusClass(order.status?.name)">
                  {{ order.status?.label }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/lib/api'
import { PlusCircle } from 'lucide-vue-next'

const orders = ref([])
const loading = ref(true)

onMounted(async () => {
  try {
    const response = await api.get('/orders')
    orders.value = response.data.data || response.data
  } catch (err) {
    console.error('Failed to load orders', err)
  } finally {
    loading.value = false
  }
})

const statusClass = (status) => {
  const classes = {
    pending_pricing: 'bg-amber-100 text-amber-800',
    pricing_sent: 'bg-blue-100 text-blue-800',
    approved: 'bg-emerald-100 text-emerald-800',
    confirmed: 'bg-purple-100 text-purple-800',
  }
  return classes[status] || 'bg-slate-100 text-slate-600'
}

const formatCurrency = (value) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value || 0)
const formatDate = (date) => new Date(date).toLocaleDateString('ar-EG')
</script>

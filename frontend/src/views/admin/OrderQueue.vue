<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 flex items-center justify-between">
      <div>
        <h2 class="text-lg font-extrabold text-slate-900">قائمة الطلبات والحجوزات</h2>
        <p class="text-xs text-slate-400 mt-0.5">مراجعة طلبات الوكلاء وإرسال عروض الأسعار</p>
      </div>
      <div class="flex items-center gap-2">
        <input
          v-model="search"
          type="text"
          placeholder="بحث برقم الطلب أو الوكالة..."
          class="border border-slate-200 rounded-xl px-4 py-2 text-xs w-52 focus:outline-none focus:ring-2 focus:ring-amber-400"
        />
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="bg-white rounded-3xl p-12 shadow-sm border border-slate-200 text-center text-slate-400 text-sm">
      جارٍ التحميل...
    </div>

    <div v-else-if="filteredOrders.length === 0" class="bg-white rounded-3xl p-12 shadow-sm border border-slate-200 text-center text-slate-400 text-sm">
      لا توجد طلبات
    </div>

    <!-- Orders Table -->
    <div v-else class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="font-bold text-sm text-slate-900">الطلبات الواردة</h3>
        <span class="bg-amber-100 text-amber-800 text-[10px] font-bold px-2.5 py-1 rounded-full">
          {{ filteredOrders.length }} طلب
        </span>
      </div>

      <div class="overflow-x-auto rounded-2xl border border-slate-200">
        <table class="w-full text-right text-xs">
          <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
            <tr>
              <th class="p-3 font-semibold">رقم الطلب</th>
              <th class="p-3 font-semibold">الوكالة</th>
              <th class="p-3 font-semibold">الركاب / الرحلات</th>
              <th class="p-3 font-semibold">التاريخ</th>
              <th class="p-3 font-semibold">الحالة</th>
              <th class="p-3 font-semibold text-center">الإجراء</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="order in filteredOrders" :key="order.id" class="hover:bg-slate-50 transition">
              <td class="p-3 font-mono font-bold text-slate-700">{{ order.order_number }}</td>
              <td class="p-3">
                <p class="font-bold text-slate-800">{{ order.agency?.name }}</p>
              </td>
              <td class="p-3 text-slate-600">{{ order.total_passengers }} ركاب • {{ order.segments?.length }} رحلة</td>
              <td class="p-3 text-slate-500">{{ formatDate(order.created_at) }}</td>
              <td class="p-3">
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold" :class="statusClass(order.status?.name)">
                  {{ order.status?.label }}
                </span>
              </td>
              <td class="p-3 text-center">
                <router-link
                  v-if="order.status?.name === 'pending_pricing'"
                  :to="`/admin/orders/${order.id}/price`"
                  class="bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold px-4 py-2 rounded-xl text-[11px] inline-flex items-center gap-1.5 transition shadow-sm"
                >
                  <Tag class="w-3.5 h-3.5" />
                  تسعير وإرسال عرض
                </router-link>
                <span v-else class="text-[10px] text-slate-400 px-3 py-2">
                  {{ order.status?.name === 'pricing_sent' ? 'بانتظار موافقة الوكيل' : 'تمت المعالجة' }}
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
import { ref, computed, onMounted } from 'vue'
import api from '@/lib/api'
import { Tag } from 'lucide-vue-next'

const orders = ref([])
const loading = ref(true)
const search = ref('')

const filteredOrders = computed(() =>
  orders.value.filter(o =>
    o.order_number?.toLowerCase().includes(search.value.toLowerCase()) ||
    o.agency?.name?.toLowerCase().includes(search.value.toLowerCase())
  )
)

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

const formatDate = (date) => new Date(date).toLocaleDateString('ar-EG')
</script>

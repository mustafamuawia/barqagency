<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 flex items-center justify-between">
      <div>
        <h2 class="text-lg font-extrabold text-slate-900">طلبات الاسترداد</h2>
        <p class="text-xs text-slate-400 mt-0.5">عرض طلبات الاسترداد وحالتها</p>
      </div>
      <router-link
        to="/agency/refunds/new"
        class="bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 transition shadow-sm"
      >
        <PlusCircle class="w-4 h-4" />
        طلب استرداد جديد
      </router-link>
    </div>

    <div v-if="loading" class="bg-white rounded-3xl p-12 shadow-sm border border-slate-200 text-center text-slate-400 text-sm">
      جارٍ التحميل...
    </div>

    <div v-else-if="refunds.length === 0" class="bg-white rounded-3xl p-12 shadow-sm border border-slate-200 text-center text-slate-400 text-sm">
      لا توجد طلبات استرداد بعد
    </div>

    <div v-else class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="font-bold text-sm text-slate-900">سجل الطلبات</h3>
        <span class="bg-slate-100 text-slate-600 text-[10px] font-bold px-2.5 py-1 rounded-full">{{ refunds.length }} طلب</span>
      </div>

      <div class="overflow-x-auto rounded-2xl border border-slate-200">
        <table class="w-full text-right text-xs">
          <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
            <tr>
              <th class="p-3 font-semibold">رقم الاسترداد</th>
              <th class="p-3 font-semibold">رقم الطلب</th>
              <th class="p-3 font-semibold">تاريخ الطلب</th>
              <th class="p-3 font-semibold">المبلغ الأصلي</th>
              <th class="p-3 font-semibold">صافي الاسترداد</th>
              <th class="p-3 font-semibold">الحالة</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="refund in refunds" :key="refund.id" class="hover:bg-slate-50 transition">
              <td class="p-3 font-mono font-bold text-slate-700">{{ refund.refund_number }}</td>
              <td class="p-3 font-mono text-slate-600">{{ refund.order?.order_number }}</td>
              <td class="p-3 text-slate-500">{{ formatDate(refund.requested_at) }}</td>
              <td class="p-3 font-mono text-slate-600">{{ formatCurrency(refund.original_amount) }}</td>
              <td class="p-3 font-mono font-bold text-emerald-600">
                {{ refund.status?.name === 'credited' ? formatCurrency(refund.refund_amount) : '—' }}
              </td>
              <td class="p-3">
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold" :class="statusClass(refund.status?.name)">
                  {{ statusLabel(refund.status?.name) }}
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

const refunds = ref([])
const loading = ref(true)

onMounted(async () => {
  try {
    const response = await api.get('/refunds')
    refunds.value = response.data
  } catch (err) {
    console.error('Failed to load refunds', err)
  } finally {
    loading.value = false
  }
})

const statusClass = (status) => {
  const classes = {
    pending: 'bg-amber-100 text-amber-800',
    credited: 'bg-emerald-100 text-emerald-800',
    rejected: 'bg-red-100 text-red-800',
  }
  return classes[status] || 'bg-slate-100 text-slate-600'
}

const statusLabel = (status) => {
  const labels = {
    pending: 'بانتظار المراجعة',
    credited: 'تم الاسترداد',
    rejected: 'مرفوض',
  }
  return labels[status] || status
}

const formatCurrency = (value) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value || 0)
const formatDate = (date) => new Date(date).toLocaleDateString('ar-EG')
</script>

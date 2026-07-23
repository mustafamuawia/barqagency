<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 flex items-center justify-between">
      <div>
        <h2 class="text-lg font-extrabold text-slate-900">التقارير المالية</h2>
        <p class="text-xs text-slate-400 mt-0.5">سجل حجوزاتك واستردادك خلال فترة محددة</p>
      </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200">
      <div class="grid grid-cols-2 md:grid-cols-3 gap-4 items-end">
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1.5">من تاريخ</label>
          <input v-model="filters.from" type="date"
            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1.5">إلى تاريخ</label>
          <input v-model="filters.to" type="date"
            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
        </div>
        <button @click="loadSummary" :disabled="loading"
          class="bg-[#0f172a] hover:bg-[#1e293b] disabled:opacity-50 text-white font-bold py-2.5 rounded-xl text-xs transition">
          {{ loading ? 'جارٍ التحميل...' : 'تطبيق' }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="bg-white rounded-3xl p-12 shadow-sm border border-slate-200 text-center text-slate-400 text-sm">
      جارٍ التحميل...
    </div>

    <template v-else-if="summary">
      <!-- Summary -->
      <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-bold text-sm text-slate-900">الملخص المالي</h3>
          <div class="flex gap-2">
            <button @click="downloadPdf" :disabled="exporting"
              class="bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-slate-950 font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition">
              <FileText class="w-3.5 h-3.5" />
              PDF
            </button>
            <button @click="downloadCsv" :disabled="exporting"
              class="bg-slate-100 hover:bg-slate-200 disabled:opacity-50 text-slate-700 font-bold px-4 py-2 rounded-xl text-xs flex items-center gap-2 transition">
              <Download class="w-3.5 h-3.5" />
              CSV
            </button>
          </div>
        </div>
        <div class="grid grid-cols-3 gap-4">
          <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200">
            <p class="text-[10px] text-slate-500">إجمالي الحجوزات</p>
            <p class="text-xl font-black text-slate-900 font-mono mt-1">{{ formatCurrency(summary.totalBookings) }}</p>
          </div>
          <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200">
            <p class="text-[10px] text-slate-500">إجمالي الاستردادات</p>
            <p class="text-xl font-black text-red-600 font-mono mt-1">{{ formatCurrency(summary.totalRefunds) }}</p>
          </div>
          <div class="bg-[#0f172a] rounded-2xl p-4">
            <p class="text-[10px] text-slate-400">صافي الإنفاق</p>
            <p class="text-xl font-black text-emerald-400 font-mono mt-1">{{ formatCurrency(summary.net) }}</p>
          </div>
        </div>
      </div>

      <!-- Booking History -->
      <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-4">
        <h3 class="font-bold text-sm text-slate-900">سجل الحجوزات ({{ summary.orders.length }})</h3>

        <div v-if="summary.orders.length === 0" class="text-center text-slate-400 text-sm py-8">
          لا توجد حجوزات في هذه الفترة
        </div>

        <div v-else class="overflow-x-auto rounded-2xl border border-slate-200">
          <table class="w-full text-right text-xs">
            <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
              <tr>
                <th class="p-3 font-semibold">رقم الطلب</th>
                <th class="p-3 font-semibold">التاريخ</th>
                <th class="p-3 font-semibold">الحالة</th>
                <th class="p-3 font-semibold">القيمة</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="order in summary.orders" :key="order.order_number" class="hover:bg-slate-50 transition">
                <td class="p-3 font-mono font-bold text-slate-700">{{ order.order_number }}</td>
                <td class="p-3 text-slate-500">{{ order.date }}</td>
                <td class="p-3 text-slate-600">{{ order.status_label }}</td>
                <td class="p-3 font-mono text-slate-700">{{ formatCurrency(order.amount) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/lib/api'
import { downloadFile } from '@/lib/download'
import { FileText, Download } from 'lucide-vue-next'

const loading = ref(false)
const exporting = ref(false)
const summary = ref(null)

const today = new Date().toISOString().slice(0, 10)
const firstOfMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10)

const filters = ref({ from: firstOfMonth, to: today })

const loadSummary = async () => {
  loading.value = true
  try {
    const response = await api.get('/reports/financial-summary', { params: filters.value })
    summary.value = response.data
  } catch (err) {
    console.error('Failed to load report', err)
  } finally {
    loading.value = false
  }
}

const downloadPdf = async () => {
  exporting.value = true
  try {
    const response = await api.get('/reports/financial-summary/pdf', { params: filters.value, responseType: 'blob' })
    downloadFile(response.data, `financial-summary-${filters.value.from}-to-${filters.value.to}.pdf`)
  } catch (err) {
    console.error('Failed to export PDF', err)
  } finally {
    exporting.value = false
  }
}

const downloadCsv = async () => {
  exporting.value = true
  try {
    const response = await api.get('/reports/financial-summary/csv', { params: filters.value, responseType: 'blob' })
    downloadFile(response.data, `financial-summary-${filters.value.from}-to-${filters.value.to}.csv`)
  } catch (err) {
    console.error('Failed to export CSV', err)
  } finally {
    exporting.value = false
  }
}

onMounted(loadSummary)

const formatCurrency = (value) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value || 0)
</script>

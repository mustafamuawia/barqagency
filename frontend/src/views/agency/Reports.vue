<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <PageHeader title="التقارير المالية" subtitle="سجل حجوزاتك واستردادك خلال فترة محددة" />

    <!-- Filters -->
    <Card>
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
        <Button variant="dark" @click="loadSummary" :disabled="loading">
          {{ loading ? 'جارٍ التحميل...' : 'تطبيق' }}
        </Button>
      </div>
    </Card>

    <LoadingState v-if="loading" />

    <template v-else-if="summary">
      <!-- Summary -->
      <Card class="space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-bold text-sm text-slate-900">الملخص المالي</h3>
          <div class="flex gap-2">
            <Button variant="amber" @click="downloadPdf" :disabled="exporting">
              <FileText class="w-3.5 h-3.5" />
              PDF
            </Button>
            <Button variant="secondary" @click="downloadCsv" :disabled="exporting">
              <Download class="w-3.5 h-3.5" />
              CSV
            </Button>
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
      </Card>

      <!-- Booking History -->
      <Card class="space-y-4">
        <h3 class="font-bold text-sm text-slate-900">سجل الحجوزات ({{ summary.orders.length }})</h3>

        <div v-if="summary.orders.length === 0" class="text-center text-slate-400 text-sm py-8">
          لا توجد حجوزات في هذه الفترة
        </div>

        <DataTable v-else :columns="['رقم الطلب', 'التاريخ', 'الحالة', 'القيمة']">
          <tr v-for="order in summary.orders" :key="order.order_number" class="hover:bg-slate-50 transition">
            <td class="p-3 font-mono font-bold text-slate-700">{{ order.order_number }}</td>
            <td class="p-3 text-slate-500">{{ order.date }}</td>
            <td class="p-3 text-slate-600">{{ order.status_label }}</td>
            <td class="p-3 font-mono text-slate-700">{{ formatCurrency(order.amount) }}</td>
          </tr>
        </DataTable>
      </Card>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/lib/api'
import { downloadFile } from '@/lib/download'
import { FileText, Download } from 'lucide-vue-next'
import Card from '@/components/Card.vue'
import PageHeader from '@/components/PageHeader.vue'
import LoadingState from '@/components/LoadingState.vue'
import DataTable from '@/components/DataTable.vue'
import Button from '@/components/Button.vue'

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

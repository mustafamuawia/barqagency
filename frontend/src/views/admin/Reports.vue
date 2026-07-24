<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <PageHeader title="التقارير المالية" subtitle="سجل الحجوزات والاستردادات والإيداعات البنكية" />

    <!-- Tab Navigation -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-0">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        @click="activeTab = tab.key"
        :class="[
          'px-4 py-2.5 text-xs font-bold rounded-t-xl transition border-b-2 -mb-px',
          activeTab === tab.key
            ? 'text-amber-600 border-amber-500 bg-amber-50'
            : 'text-slate-500 border-transparent hover:text-slate-800'
        ]"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Filters (shared across tabs) -->
    <Card>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 items-end">
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
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1.5">الوكالة</label>
          <select v-model="filters.agency_id"
            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400">
            <option :value="null">جميع الوكالات</option>
            <option v-for="agency in agencies" :key="agency.id" :value="agency.id">{{ agency.name }}</option>
          </select>
        </div>
        <Button @click="applyFilters" variant="dark" size="sm" :disabled="loading || depositsLoading">
          {{ (loading || depositsLoading) ? 'جارٍ التحميل...' : 'تطبيق' }}
        </Button>
      </div>
    </Card>

    <!-- ======= FINANCIAL SUMMARY TAB ======= -->
    <template v-if="activeTab === 'summary'">
      <LoadingState v-if="loading" />

      <template v-else-if="summary">
        <!-- Summary -->
        <Card class="space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="font-bold text-sm text-slate-900">الملخص المالي</h3>
            <div class="flex gap-2">
              <Button @click="downloadPdf" variant="amber" size="sm" :disabled="exporting">
                <FileText class="w-3.5 h-3.5" />
                PDF
              </Button>
              <Button @click="downloadCsv" variant="secondary" size="sm" :disabled="exporting">
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
              <p class="text-[10px] text-slate-400">صافي الإيرادات</p>
              <p class="text-xl font-black text-emerald-400 font-mono mt-1">{{ formatCurrency(summary.net) }}</p>
            </div>
          </div>
        </Card>

        <!-- Booking History -->
        <Card class="space-y-4">
          <h3 class="font-bold text-sm text-slate-900">سجل الحجوزات ({{ bookingHistoryMeta?.total ?? bookingHistory.length }})</h3>

          <div v-if="bookingHistory.length === 0" class="text-center text-slate-400 text-sm py-8">
            لا توجد حجوزات في هذه الفترة
          </div>

          <DataTable v-else :columns="['رقم الطلب', 'الوكالة', 'التاريخ', 'الحالة', 'القيمة']">
            <tr v-for="order in bookingHistory" :key="order.order_number" class="hover:bg-slate-50 transition">
              <td class="p-3 font-mono font-bold text-slate-700">{{ order.order_number }}</td>
              <td class="p-3 text-slate-600">{{ order.agency_name }}</td>
              <td class="p-3 text-slate-500">{{ order.date }}</td>
              <td class="p-3 text-slate-600">{{ order.status_label }}</td>
              <td class="p-3 font-mono text-slate-700">{{ formatCurrency(order.amount) }}</td>
            </tr>
          </DataTable>

          <Pagination :meta="bookingHistoryMeta" @change="page => loadBookingHistory(page)" />
        </Card>
      </template>
    </template>

    <!-- ======= ALL DEPOSITS TAB ======= -->
    <template v-else-if="activeTab === 'deposits'">
      <Card class="space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-bold text-sm text-slate-900">سجل جميع إشعارات الإيداع البنكي</h3>
          <div class="flex items-center gap-3">
            <Button @click="downloadDepositsPdf" variant="amber" size="sm" :disabled="depositsExporting">
              <FileText class="w-3.5 h-3.5" />
              PDF
            </Button>
            <Button @click="downloadDepositsCsv" variant="secondary" size="sm" :disabled="depositsExporting">
              <Download class="w-3.5 h-3.5" />
              CSV
            </Button>
            <button @click="loadAllDeposits" class="text-xs text-amber-600 font-bold hover:underline">تحديث</button>
          </div>
        </div>

        <LoadingState v-if="depositsLoading" />

        <div v-else-if="!allDeposits.length" class="text-center text-slate-400 text-sm py-8">
          لا توجد إشعارات إيداع مسجلة
        </div>

        <div v-else class="overflow-x-auto rounded-xl border border-slate-200">
          <table class="w-full text-right text-xs">
            <thead class="bg-slate-50 text-slate-500 border-b">
              <tr>
                <th class="p-3 font-semibold">الوكالة</th>
                <th class="p-3 font-semibold">التاريخ</th>
                <th class="p-3 font-semibold">رقم التحويل</th>
                <th class="p-3 font-semibold">المبلغ</th>
                <th class="p-3 font-semibold text-center">الحالة</th>
                <th class="p-3 font-semibold">الإيصال</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="dep in allDeposits" :key="dep.id" class="hover:bg-slate-50 transition">
                <td class="p-3 font-bold text-slate-800">{{ dep.wallet?.agency?.name || '—' }}</td>
                <td class="p-3 text-slate-500 font-mono">{{ formatFullDate(dep.created_at) }}</td>
                <td class="p-3 font-mono font-bold text-slate-700">{{ dep.metadata?.reference_number || '—' }}</td>
                <td class="p-3 font-mono font-black text-emerald-600">${{ parseFloat(dep.amount).toFixed(2) }}</td>
                <td class="p-3 text-center">
                  <span v-if="dep.metadata?.status === 'pending'"
                    class="bg-amber-100 text-amber-800 font-bold px-2.5 py-1 rounded-full text-[10px]">
                    قيد المراجعة
                  </span>
                  <span v-else-if="dep.metadata?.status === 'approved'"
                    class="bg-emerald-100 text-emerald-800 font-bold px-2.5 py-1 rounded-full text-[10px]">
                    ✓ مقبول
                  </span>
                  <span v-else-if="dep.metadata?.status === 'rejected'"
                    class="bg-rose-100 text-rose-800 font-bold px-2.5 py-1 rounded-full text-[10px]">
                    ✕ مرفوض
                  </span>
                </td>
                <td class="p-3">
                  <a v-if="dep.metadata?.receipt_path" :href="`/storage/${dep.metadata.receipt_path}`" target="_blank"
                    class="text-amber-600 text-xs font-bold hover:underline">
                    عرض الإيصال
                  </a>
                  <span v-else class="text-slate-400">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <Pagination :meta="depositsMeta" @change="page => loadAllDeposits(page)" />
      </Card>
    </template>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/lib/api'
import { downloadFile } from '@/lib/download'
import { FileText, Download } from 'lucide-vue-next'
import PageHeader from '@/components/PageHeader.vue'
import Card from '@/components/Card.vue'
import Button from '@/components/Button.vue'
import LoadingState from '@/components/LoadingState.vue'
import Pagination from '@/components/Pagination.vue'
import DataTable from '@/components/DataTable.vue'

const activeTab = ref('summary')
const tabs = [
  { key: 'summary', label: 'الملخص المالي' },
  { key: 'deposits', label: 'سجل الإيداعات البنكية' },
]

const loading = ref(false)
const exporting = ref(false)
const depositsLoading = ref(false)
const depositsExporting = ref(false)
const agencies = ref([])
const summary = ref(null)
const bookingHistory = ref([])
const bookingHistoryMeta = ref(null)
const allDeposits = ref([])
const depositsMeta = ref(null)

const today = new Date().toISOString().slice(0, 10)
const firstOfMonth = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10)

const filters = ref({ from: firstOfMonth, to: today, agency_id: null })

const loadAgencies = async () => {
  try {
    const response = await api.get('/agencies')
    agencies.value = response.data
  } catch (err) {
    console.error('Failed to load agencies', err)
  }
}

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

const loadBookingHistory = async (page = 1) => {
  try {
    const response = await api.get('/reports/booking-history', { params: { ...filters.value, page } })
    bookingHistory.value = response.data.data
    bookingHistoryMeta.value = response.data
  } catch (err) {
    console.error('Failed to load booking history', err)
  }
}

const loadAllDeposits = async (page = 1) => {
  depositsLoading.value = true
  try {
    const response = await api.get('/wallets/all-deposits', { params: { ...filters.value, page } })
    allDeposits.value = response.data.data
    depositsMeta.value = response.data
  } catch (err) {
    console.error('Failed to load all deposits', err)
  } finally {
    depositsLoading.value = false
  }
}

const applyFilters = async () => {
  await Promise.all([loadSummary(), loadBookingHistory(), loadAllDeposits()])
}

const downloadDepositsPdf = async () => {
  depositsExporting.value = true
  try {
    const response = await api.get('/wallets/all-deposits/pdf', { params: filters.value, responseType: 'blob' })
    downloadFile(response.data, `deposits-${filters.value.from}-to-${filters.value.to}.pdf`)
  } catch (err) {
    console.error('Failed to export deposits PDF', err)
  } finally {
    depositsExporting.value = false
  }
}

const downloadDepositsCsv = async () => {
  depositsExporting.value = true
  try {
    const response = await api.get('/wallets/all-deposits/csv', { params: filters.value, responseType: 'blob' })
    downloadFile(response.data, `deposits-${filters.value.from}-to-${filters.value.to}.csv`)
  } catch (err) {
    console.error('Failed to export deposits CSV', err)
  } finally {
    depositsExporting.value = false
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

onMounted(async () => {
  await loadAgencies()
  await Promise.all([loadSummary(), loadBookingHistory(), loadAllDeposits()])
})

const formatCurrency = (value) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value || 0)
const formatFullDate = (date) => new Date(date).toLocaleString('ar-SA')
</script>

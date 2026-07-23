<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <PageHeader title="طلبات الاسترداد" subtitle="عرض طلبات الاسترداد وحالتها">
      <template #actions>
        <router-link
          to="/agency/refunds/new"
          class="bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 transition shadow-sm"
        >
          <PlusCircle class="w-4 h-4" />
          طلب استرداد جديد
        </router-link>
      </template>
    </PageHeader>

    <LoadingState v-if="loading" />

    <EmptyState v-else-if="refunds.length === 0">لا توجد طلبات استرداد بعد</EmptyState>

    <Card v-else class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="font-bold text-sm text-slate-900">سجل الطلبات</h3>
        <Badge color="slate">{{ refunds.length }} طلب</Badge>
      </div>

      <DataTable :columns="['رقم الاسترداد', 'رقم الطلب', 'تاريخ الطلب', 'المبلغ الأصلي', 'صافي الاسترداد', 'الحالة', 'إجراءات']">
        <tr v-for="refund in refunds" :key="refund.id" class="hover:bg-slate-50 transition">
          <td class="p-3 font-mono font-bold text-slate-700">{{ refund.refund_number }}</td>
          <td class="p-3 font-mono text-slate-600">{{ refund.order?.order_number }}</td>
          <td class="p-3 text-slate-500">{{ formatDate(refund.requested_at) }}</td>
          <td class="p-3 font-mono text-slate-600">{{ formatCurrency(refund.original_amount) }}</td>
          <td class="p-3 font-mono font-bold text-emerald-600">
            {{ refund.status?.name === 'credited' ? formatCurrency(refund.refund_amount) : '—' }}
          </td>
          <td class="p-3">
            <Badge :color="statusColor(refund.status?.name)">{{ statusLabel(refund.status?.name) }}</Badge>
          </td>
          <td class="p-3">
            <button
              v-if="refund.status?.name === 'credited'"
              @click="downloadCreditNote(refund)"
              :disabled="downloadingId === refund.id"
              class="bg-slate-100 hover:bg-slate-200 disabled:opacity-50 text-slate-700 font-bold px-3 py-1.5 rounded-lg text-[10px] flex items-center gap-1.5 transition"
            >
              <FileText class="w-3.5 h-3.5" />
              {{ downloadingId === refund.id ? 'جارٍ...' : 'سند الاسترداد' }}
            </button>
            <span v-else class="text-slate-300">—</span>
          </td>
        </tr>
      </DataTable>
    </Card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/lib/api'
import { downloadFile } from '@/lib/download'
import { PlusCircle, FileText } from 'lucide-vue-next'
import Card from '@/components/Card.vue'
import PageHeader from '@/components/PageHeader.vue'
import Badge from '@/components/Badge.vue'
import LoadingState from '@/components/LoadingState.vue'
import EmptyState from '@/components/EmptyState.vue'
import DataTable from '@/components/DataTable.vue'

const refunds = ref([])
const loading = ref(true)
const downloadingId = ref(null)

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

const statusColor = (status) => {
  const colors = {
    pending: 'amber',
    credited: 'emerald',
    rejected: 'red',
  }
  return colors[status] || 'slate'
}

const statusLabel = (status) => {
  const labels = {
    pending: 'بانتظار المراجعة',
    credited: 'تم الاسترداد',
    rejected: 'مرفوض',
  }
  return labels[status] || status
}

const downloadCreditNote = async (refund) => {
  downloadingId.value = refund.id
  try {
    const response = await api.get(`/refunds/${refund.id}/credit-note`, { responseType: 'blob' })
    downloadFile(response.data, `credit-note-${refund.refund_number}.pdf`)
  } catch (err) {
    console.error('Failed to download credit note', err)
  } finally {
    downloadingId.value = null
  }
}

const formatCurrency = (value) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value || 0)
const formatDate = (date) => new Date(date).toLocaleDateString('ar-EG')
</script>

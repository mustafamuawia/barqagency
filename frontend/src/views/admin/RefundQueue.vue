<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <PageHeader title="طلبات الاسترداد" subtitle="مراجعة طلبات استرداد الوكلاء والموافقة عليها أو رفضها" />

    <!-- Loading -->
    <LoadingState v-if="loading" />

    <EmptyState v-else-if="refunds.length === 0">لا توجد طلبات استرداد</EmptyState>

    <!-- Refunds Table -->
    <Card v-else class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="font-bold text-sm text-slate-900">جميع الطلبات</h3>
        <Badge color="yellow">{{ pendingCount }} بانتظار المراجعة</Badge>
      </div>

      <DataTable :columns="['رقم الاسترداد', 'الوكالة', 'رقم الطلب', 'المبلغ الأصلي', 'التاريخ', 'الحالة', 'الإجراء']">
        <tr v-for="refund in refunds" :key="refund.id" class="hover:bg-slate-50 transition">
          <td class="p-3 font-mono font-bold text-slate-700">{{ refund.refund_number }}</td>
          <td class="p-3 font-bold text-slate-800">{{ refund.agency?.name }}</td>
          <td class="p-3 font-mono text-slate-600">{{ refund.order?.order_number }}</td>
          <td class="p-3 font-mono font-bold text-slate-700">{{ formatMoney(refund.original_amount) }}</td>
          <td class="p-3 text-slate-500">{{ formatDate(refund.requested_at) }}</td>
          <td class="p-3">
            <Badge :color="statusColor(refund.status?.name)">{{ statusLabel(refund.status?.name) }}</Badge>
          </td>
          <td class="p-3 text-center">
            <router-link
              v-if="refund.status?.name === 'pending'"
              :to="`/admin/refunds/${refund.id}`"
              class="bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold px-4 py-2 rounded-xl text-[11px] inline-flex items-center gap-1.5 transition shadow-sm"
            >
              <Eye class="w-3.5 h-3.5" />
              مراجعة الطلب
            </router-link>
            <span v-else class="text-[10px] text-slate-400 px-3 py-2">تمت المعالجة</span>
          </td>
        </tr>
      </DataTable>

      <Pagination :meta="meta" @change="page => loadRefunds(page)" />
    </Card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/lib/api'
import { Eye } from 'lucide-vue-next'
import PageHeader from '@/components/PageHeader.vue'
import Card from '@/components/Card.vue'
import Badge from '@/components/Badge.vue'
import LoadingState from '@/components/LoadingState.vue'
import EmptyState from '@/components/EmptyState.vue'
import DataTable from '@/components/DataTable.vue'
import Pagination from '@/components/Pagination.vue'
import { formatMoney } from '@/utils/points'

const refunds = ref([])
const meta = ref(null)
const pendingCount = ref(0)
const loading = ref(true)

const loadRefunds = async (page = 1) => {
  loading.value = true
  try {
    const response = await api.get('/refunds', { params: { page } })
    refunds.value = response.data.data
    pendingCount.value = response.data.pending_count
    meta.value = response.data
  } catch (err) {
    console.error('Failed to load refunds', err)
  } finally {
    loading.value = false
  }
}

onMounted(loadRefunds)

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

const formatDate = (date) => new Date(date).toLocaleDateString('ar-EG')
</script>

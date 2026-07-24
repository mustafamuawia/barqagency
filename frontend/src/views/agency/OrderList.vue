<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <PageHeader title="طلباتي وحجوزاتي" subtitle="عرض جميع طلبات الحجز وحالتها">
      <template #actions>
        <router-link
          to="/agency/orders/new"
          class="bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold px-4 py-2.5 rounded-xl text-xs flex items-center gap-2 transition shadow-sm"
        >
          <PlusCircle class="w-4 h-4" />
          طلب حجز جديد
        </router-link>
      </template>
    </PageHeader>

    <LoadingState v-if="loading" />

    <EmptyState v-else-if="orders.length === 0">لا توجد طلبات بعد. أنشئ أول حجز لك.</EmptyState>

    <Card v-else class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="font-bold text-sm text-slate-900">قائمة الطلبات</h3>
        <Badge color="slate">{{ meta?.total ?? orders.length }} طلب</Badge>
      </div>

      <DataTable :columns="['رقم الطلب', 'تاريخ الإنشاء', 'الركاب', 'المغادرة', 'العرض المقدم', 'الحالة']">
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
            <Badge :color="statusColor(order.status?.name)">{{ order.status?.label }}</Badge>
          </td>
        </tr>
      </DataTable>

      <Pagination :meta="meta" @change="page => loadOrders(page)" />
    </Card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/lib/api'
import { PlusCircle } from 'lucide-vue-next'
import Card from '@/components/Card.vue'
import PageHeader from '@/components/PageHeader.vue'
import Badge from '@/components/Badge.vue'
import LoadingState from '@/components/LoadingState.vue'
import EmptyState from '@/components/EmptyState.vue'
import DataTable from '@/components/DataTable.vue'
import Pagination from '@/components/Pagination.vue'

const orders = ref([])
const meta = ref(null)
const loading = ref(true)

const loadOrders = async (page = 1) => {
  loading.value = true
  try {
    const response = await api.get('/orders', { params: { page } })
    orders.value = response.data.data
    meta.value = response.data
  } catch (err) {
    console.error('Failed to load orders', err)
  } finally {
    loading.value = false
  }
}

onMounted(loadOrders)

const statusColor = (status) => {
  const colors = {
    pending_pricing: 'amber',
    pricing_sent: 'blue',
    approved: 'emerald',
    confirmed: 'purple',
  }
  return colors[status] || 'slate'
}

const formatCurrency = (value) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value || 0)
const formatDate = (date) => new Date(date).toLocaleDateString('ar-EG')
</script>

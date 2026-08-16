<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <PageHeader title="قائمة الطلبات والحجوزات" subtitle="مراجعة طلبات الوكلاء وإرسال عروض الأسعار">
      <template #actions>
        <select
          v-model="agencyId"
          class="border border-slate-200 rounded-xl px-4 py-2 text-xs w-44 focus:outline-none focus:ring-2 focus:ring-amber-400"
        >
          <option :value="null">جميع الوكالات</option>
          <option v-for="agency in agencies" :key="agency.id" :value="agency.id">{{ agency.name }}</option>
        </select>
        <input
          v-model="search"
          type="text"
          placeholder="بحث برقم الطلب أو الوكالة..."
          class="border border-slate-200 rounded-xl px-4 py-2 text-xs w-52 focus:outline-none focus:ring-2 focus:ring-amber-400"
        />
      </template>
    </PageHeader>

    <!-- Loading -->
    <LoadingState v-if="loading" />

    <EmptyState v-else-if="filteredOrders.length === 0">لا توجد طلبات</EmptyState>

    <!-- Orders Table -->
    <Card v-else class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="font-bold text-sm text-slate-900">الطلبات الواردة</h3>
        <Badge color="amber">{{ search ? filteredOrders.length : (meta?.total ?? filteredOrders.length) }} طلب</Badge>
      </div>

      <DataTable :columns="['رقم الطلب', 'الوكالة', 'الركاب / الرحلات', 'التاريخ', 'الحالة', 'الإجراء']">
        <tr v-for="order in filteredOrders" :key="order.id" class="hover:bg-slate-50 transition">
          <td class="p-3 font-mono font-bold text-slate-700">{{ order.order_number }}</td>
          <td class="p-3">
            <p class="font-bold text-slate-800">{{ order.agency?.name }}</p>
          </td>
          <td class="p-3 text-slate-600">{{ order.total_passengers }} ركاب • {{ order.segments?.length }} رحلة</td>
          <td class="p-3 text-slate-500">{{ formatDate(order.created_at) }}</td>
          <td class="p-3">
            <Badge :color="statusColor(order.status?.name)">{{ order.status?.label }}</Badge>
          </td>
          <td class="p-3 text-center">
            <router-link
              :to="`/admin/orders/${order.id}/price`"
              class="font-bold px-4 py-2 rounded-xl text-[11px] inline-flex items-center gap-1.5 transition"
              :class="action(order.status?.name).class"
            >
              <component :is="action(order.status?.name).icon" class="w-3.5 h-3.5" />
              {{ action(order.status?.name).label }}
            </router-link>
          </td>
        </tr>
      </DataTable>

      <Pagination :meta="meta" @change="page => loadOrders(page)" />
    </Card>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import api from '@/lib/api'
import { Tag, Pencil, Eye } from 'lucide-vue-next'
import PageHeader from '@/components/PageHeader.vue'
import Card from '@/components/Card.vue'
import Badge from '@/components/Badge.vue'
import LoadingState from '@/components/LoadingState.vue'
import EmptyState from '@/components/EmptyState.vue'
import DataTable from '@/components/DataTable.vue'
import Pagination from '@/components/Pagination.vue'

const orders = ref([])
const agencies = ref([])
const meta = ref(null)
const loading = ref(true)
const search = ref('')
const agencyId = ref(null)

const filteredOrders = computed(() =>
  orders.value.filter(o =>
    o.order_number?.toLowerCase().includes(search.value.toLowerCase()) ||
    o.agency?.name?.toLowerCase().includes(search.value.toLowerCase())
  )
)

const loadOrders = async (page = 1) => {
  loading.value = true
  try {
    const params = { page }
    if (agencyId.value) params.agency_id = agencyId.value
    const response = await api.get('/orders', { params })
    orders.value = response.data.data
    meta.value = response.data
  } catch (err) {
    console.error('Failed to load orders', err)
  } finally {
    loading.value = false
  }
}

const loadAgencies = async () => {
  try {
    const response = await api.get('/agencies')
    agencies.value = response.data
  } catch (err) {
    console.error('Failed to load agencies', err)
  }
}

/** Filtering is server-side, so a new agency always restarts at page 1. */
watch(agencyId, () => loadOrders(1))

onMounted(() => {
  loadOrders()
  loadAgencies()
})

/** The admin can revise the offer until the agency acts on it; after that it is read-only. */
const action = (status) => {
  if (status === 'pending_pricing') {
    return {
      label: 'تسعير وإرسال عرض',
      icon: Tag,
      class: 'bg-amber-500 hover:bg-amber-600 text-slate-950 shadow-sm',
    }
  }

  if (status === 'pricing_sent') {
    return {
      label: 'عرض وتعديل',
      icon: Pencil,
      class: 'bg-blue-500 hover:bg-blue-600 text-white shadow-sm',
    }
  }

  return {
    label: 'عرض التفاصيل',
    icon: Eye,
    class: 'bg-slate-100 hover:bg-slate-200 text-slate-700',
  }
}

const statusColor = (status) => {
  const colors = {
    pending_pricing: 'amber',
    pricing_sent: 'blue',
    approved: 'emerald',
    confirmed: 'purple',
  }
  return colors[status] || 'slate'
}

const formatDate = (date) => new Date(date).toLocaleDateString('ar-EG')
</script>

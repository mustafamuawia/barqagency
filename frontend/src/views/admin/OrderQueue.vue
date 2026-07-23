<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <PageHeader title="قائمة الطلبات والحجوزات" subtitle="مراجعة طلبات الوكلاء وإرسال عروض الأسعار">
      <template #actions>
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
        <Badge color="amber">{{ filteredOrders.length }} طلب</Badge>
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
      </DataTable>
    </Card>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/lib/api'
import { Tag } from 'lucide-vue-next'
import PageHeader from '@/components/PageHeader.vue'
import Card from '@/components/Card.vue'
import Badge from '@/components/Badge.vue'
import LoadingState from '@/components/LoadingState.vue'
import EmptyState from '@/components/EmptyState.vue'
import DataTable from '@/components/DataTable.vue'

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

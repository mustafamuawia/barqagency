<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <PageHeader title="لوحة تحكم الوكالة" subtitle="نظرة عامة على رصيدك وطلباتك الأخيرة">
      <template #actions>
        <Button variant="secondary" @click="loadData">
          <RefreshCw class="w-4 h-4" />
          تحديث
        </Button>
      </template>
    </PageHeader>

    <LoadingState v-if="loading" />

    <template v-else>
      <!-- Stats Grid -->
      <div class="grid grid-cols-3 gap-4">
        <StatTile
          :icon="Wallet"
          icon-bg="bg-emerald-100"
          icon-color="text-emerald-600"
          label="الرصيد الحالي"
          :value="formatCurrency(wallet?.balance)"
          value-color="text-emerald-600"
          :caption="wallet?.status || 'نشطة'"
        />
        <StatTile
          :icon="ClipboardList"
          icon-bg="bg-amber-100"
          icon-color="text-amber-600"
          label="الطلبات النشطة"
          :value="activeOrders"
          :mono="false"
          large
          caption="بانتظار التسعير أو الموافقة"
        />
        <StatTile
          :icon="CreditCard"
          icon-bg="bg-blue-100"
          icon-color="text-blue-600"
          label="حد الائتمان"
          :value="formatCurrency(wallet?.credit_limit)"
          value-color="text-blue-600"
          caption="ائتمان متاح"
        />
      </div>

      <!-- Quick Actions -->
      <Card>
        <h3 class="font-bold text-sm text-slate-900 mb-4">إجراءات سريعة</h3>
        <div class="grid grid-cols-3 gap-3">
          <router-link to="/agency/orders/new" class="bg-[#0f172a] hover:bg-slate-800 text-white p-4 rounded-2xl text-center text-xs font-bold transition flex flex-col items-center gap-2">
            <PlusCircle class="w-5 h-5 text-amber-400" />
            طلب حجز جديد
          </router-link>
          <router-link to="/agency/wallet" class="bg-[#0f172a] hover:bg-slate-800 text-white p-4 rounded-2xl text-center text-xs font-bold transition flex flex-col items-center gap-2">
            <Wallet class="w-5 h-5 text-emerald-400" />
            المحفظة والرصيد
          </router-link>
          <router-link to="/agency/orders" class="bg-[#0f172a] hover:bg-slate-800 text-white p-4 rounded-2xl text-center text-xs font-bold transition flex flex-col items-center gap-2">
            <ClipboardList class="w-5 h-5 text-blue-400" />
            طلباتي
          </router-link>
        </div>
      </Card>

      <!-- Recent Orders -->
      <Card class="space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-bold text-sm text-slate-900">آخر الطلبات</h3>
          <router-link to="/agency/orders" class="text-xs text-amber-600 font-bold hover:underline">عرض الكل</router-link>
        </div>

        <div v-if="orders.length === 0" class="text-center text-slate-400 text-sm py-8">
          لا توجد طلبات بعد. أنشئ أول حجز لك.
        </div>

        <DataTable v-else :columns="['رقم الطلب', 'التاريخ', 'الحالة']">
          <tr
            v-for="order in orders.slice(0, 5)"
            :key="order.id"
            class="hover:bg-slate-50 transition cursor-pointer"
            @click="$router.push(`/agency/orders/${order.id}`)"
          >
            <td class="p-3 font-mono font-bold text-slate-700">{{ order.order_number }}</td>
            <td class="p-3 text-slate-500">{{ formatDate(order.created_at) }}</td>
            <td class="p-3">
              <Badge :color="statusColor(order.status?.name)">{{ order.status?.label }}</Badge>
            </td>
          </tr>
        </DataTable>
      </Card>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/lib/api'
import { RefreshCw, Wallet, ClipboardList, CreditCard, PlusCircle } from 'lucide-vue-next'
import Card from '@/components/Card.vue'
import PageHeader from '@/components/PageHeader.vue'
import StatTile from '@/components/StatTile.vue'
import Badge from '@/components/Badge.vue'
import LoadingState from '@/components/LoadingState.vue'
import DataTable from '@/components/DataTable.vue'
import Button from '@/components/Button.vue'

const wallet = ref(null)
const orders = ref([])
const loading = ref(true)

const activeOrders = computed(() =>
  orders.value.filter(o => ['pending_pricing', 'pricing_sent', 'approved'].includes(o.status?.name)).length
)

const loadData = async () => {
  loading.value = true
  try {
    const [walletRes, ordersRes] = await Promise.all([api.get('/wallets'), api.get('/orders')])
    wallet.value = walletRes.data
    orders.value = ordersRes.data.data || ordersRes.data
  } catch (err) {
    console.error('Failed to load dashboard', err)
  } finally {
    loading.value = false
  }
}

onMounted(loadData)

const formatCurrency = (value) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value || 0)
const formatDate = (date) => new Date(date).toLocaleDateString('ar-EG')

const statusColor = (status) => {
  const colors = {
    pending_pricing: 'amber',
    pricing_sent: 'blue',
    approved: 'emerald',
    confirmed: 'purple',
  }
  return colors[status] || 'slate'
}
</script>

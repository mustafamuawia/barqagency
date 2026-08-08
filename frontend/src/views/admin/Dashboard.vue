<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <PageHeader title="لوحة التحكم الرئيسية" subtitle="نظرة عامة على حالة المنصة والطلبات المعلقة">
      <template #actions>
        <Button variant="secondary" @click="loadData">
          <RefreshCw class="w-4 h-4" />
          تحديث
        </Button>
      </template>
    </PageHeader>

    <!-- Loading -->
    <LoadingState v-if="loading" />

    <template v-else>
      <!-- Stats Grid -->
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
        <StatTile
          :icon="ClipboardList"
          icon-bg="bg-amber-100"
          icon-color="text-amber-600"
          label="طلبات معلقة"
          :value="stats.pendingOrders"
          caption="بانتظار التسعير"
          :large="true"
          :mono="false"
        />
        <router-link to="/admin/agencies">
          <StatTile
            :icon="Users"
            icon-bg="bg-purple-100"
            icon-color="text-purple-600"
            label="وكالات جديدة"
            :value="stats.pendingAgencies"
            caption="بانتظار الموافقة"
            value-color="text-purple-600"
            :large="true"
            :mono="false"
          />
        </router-link>
        <StatTile
          :icon="RotateCcw"
          icon-bg="bg-yellow-100"
          icon-color="text-yellow-600"
          label="استردادات معلقة"
          :value="stats.pendingRefunds"
          caption="بانتظار الموافقة"
          value-color="text-yellow-600"
          :large="true"
          :mono="false"
        />
        <StatTile
          :icon="Wallet"
          icon-bg="bg-blue-100"
          icon-color="text-blue-600"
          label="شحن محافظ"
          :value="stats.pendingTopups"
          caption="بانتظار التحقق"
          value-color="text-blue-600"
          :large="true"
          :mono="false"
        />
        <StatTile
          :icon="TrendingUp"
          icon-bg="bg-emerald-100"
          icon-color="text-emerald-600"
          label="إجمالي الأرصدة"
          :value="formatCurrency(stats.totalBalance)"
          caption="عبر جميع الوكالات"
          value-color="text-emerald-600"
        />
      </div>

      <!-- Financial Summary -->
      <Card class="space-y-4">
        <h3 class="font-bold text-sm text-slate-900">الملخص المالي (هذا الشهر)</h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200">
            <p class="text-[10px] text-slate-500">إجمالي الحجوزات</p>
            <p class="text-xl font-black text-slate-900 font-mono mt-1">{{ formatCurrency(financials.totalBookings) }}</p>
          </div>
          <div class="bg-slate-50 rounded-2xl p-4 border border-slate-200">
            <p class="text-[10px] text-slate-500">إجمالي الاستردادات</p>
            <p class="text-xl font-black text-red-600 font-mono mt-1">{{ formatCurrency(financials.totalRefunds) }}</p>
          </div>
          <div class="bg-[#0f172a] rounded-2xl p-4">
            <p class="text-[10px] text-slate-400">صافي الإيرادات</p>
            <p class="text-xl font-black text-emerald-400 font-mono mt-1">{{ formatCurrency(financials.totalBookings - financials.totalRefunds) }}</p>
          </div>
        </div>
      </Card>

      <!-- Quick Actions -->
      <Card>
        <h3 class="font-bold text-sm text-slate-900 mb-4">إجراءات سريعة</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <router-link to="/admin/orders" class="bg-[#0f172a] hover:bg-slate-800 text-white p-4 rounded-2xl text-center text-xs font-bold transition flex flex-col items-center gap-2">
            <ClipboardList class="w-5 h-5 text-amber-400" />
            عرض الطلبات
          </router-link>
          <router-link to="/admin/refunds" class="bg-[#0f172a] hover:bg-slate-800 text-white p-4 rounded-2xl text-center text-xs font-bold transition flex flex-col items-center gap-2">
            <RotateCcw class="w-5 h-5 text-red-400" />
            طلبات الاسترداد
          </router-link>
          <router-link to="/admin/wallet/topup" class="bg-[#0f172a] hover:bg-slate-800 text-white p-4 rounded-2xl text-center text-xs font-bold transition flex flex-col items-center gap-2">
            <Wallet class="w-5 h-5 text-emerald-400" />
            شحن المحافظ
          </router-link>
          <router-link to="/admin/agencies" class="bg-[#0f172a] hover:bg-slate-800 text-white p-4 rounded-2xl text-center text-xs font-bold transition flex flex-col items-center gap-2">
            <Users class="w-5 h-5 text-blue-400" />
            إدارة الوكالات
          </router-link>
        </div>
      </Card>

      <!-- Recent Orders -->
      <Card class="space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-bold text-sm text-slate-900">آخر الطلبات</h3>
          <router-link to="/admin/orders" class="text-xs text-amber-600 font-bold hover:underline">عرض الكل</router-link>
        </div>

        <div v-if="recentOrders.length === 0" class="text-center text-slate-400 text-sm py-8">
          لا توجد طلبات بعد
        </div>

        <DataTable v-else :columns="['رقم الطلب', 'الوكالة', 'الحالة']">
          <tr v-for="order in recentOrders" :key="order.id" class="hover:bg-slate-50 transition">
            <td class="p-3 font-mono font-bold text-slate-700">{{ order.order_number }}</td>
            <td class="p-3 text-slate-600">{{ order.agency?.name }}</td>
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
import { ref, onMounted } from 'vue'
import api from '@/lib/api'
import { RefreshCw, ClipboardList, RotateCcw, Wallet, TrendingUp, Users } from 'lucide-vue-next'
import PageHeader from '@/components/PageHeader.vue'
import Card from '@/components/Card.vue'
import StatTile from '@/components/StatTile.vue'
import Badge from '@/components/Badge.vue'
import LoadingState from '@/components/LoadingState.vue'
import DataTable from '@/components/DataTable.vue'
import Button from '@/components/Button.vue'

const loading = ref(true)
const stats = ref({ pendingOrders: 0, pendingRefunds: 0, pendingAgencies: 0, pendingTopups: 0, totalBalance: 0 })
const financials = ref({ totalBookings: 0, totalRefunds: 0 })
const recentOrders = ref([])

const loadData = async () => {
  loading.value = true
  try {
    const response = await api.get('/admin/dashboard')
    stats.value = response.data.stats
    financials.value = response.data.financials
    recentOrders.value = response.data.recentOrders
  } catch (err) {
    console.error('Failed to load dashboard', err)
  } finally {
    loading.value = false
  }
}

onMounted(loadData)

const formatCurrency = (value) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value || 0)

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

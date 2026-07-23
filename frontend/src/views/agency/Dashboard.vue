<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 flex items-center justify-between">
      <div>
        <h2 class="text-lg font-extrabold text-slate-900">لوحة تحكم الوكالة</h2>
        <p class="text-xs text-slate-400 mt-0.5">نظرة عامة على رصيدك وطلباتك الأخيرة</p>
      </div>
      <button @click="loadData" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-4 py-2.5 rounded-xl flex items-center gap-2 transition">
        <RefreshCw class="w-4 h-4" />
        تحديث
      </button>
    </div>

    <div v-if="loading" class="bg-white rounded-3xl p-12 shadow-sm border border-slate-200 text-center text-slate-400 text-sm">
      جارٍ التحميل...
    </div>

    <template v-else>
      <!-- Stats Grid -->
      <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-3xl p-5 shadow-sm border border-slate-200">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 rounded-xl bg-emerald-100 flex items-center justify-center">
              <Wallet class="w-4 h-4 text-emerald-600" />
            </div>
            <p class="text-xs text-slate-500 font-medium">الرصيد الحالي</p>
          </div>
          <p class="text-2xl font-black text-emerald-600 font-mono">{{ formatCurrency(wallet?.balance) }}</p>
          <p class="text-[10px] text-slate-400 mt-1">{{ wallet?.status || 'نشطة' }}</p>
        </div>
        <div class="bg-white rounded-3xl p-5 shadow-sm border border-slate-200">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 rounded-xl bg-amber-100 flex items-center justify-center">
              <ClipboardList class="w-4 h-4 text-amber-600" />
            </div>
            <p class="text-xs text-slate-500 font-medium">الطلبات النشطة</p>
          </div>
          <p class="text-3xl font-black text-slate-900">{{ activeOrders }}</p>
          <p class="text-[10px] text-slate-400 mt-1">بانتظار التسعير أو الموافقة</p>
        </div>
        <div class="bg-white rounded-3xl p-5 shadow-sm border border-slate-200">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 rounded-xl bg-blue-100 flex items-center justify-center">
              <CreditCard class="w-4 h-4 text-blue-600" />
            </div>
            <p class="text-xs text-slate-500 font-medium">حد الائتمان</p>
          </div>
          <p class="text-2xl font-black text-blue-600 font-mono">{{ formatCurrency(wallet?.credit_limit) }}</p>
          <p class="text-[10px] text-slate-400 mt-1">ائتمان متاح</p>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200">
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
      </div>

      <!-- Recent Orders -->
      <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-bold text-sm text-slate-900">آخر الطلبات</h3>
          <router-link to="/agency/orders" class="text-xs text-amber-600 font-bold hover:underline">عرض الكل</router-link>
        </div>

        <div v-if="orders.length === 0" class="text-center text-slate-400 text-sm py-8">
          لا توجد طلبات بعد. أنشئ أول حجز لك.
        </div>

        <div v-else class="overflow-x-auto rounded-2xl border border-slate-200">
          <table class="w-full text-right text-xs">
            <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
              <tr>
                <th class="p-3 font-semibold">رقم الطلب</th>
                <th class="p-3 font-semibold">التاريخ</th>
                <th class="p-3 font-semibold">الحالة</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr
                v-for="order in orders.slice(0, 5)"
                :key="order.id"
                class="hover:bg-slate-50 transition cursor-pointer"
                @click="$router.push(`/agency/orders/${order.id}`)"
              >
                <td class="p-3 font-mono font-bold text-slate-700">{{ order.order_number }}</td>
                <td class="p-3 text-slate-500">{{ formatDate(order.created_at) }}</td>
                <td class="p-3">
                  <span class="px-2.5 py-1 rounded-full text-[10px] font-bold" :class="statusClass(order.status?.name)">
                    {{ order.status?.label }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/lib/api'
import { RefreshCw, Wallet, ClipboardList, CreditCard, PlusCircle } from 'lucide-vue-next'

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

const statusClass = (status) => {
  const classes = {
    pending_pricing: 'bg-amber-100 text-amber-800',
    pricing_sent: 'bg-blue-100 text-blue-800',
    approved: 'bg-emerald-100 text-emerald-800',
    confirmed: 'bg-purple-100 text-purple-800',
  }
  return classes[status] || 'bg-slate-100 text-slate-600'
}
</script>

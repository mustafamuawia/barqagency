<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 flex items-center justify-between">
      <div>
        <h2 class="text-lg font-extrabold text-slate-900">المحفظة والرصيد</h2>
        <p class="text-xs text-slate-400 mt-0.5">عرض رصيدك وسجل المعاملات المالية</p>
      </div>
    </div>

    <div v-if="loading" class="bg-white rounded-3xl p-12 shadow-sm border border-slate-200 text-center text-slate-400 text-sm">
      جارٍ التحميل...
    </div>

    <template v-else-if="wallet">
      <!-- Balance Cards -->
      <div class="grid grid-cols-3 gap-4">
        <div class="bg-white rounded-3xl p-5 shadow-sm border border-slate-200">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 rounded-xl bg-emerald-100 flex items-center justify-center">
              <Wallet class="w-4 h-4 text-emerald-600" />
            </div>
            <p class="text-xs text-slate-500 font-medium">الرصيد الحالي</p>
          </div>
          <p class="text-2xl font-black text-emerald-600 font-mono">{{ formatCurrency(wallet.balance) }}</p>
          <p class="text-[10px] text-slate-400 mt-1">{{ wallet.status || 'نشطة' }}</p>
        </div>
        <div class="bg-white rounded-3xl p-5 shadow-sm border border-slate-200">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 rounded-xl bg-blue-100 flex items-center justify-center">
              <CreditCard class="w-4 h-4 text-blue-600" />
            </div>
            <p class="text-xs text-slate-500 font-medium">حد الائتمان</p>
          </div>
          <p class="text-2xl font-black text-blue-600 font-mono">{{ formatCurrency(wallet.credit_limit) }}</p>
        </div>
        <div class="bg-white rounded-3xl p-5 shadow-sm border border-slate-200">
          <div class="flex items-center gap-2 mb-3">
            <div class="w-8 h-8 rounded-xl bg-amber-100 flex items-center justify-center">
              <TrendingUp class="w-4 h-4 text-amber-600" />
            </div>
            <p class="text-xs text-slate-500 font-medium">الرصيد المتاح</p>
          </div>
          <p class="text-2xl font-black text-amber-600 font-mono">{{ formatCurrency(Number(wallet.balance) + Number(wallet.credit_limit)) }}</p>
        </div>
      </div>

      <!-- Transactions -->
      <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-4">
        <h3 class="font-bold text-sm text-slate-900">سجل المعاملات</h3>

        <div v-if="!wallet.transactions?.length" class="text-center text-slate-400 text-sm py-8">
          لا توجد معاملات بعد
        </div>

        <div v-else class="overflow-x-auto rounded-2xl border border-slate-200">
          <table class="w-full text-right text-xs">
            <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
              <tr>
                <th class="p-3 font-semibold">التاريخ</th>
                <th class="p-3 font-semibold">النوع</th>
                <th class="p-3 font-semibold">الوصف</th>
                <th class="p-3 font-semibold text-center">المبلغ</th>
                <th class="p-3 font-semibold text-center">الرصيد بعد</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="tx in wallet.transactions" :key="tx.id" class="hover:bg-slate-50 transition">
                <td class="p-3 text-slate-500">{{ formatDate(tx.created_at) }}</td>
                <td class="p-3 text-slate-600">{{ tx.transaction_type?.label }}</td>
                <td class="p-3 text-slate-600">{{ tx.description }}</td>
                <td class="p-3 text-center font-mono font-bold" :class="tx.amount > 0 ? 'text-emerald-600' : 'text-red-500'">
                  {{ tx.amount > 0 ? '+' : '' }}{{ formatCurrency(tx.amount) }}
                </td>
                <td class="p-3 text-center font-mono font-bold text-slate-700">{{ formatCurrency(tx.balance_after) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/lib/api'
import { Wallet, CreditCard, TrendingUp } from 'lucide-vue-next'

const wallet = ref(null)
const loading = ref(true)

onMounted(async () => {
  try {
    const response = await api.get('/wallets')
    wallet.value = response.data
  } catch (err) {
    console.error('Failed to load wallet', err)
  } finally {
    loading.value = false
  }
})

const formatCurrency = (value) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value || 0)
const formatDate = (date) => new Date(date).toLocaleDateString('ar-EG')
</script>

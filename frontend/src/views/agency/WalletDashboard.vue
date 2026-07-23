<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <PageHeader title="المحفظة والرصيد" subtitle="عرض رصيدك وسجل المعاملات المالية" />

    <LoadingState v-if="loading" />

    <template v-else-if="wallet">
      <!-- Balance Cards -->
      <div class="grid grid-cols-3 gap-4">
        <StatTile
          :icon="Wallet"
          icon-bg="bg-emerald-100"
          icon-color="text-emerald-600"
          label="الرصيد الحالي"
          :value="formatCurrency(wallet.balance)"
          value-color="text-emerald-600"
          :caption="wallet.status || 'نشطة'"
        />
        <StatTile
          :icon="CreditCard"
          icon-bg="bg-blue-100"
          icon-color="text-blue-600"
          label="حد الائتمان"
          :value="formatCurrency(wallet.credit_limit)"
          value-color="text-blue-600"
        />
        <StatTile
          :icon="TrendingUp"
          icon-bg="bg-amber-100"
          icon-color="text-amber-600"
          label="الرصيد المتاح"
          :value="formatCurrency(Number(wallet.balance) + Number(wallet.credit_limit))"
          value-color="text-amber-600"
        />
      </div>

      <!-- Transactions -->
      <Card class="space-y-4">
        <h3 class="font-bold text-sm text-slate-900">سجل المعاملات</h3>

        <div v-if="!wallet.transactions?.length" class="text-center text-slate-400 text-sm py-8">
          لا توجد معاملات بعد
        </div>

        <DataTable v-else :columns="['التاريخ', 'النوع', 'الوصف', 'المبلغ', 'الرصيد بعد']">
          <tr v-for="tx in wallet.transactions" :key="tx.id" class="hover:bg-slate-50 transition">
            <td class="p-3 text-slate-500">{{ formatDate(tx.created_at) }}</td>
            <td class="p-3 text-slate-600">{{ tx.transaction_type?.label }}</td>
            <td class="p-3 text-slate-600">{{ tx.description }}</td>
            <td class="p-3 text-center font-mono font-bold" :class="tx.amount > 0 ? 'text-emerald-600' : 'text-red-500'">
              {{ tx.amount > 0 ? '+' : '' }}{{ formatCurrency(tx.amount) }}
            </td>
            <td class="p-3 text-center font-mono font-bold text-slate-700">{{ formatCurrency(tx.balance_after) }}</td>
          </tr>
        </DataTable>
      </Card>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/lib/api'
import { Wallet, CreditCard, TrendingUp } from 'lucide-vue-next'
import PageHeader from '@/components/PageHeader.vue'
import LoadingState from '@/components/LoadingState.vue'
import Card from '@/components/Card.vue'
import StatTile from '@/components/StatTile.vue'
import DataTable from '@/components/DataTable.vue'

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

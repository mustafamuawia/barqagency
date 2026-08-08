<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <PageHeader title="المحفظة والرصيد" subtitle="عرض رصيدك وسجل المعاملات المالية" />

    <LoadingState v-if="loading" />

    <template v-else-if="wallet">
      <!-- Balance Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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

      <!-- Bank Details & Deposit Notice Form -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Bank Account Details -->
        <Card class="space-y-3">
          <h3 class="font-bold text-sm text-slate-900 border-b pb-2">معلومات التحويل البنكي</h3>
          <div class="space-y-2 text-xs">
            <div class="flex flex-wrap justify-between gap-x-2"><span class="text-slate-400 shrink-0">اسم البنك:</span> <span class="font-bold text-left">البنك الأهلي التجاري (SNB)</span></div>
            <div class="flex flex-wrap justify-between gap-x-2"><span class="text-slate-400 shrink-0">اسم الحساب:</span> <span class="font-bold text-left">شركة سكاي بلان المحدودة / برق LLC</span></div>
            <div class="flex flex-wrap justify-between gap-x-2"><span class="text-slate-400 shrink-0">رقم الحساب:</span> <span class="font-mono font-bold break-all text-left">966-2244-6688-22</span></div>
            <div class="flex flex-wrap justify-between gap-x-2"><span class="text-slate-400 shrink-0">رقم IBAN:</span> <span class="font-mono font-bold text-[11px] break-all text-left">SA9340000009662244668822</span></div>
          </div>
        </Card>

        <!-- Deposit Notice Form -->
        <Card class="space-y-3">
          <h3 class="font-bold text-sm text-slate-900 border-b pb-2">إرسال إشعار إيداع رصيد</h3>
          
          <div v-if="depositMessage" class="p-2 rounded-xl text-xs font-semibold bg-emerald-100 text-emerald-800">
            {{ depositMessage }}
          </div>
          <div v-if="depositError" class="p-2 rounded-xl text-xs font-semibold bg-rose-100 text-rose-800">
            {{ depositError }}
          </div>

          <form @submit.prevent="submitDeposit" class="space-y-3">
            <div class="grid grid-cols-2 gap-2">
              <div class="space-y-1">
                <label class="block text-[10px] font-bold text-slate-500">المبلغ ($ USD)</label>
                <input v-model="depositForm.amount" type="number" step="0.01" min="1" class="w-full bg-slate-50 border rounded-xl px-3 py-1.5 text-xs font-bold font-mono" required>
              </div>
              <div class="space-y-1">
                <label class="block text-[10px] font-bold text-slate-500">رقم التحويل / الإسناد</label>
                <input v-model="depositForm.reference_number" type="text" placeholder="مثال: TXN987251" class="w-full bg-slate-50 border rounded-xl px-3 py-1.5 text-xs" required>
              </div>
            </div>

            <div class="space-y-1">
              <label class="block text-[10px] font-bold text-slate-500">إيصال التحويل (اختياري)</label>
              <input type="file" @change="handleFileChange" accept="image/*,application/pdf" class="w-full text-xs">
            </div>

            <button type="submit" :disabled="submitting" class="w-full bg-amber-400 hover:bg-amber-500 text-slate-950 font-bold py-2 rounded-xl text-xs transition shadow-sm disabled:opacity-50">
              {{ submitting ? 'جاري الإرسال...' : 'تأكيد الإرسال للإدارة' }}
            </button>
          </form>
        </Card>
      </div>

      <!-- My Deposit Submissions History -->
      <Card class="space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-bold text-sm text-slate-900">سجل التحويلات البنكية المُرسَلة</h3>
          <span class="text-[10px] text-slate-400">طلباتك المرسلة للإدارة وحالتها</span>
        </div>

        <div v-if="!depositHistory.length" class="text-center text-slate-400 text-sm py-6">
          لم تُرسل أي إشعارات إيداع بعد
        </div>

        <div v-else class="overflow-x-auto rounded-xl border border-slate-200">
          <table class="w-full text-right text-xs">
            <thead class="bg-slate-50 text-slate-500 border-b">
              <tr>
                <th class="p-3 font-semibold">التاريخ</th>
                <th class="p-3 font-semibold">رقم التحويل</th>
                <th class="p-3 font-semibold">المبلغ المطلوب</th>
                <th class="p-3 font-semibold text-center">الحالة</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium">
              <tr v-for="dep in depositHistory" :key="dep.id" class="hover:bg-slate-50 transition">
                <td class="p-3 text-slate-500 font-mono">{{ formatDate(dep.created_at) }}</td>
                <td class="p-3 font-mono font-bold text-slate-700">{{ dep.metadata?.reference_number || '—' }}</td>
                <td class="p-3 font-mono font-black text-emerald-600">${{ parseFloat(dep.amount).toFixed(2) }}</td>
                <td class="p-3 text-center">
                  <span v-if="dep.metadata?.status === 'pending'"
                    class="bg-amber-100 text-amber-800 font-bold px-2.5 py-1 rounded-full text-[10px]">
                    قيد المراجعة
                  </span>
                  <span v-else-if="dep.metadata?.status === 'approved'"
                    class="bg-emerald-100 text-emerald-800 font-bold px-2.5 py-1 rounded-full text-[10px] inline-flex items-center gap-1">
                    <CheckCircle class="w-3 h-3" /> مقبول وتم الشحن
                  </span>
                  <span v-else-if="dep.metadata?.status === 'rejected'"
                    class="bg-rose-100 text-rose-800 font-bold px-2.5 py-1 rounded-full text-[10px] inline-flex items-center gap-1">
                    <XCircle class="w-3 h-3" /> مرفوض
                  </span>
                  <span v-else class="text-slate-400">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <Pagination :meta="depositHistoryMeta" @change="page => loadDepositHistory(page)" />
      </Card>

      <!-- Full Transactions -->
      <Card class="space-y-4">
        <h3 class="font-bold text-sm text-slate-900">سجل المعاملات والإيداعات</h3>

        <div v-if="!transactions.length" class="text-center text-slate-400 text-sm py-8">
          لا توجد معاملات بعد
        </div>

        <DataTable v-else :columns="['التاريخ', 'النوع', 'الوصف', 'المبلغ', 'الرصيد بعد']">
          <tr v-for="tx in transactions" :key="tx.id" class="hover:bg-slate-50 transition">
            <td class="p-3 text-slate-500">{{ formatDate(tx.created_at) }}</td>
            <td class="p-3 text-slate-600">{{ tx.transaction_type?.label || 'إيداع' }}</td>
            <td class="p-3 text-slate-600">{{ tx.description }}</td>
            <td class="p-3 text-center font-mono font-bold" :class="tx.amount > 0 ? 'text-emerald-600' : 'text-red-500'">
              {{ tx.amount > 0 ? '+' : '' }}{{ formatCurrency(tx.amount) }}
            </td>
            <td class="p-3 text-center font-mono font-bold text-slate-700">{{ formatCurrency(tx.balance_after) }}</td>
          </tr>
        </DataTable>

        <Pagination :meta="transactionsMeta" @change="page => loadTransactions(page)" />
      </Card>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '@/lib/api'
import { Wallet, CreditCard, TrendingUp, CheckCircle, XCircle } from 'lucide-vue-next'
import PageHeader from '@/components/PageHeader.vue'
import LoadingState from '@/components/LoadingState.vue'
import Card from '@/components/Card.vue'
import StatTile from '@/components/StatTile.vue'
import DataTable from '@/components/DataTable.vue'
import Pagination from '@/components/Pagination.vue'

const wallet = ref(null)
const loading = ref(true)
const submitting = ref(false)
const depositMessage = ref('')
const depositError = ref('')
const receiptFile = ref(null)
const depositHistory = ref([])
const depositHistoryMeta = ref(null)
const transactions = ref([])
const transactionsMeta = ref(null)

const depositForm = reactive({
  amount: 250,
  reference_number: '',
})

const fetchWallet = async () => {
  try {
    const response = await api.get('/wallets')
    wallet.value = response.data
  } catch (err) {
    console.error('Failed to load wallet', err)
  } finally {
    loading.value = false
  }
}

const loadDepositHistory = async (page = 1) => {
  try {
    const response = await api.get('/wallets/my-deposits', { params: { page } })
    depositHistory.value = response.data.data
    depositHistoryMeta.value = response.data
  } catch (err) {
    console.error('Failed to load deposit history', err)
  }
}

const loadTransactions = async (page = 1) => {
  try {
    const response = await api.get('/wallets/transactions', { params: { page } })
    transactions.value = response.data.data
    transactionsMeta.value = response.data
  } catch (err) {
    console.error('Failed to load transactions', err)
  }
}

onMounted(async () => {
  await Promise.all([fetchWallet(), loadDepositHistory(), loadTransactions()])
})

const handleFileChange = (e) => {
  if (e.target.files && e.target.files[0]) {
    receiptFile.value = e.target.files[0]
  }
}

const submitDeposit = async () => {
  submitting.value = true
  depositMessage.value = ''
  depositError.value = ''

  const formData = new FormData()
  formData.append('amount', depositForm.amount)
  formData.append('reference_number', depositForm.reference_number)
  if (receiptFile.value) {
    formData.append('receipt', receiptFile.value)
  }

  try {
    await api.post('/wallets/deposit', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    depositMessage.value = 'تم إرسال إشعار الإيداع بنجاح. سيتم شحن المحفظة بعد مراجعة الإدارة.'
    depositForm.reference_number = ''
    receiptFile.value = null
    await Promise.all([fetchWallet(), loadDepositHistory(), loadTransactions()])
  } catch (err) {
    depositError.value = err.response?.data?.message || 'حدث خطأ أثناء إرسال الإشعار.'
  } finally {
    submitting.value = false
  }
}

const formatCurrency = (value) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value || 0)
const formatDate = (date) => new Date(date).toLocaleDateString('ar-EG')
</script>

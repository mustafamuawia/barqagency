<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <PageHeader title="إيداعات المحافظ وشحن الرصيد" subtitle="تحديد الوكالة وإضافة رصيد بعد التحقق من الإيصال" />

    <div class="grid md:grid-cols-2 gap-6">
      <!-- Form Card -->
      <Card class="space-y-5">
        <h3 class="font-bold text-sm text-slate-900">بيانات الشحن</h3>

        <!-- Agency Select -->
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1.5">اختر الوكالة</label>
          <select
            v-model="selectedAgencyId"
            @change="loadWallet"
            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400"
          >
            <option value="">-- اختر الوكالة --</option>
            <option v-for="agency in agencies" :key="agency.id" :value="agency.id">{{ agency.name }}</option>
          </select>
        </div>

        <template v-if="wallet">
          <!-- Amount -->
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">مبلغ الشحن (USD)</label>
            <input
              v-model.number="topUpAmount"
              type="number"
              step="0.01"
              min="0"
              placeholder="0.00"
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-amber-400"
            />
          </div>

          <!-- Description -->
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">ملاحظات</label>
            <textarea
              v-model="topUpDescription"
              rows="3"
              placeholder="تحويل بنكي تم التحقق منه..."
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400 resize-none"
            ></textarea>
          </div>

          <!-- Message -->
          <div v-if="message" class="p-3 rounded-xl text-xs font-semibold" :class="messageClass">
            {{ message }}
          </div>

          <!-- Submit -->
          <Button @click="submitTopUp" variant="amber" size="md" block :disabled="!topUpAmount || topUpLoading">
            <Wallet class="w-4 h-4" />
            {{ topUpLoading ? 'جارٍ المعالجة...' : 'تأكيد الشحن' }}
          </Button>
        </template>
      </Card>

      <!-- Wallet Info Card -->
      <Card v-if="wallet" class="space-y-4">
        <h3 class="font-bold text-sm text-slate-900">معلومات المحفظة</h3>

        <div class="bg-[#0f172a] rounded-2xl p-5 space-y-4">
          <div>
            <p class="text-[10px] text-slate-400">الرصيد الحالي</p>
            <p class="text-3xl font-black text-emerald-400 font-mono mt-1">{{ formatCurrency(wallet.balance) }}</p>
          </div>
          <div class="grid grid-cols-2 gap-3 pt-3 border-t border-slate-800">
            <div>
              <p class="text-[10px] text-slate-400">حد الائتمان</p>
              <p class="text-sm font-bold text-slate-200 font-mono mt-0.5">{{ formatCurrency(wallet.credit_limit) }}</p>
            </div>
            <div>
              <p class="text-[10px] text-slate-400">الحالة</p>
              <p class="text-sm font-bold text-amber-400 mt-0.5">{{ wallet.status || 'نشطة' }}</p>
            </div>
          </div>
        </div>
      </Card>

      <Card v-else-if="!selectedAgencyId" class="flex items-center justify-center text-slate-400 text-sm">
        اختر وكالة لعرض معلومات المحفظة
      </Card>
    </div>

    <!-- Pending Agency Deposit Requests Table -->
    <Card class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="font-bold text-sm text-slate-900">طلبات وإشعارات الإيداع المعلقة (طلبات الوكلاء)</h3>
        <span class="bg-amber-100 text-amber-800 text-xs font-bold px-2.5 py-0.5 rounded-full">
          {{ pendingDepositsMeta?.total ?? pendingDeposits.length }} طلب معلق
        </span>
      </div>

      <div v-if="!pendingDeposits.length" class="text-center text-slate-400 text-sm py-6">
        لا توجد إشعارات إيداع معلقة بانتظار المراجعة
      </div>

      <div v-else class="overflow-x-auto rounded-xl border border-slate-200">
        <table class="w-full text-right text-xs">
          <thead class="bg-slate-50 text-slate-600 border-b">
            <tr>
              <th class="p-3 font-bold">الوكالة</th>
              <th class="p-3 font-bold">التاريخ</th>
              <th class="p-3 font-bold">رقم الإسناد / التحويل</th>
              <th class="p-3 font-bold">المبلغ المطلوب</th>
              <th class="p-3 font-bold">الإيصال</th>
              <th class="p-3 font-bold text-center">الإجراءات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="dep in pendingDeposits" :key="dep.id" class="hover:bg-slate-50 transition">
              <td class="p-3 font-bold text-slate-800">{{ dep.wallet?.agency?.name || 'وكالة غير معروفة' }}</td>
              <td class="p-3 text-slate-500 font-mono">{{ formatDate(dep.created_at) }}</td>
              <td class="p-3 font-mono font-bold text-slate-700">{{ dep.metadata?.reference_number || dep.id }}</td>
              <td class="p-3 font-mono font-black text-emerald-600">${{ parseFloat(dep.amount).toFixed(2) }}</td>
              <td class="p-3">
                <a v-if="dep.metadata?.receipt_path" :href="`/storage/${dep.metadata.receipt_path}`" target="_blank" class="text-amber-600 font-bold hover:underline inline-flex items-center gap-1">
                  <FileText class="w-3.5 h-3.5" />
                  عرض الإيصال
                </a>
                <span v-else class="text-slate-400">بدون مرفق</span>
              </td>
              <td class="p-3 flex items-center justify-center gap-2">
                <button @click="approveDeposit(dep.id)" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition">
                  موافقة وشحن
                </button>
                <button @click="rejectDeposit(dep.id)" class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition">
                  رفض
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <Pagination :meta="pendingDepositsMeta" @change="page => loadPendingDeposits(page)" />
    </Card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/lib/api'
import { Wallet, FileText } from 'lucide-vue-next'
import PageHeader from '@/components/PageHeader.vue'
import Card from '@/components/Card.vue'
import Button from '@/components/Button.vue'
import Pagination from '@/components/Pagination.vue'

const agencies = ref([])
const wallet = ref(null)
const selectedAgencyId = ref('')
const topUpAmount = ref('')
const topUpDescription = ref('')
const topUpLoading = ref(false)
const message = ref('')
const messageClass = ref('')
const pendingDeposits = ref([])
const pendingDepositsMeta = ref(null)

const loadPendingDeposits = async (page = 1) => {
  try {
    const res = await api.get('/wallets/pending-deposits', { params: { page } })
    pendingDeposits.value = res.data.data
    pendingDepositsMeta.value = res.data
  } catch (err) {
    console.error('Failed to load pending deposits', err)
  }
}

onMounted(async () => {
  try {
    const response = await api.get('/agencies')
    agencies.value = response.data
    await loadPendingDeposits()
  } catch (err) {
    message.value = 'فشل تحميل البيانات'
    messageClass.value = 'bg-red-100 text-red-700'
  }
})

const loadWallet = async () => {
  message.value = ''
  if (!selectedAgencyId.value) { wallet.value = null; return }
  try {
    const response = await api.get(`/wallets/agency/${selectedAgencyId.value}`)
    wallet.value = response.data
  } catch (err) {
    message.value = 'فشل تحميل المحفظة'
    messageClass.value = 'bg-red-100 text-red-700'
  }
}

const submitTopUp = async () => {
  if (!topUpAmount.value || !wallet.value) return
  topUpLoading.value = true
  message.value = ''
  try {
    const response = await api.post(`/wallets/${wallet.value.id}/topup`, {
      amount: topUpAmount.value,
      description: topUpDescription.value,
    })
    wallet.value = response.data.wallet
    topUpAmount.value = ''
    topUpDescription.value = ''
    message.value = 'تم شحن المحفظة بنجاح!'
    messageClass.value = 'bg-emerald-100 text-emerald-700'
  } catch (err) {
    message.value = err.response?.data?.message || 'فشل عملية الشحن'
    messageClass.value = 'bg-red-100 text-red-700'
  } finally {
    topUpLoading.value = false
  }
}

const approveDeposit = async (transactionId) => {
  try {
    await api.put(`/wallets/deposits/${transactionId}/approve`)
    message.value = 'تمت الموافقة على طلب الإيداع وشحن محفظة الوكيل بنجاح!'
    messageClass.value = 'bg-emerald-100 text-emerald-700'
    await loadPendingDeposits()
    if (selectedAgencyId.value) loadWallet()
  } catch (err) {
    message.value = err.response?.data?.message || 'فشل قبول الإيداع'
    messageClass.value = 'bg-red-100 text-red-700'
  }
}

const rejectDeposit = async (transactionId) => {
  const reason = prompt('سبب الرفض (اختياري):')
  try {
    await api.put(`/wallets/deposits/${transactionId}/reject`, { reason })
    message.value = 'تم رفض طلب الإيداع.'
    messageClass.value = 'bg-amber-100 text-amber-800'
    await loadPendingDeposits()
  } catch (err) {
    message.value = err.response?.data?.message || 'فشل رفض الإيداع'
    messageClass.value = 'bg-red-100 text-red-700'
  }
}

const formatCurrency = (value) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value || 0)
const formatDate = (date) => new Date(date).toLocaleString('ar-SA')
</script>

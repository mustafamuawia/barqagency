<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <PageHeader title="مراجعة طلب الاسترداد" subtitle="تحديد مبلغ الجزاء واعتماد أو رفض الطلب">
      <template #actions>
        <router-link to="/admin/refunds" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-4 py-2.5 rounded-xl flex items-center gap-2 transition">
          <ArrowRight class="w-4 h-4" />
          العودة للطلبات
        </router-link>
      </template>
    </PageHeader>

    <LoadingState v-if="loading" />

    <div v-else-if="refund" class="grid md:grid-cols-2 gap-6">
      <!-- Refund + Order Info -->
      <Card class="space-y-4">
        <h3 class="font-bold text-sm text-slate-900">تفاصيل الطلب</h3>

        <div class="bg-[#0f172a] rounded-2xl p-5 space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-[10px] text-slate-400">رقم الاسترداد</span>
            <span class="font-mono font-bold text-amber-400 text-sm">{{ refund.refund_number }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[10px] text-slate-400">رقم الطلب</span>
            <span class="font-mono font-bold text-slate-200 text-sm">{{ refund.order?.order_number }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[10px] text-slate-400">الوكالة</span>
            <span class="text-sm font-bold text-white">{{ refund.agency?.name }}</span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-[10px] text-slate-400">المبلغ الأصلي</span>
            <span class="text-sm font-bold text-emerald-400 font-mono">{{ formatCurrency(refund.original_amount) }}</span>
          </div>
        </div>

        <div v-if="refund.order?.segments?.length" class="space-y-2">
          <p class="text-xs font-semibold text-slate-500">خط السير</p>
          <div v-for="seg in refund.order.segments" :key="seg.id" class="bg-slate-50 rounded-xl p-3 text-xs text-slate-600 border border-slate-200">
            <span class="font-bold text-slate-800">{{ seg.departure_airport?.code }}</span>
            <span class="mx-2 text-slate-400">←</span>
            <span class="font-bold text-slate-800">{{ seg.arrival_airport?.code }}</span>
            <span class="text-slate-400 mx-2">•</span>
            {{ seg.airline?.name }}
          </div>
        </div>

        <div class="space-y-2">
          <p class="text-xs font-semibold text-slate-500">سبب الاسترداد</p>
          <p class="bg-slate-50 rounded-xl p-3 text-xs text-slate-700 border border-slate-200">{{ refund.reason }}</p>
        </div>
      </Card>

      <!-- Decision Form -->
      <Card class="space-y-5">
        <h3 class="font-bold text-sm text-slate-900">اتخاذ القرار</h3>

        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1.5">مبلغ الجزاء / رسوم الإلغاء (USD)</label>
          <input v-model.number="penaltyAmount" type="number" step="0.01" min="0" :max="refund.original_amount"
            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-amber-400" />
        </div>

        <div class="bg-[#0f172a] rounded-2xl p-4 flex items-center justify-between">
          <span class="text-xs text-slate-400">صافي المبلغ المسترد</span>
          <span class="text-2xl font-black text-emerald-400 font-mono">{{ formatCurrency(netRefund) }}</span>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1.5">ملاحظات الإدارة</label>
          <textarea v-model="adminRemarks" rows="3" placeholder="سبب القرار..."
            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400 resize-none"></textarea>
        </div>

        <div v-if="error" class="bg-red-100 text-red-700 text-xs font-semibold p-3 rounded-xl">{{ error }}</div>
        <div v-if="message" class="bg-emerald-100 text-emerald-700 text-xs font-semibold p-3 rounded-xl">{{ message }}</div>

        <div class="grid grid-cols-2 gap-3" v-if="refund.status?.name === 'pending'">
          <Button @click="approve" variant="amber" size="md" :disabled="submitting">
            <CheckCircle class="w-4 h-4" />
            {{ submitting ? 'جارٍ المعالجة...' : 'اعتماد الاسترداد' }}
          </Button>
          <Button @click="reject" variant="danger-outline" size="md" :disabled="submitting">
            <XCircle class="w-4 h-4" />
            رفض الطلب
          </Button>
        </div>
        <Button
          v-else-if="refund.status?.name === 'credited'"
          @click="downloadCreditNote"
          variant="dark"
          size="md"
          block
          :disabled="downloadingNote"
        >
          <FileText class="w-4 h-4 text-amber-400" />
          {{ downloadingNote ? 'جارٍ التحميل...' : 'تحميل سند الاسترداد' }}
        </Button>
        <div v-else class="text-center text-xs text-slate-400 py-2">تمت معالجة هذا الطلب مسبقاً</div>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/lib/api'
import { downloadFile } from '@/lib/download'
import { ArrowRight, CheckCircle, XCircle, FileText } from 'lucide-vue-next'
import PageHeader from '@/components/PageHeader.vue'
import Card from '@/components/Card.vue'
import LoadingState from '@/components/LoadingState.vue'
import Button from '@/components/Button.vue'

const props = defineProps({ id: [String, Number] })
const router = useRouter()

const refund = ref(null)
const loading = ref(true)
const submitting = ref(false)
const downloadingNote = ref(false)
const error = ref('')
const message = ref('')

const penaltyAmount = ref(0)
const adminRemarks = ref('')

const netRefund = computed(() => Math.max(0, (refund.value?.original_amount || 0) - (Number(penaltyAmount.value) || 0)))

const loadRefund = async () => {
  try {
    const response = await api.get(`/refunds/${props.id}`)
    refund.value = response.data
  } catch (err) {
    error.value = 'فشل تحميل طلب الاسترداد'
  } finally {
    loading.value = false
  }
}

onMounted(loadRefund)

const approve = async () => {
  submitting.value = true
  error.value = ''
  message.value = ''
  try {
    await api.put(`/refunds/${props.id}/approve`, {
      penalty_amount: penaltyAmount.value || 0,
      admin_remarks: adminRemarks.value || null,
    })
    message.value = 'تمت الموافقة على الاسترداد بنجاح'
    setTimeout(() => router.push('/admin/refunds'), 1000)
  } catch (err) {
    error.value = err.response?.data?.message || 'فشل اعتماد الاسترداد'
  } finally {
    submitting.value = false
  }
}

const reject = async () => {
  if (!adminRemarks.value) {
    error.value = 'يرجى إدخال سبب الرفض في حقل الملاحظات'
    return
  }
  submitting.value = true
  error.value = ''
  message.value = ''
  try {
    await api.put(`/refunds/${props.id}/reject`, { admin_remarks: adminRemarks.value })
    message.value = 'تم رفض طلب الاسترداد'
    setTimeout(() => router.push('/admin/refunds'), 1000)
  } catch (err) {
    error.value = err.response?.data?.message || 'فشل رفض الطلب'
  } finally {
    submitting.value = false
  }
}

const downloadCreditNote = async () => {
  downloadingNote.value = true
  try {
    const response = await api.get(`/refunds/${props.id}/credit-note`, { responseType: 'blob' })
    downloadFile(response.data, `credit-note-${refund.value.refund_number}.pdf`)
  } catch (err) {
    error.value = 'فشل تحميل سند الاسترداد'
  } finally {
    downloadingNote.value = false
  }
}

const formatCurrency = (value) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value || 0)
</script>

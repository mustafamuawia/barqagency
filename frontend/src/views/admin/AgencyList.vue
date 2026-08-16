<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <PageHeader title="إدارة وكلاء السفر (B2B)" subtitle="عرض جميع الوكالات المسجلة، مراجعة طلبات التسجيل الجديدة وأرصدة المحافظ" />

    <!-- Status Filter Tabs -->
    <div class="flex flex-wrap gap-2">
      <button
        v-for="tab in tabs"
        :key="tab.value"
        @click="setFilter(tab.value)"
        class="px-4 py-2 rounded-xl text-xs font-bold transition"
        :class="filter === tab.value ? 'bg-[#0f172a] text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50'"
      >
        {{ tab.label }}
      </button>
    </div>

    <!-- Loading -->
    <LoadingState v-if="loading" />

    <EmptyState v-else-if="agencies.length === 0">لا توجد وكالات في هذا التصنيف</EmptyState>

    <!-- Agencies Table -->
    <Card v-else class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="font-bold text-sm text-slate-900">قائمة الوكالات</h3>
        <Badge color="blue">{{ agencies.length }} وكالة</Badge>
      </div>

      <DataTable :columns="['اسم الوكالة', 'البريد الإلكتروني', 'تحقق البريد', 'الرصيد الحالي', 'حد الائتمان', 'الحالة', 'الإجراء']">
        <tr v-for="agency in agencies" :key="agency.id" class="hover:bg-slate-50 transition">
          <td class="p-3 font-bold text-slate-800">{{ agency.name }}</td>
          <td class="p-3 font-mono text-slate-500">{{ agency.email }}</td>
          <td class="p-3">
            <Badge :color="ownerEmailVerified(agency) ? 'emerald' : 'slate'">
              {{ ownerEmailVerified(agency) ? 'مُفعّل' : 'غير مُفعّل' }}
            </Badge>
          </td>
          <td class="p-3 font-mono font-bold text-emerald-600">{{ formatPoints(agency.wallet?.balance) }}</td>
          <td class="p-3 font-mono text-slate-600">
            <div v-if="editingId === agency.id" class="flex items-center gap-1.5">
              <input
                v-model.number="editValue"
                type="number" step="0.01" min="0"
                class="w-24 border border-slate-200 rounded-lg px-2 py-1 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-amber-400"
              />
              <button @click="saveCreditLimit(agency)" :disabled="savingId === agency.id" class="text-emerald-600 hover:text-emerald-700 disabled:opacity-50">
                <Check class="w-4 h-4" />
              </button>
              <button @click="cancelEdit" class="text-slate-400 hover:text-slate-600">
                <X class="w-4 h-4" />
              </button>
            </div>
            <span v-else>{{ formatPoints(agency.wallet?.credit_limit) }}</span>
          </td>
          <td class="p-3">
            <Badge :color="statusColor(agency.status)">{{ statusLabel(agency.status) }}</Badge>
          </td>
          <td class="p-3 text-center">
            <div v-if="agency.status === 'pending'" class="flex items-center justify-center gap-1.5">
              <button
                @click="approve(agency)"
                :disabled="processingId === agency.id"
                class="bg-emerald-500 hover:bg-emerald-600 disabled:opacity-50 text-white font-bold px-3 py-1.5 rounded-lg text-[11px] inline-flex items-center gap-1 transition"
              >
                <CheckCircle class="w-3.5 h-3.5" />
                موافقة
              </button>
              <button
                @click="openRejectModal(agency)"
                :disabled="processingId === agency.id"
                class="bg-red-500/10 hover:bg-red-500/20 disabled:opacity-50 text-red-600 border border-red-300 font-bold px-3 py-1.5 rounded-lg text-[11px] inline-flex items-center gap-1 transition"
              >
                <XCircle class="w-3.5 h-3.5" />
                رفض
              </button>
            </div>
            <button
              v-else-if="editingId !== agency.id"
              @click="startEdit(agency)"
              class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-3 py-1.5 rounded-lg text-[11px] inline-flex items-center gap-1 transition"
            >
              <Pencil class="w-3 h-3" />
              تعديل حد الائتمان
            </button>
          </td>
        </tr>
      </DataTable>

      <div v-if="message" class="p-3 rounded-xl text-xs font-semibold" :class="messageClass">{{ message }}</div>
    </Card>

    <!-- Reject Modal -->
    <div v-if="rejectTarget" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 flex items-center justify-center p-4" @click.self="closeRejectModal">
      <div class="bg-white rounded-3xl p-6 max-w-md w-full space-y-4 text-right">
        <h3 class="font-bold text-sm text-slate-900">رفض طلب الوكالة: {{ rejectTarget.name }}</h3>
        <div class="space-y-1">
          <label class="block text-xs font-semibold text-slate-600">سبب الرفض (اختياري)</label>
          <textarea
            v-model="rejectReason"
            rows="3"
            placeholder="اكتب سبب الرفض ليتم إرساله للوكالة عبر البريد..."
            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-red-300 resize-none"
          ></textarea>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <Button variant="danger-outline" size="md" :disabled="processingId === rejectTarget.id" @click="confirmReject">
            {{ processingId === rejectTarget.id ? 'جارٍ الرفض...' : 'تأكيد الرفض' }}
          </Button>
          <Button variant="secondary" size="md" @click="closeRejectModal">إلغاء</Button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/lib/api'
import { Pencil, Check, X, CheckCircle, XCircle } from 'lucide-vue-next'
import PageHeader from '@/components/PageHeader.vue'
import Card from '@/components/Card.vue'
import Badge from '@/components/Badge.vue'
import LoadingState from '@/components/LoadingState.vue'
import EmptyState from '@/components/EmptyState.vue'
import DataTable from '@/components/DataTable.vue'
import Button from '@/components/Button.vue'
import { formatPoints } from '@/utils/points'

const tabs = [
  { value: null, label: 'الكل' },
  { value: 'pending', label: 'بانتظار الموافقة' },
  { value: 'active', label: 'نشطة' },
  { value: 'rejected', label: 'مرفوضة' },
]

const agencies = ref([])
const loading = ref(true)
const filter = ref('pending')

const editingId = ref(null)
const editValue = ref(0)
const savingId = ref(null)
const processingId = ref(null)
const message = ref('')
const messageClass = ref('')

const rejectTarget = ref(null)
const rejectReason = ref('')

const loadAgencies = async () => {
  loading.value = true
  try {
    const response = await api.get('/agencies', { params: filter.value ? { status: filter.value } : {} })
    agencies.value = response.data
  } catch (err) {
    console.error('Failed to load agencies', err)
  } finally {
    loading.value = false
  }
}

onMounted(loadAgencies)

const setFilter = (value) => {
  filter.value = value
  loadAgencies()
}

const ownerEmailVerified = (agency) => {
  const owner = agency.users?.find((u) => u.role === 'owner')
  return !!owner?.user?.email_verified_at
}

const startEdit = (agency) => {
  editingId.value = agency.id
  editValue.value = Number(agency.wallet?.credit_limit) || 0
  message.value = ''
}

const cancelEdit = () => {
  editingId.value = null
}

const saveCreditLimit = async (agency) => {
  savingId.value = agency.id
  message.value = ''
  try {
    const response = await api.patch(`/agencies/${agency.id}`, { credit_limit: editValue.value })
    agency.wallet = response.data.agency.wallet
    editingId.value = null
    message.value = 'تم تحديث حد الائتمان بنجاح'
    messageClass.value = 'bg-emerald-100 text-emerald-700'
  } catch (err) {
    message.value = err.response?.data?.message || 'فشل تحديث حد الائتمان'
    messageClass.value = 'bg-red-100 text-red-700'
  } finally {
    savingId.value = null
  }
}

const approve = async (agency) => {
  processingId.value = agency.id
  message.value = ''
  try {
    await api.put(`/agencies/${agency.id}/approve`)
    message.value = `تمت الموافقة على وكالة ${agency.name}`
    messageClass.value = 'bg-emerald-100 text-emerald-700'
    loadAgencies()
  } catch (err) {
    message.value = err.response?.data?.message || 'فشل اعتماد الوكالة'
    messageClass.value = 'bg-red-100 text-red-700'
  } finally {
    processingId.value = null
  }
}

const openRejectModal = (agency) => {
  rejectTarget.value = agency
  rejectReason.value = ''
}

const closeRejectModal = () => {
  rejectTarget.value = null
}

const confirmReject = async () => {
  const agency = rejectTarget.value
  processingId.value = agency.id
  message.value = ''
  try {
    await api.put(`/agencies/${agency.id}/reject`, { reason: rejectReason.value || null })
    message.value = `تم رفض وكالة ${agency.name}`
    messageClass.value = 'bg-red-100 text-red-700'
    closeRejectModal()
    loadAgencies()
  } catch (err) {
    message.value = err.response?.data?.message || 'فشل رفض الوكالة'
    messageClass.value = 'bg-red-100 text-red-700'
  } finally {
    processingId.value = null
  }
}

const statusColor = (status) => {
  const colors = { active: 'emerald', pending: 'amber', rejected: 'red', suspended: 'red', inactive: 'slate' }
  return colors[status] || 'slate'
}

const statusLabel = (status) => {
  const labels = { active: 'نشطة', pending: 'بانتظار الموافقة', rejected: 'مرفوضة', suspended: 'موقوفة', inactive: 'غير نشطة' }
  return labels[status] || status
}

</script>

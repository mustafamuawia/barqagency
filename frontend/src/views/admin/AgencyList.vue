<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <PageHeader title="إدارة وكلاء السفر (B2B)" subtitle="عرض جميع الوكالات المسجلة وأرصدة محافظها" />

    <!-- Loading -->
    <LoadingState v-if="loading" />

    <!-- Agencies Table -->
    <Card v-else class="space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="font-bold text-sm text-slate-900">قائمة الوكالات المسجلة</h3>
        <Badge color="blue">{{ agencies.length }} وكالة</Badge>
      </div>

      <DataTable :columns="['اسم الوكالة', 'البريد الإلكتروني', 'الرصيد الحالي', 'حد الائتمان', 'الحالة', 'الإجراء']">
        <tr v-for="agency in agencies" :key="agency.id" class="hover:bg-slate-50 transition">
          <td class="p-3 font-bold text-slate-800">{{ agency.name }}</td>
          <td class="p-3 font-mono text-slate-500">{{ agency.email }}</td>
          <td class="p-3 font-mono font-bold text-emerald-600">{{ formatCurrency(agency.wallet?.balance) }}</td>
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
            <span v-else>{{ formatCurrency(agency.wallet?.credit_limit) }}</span>
          </td>
          <td class="p-3">
            <Badge :color="statusColor(agency.status)">
              {{ agency.status === 'active' ? 'نشطة' : agency.status === 'suspended' ? 'موقوفة' : agency.status }}
            </Badge>
          </td>
          <td class="p-3 text-center">
            <button
              v-if="editingId !== agency.id"
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
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/lib/api'
import { Pencil, Check, X } from 'lucide-vue-next'
import PageHeader from '@/components/PageHeader.vue'
import Card from '@/components/Card.vue'
import Badge from '@/components/Badge.vue'
import LoadingState from '@/components/LoadingState.vue'
import DataTable from '@/components/DataTable.vue'

const agencies = ref([])
const loading = ref(true)

const editingId = ref(null)
const editValue = ref(0)
const savingId = ref(null)
const message = ref('')
const messageClass = ref('')

const loadAgencies = async () => {
  try {
    const response = await api.get('/agencies')
    agencies.value = response.data
  } catch (err) {
    console.error('Failed to load agencies', err)
  } finally {
    loading.value = false
  }
}

onMounted(loadAgencies)

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

const statusColor = (status) => {
  if (status === 'active') return 'emerald'
  if (status === 'suspended') return 'red'
  return 'slate'
}

const formatCurrency = (value) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value || 0)
</script>

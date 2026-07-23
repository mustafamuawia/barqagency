<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 flex items-center justify-between">
      <div>
        <h2 class="text-lg font-extrabold text-slate-900">إدارة وكلاء السفر (B2B)</h2>
        <p class="text-xs text-slate-400 mt-0.5">عرض جميع الوكالات المسجلة وأرصدة محافظها</p>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="bg-white rounded-3xl p-12 shadow-sm border border-slate-200 text-center text-slate-400 text-sm">
      جارٍ التحميل...
    </div>

    <!-- Agencies Table -->
    <div v-else class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 space-y-4">
      <div class="flex items-center justify-between">
        <h3 class="font-bold text-sm text-slate-900">قائمة الوكالات المسجلة</h3>
        <span class="bg-blue-100 text-blue-800 text-[10px] font-bold px-2.5 py-1 rounded-full">
          {{ agencies.length }} وكالة
        </span>
      </div>

      <div class="overflow-x-auto rounded-2xl border border-slate-200">
        <table class="w-full text-right text-xs">
          <thead class="bg-slate-50 text-slate-500 border-b border-slate-200">
            <tr>
              <th class="p-3 font-semibold">اسم الوكالة</th>
              <th class="p-3 font-semibold">البريد الإلكتروني</th>
              <th class="p-3 font-semibold">الرصيد الحالي</th>
              <th class="p-3 font-semibold">حد الائتمان</th>
              <th class="p-3 font-semibold">الحالة</th>
              <th class="p-3 font-semibold text-center">الإجراء</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
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
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold" :class="statusClass(agency.status)">
                  {{ agency.status === 'active' ? 'نشطة' : agency.status === 'suspended' ? 'موقوفة' : agency.status }}
                </span>
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
          </tbody>
        </table>
      </div>

      <div v-if="message" class="p-3 rounded-xl text-xs font-semibold" :class="messageClass">{{ message }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/lib/api'
import { Pencil, Check, X } from 'lucide-vue-next'

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

const statusClass = (status) => {
  if (status === 'active') return 'bg-emerald-100 text-emerald-800'
  if (status === 'suspended') return 'bg-red-100 text-red-800'
  return 'bg-slate-100 text-slate-600'
}

const formatCurrency = (value) => new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value || 0)
</script>

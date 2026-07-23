<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <PageHeader title="سجل النشاطات والتدقيق" subtitle="سجل كامل للعمليات الحساسة ومحاولات تسجيل الدخول">
      <template #actions>
        <Button variant="secondary" size="sm" @click="tab === 'audit' ? loadAuditLogs() : loadLoginHistory()">
          <RefreshCw class="w-4 h-4" />
          تحديث
        </Button>
      </template>
    </PageHeader>

    <Card :padded="false" class="p-2 flex gap-2">
      <button
        @click="switchTab('audit')"
        class="flex-1 py-2.5 rounded-2xl text-xs font-bold transition"
        :class="tab === 'audit' ? 'bg-[#0f172a] text-white' : 'text-slate-500 hover:bg-slate-50'"
      >
        سجل العمليات
      </button>
      <button
        @click="switchTab('logins')"
        class="flex-1 py-2.5 rounded-2xl text-xs font-bold transition"
        :class="tab === 'logins' ? 'bg-[#0f172a] text-white' : 'text-slate-500 hover:bg-slate-50'"
      >
        سجل تسجيل الدخول
      </button>
    </Card>

    <LoadingState v-if="loading" />

    <template v-else-if="tab === 'audit'">
      <EmptyState v-if="auditLogs.length === 0">لا توجد أحداث مسجلة بعد</EmptyState>

      <Card v-else class="space-y-4">
        <DataTable :columns="['الحدث', 'المستخدم', 'العنصر', 'قبل', 'بعد', 'التاريخ']">
          <tr v-for="log in auditLogs" :key="log.id" class="hover:bg-slate-50 transition">
            <td class="p-3 font-mono font-bold text-slate-700">{{ log.event }}</td>
            <td class="p-3 text-slate-600">{{ log.user?.name || '—' }}</td>
            <td class="p-3 text-slate-500 font-mono text-[10px]">{{ shortModel(log.auditable_type) }}{{ log.auditable_id ? ` #${log.auditable_id}` : '' }}</td>
            <td class="p-3 text-slate-500 font-mono text-[10px] max-w-xs truncate" :title="JSON.stringify(log.old_values)">{{ preview(log.old_values) }}</td>
            <td class="p-3 text-slate-500 font-mono text-[10px] max-w-xs truncate" :title="JSON.stringify(log.new_values)">{{ preview(log.new_values) }}</td>
            <td class="p-3 text-slate-500">{{ formatDate(log.created_at) }}</td>
          </tr>
        </DataTable>

        <Pagination :meta="auditMeta" @change="page => loadAuditLogs(page)" />
      </Card>
    </template>

    <template v-else>
      <EmptyState v-if="loginHistory.length === 0">لا توجد محاولات تسجيل دخول مسجلة بعد</EmptyState>

      <Card v-else class="space-y-4">
        <DataTable :columns="['المستخدم', 'عنوان IP', 'المتصفح', 'الحالة', 'التاريخ']">
          <tr v-for="entry in loginHistory" :key="entry.id" class="hover:bg-slate-50 transition">
            <td class="p-3 font-bold text-slate-700">{{ entry.user?.name || '—' }}</td>
            <td class="p-3 text-slate-500 font-mono">{{ entry.ip_address || '—' }}</td>
            <td class="p-3 text-slate-500 text-[10px] max-w-xs truncate" :title="entry.user_agent">{{ entry.user_agent || '—' }}</td>
            <td class="p-3">
              <Badge :color="entry.success ? 'emerald' : 'red'">{{ entry.success ? 'نجاح' : 'فشل' }}</Badge>
            </td>
            <td class="p-3 text-slate-500">{{ formatDate(entry.created_at) }}</td>
          </tr>
        </DataTable>

        <Pagination :meta="loginMeta" @change="page => loadLoginHistory(page)" />
      </Card>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import api from '@/lib/api'
import { RefreshCw } from 'lucide-vue-next'
import PageHeader from '@/components/PageHeader.vue'
import Card from '@/components/Card.vue'
import Button from '@/components/Button.vue'
import Badge from '@/components/Badge.vue'
import DataTable from '@/components/DataTable.vue'
import LoadingState from '@/components/LoadingState.vue'
import EmptyState from '@/components/EmptyState.vue'
import Pagination from '@/components/Pagination.vue'

const tab = ref('audit')
const loading = ref(true)

const auditLogs = ref([])
const auditMeta = ref(null)
const loginHistory = ref([])
const loginMeta = ref(null)

const switchTab = (next) => {
  tab.value = next
  if (next === 'audit' && auditLogs.value.length === 0) loadAuditLogs()
  if (next === 'logins' && loginHistory.value.length === 0) loadLoginHistory()
}

const loadAuditLogs = async (page = 1) => {
  loading.value = true
  try {
    const response = await api.get('/admin/audit-logs', { params: { page } })
    auditLogs.value = response.data.data
    auditMeta.value = response.data
  } catch (err) {
    console.error('Failed to load audit logs', err)
  } finally {
    loading.value = false
  }
}

const loadLoginHistory = async (page = 1) => {
  loading.value = true
  try {
    const response = await api.get('/admin/login-history', { params: { page } })
    loginHistory.value = response.data.data
    loginMeta.value = response.data
  } catch (err) {
    console.error('Failed to load login history', err)
  } finally {
    loading.value = false
  }
}

onMounted(loadAuditLogs)

const shortModel = (type) => type ? type.replace('App\\Models\\', '') : '—'
const preview = (values) => {
  if (!values) return '—'
  const str = JSON.stringify(values)
  return str.length > 40 ? str.slice(0, 40) + '…' : str
}
const formatDate = (date) => new Date(date).toLocaleString('ar-EG')
</script>

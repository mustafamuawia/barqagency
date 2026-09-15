<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <PageHeader title="الحسابات البنكية" subtitle="إدارة حسابات التحويل البنكي الظاهرة للوكالات عند شحن المحفظة" />

    <LoadingState v-if="loading" />

    <Card v-else class="space-y-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <h3 class="font-bold text-sm text-slate-900">قائمة الحسابات</h3>
          <Badge color="blue">{{ accounts.length }} حساب</Badge>
        </div>
        <Button variant="amber" @click="openCreate">
          <Plus class="w-4 h-4" />
          حساب جديد
        </Button>
      </div>

      <DataTable :columns="['اسم البنك', 'اسم الحساب', 'رقم الحساب', 'IBAN', 'الحالة', 'الإجراءات']">
        <tr v-for="account in accounts" :key="account.id" class="hover:bg-slate-50 transition">
          <td class="p-3 font-bold text-slate-800">{{ account.bank_name }}</td>
          <td class="p-3 text-slate-600">{{ account.account_name }}</td>
          <td class="p-3 font-mono text-slate-600">{{ account.account_number }}</td>
          <td class="p-3 font-mono text-[11px] text-slate-500 break-all">{{ account.iban || '—' }}</td>
          <td class="p-3">
            <Badge :color="account.is_active ? 'emerald' : 'red'">
              {{ account.is_active ? 'ظاهر للوكالات' : 'مخفي' }}
            </Badge>
          </td>
          <td class="p-3">
            <div class="flex items-center justify-center gap-1.5">
              <button @click="openEdit(account)" class="action-btn text-slate-600" title="تعديل">
                <Pencil class="w-4 h-4" />
              </button>
              <button
                @click="toggleActive(account)"
                class="action-btn"
                :class="account.is_active ? 'text-amber-600' : 'text-emerald-600'"
                :title="account.is_active ? 'إخفاء' : 'إظهار'"
              >
                <component :is="account.is_active ? EyeOff : Eye" class="w-4 h-4" />
              </button>
              <button @click="deleteAccount(account)" class="action-btn text-red-600" title="حذف">
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
          </td>
        </tr>
      </DataTable>

      <EmptyState v-if="!accounts.length" message="لا توجد حسابات بنكية بعد" />

      <div v-if="message" class="p-3 rounded-xl text-xs font-semibold" :class="messageClass">{{ message }}</div>
    </Card>

    <!-- Create / Edit Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
      @click.self="closeModal"
    >
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-6 space-y-5" dir="rtl">
        <div class="flex items-center justify-between">
          <h3 class="font-black text-lg text-slate-900">{{ editing ? 'تعديل الحساب البنكي' : 'إضافة حساب بنكي جديد' }}</h3>
          <button @click="closeModal" class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitForm" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">اسم البنك</label>
            <input v-model="form.bank_name" type="text" required class="form-input" placeholder="مثال: البنك الأهلي التجاري (SNB)" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">اسم الحساب</label>
            <input v-model="form.account_name" type="text" required class="form-input" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">رقم الحساب</label>
            <input v-model="form.account_number" type="text" required class="form-input" dir="ltr" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">رقم IBAN <span class="font-normal text-slate-400">(اختياري)</span></label>
            <input v-model="form.iban" type="text" class="form-input" dir="ltr" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">ترتيب العرض</label>
            <input v-model.number="form.sort_order" type="number" min="0" class="form-input" dir="ltr" />
          </div>
          <label class="flex items-center gap-2 cursor-pointer">
            <input v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded accent-amber-500" />
            <span class="text-xs font-bold text-slate-700">ظاهر للوكالات</span>
          </label>

          <div v-if="formError" class="p-3 rounded-xl text-xs font-semibold bg-red-100 text-red-700">{{ formError }}</div>

          <div class="flex gap-2 pt-2">
            <Button type="submit" variant="amber" block :disabled="saving">
              {{ saving ? 'جارٍ الحفظ...' : editing ? 'حفظ التغييرات' : 'إضافة الحساب' }}
            </Button>
            <Button type="button" variant="secondary" @click="closeModal">إلغاء</Button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import api from '@/lib/api'
import { Plus, Pencil, Trash2, Eye, EyeOff, X } from 'lucide-vue-next'
import PageHeader from '@/components/PageHeader.vue'
import Card from '@/components/Card.vue'
import Badge from '@/components/Badge.vue'
import Button from '@/components/Button.vue'
import LoadingState from '@/components/LoadingState.vue'
import EmptyState from '@/components/EmptyState.vue'
import DataTable from '@/components/DataTable.vue'

const accounts = ref([])
const loading = ref(true)

const message = ref('')
const messageClass = ref('')

const showModal = ref(false)
const editing = ref(null)
const saving = ref(false)
const formError = ref('')
const form = reactive({ bank_name: '', account_name: '', account_number: '', iban: '', sort_order: 0, is_active: true })

const loadAccounts = async () => {
  loading.value = true
  try {
    const response = await api.get('/admin/bank-accounts')
    accounts.value = response.data.data
  } catch (err) {
    console.error('Failed to load bank accounts', err)
  } finally {
    loading.value = false
  }
}

onMounted(loadAccounts)

const flash = (text, ok = true) => {
  message.value = text
  messageClass.value = ok ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'
  setTimeout(() => { message.value = '' }, 4000)
}

const openCreate = () => {
  editing.value = null
  formError.value = ''
  Object.assign(form, { bank_name: '', account_name: '', account_number: '', iban: '', sort_order: accounts.value.length, is_active: true })
  showModal.value = true
}

const openEdit = (account) => {
  editing.value = account
  formError.value = ''
  Object.assign(form, {
    bank_name: account.bank_name,
    account_name: account.account_name,
    account_number: account.account_number,
    iban: account.iban || '',
    sort_order: account.sort_order,
    is_active: account.is_active,
  })
  showModal.value = true
}

const closeModal = () => { showModal.value = false }

const submitForm = async () => {
  saving.value = true
  formError.value = ''
  try {
    const payload = {
      bank_name: form.bank_name,
      account_name: form.account_name,
      account_number: form.account_number,
      iban: form.iban || null,
      sort_order: form.sort_order,
      is_active: form.is_active,
    }

    if (editing.value) {
      await api.put(`/admin/bank-accounts/${editing.value.id}`, payload)
      flash('تم تحديث الحساب البنكي بنجاح')
    } else {
      await api.post('/admin/bank-accounts', payload)
      flash('تمت إضافة الحساب البنكي بنجاح')
    }
    showModal.value = false
    await loadAccounts()
  } catch (err) {
    const errors = err.response?.data?.errors
    formError.value = errors ? Object.values(errors).flat().join('، ') : (err.response?.data?.message || 'تعذر حفظ الحساب البنكي')
  } finally {
    saving.value = false
  }
}

const toggleActive = async (account) => {
  try {
    await api.put(`/admin/bank-accounts/${account.id}`, { ...account, is_active: !account.is_active })
    flash(account.is_active ? 'تم إخفاء الحساب' : 'تم إظهار الحساب')
    await loadAccounts()
  } catch (err) {
    flash(err.response?.data?.message || 'تعذر تغيير الحالة', false)
  }
}

const deleteAccount = async (account) => {
  if (!confirm(`هل أنت متأكد من حذف حساب "${account.bank_name}"؟`)) return
  try {
    await api.delete(`/admin/bank-accounts/${account.id}`)
    flash('تم حذف الحساب البنكي بنجاح')
    await loadAccounts()
  } catch (err) {
    flash(err.response?.data?.message || 'تعذر حذف الحساب', false)
  }
}
</script>

<style scoped>
.form-input {
  width: 100%;
  border: 1px solid rgb(226 232 240);
  border-radius: 0.75rem;
  padding: 0.625rem 0.75rem;
  font-size: 0.8125rem;
  outline: none;
}
.form-input:focus {
  box-shadow: 0 0 0 2px rgb(251 191 36);
}
.action-btn {
  padding: 0.375rem;
  border-radius: 0.5rem;
  transition: background-color 0.15s;
}
.action-btn:hover {
  background-color: rgb(241 245 249);
}
</style>

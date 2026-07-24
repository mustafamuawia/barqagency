<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <PageHeader title="إدارة المستخدمين" subtitle="إضافة وتعديل مستخدمي لوحة الإدارة، تعطيل الحسابات أو حذفها" />

    <LoadingState v-if="loading" />

    <Card v-else class="space-y-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <h3 class="font-bold text-sm text-slate-900">قائمة المستخدمين</h3>
          <Badge color="blue">{{ users.length }} مستخدم</Badge>
        </div>
        <div class="flex items-center gap-2">
          <select
            v-model="trashedFilter"
            @change="loadUsers"
            class="border border-slate-200 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-amber-400"
          >
            <option value="">الحسابات النشطة</option>
            <option value="with">الكل (مع المحذوفة)</option>
            <option value="only">المحذوفة فقط</option>
          </select>
          <Button variant="amber" @click="openCreate">
            <UserPlus class="w-4 h-4" />
            مستخدم جديد
          </Button>
        </div>
      </div>

      <DataTable :columns="['الاسم', 'البريد الإلكتروني', 'الصلاحية', 'الحالة', 'الإجراءات']">
        <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50 transition" :class="{ 'opacity-60': user.is_deleted }">
          <td class="p-3 font-bold text-slate-800">{{ user.name }}</td>
          <td class="p-3 font-mono text-slate-500">{{ user.email }}</td>
          <td class="p-3">
            <Badge :color="user.user_type === 'super_admin' ? 'purple' : 'blue'">
              {{ roleLabel(user.user_type) }}
            </Badge>
          </td>
          <td class="p-3">
            <Badge v-if="user.is_deleted" color="slate">محذوف</Badge>
            <Badge v-else :color="user.is_active ? 'emerald' : 'red'">
              {{ user.is_active ? 'نشط' : 'معطّل' }}
            </Badge>
          </td>
          <td class="p-3">
            <div class="flex items-center justify-center gap-1.5">
              <template v-if="user.is_deleted">
                <button @click="restoreUser(user)" class="action-btn text-emerald-600" title="استعادة">
                  <RotateCcw class="w-4 h-4" />
                </button>
              </template>
              <template v-else>
                <button @click="openEdit(user)" class="action-btn text-slate-600" title="تعديل">
                  <Pencil class="w-4 h-4" />
                </button>
                <button
                  v-if="user.id !== auth.user?.id"
                  @click="toggleActive(user)"
                  class="action-btn"
                  :class="user.is_active ? 'text-amber-600' : 'text-emerald-600'"
                  :title="user.is_active ? 'تعطيل' : 'تفعيل'"
                >
                  <component :is="user.is_active ? PowerOff : Power" class="w-4 h-4" />
                </button>
                <button
                  v-if="user.id !== auth.user?.id"
                  @click="deleteUser(user)"
                  class="action-btn text-red-600"
                  title="حذف"
                >
                  <Trash2 class="w-4 h-4" />
                </button>
              </template>
            </div>
          </td>
        </tr>
      </DataTable>

      <EmptyState v-if="!users.length" message="لا يوجد مستخدمون لعرضهم" />

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
          <h3 class="font-black text-lg text-slate-900">{{ editing ? 'تعديل المستخدم' : 'إضافة مستخدم جديد' }}</h3>
          <button @click="closeModal" class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitForm" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">الاسم</label>
            <input v-model="form.name" type="text" required class="form-input" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">البريد الإلكتروني</label>
            <input v-model="form.email" type="email" required class="form-input" dir="ltr" />
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">الصلاحية</label>
            <select v-model="form.user_type_id" required class="form-input">
              <option value="" disabled>اختر الصلاحية</option>
              <option v-for="t in userTypes" :key="t.id" :value="t.id">{{ roleLabel(t.name) }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">
              كلمة المرور <span v-if="editing" class="font-normal text-slate-400">(اتركها فارغة لعدم التغيير)</span>
            </label>
            <input v-model="form.password" type="password" :required="!editing" minlength="8" class="form-input" dir="ltr" />
          </div>
          <div v-if="form.password">
            <label class="block text-xs font-bold text-slate-700 mb-1.5">تأكيد كلمة المرور</label>
            <input v-model="form.password_confirmation" type="password" class="form-input" dir="ltr" />
          </div>
          <label class="flex items-center gap-2 cursor-pointer">
            <input v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded accent-amber-500" />
            <span class="text-xs font-bold text-slate-700">الحساب نشط</span>
          </label>

          <div v-if="formError" class="p-3 rounded-xl text-xs font-semibold bg-red-100 text-red-700">{{ formError }}</div>

          <div class="flex gap-2 pt-2">
            <Button type="submit" variant="amber" block :disabled="saving">
              {{ saving ? 'جارٍ الحفظ...' : editing ? 'حفظ التغييرات' : 'إنشاء المستخدم' }}
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
import { useAuthStore } from '@/stores/auth'
import { UserPlus, Pencil, Trash2, RotateCcw, Power, PowerOff, X } from 'lucide-vue-next'
import PageHeader from '@/components/PageHeader.vue'
import Card from '@/components/Card.vue'
import Badge from '@/components/Badge.vue'
import Button from '@/components/Button.vue'
import LoadingState from '@/components/LoadingState.vue'
import EmptyState from '@/components/EmptyState.vue'
import DataTable from '@/components/DataTable.vue'

const auth = useAuthStore()

const users = ref([])
const userTypes = ref([])
const loading = ref(true)
const trashedFilter = ref('')

const message = ref('')
const messageClass = ref('')

const showModal = ref(false)
const editing = ref(null)
const saving = ref(false)
const formError = ref('')
const form = reactive({ name: '', email: '', user_type_id: '', password: '', password_confirmation: '', is_active: true })

const ROLE_LABELS = { super_admin: 'مدير عام', admin: 'مدير', b2b: 'وكالة' }
const roleLabel = (name) => ROLE_LABELS[name] || name

const loadUsers = async () => {
  loading.value = true
  try {
    const params = trashedFilter.value ? { trashed: trashedFilter.value } : {}
    const response = await api.get('/admin/users', { params })
    users.value = response.data.users
    userTypes.value = response.data.user_types
  } catch (err) {
    console.error('Failed to load users', err)
  } finally {
    loading.value = false
  }
}

onMounted(loadUsers)

const flash = (text, ok = true) => {
  message.value = text
  messageClass.value = ok ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'
  setTimeout(() => { message.value = '' }, 4000)
}

const openCreate = () => {
  editing.value = null
  formError.value = ''
  Object.assign(form, { name: '', email: '', user_type_id: '', password: '', password_confirmation: '', is_active: true })
  showModal.value = true
}

const openEdit = (user) => {
  editing.value = user
  formError.value = ''
  Object.assign(form, {
    name: user.name,
    email: user.email,
    user_type_id: user.user_type_id,
    password: '',
    password_confirmation: '',
    is_active: user.is_active,
  })
  showModal.value = true
}

const closeModal = () => { showModal.value = false }

const submitForm = async () => {
  saving.value = true
  formError.value = ''
  try {
    const payload = {
      name: form.name,
      email: form.email,
      user_type_id: form.user_type_id,
      is_active: form.is_active,
    }
    if (form.password) {
      payload.password = form.password
      payload.password_confirmation = form.password_confirmation
    }

    if (editing.value) {
      await api.put(`/admin/users/${editing.value.id}`, payload)
      flash('تم تحديث المستخدم بنجاح')
    } else {
      await api.post('/admin/users', payload)
      flash('تم إنشاء المستخدم بنجاح')
    }
    showModal.value = false
    await loadUsers()
  } catch (err) {
    const errors = err.response?.data?.errors
    formError.value = errors ? Object.values(errors).flat().join('، ') : (err.response?.data?.message || 'تعذر حفظ المستخدم')
  } finally {
    saving.value = false
  }
}

const toggleActive = async (user) => {
  try {
    await api.put(`/admin/users/${user.id}/toggle-active`, { is_active: !user.is_active })
    flash(user.is_active ? 'تم تعطيل المستخدم' : 'تم تفعيل المستخدم')
    await loadUsers()
  } catch (err) {
    flash(err.response?.data?.message || 'تعذر تغيير الحالة', false)
  }
}

const deleteUser = async (user) => {
  if (!confirm(`هل أنت متأكد من حذف المستخدم "${user.name}"؟`)) return
  try {
    await api.delete(`/admin/users/${user.id}`)
    flash('تم حذف المستخدم بنجاح')
    await loadUsers()
  } catch (err) {
    flash(err.response?.data?.message || 'تعذر حذف المستخدم', false)
  }
}

const restoreUser = async (user) => {
  try {
    await api.put(`/admin/users/${user.id}/restore`)
    flash('تمت استعادة المستخدم بنجاح')
    await loadUsers()
  } catch (err) {
    flash(err.response?.data?.message || 'تعذر استعادة المستخدم', false)
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

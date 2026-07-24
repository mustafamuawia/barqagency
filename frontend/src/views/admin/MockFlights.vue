<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <PageHeader title="إدارة رحلات البحث الوهمية" subtitle="أضف وعدّل مسارات وأسعار الرحلات التي تظهر في نتائج البحث بالصفحة الرئيسية" />

    <LoadingState v-if="loading" />

    <Card v-else class="space-y-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <h3 class="font-bold text-sm text-slate-900">قائمة الرحلات</h3>
          <Badge color="blue">{{ meta?.total ?? offers.length }} رحلة</Badge>
        </div>
        <Button variant="amber" @click="openCreate">
          <PlaneTakeoff class="w-4 h-4" />
          مسار جديد
        </Button>
      </div>

      <DataTable :columns="['شركة الطيران', 'المسار', 'الدرجة', 'سعر ذهاب', 'سعر ذهاب وعودة', 'المدة', 'الحالة', 'الإجراءات']">
        <tr v-for="offer in offers" :key="offer.id" class="hover:bg-slate-50 transition">
          <td class="p-3 font-bold text-slate-800">{{ offer.airline }}</td>
          <td class="p-3 font-mono text-slate-600">{{ offer.origin }} → {{ offer.destination }}</td>
          <td class="p-3">
            <Badge color="slate">{{ offer.cabin_class }}</Badge>
          </td>
          <td class="p-3 font-mono font-bold text-slate-800">{{ formatCurrency(offer.price_oneway, offer.currency) }}</td>
          <td class="p-3 font-mono text-slate-600">{{ offer.price_roundtrip ? formatCurrency(offer.price_roundtrip, offer.currency) : '—' }}</td>
          <td class="p-3 text-slate-500">{{ offer.duration_minutes ? formatDuration(offer.duration_minutes) : '—' }}</td>
          <td class="p-3">
            <Badge :color="offer.is_active ? 'emerald' : 'red'">{{ offer.is_active ? 'مفعّل' : 'معطّل' }}</Badge>
          </td>
          <td class="p-3">
            <div class="flex items-center justify-center gap-1.5">
              <button @click="openEdit(offer)" class="action-btn text-slate-600" title="تعديل">
                <Pencil class="w-4 h-4" />
              </button>
              <button @click="deleteOffer(offer)" class="action-btn text-red-600" title="حذف">
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
          </td>
        </tr>
      </DataTable>

      <Pagination :meta="meta" @change="page => loadOffers(page)" />

      <EmptyState v-if="!offers.length" message="لا توجد مسارات رحلات مضافة بعد" />

      <div v-if="message" class="p-3 rounded-xl text-xs font-semibold" :class="messageClass">{{ message }}</div>
    </Card>

    <!-- Create / Edit Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
      @click.self="closeModal"
    >
      <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg p-6 space-y-5" dir="rtl">
        <div class="flex items-center justify-between">
          <h3 class="font-black text-lg text-slate-900">{{ editing ? 'تعديل المسار' : 'إضافة مسار جديد' }}</h3>
          <button @click="closeModal" class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitForm" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">شركة الطيران</label>
            <select v-model="form.airline_id" required class="form-input">
              <option value="" disabled>اختر شركة الطيران</option>
              <option v-for="a in airlines" :key="a.id" :value="a.id">{{ a.name }}</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">من</label>
              <select v-model="form.origin_airport_id" required class="form-input">
                <option value="" disabled>مطار المغادرة</option>
                <option v-for="ap in airports" :key="ap.id" :value="ap.id">{{ ap.code }} - {{ ap.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">إلى</label>
              <select v-model="form.destination_airport_id" required class="form-input">
                <option value="" disabled>مطار الوصول</option>
                <option v-for="ap in airports" :key="ap.id" :value="ap.id">{{ ap.code }} - {{ ap.name }}</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">درجة السفر</label>
            <select v-model="form.cabin_class_id" required class="form-input">
              <option value="" disabled>اختر الدرجة</option>
              <option v-for="c in cabinClasses" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">سعر الذهاب فقط</label>
              <input v-model.number="form.price_oneway" type="number" step="0.01" min="0" required class="form-input" dir="ltr" />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">
                سعر الذهاب والعودة <span class="font-normal text-slate-400">(اختياري)</span>
              </label>
              <input v-model.number="form.price_roundtrip" type="number" step="0.01" min="0" class="form-input" dir="ltr" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">العملة</label>
              <input v-model="form.currency" type="text" maxlength="3" class="form-input uppercase" dir="ltr" placeholder="USD" />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">مدة الرحلة (دقيقة)</label>
              <input v-model.number="form.duration_minutes" type="number" min="1" class="form-input" dir="ltr" />
            </div>
          </div>

          <label class="flex items-center gap-2 cursor-pointer">
            <input v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded accent-amber-500" />
            <span class="text-xs font-bold text-slate-700">مسار مفعّل ويظهر في نتائج البحث</span>
          </label>

          <div v-if="formError" class="p-3 rounded-xl text-xs font-semibold bg-red-100 text-red-700">{{ formError }}</div>

          <div class="flex gap-2 pt-2">
            <Button type="submit" variant="amber" block :disabled="saving">
              {{ saving ? 'جارٍ الحفظ...' : editing ? 'حفظ التغييرات' : 'إضافة المسار' }}
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
import { PlaneTakeoff, Pencil, Trash2, X } from 'lucide-vue-next'
import PageHeader from '@/components/PageHeader.vue'
import Card from '@/components/Card.vue'
import Badge from '@/components/Badge.vue'
import Button from '@/components/Button.vue'
import LoadingState from '@/components/LoadingState.vue'
import EmptyState from '@/components/EmptyState.vue'
import DataTable from '@/components/DataTable.vue'
import Pagination from '@/components/Pagination.vue'

const offers = ref([])
const meta = ref(null)
const airlines = ref([])
const airports = ref([])
const cabinClasses = ref([])
const loading = ref(true)

const message = ref('')
const messageClass = ref('')

const showModal = ref(false)
const editing = ref(null)
const saving = ref(false)
const formError = ref('')

const emptyForm = () => ({
  airline_id: '',
  origin_airport_id: '',
  destination_airport_id: '',
  cabin_class_id: '',
  price_oneway: '',
  price_roundtrip: '',
  currency: 'USD',
  duration_minutes: '',
  is_active: true,
})

const form = reactive(emptyForm())

const loadOffers = async (page = 1) => {
  try {
    const response = await api.get('/admin/mock-flights', { params: { page } })
    offers.value = response.data.data
    meta.value = response.data
  } catch (err) {
    console.error('Failed to load mock flights', err)
  }
}

const loadAll = async () => {
  loading.value = true
  try {
    const [, airlinesRes, airportsRes, cabinsRes] = await Promise.all([
      loadOffers(),
      api.get('/airlines'),
      api.get('/airports'),
      api.get('/cabin-classes'),
    ])
    airlines.value = airlinesRes.data
    airports.value = airportsRes.data
    cabinClasses.value = cabinsRes.data
  } catch (err) {
    console.error('Failed to load mock flights', err)
  } finally {
    loading.value = false
  }
}

onMounted(loadAll)

const flash = (text, ok = true) => {
  message.value = text
  messageClass.value = ok ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'
  setTimeout(() => { message.value = '' }, 4000)
}

const openCreate = () => {
  editing.value = null
  formError.value = ''
  Object.assign(form, emptyForm())
  showModal.value = true
}

const openEdit = (offer) => {
  editing.value = offer
  formError.value = ''
  Object.assign(form, {
    airline_id: offer.airline_id,
    origin_airport_id: offer.origin_airport_id,
    destination_airport_id: offer.destination_airport_id,
    cabin_class_id: offer.cabin_class_id,
    price_oneway: offer.price_oneway,
    price_roundtrip: offer.price_roundtrip ?? '',
    currency: offer.currency,
    duration_minutes: offer.duration_minutes ?? '',
    is_active: offer.is_active,
  })
  showModal.value = true
}

const closeModal = () => { showModal.value = false }

const submitForm = async () => {
  saving.value = true
  formError.value = ''
  try {
    const payload = {
      airline_id: form.airline_id,
      origin_airport_id: form.origin_airport_id,
      destination_airport_id: form.destination_airport_id,
      cabin_class_id: form.cabin_class_id,
      price_oneway: form.price_oneway,
      price_roundtrip: form.price_roundtrip === '' ? null : form.price_roundtrip,
      currency: (form.currency || 'USD').toUpperCase(),
      duration_minutes: form.duration_minutes === '' ? null : form.duration_minutes,
      is_active: form.is_active,
    }

    if (editing.value) {
      await api.put(`/admin/mock-flights/${editing.value.id}`, payload)
      flash('تم تحديث المسار بنجاح')
    } else {
      await api.post('/admin/mock-flights', payload)
      flash('تمت إضافة المسار بنجاح')
    }
    showModal.value = false
    await loadOffers(meta.value?.current_page || 1)
  } catch (err) {
    const errors = err.response?.data?.errors
    formError.value = errors ? Object.values(errors).flat().join('، ') : (err.response?.data?.message || 'تعذر حفظ المسار')
  } finally {
    saving.value = false
  }
}

const deleteOffer = async (offer) => {
  if (!confirm(`هل أنت متأكد من حذف مسار ${offer.origin} → ${offer.destination}؟`)) return
  try {
    await api.delete(`/admin/mock-flights/${offer.id}`)
    flash('تم حذف المسار بنجاح')
    await loadOffers(meta.value?.current_page || 1)
  } catch (err) {
    flash(err.response?.data?.message || 'تعذر حذف المسار', false)
  }
}

const formatCurrency = (value, currency = 'USD') =>
  new Intl.NumberFormat('en-US', { style: 'currency', currency: currency || 'USD' }).format(value || 0)

const formatDuration = (minutes) => {
  const h = Math.floor(minutes / 60)
  const m = minutes % 60
  return `${h}س ${m}د`
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

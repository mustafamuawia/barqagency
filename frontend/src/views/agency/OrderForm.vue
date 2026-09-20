<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <PageHeader title="طلب حجز رحلة جديدة" subtitle="أدخل تفاصيل الرحلة والركاب يدويًا">
      <template #actions>
        <router-link to="/agency/orders" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-4 py-2.5 rounded-xl flex items-center gap-2 transition">
          <ArrowRight class="w-4 h-4" />
          العودة للطلبات
        </router-link>
      </template>
    </PageHeader>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <Card class="space-y-4">
        <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
          <PlaneTakeoff class="w-4 h-4 text-amber-500" />
          تفاصيل الرحلة
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">نوع الرحلة</label>
            <select v-model="form.trip_type" required
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400">
              <option value="one_way">ذهاب فقط</option>
              <option value="round_trip">ذهاب وعودة</option>
              <option value="multi_city">مدن متعددة</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">تاريخ المغادرة</label>
            <input v-model="form.departure_date" type="date" required
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
          </div>
          <div v-if="form.trip_type === 'round_trip'">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">تاريخ العودة</label>
            <input v-model="form.return_date" type="date" required
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">عدد الركاب</label>
            <input v-model.number="form.total_passengers" type="number" min="1" max="9" required
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
          </div>
        </div>
      </Card>

      <Card class="space-y-4">
        <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
          <MapPin class="w-4 h-4 text-blue-500" />
          خط السير
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">درجة السفر</label>
            <select v-model="segment.cabin_class_id" required
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400">
              <option value="">اختر الدرجة</option>
              <option v-for="cabin in cabins" :key="cabin.id" :value="cabin.id">{{ cabin.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">مطار المغادرة</label>
            <AirportAutocomplete v-model="departureAirportQuery" required />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">مطار الوصول</label>
            <AirportAutocomplete v-model="arrivalAirportQuery" required />
          </div>
        </div>
      </Card>

      <Card class="space-y-5">
        <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
          <User class="w-4 h-4 text-emerald-500" />
          بيانات الركاب
        </h3>
        <div v-for="(p, i) in passengers" :key="i" class="border border-slate-200 rounded-2xl p-4 space-y-4">
          <div class="flex items-center gap-2">
            <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 text-[11px] font-bold flex items-center justify-center">{{ i + 1 }}</span>
            <p class="text-xs font-bold text-slate-700">الراكب {{ i + 1 }}</p>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1.5">نوع الراكب</label>
              <select v-model="p.passenger_type_id" required
                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400">
                <option v-for="type in passengerTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1.5">اللقب</label>
              <select v-model="p.title" required
                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400">
                <option value="">اختر اللقب</option>
                <option v-for="t in titleOptions" :key="t" :value="t">{{ t }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1.5">تاريخ الميلاد</label>
              <input v-model="p.date_of_birth" type="date" required
                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1.5">الاسم الأول (كما في الجواز)</label>
              <input v-model="p.first_name" type="text" required
                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1.5">اسم العائلة (كما في الجواز)</label>
              <input v-model="p.last_name" type="text" required
                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1.5">الجنسية</label>
              <input v-model="p.nationality" type="text" required
                placeholder="اكتب الجنسية"
                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1.5">رقم جواز السفر</label>
              <input v-model="p.passport_number" type="text" required
                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1.5">تاريخ انتهاء الجواز</label>
              <input v-model="p.passport_expiry" type="date" required
                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
            </div>
          </div>
        </div>
      </Card>

      <div v-if="error" class="bg-red-100 text-red-700 text-xs font-semibold p-4 rounded-2xl">{{ error }}</div>

      <Button type="submit" variant="amber" size="lg" block :disabled="loading">
        <Send class="w-4 h-4" />
        {{ loading ? 'جارٍ الإرسال...' : 'إرسال طلب الحجز' }}
      </Button>
    </form>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/lib/api'
import { ArrowRight, PlaneTakeoff, MapPin, User, Send } from 'lucide-vue-next'
import Card from '@/components/Card.vue'
import PageHeader from '@/components/PageHeader.vue'
import Button from '@/components/Button.vue'
import AirportAutocomplete from '@/components/AirportAutocomplete.vue'

const router = useRouter()
const loading = ref(false)
const error = ref('')

const cabins = ref([])
const passengerTypes = ref([])
const titleOptions = ['السيد', 'السيدة', 'الآنسة']

onMounted(async () => {
  try {
    const [cabinsRes, typesRes] = await Promise.all([
      api.get('/cabin-classes'), api.get('/passenger-types'),
    ])
    cabins.value = cabinsRes.data
    passengerTypes.value = typesRes.data
    if (passengerTypes.value.length) {
      passengers.value.forEach((p) => { p.passenger_type_id = p.passenger_type_id || passengerTypes.value[0].id })
    }
  } catch (err) {
    error.value = 'فشل تحميل البيانات المرجعية'
  }
})

const todayStr = () => new Date().toISOString().slice(0, 10)
const form = ref({ trip_type: 'one_way', departure_date: todayStr(), return_date: '', total_passengers: 1 })
const segment = ref({ departure_airport_name: '', arrival_airport_name: '', cabin_class_id: '', sequence: 1 })

const makePassenger = (sequence) => ({
  passenger_type_id: passengerTypes.value[0]?.id || '',
  title: '',
  first_name: '',
  last_name: '',
  date_of_birth: '',
  nationality: '',
  passport_number: '',
  passport_expiry: '',
  sequence,
})
const passengers = ref([makePassenger(1)])

watch(() => form.value.total_passengers, (count) => {
  const target = Math.max(1, Math.min(9, count || 1))
  while (passengers.value.length < target) passengers.value.push(makePassenger(passengers.value.length + 1))
  while (passengers.value.length > target) passengers.value.pop()
})

const departureAirportQuery = ref('')
const arrivalAirportQuery = ref('')

const normalize = (value) => value.trim().toLowerCase()

const handleSubmit = async () => {
  error.value = ''

  const departureAirport = departureAirportQuery.value.trim()
  const arrivalAirport = arrivalAirportQuery.value.trim()

  if (!departureAirport || !arrivalAirport) {
    error.value = 'يرجى إدخال مطار المغادرة ومطار الوصول'
    return
  }
  if (normalize(departureAirport) === normalize(arrivalAirport)) {
    error.value = 'يجب أن يختلف مطار المغادرة عن مطار الوصول'
    return
  }

  segment.value.departure_airport_name = departureAirport
  segment.value.arrival_airport_name = arrivalAirport
  form.value.return_date = form.value.trip_type === 'round_trip' ? form.value.return_date || null : null

  loading.value = true
  try {
    const response = await api.post('/orders', { ...form.value, segments: [segment.value], passengers: passengers.value })
    router.push(`/agency/orders/${response.data.order.id}`)
  } catch (err) {
    error.value = err.response?.data?.message || 'فشل إنشاء الطلب'
  } finally {
    loading.value = false
  }
}
</script>

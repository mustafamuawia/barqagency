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
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">تاريخ المغادرة</label>
            <input v-model="form.departure_date" type="date" required
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">تاريخ العودة (اختياري)</label>
            <input v-model="form.return_date" type="date"
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
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">شركة الطيران</label>
            <input v-model="airlineQuery" type="text" list="airline-options" required
              placeholder="ابحث عن شركة الطيران"
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
            <datalist id="airline-options">
              <option v-for="airline in airlines" :key="airline.id" :value="airline.name" />
            </datalist>
          </div>
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
            <input v-model="departureAirportQuery" type="text" list="airport-options" required
              placeholder="ابحث عن المطار"
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">مطار الوصول</label>
            <input v-model="arrivalAirportQuery" type="text" list="airport-options" required
              placeholder="ابحث عن المطار"
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
            <datalist id="airport-options">
              <option v-for="airport in airports" :key="airport.id" :value="airportLabel(airport)" />
            </datalist>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">وقت المغادرة</label>
            <input v-model="segment.departure_datetime" type="datetime-local" required
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">وقت الوصول</label>
            <input v-model="segment.arrival_datetime" type="datetime-local" required
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
          </div>
        </div>
      </Card>

      <Card class="space-y-4">
        <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
          <User class="w-4 h-4 text-emerald-500" />
          بيانات الركاب
        </h3>
        <div v-for="(p, i) in passengers" :key="i" class="space-y-2">
          <p class="text-xs font-bold text-slate-500">الراكب {{ i + 1 }}</p>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1.5">نوع الراكب</label>
              <select v-model="p.passenger_type_id" required
                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400">
                <option v-for="type in passengerTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1.5">الاسم الأول</label>
              <input v-model="p.first_name" type="text" required
                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1.5">اسم العائلة</label>
              <input v-model="p.last_name" type="text" required
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

const router = useRouter()
const loading = ref(false)
const error = ref('')

const airlines = ref([])
const cabins = ref([])
const airports = ref([])
const passengerTypes = ref([])

onMounted(async () => {
  try {
    const [airlinesRes, cabinsRes, airportsRes, typesRes] = await Promise.all([
      api.get('/airlines'), api.get('/cabin-classes'), api.get('/airports'), api.get('/passenger-types'),
    ])
    airlines.value = airlinesRes.data
    cabins.value = cabinsRes.data
    airports.value = airportsRes.data
    passengerTypes.value = typesRes.data
    if (passengerTypes.value.length) {
      passengers.value.forEach((p) => { p.passenger_type_id = p.passenger_type_id || passengerTypes.value[0].id })
    }
  } catch (err) {
    error.value = 'فشل تحميل البيانات المرجعية'
  }
})

const todayStr = () => new Date().toISOString().slice(0, 10)
const form = ref({ departure_date: todayStr(), return_date: '', total_passengers: 1 })
const segment = ref({ airline_id: '', departure_airport_id: '', arrival_airport_id: '', cabin_class_id: '', departure_datetime: '', arrival_datetime: '', sequence: 1 })

const makePassenger = (sequence) => ({ passenger_type_id: passengerTypes.value[0]?.id || '', first_name: '', last_name: '', sequence })
const passengers = ref([makePassenger(1)])

watch(() => form.value.total_passengers, (count) => {
  const target = Math.max(1, Math.min(9, count || 1))
  while (passengers.value.length < target) passengers.value.push(makePassenger(passengers.value.length + 1))
  while (passengers.value.length > target) passengers.value.pop()
})

const airlineQuery = ref('')
const departureAirportQuery = ref('')
const arrivalAirportQuery = ref('')

const airportLabel = (airport) => `${airport.code} - ${airport.name}`
const normalize = (value) => value.trim().toLowerCase()

const findAirlineByName = (name) => airlines.value.find((a) => normalize(a.name) === normalize(name))
const findAirportByLabel = (label) => airports.value.find((a) => normalize(airportLabel(a)) === normalize(label))

const handleSubmit = async () => {
  error.value = ''

  const airline = findAirlineByName(airlineQuery.value)
  const departureAirport = findAirportByLabel(departureAirportQuery.value)
  const arrivalAirport = findAirportByLabel(arrivalAirportQuery.value)

  if (!airline) {
    error.value = 'يرجى اختيار شركة طيران من القائمة المقترحة'
    return
  }
  if (!departureAirport || !arrivalAirport) {
    error.value = 'يرجى اختيار مطار المغادرة والوصول من القائمة المقترحة'
    return
  }
  if (departureAirport.id === arrivalAirport.id) {
    error.value = 'يجب أن يختلف مطار المغادرة عن مطار الوصول'
    return
  }

  segment.value.airline_id = airline.id
  segment.value.departure_airport_id = departureAirport.id
  segment.value.arrival_airport_id = arrivalAirport.id

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

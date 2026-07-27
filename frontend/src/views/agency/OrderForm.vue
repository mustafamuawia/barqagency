<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <PageHeader title="طلب حجز رحلة جديدة" :subtitle="mode === 'search' ? 'ابحث عن رحلة حقيقية واحجزها بسعر فوري' : 'أدخل تفاصيل الرحلة والركاب يدويًا'">
      <template #actions>
        <button @click="mode = mode === 'search' ? 'manual' : 'search'"
          class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-4 py-2.5 rounded-xl flex items-center gap-2 transition">
          <component :is="mode === 'search' ? Pencil : Search" class="w-4 h-4" />
          {{ mode === 'search' ? 'إدخال يدوي' : 'بحث عن رحلة' }}
        </button>
        <router-link to="/agency/orders" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-4 py-2.5 rounded-xl flex items-center gap-2 transition">
          <ArrowRight class="w-4 h-4" />
          العودة للطلبات
        </router-link>
      </template>
    </PageHeader>

    <!-- SEARCH MODE -->
    <template v-if="mode === 'search'">
      <Card v-if="!selected" class="space-y-4">
        <div class="flex items-center gap-2 bg-slate-100 p-1.5 rounded-2xl w-fit text-xs font-bold text-slate-600">
          <button type="button" @click="search.tripType = 'roundtrip'" class="px-4 py-2 rounded-xl transition"
            :class="search.tripType === 'roundtrip' ? 'bg-white text-slate-900 shadow-sm' : ''">ذهاب وعودة</button>
          <button type="button" @click="search.tripType = 'oneway'" class="px-4 py-2 rounded-xl transition"
            :class="search.tripType === 'oneway' ? 'bg-white text-slate-900 shadow-sm' : ''">ذهاب فقط</button>
        </div>

        <form @submit.prevent="onSearch" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
          <div class="md:col-span-2 space-y-1">
            <label class="block text-xs font-semibold text-slate-600">من</label>
            <input v-model="search.origin" type="text" placeholder="CAI" maxlength="3"
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs uppercase focus:outline-none focus:ring-2 focus:ring-amber-400" />
          </div>
          <div class="md:col-span-2 space-y-1">
            <label class="block text-xs font-semibold text-slate-600">إلى</label>
            <input v-model="search.destination" type="text" placeholder="JED" maxlength="3"
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs uppercase focus:outline-none focus:ring-2 focus:ring-amber-400" />
          </div>
          <div class="space-y-1" :class="search.tripType === 'roundtrip' ? 'md:col-span-2' : 'md:col-span-3'">
            <label class="block text-xs font-semibold text-slate-600">تاريخ الذهاب</label>
            <input v-model="search.departureDate" type="date" required
              class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
          </div>
          <div v-if="search.tripType === 'roundtrip'" class="md:col-span-2 space-y-1">
            <label class="block text-xs font-semibold text-slate-600">تاريخ العودة</label>
            <input v-model="search.returnDate" type="date" required
              class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
          </div>
          <div class="md:col-span-2 space-y-1">
            <label class="block text-xs font-semibold text-slate-600">الركاب</label>
            <input v-model.number="search.passengers" type="number" min="1" max="9"
              class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
          </div>
          <div class="md:col-span-2 space-y-1">
            <label class="block text-xs font-semibold text-slate-600">الدرجة</label>
            <select v-model="search.cabin" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400">
              <option value="economy">سياحية</option>
              <option value="premium_economy">سياحية مميزة</option>
              <option value="business">أعمال</option>
              <option value="first">أولى</option>
            </select>
          </div>
          <div class="md:col-span-12">
            <Button type="submit" variant="amber" size="lg" block :disabled="searching">
              <Search class="w-4 h-4" />
              {{ searching ? 'جارٍ البحث...' : 'ابحث عن رحلات' }}
            </Button>
          </div>
        </form>

        <div v-if="searchError" class="bg-red-100 text-red-700 text-xs font-semibold p-4 rounded-2xl">{{ searchError }}</div>

        <div v-if="searched && !searching" class="space-y-2 pt-2 border-t border-slate-100">
          <div v-if="!results.length" class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-center text-xs text-slate-500">
            لا توجد رحلات متاحة على هذا المسار في التاريخ المحدد
          </div>
          <div v-for="(r, i) in results" :key="i"
            class="flex flex-wrap items-center justify-between gap-3 bg-slate-50 border border-slate-200 rounded-2xl p-4">
            <div>
              <p class="font-bold text-sm text-slate-900">{{ r.airline || r.airline_code }}</p>
              <p class="text-[11px] text-slate-500 font-mono">
                {{ r.origin }} → {{ r.destination }} · {{ r.cabin_class }}
                <span v-if="r.duration_minutes"> · {{ formatDuration(r.duration_minutes) }}</span>
              </p>
            </div>
            <div class="flex items-center gap-3">
              <div class="text-left">
                <p class="text-lg font-black text-emerald-600 font-mono">{{ formatPrice(r.total_price, r.currency) }}</p>
                <p class="text-[10px] text-slate-400">{{ r.passengers }} مسافر × {{ formatPrice(r.unit_price, r.currency) }}</p>
              </div>
              <Button variant="amber" size="sm" @click="selectItinerary(r)">اختيار</Button>
            </div>
          </div>
        </div>
      </Card>

      <!-- Passenger details for the selected itinerary -->
      <Card v-else class="space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-bold text-sm text-slate-900">تأكيد الحجز وبيانات الركاب</h3>
          <button @click="selected = null" class="text-xs font-bold text-slate-500 hover:text-slate-800">تغيير الاختيار</button>
        </div>

        <div class="bg-[#0f172a] rounded-2xl p-5 space-y-2 text-slate-200 text-xs">
          <div class="flex items-center justify-between">
            <span>{{ selected.airline || selected.airline_code }} · {{ selected.origin }} → {{ selected.destination }} · {{ selected.cabin_class }}</span>
            <span class="font-mono font-black text-emerald-400 text-lg">{{ formatPrice(selected.total_price, selected.currency) }}</span>
          </div>
        </div>

        <div v-for="(p, i) in passengers" :key="i" class="grid grid-cols-3 gap-3 bg-slate-50 rounded-xl p-3 border border-slate-200">
          <select v-model="p.passenger_type_id" required
            class="border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400">
            <option v-for="type in passengerTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
          </select>
          <input v-model="p.first_name" type="text" placeholder="الاسم الأول" required
            class="border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
          <input v-model="p.last_name" type="text" placeholder="اسم العائلة" required
            class="border border-slate-200 rounded-xl px-3 py-2 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
        </div>

        <div v-if="error" class="bg-red-100 text-red-700 text-xs font-semibold p-4 rounded-2xl">{{ error }}</div>

        <Button variant="amber" size="lg" block :disabled="loading" @click="confirmBooking">
          <Send class="w-4 h-4" />
          {{ loading ? 'جارٍ إرسال الطلب...' : 'تأكيد الحجز بالسعر المعروض' }}
        </Button>
      </Card>
    </template>

    <!-- MANUAL MODE (legacy: admin prices it afterwards) -->
    <form v-else @submit.prevent="handleSubmit" class="space-y-6">
      <Card class="space-y-4">
        <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
          <PlaneTakeoff class="w-4 h-4 text-amber-500" />
          تفاصيل الرحلة
        </h3>
        <div class="grid grid-cols-2 gap-4">
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
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">شركة الطيران</label>
            <select v-model="segment.airline_id" required
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400">
              <option value="">اختر شركة الطيران</option>
              <option v-for="airline in airlines" :key="airline.id" :value="airline.id">{{ airline.name }}</option>
            </select>
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
            <select v-model="segment.departure_airport_id" required
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400">
              <option value="">اختر المطار</option>
              <option v-for="airport in airports" :key="airport.id" :value="airport.id">{{ airport.code }} - {{ airport.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">مطار الوصول</label>
            <select v-model="segment.arrival_airport_id" required
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400">
              <option value="">اختر المطار</option>
              <option v-for="airport in airports" :key="airport.id" :value="airport.id">{{ airport.code }} - {{ airport.name }}</option>
            </select>
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
          بيانات الراكب
        </h3>
        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">نوع الراكب</label>
            <select v-model="passenger.passenger_type_id" required
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400">
              <option v-for="type in passengerTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">الاسم الأول</label>
            <input v-model="passenger.first_name" type="text" required
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">اسم العائلة</label>
            <input v-model="passenger.last_name" type="text" required
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
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
import { ArrowRight, PlaneTakeoff, MapPin, User, Send, Search, Pencil } from 'lucide-vue-next'
import Card from '@/components/Card.vue'
import PageHeader from '@/components/PageHeader.vue'
import Button from '@/components/Button.vue'

const router = useRouter()
const loading = ref(false)
const error = ref('')
const mode = ref('search')

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
    if (passengerTypes.value.length) passenger.value.passenger_type_id = passengerTypes.value[0].id
  } catch (err) {
    error.value = 'فشل تحميل البيانات المرجعية'
  }
})

// --- Search & book (real Ignav-backed flow) ---
const todayStr = () => new Date().toISOString().slice(0, 10)
const search = ref({ origin: '', destination: '', tripType: 'oneway', departureDate: todayStr(), returnDate: todayStr(), passengers: 1, cabin: 'economy' })
const searching = ref(false)
const searched = ref(false)
const searchError = ref('')
const results = ref([])
const selected = ref(null)
const passengers = ref([])

const onSearch = async () => {
  searchError.value = ''
  const origin = search.value.origin.trim().toUpperCase()
  const destination = search.value.destination.trim().toUpperCase()
  if (origin.length !== 3 || destination.length !== 3 || origin === destination) {
    searchError.value = 'يرجى إدخال رموز مطارات صحيحة ومختلفة'
    return
  }

  searching.value = true
  searched.value = true
  try {
    const response = await api.get('/flight-search', {
      params: {
        origin,
        destination,
        trip_type: search.value.tripType,
        cabin: search.value.cabin,
        passengers: search.value.passengers,
        departure_date: search.value.departureDate,
        return_date: search.value.tripType === 'roundtrip' ? search.value.returnDate : undefined,
      },
    })
    results.value = response.data.results
  } catch (err) {
    searchError.value = err.response?.data?.message || 'تعذر إتمام البحث'
    results.value = []
  } finally {
    searching.value = false
  }
}

const selectItinerary = (itinerary) => {
  selected.value = itinerary
  passengers.value = Array.from({ length: itinerary.passengers }, () => ({
    passenger_type_id: passengerTypes.value[0]?.id || '',
    first_name: '',
    last_name: '',
  }))
}

const confirmBooking = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await api.post('/orders/book-itinerary', {
      departure_date: search.value.departureDate,
      return_date: search.value.tripType === 'roundtrip' ? search.value.returnDate : null,
      itinerary: selected.value,
      passengers: passengers.value,
    })
    router.push(`/agency/orders/${response.data.order.id}`)
  } catch (err) {
    error.value = err.response?.data?.message || 'فشل إنشاء الطلب'
  } finally {
    loading.value = false
  }
}

const formatPrice = (value, currency = 'USD') =>
  new Intl.NumberFormat('en-US', { style: 'currency', currency: currency || 'USD' }).format(value || 0)
const formatDuration = (minutes) => `${Math.floor(minutes / 60)}س ${minutes % 60}د`

// --- Manual entry (legacy: admin prices it afterwards) ---
const form = ref({ departure_date: todayStr(), return_date: '', total_passengers: 1 })
const segment = ref({ airline_id: '', departure_airport_id: '', arrival_airport_id: '', cabin_class_id: '', departure_datetime: '', arrival_datetime: '', sequence: 1 })
const passenger = ref({ passenger_type_id: '', first_name: '', last_name: '', sequence: 1 })

const handleSubmit = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await api.post('/orders', { ...form.value, segments: [segment.value], passengers: [passenger.value] })
    router.push(`/agency/orders/${response.data.order.id}`)
  } catch (err) {
    error.value = err.response?.data?.message || 'فشل إنشاء الطلب'
  } finally {
    loading.value = false
  }
}
</script>

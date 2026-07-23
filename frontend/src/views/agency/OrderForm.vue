<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <!-- Section Header -->
    <PageHeader title="طلب حجز رحلة جديدة" subtitle="أدخل تفاصيل الرحلة والركاب لإرسال طلب الحجز">
      <template #actions>
        <router-link to="/agency/orders" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-4 py-2.5 rounded-xl flex items-center gap-2 transition">
          <ArrowRight class="w-4 h-4" />
          العودة للطلبات
        </router-link>
      </template>
    </PageHeader>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Flight Details -->
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

      <!-- Segment -->
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

      <!-- Passenger -->
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
import { ref, onMounted } from 'vue'
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

const form = ref({ departure_date: '', return_date: '', total_passengers: 1 })
const segment = ref({ airline_id: '', departure_airport_id: '', arrival_airport_id: '', cabin_class_id: '', departure_datetime: '', arrival_datetime: '', sequence: 1 })
const passenger = ref({ passenger_type_id: '', first_name: '', last_name: '', sequence: 1 })

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

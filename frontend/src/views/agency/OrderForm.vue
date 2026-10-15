<template>
  <div class="max-w-6xl mx-auto p-4 md:p-6 space-y-6">

    <PageHeader title="طلب حجز رحلة جديدة" subtitle="أدخل تفاصيل الرحلة وعدد الركاب للحصول على عرض سعر">
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
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">الوزن (كجم) <span class="text-slate-400 font-normal">(اختياري)</span></label>
            <input v-model.number="form.weight" type="number" min="1" max="500" placeholder="مثال: 23"
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

      <Card class="space-y-4">
        <div>
          <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
            <User class="w-4 h-4 text-emerald-500" />
            عدد الركاب
          </h3>
          <p class="text-[11px] text-slate-500 mt-1.5">
            حدد عدد الركاب من كل نوع ليتم التسعير على أساسه. تُدخل بيانات الركاب لاحقًا عند الموافقة على عرض السعر.
          </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div v-for="type in passengerTypes" :key="type.id">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">{{ passengerTypeLabel(type) }}</label>
            <input v-model.number="counts[type.id]" type="number" min="0" max="9"
              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
          </div>
        </div>

        <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 flex items-center justify-between">
          <span class="text-xs font-semibold text-slate-600">إجمالي الركاب</span>
          <span class="text-sm font-black text-slate-900">{{ totalPassengers }}</span>
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
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/lib/api'
import { ArrowRight, PlaneTakeoff, MapPin, User, Send } from 'lucide-vue-next'
import Card from '@/components/Card.vue'
import PageHeader from '@/components/PageHeader.vue'
import Button from '@/components/Button.vue'
import AirportAutocomplete from '@/components/AirportAutocomplete.vue'
import { passengerTypeLabel } from '@/utils/passengers'

const router = useRouter()
const loading = ref(false)
const error = ref('')

const cabins = ref([])
const passengerTypes = ref([])

// Head count per passenger type id — the order is priced against this mix.
const counts = ref({})

onMounted(async () => {
  try {
    const [cabinsRes, typesRes] = await Promise.all([
      api.get('/cabin-classes'), api.get('/passenger-types'),
    ])
    cabins.value = cabinsRes.data
    passengerTypes.value = typesRes.data
    // One adult by default: the first type is ADT, and every booking needs at least one.
    counts.value = Object.fromEntries(passengerTypes.value.map((type, index) => [type.id, index === 0 ? 1 : 0]))
  } catch (err) {
    error.value = 'فشل تحميل البيانات المرجعية'
  }
})

const todayStr = () => new Date().toISOString().slice(0, 10)
const form = ref({ trip_type: 'one_way', departure_date: todayStr(), return_date: '', weight: null })
const segment = ref({ departure_airport_name: '', arrival_airport_name: '', cabin_class_id: '', sequence: 1 })

const passengerCounts = computed(() =>
  passengerTypes.value.map((type) => ({ passenger_type_id: type.id, count: Number(counts.value[type.id]) || 0 }))
)
const totalPassengers = computed(() => passengerCounts.value.reduce((sum, entry) => sum + entry.count, 0))

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
  if (totalPassengers.value < 1) {
    error.value = 'يجب إضافة راكب واحد على الأقل'
    return
  }
  if (totalPassengers.value > 9) {
    error.value = 'لا يمكن أن يتجاوز إجمالي عدد الركاب 9 ركاب'
    return
  }

  segment.value.departure_airport_name = departureAirport
  segment.value.arrival_airport_name = arrivalAirport
  form.value.return_date = form.value.trip_type === 'round_trip' ? form.value.return_date || null : null

  loading.value = true
  try {
    const payload = {
      ...form.value,
      segments: [segment.value],
      passenger_counts: passengerCounts.value,
    }
    if (!payload.weight) delete payload.weight
    const response = await api.post('/orders', payload)
    router.push(`/agency/orders/${response.data.order.id}`)
  } catch (err) {
    error.value = err.response?.data?.message || 'فشل إنشاء الطلب'
  } finally {
    loading.value = false
  }
}
</script>

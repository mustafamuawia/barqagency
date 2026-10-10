<template>
  <div class="max-w-4xl mx-auto p-4 md:p-6 space-y-6">

    <PageHeader title="بيانات الركاب والموافقة" subtitle="أدخل بيانات كل راكب كما في جواز السفر لتأكيد الحجز">
      <template #actions>
        <router-link :to="`/agency/orders/${id}`"
          class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold px-4 py-2.5 rounded-xl flex items-center gap-2 transition">
          <ArrowRight class="w-4 h-4" />
          العودة للطلب
        </router-link>
      </template>
    </PageHeader>

    <LoadingState v-if="loading" :rows="3" />

    <div v-else-if="!order" class="bg-red-100 text-red-700 text-xs font-semibold p-4 rounded-2xl">{{ error }}</div>

    <div v-else-if="order.status?.name !== 'pricing_sent'"
      class="bg-amber-50 border border-amber-200 text-amber-800 text-xs font-semibold p-4 rounded-2xl">
      هذا الطلب ليس بانتظار الموافقة.
    </div>

    <form v-else @submit.prevent="handleSubmit" class="space-y-6">
      <Card class="space-y-3">
        <h3 class="font-bold text-sm text-slate-900">عرض السعر</h3>
        <div class="bg-[#0f172a] rounded-2xl p-4 space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-xs text-slate-400">إجمالي العرض ({{ order.order_number }})</span>
            <span class="font-mono font-black text-emerald-400 text-xl">{{ formatMoney(latestQuote?.total_price) }}</span>
          </div>
          <!-- The wallet ledger is in points, so the money total alone does not tell the
               agency what its balance will actually be charged. -->
          <div v-if="rate.money_amount" class="flex items-center justify-between border-t border-slate-700 pt-2">
            <span class="text-xs text-slate-400">الخصم من المحفظة</span>
            <span class="font-mono font-bold text-amber-400 text-sm">{{ formatPoints(moneyToPoints(latestQuote?.total_price)) }}</span>
          </div>
        </div>
        <p v-if="rate.money_amount" class="text-[11px] text-slate-500">
          سيتم خصم {{ formatPoints(moneyToPoints(latestQuote?.total_price)) }} من محفظة الوكالة فور تأكيد الحجز
          ({{ rate.money_amount }} ج.س = {{ rate.point_amount }} نقطة).
        </p>
      </Card>

      <Card class="space-y-5">
        <h3 class="font-bold text-sm text-slate-900 flex items-center gap-2">
          <User class="w-4 h-4 text-emerald-500" />
          بيانات الركاب ({{ passengers.length }})
        </h3>

        <div v-for="(p, i) in passengers" :key="p.id" class="border border-slate-200 rounded-2xl p-4 space-y-4">
          <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 text-[11px] font-bold flex items-center justify-center">{{ i + 1 }}</span>
              <p class="text-xs font-bold text-slate-700">الراكب {{ i + 1 }}</p>
            </div>
            <Badge color="slate">{{ passengerTypeLabel(p.passenger_type) }}</Badge>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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
              <input v-model="p.date_of_birth" type="date" required :max="todayStr"
                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1.5">الجنسية</label>
              <input v-model="p.nationality" type="text" required placeholder="اكتب الجنسية"
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
              <label class="block text-xs font-semibold text-slate-600 mb-1.5">رقم جواز السفر</label>
              <input v-model="p.passport_number" type="text" required
                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1.5">تاريخ انتهاء الجواز</label>
              <input v-model="p.passport_expiry" type="date" required :min="todayStr"
                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
            </div>
          </div>
        </div>
      </Card>

      <div v-if="error" class="bg-red-100 text-red-700 text-xs font-semibold p-4 rounded-2xl">{{ error }}</div>

      <Button type="submit" variant="amber" size="lg" block :disabled="saving">
        <CheckCircle class="w-4 h-4" />
        {{ saving ? 'جارٍ المعالجة...' : 'حفظ البيانات وتأكيد الحجز' }}
      </Button>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/lib/api'
import { ArrowRight, User, CheckCircle } from 'lucide-vue-next'
import PageHeader from '@/components/PageHeader.vue'
import LoadingState from '@/components/LoadingState.vue'
import Card from '@/components/Card.vue'
import Badge from '@/components/Badge.vue'
import Button from '@/components/Button.vue'
import { formatMoney, formatPoints } from '@/utils/points'
import { usePointRate } from '@/composables/usePointRate'
import { passengerTypeLabel } from '@/utils/passengers'

const { rate, loadRate, moneyToPoints } = usePointRate()

const props = defineProps({ id: [String, Number] })
const router = useRouter()

const order = ref(null)
const passengers = ref([])
const loading = ref(true)
const saving = ref(false)
const error = ref('')

const titleOptions = ['السيد', 'السيدة', 'الآنسة']
const todayStr = new Date().toISOString().slice(0, 10)

const latestQuote = computed(() => {
  const quotes = order.value?.quotes
  if (!quotes?.length) return null
  return [...quotes].sort((a, b) => new Date(b.created_at) - new Date(a.created_at))[0]
})

// The API casts these to full timestamps; a date input only accepts YYYY-MM-DD.
const toDateInput = (value) => (value ? String(value).slice(0, 10) : '')

onMounted(async () => {
  try {
    const [response] = await Promise.all([api.get(`/orders/${props.id}`), loadRate()])
    order.value = response.data
    // Prefilled from whatever the order already holds: the passenger type is fixed (the
    // admin priced against it) but details may already exist on an older order.
    passengers.value = (response.data.passengers || []).map((p) => ({
      id: p.id,
      passenger_type: p.passenger_type,
      title: p.title || '',
      first_name: p.first_name || '',
      last_name: p.last_name || '',
      date_of_birth: toDateInput(p.date_of_birth),
      nationality: p.nationality || '',
      passport_number: p.passport_number || '',
      passport_expiry: toDateInput(p.passport_expiry),
    }))
  } catch (err) {
    error.value = 'فشل تحميل الطلب'
  } finally {
    loading.value = false
  }
})

const handleSubmit = async () => {
  saving.value = true
  error.value = ''
  try {
    await api.put(`/orders/${props.id}/approve`, {
      passengers: passengers.value.map(({ passenger_type, ...details }) => details),
    })
    router.push(`/agency/orders/${props.id}`)
  } catch (err) {
    error.value = err.response?.data?.message || 'فشل تأكيد الطلب'
  } finally {
    saving.value = false
  }
}
</script>

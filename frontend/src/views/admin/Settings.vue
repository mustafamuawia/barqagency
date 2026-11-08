<template>
  <div class="max-w-3xl mx-auto p-4 md:p-6 space-y-6">

    <PageHeader title="الإعدادات" subtitle="إعدادات النقاط وعمولة الوكالة" />

    <LoadingState v-if="loading" />

    <template v-else>
      <!-- Current Rate -->
      <Card class="space-y-4">
        <h3 class="font-bold text-sm text-slate-900">السعر الحالي</h3>

        <div class="bg-[#0f172a] rounded-2xl p-5 space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-[10px] text-slate-400">حالياً</span>
            <span class="text-sm font-bold text-slate-200">{{ setting?.money_amount }} ج.س = {{ setting?.point_amount }} نقطة</span>
          </div>
          <div class="flex items-center justify-between pt-3 border-t border-slate-800">
            <span class="text-[10px] text-slate-400">مثال</span>
            <span class="font-mono font-black text-emerald-400 text-lg">
              100 ج.س = {{ examplePoints }} نقطة
            </span>
          </div>
          <div class="flex items-center justify-between pt-3 border-t border-slate-800">
            <span class="text-[10px] text-slate-400">عمولة الوكالة</span>
            <span class="font-mono font-bold text-sky-400 text-sm">{{ setting?.agency_commission_amount ?? 0 }} نقطة</span>
          </div>
          <div class="flex items-center justify-between pt-3 border-t border-slate-800">
            <span class="text-[10px] text-slate-400">سعر صرف الدولار</span>
            <span class="font-mono font-bold text-amber-400 text-sm">1 USD = {{ setting?.usd_to_sar_rate ?? 3.75 }} نقطة</span>
          </div>
          <div class="flex items-center justify-between" v-if="setting?.updated_at">
            <span class="text-[10px] text-slate-400">آخر تحديث</span>
            <span class="text-xs font-bold text-slate-300">{{ formatDate(setting.updated_at) }}</span>
          </div>
        </div>
      </Card>

      <!-- Update Form -->
      <Card class="space-y-4">
        <h3 class="font-bold text-sm text-slate-900">تحديث سعر التحويل</h3>
        <p class="text-xs text-slate-500">أدخل كم جنيه سوداني يعادل كم نقطة — مثال: 1 = 1 (نقطة لكل جنيه) أو 10 = 1 (نقطة لكل 10 جنيه)</p>

        <form @submit.prevent="submitForm" class="space-y-4">
          <div class="grid grid-cols-2 gap-4 items-end">
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1.5">المبلغ (ج.س)</label>
              <input
                v-model.number="form.money_amount"
                type="number"
                step="0.01"
                min="0.01"
                required
                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-amber-400"
              />
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-600 mb-1.5">عدد النقاط</label>
              <input
                v-model.number="form.point_amount"
                type="number"
                step="0.01"
                min="0.01"
                required
                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-amber-400"
              />
            </div>
          </div>

          <p v-if="form.money_amount && form.point_amount" class="text-[11px] font-semibold text-emerald-600">
            {{ form.money_amount }} ج.س = {{ form.point_amount }} نقطة — مثال: 100 ج.س = {{ formPreviewPoints }} نقطة
          </p>

          <div class="border-t border-slate-100 pt-4 space-y-3">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">عمولة الوكالة (مبلغ ثابت)</label>
            <p class="text-xs text-slate-400 -mt-1">المبلغ الثابت الذي يُضاف على سعر التذكرة كعمولة للوكالة. يُعرض للوكيل في نافذة الحجز كمرجع لتسعير عملائه.</p>
            <div class="flex items-center gap-2">
              <input
                v-model.number="form.agency_commission_amount"
                type="number"
                step="0.01"
                min="0"
                class="w-40 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-amber-400"
              />
              <span class="text-sm font-bold text-slate-500">نقطة</span>
            </div>
          </div>

          <div class="border-t border-slate-100 pt-4 space-y-3">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">سعر صرف الدولار (USD → نقطة)</label>
            <p class="text-xs text-slate-400 -mt-1">كم نقطة يساوي 1 دولار أمريكي — يُستخدم لتحويل أسعار التذاكر من Duffel.</p>
            <div class="flex items-center gap-2">
              <span class="text-sm font-bold text-slate-500">1 USD =</span>
              <input
                v-model.number="form.usd_to_sar_rate"
                type="number"
                step="0.0001"
                min="0.01"
                class="w-40 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-amber-400"
              />
              <span class="text-sm font-bold text-slate-500">نقطة</span>
            </div>
          </div>

          <div v-if="error" class="bg-red-100 text-red-700 text-xs font-semibold p-3 rounded-xl">{{ error }}</div>
          <div v-if="message" class="bg-emerald-100 text-emerald-700 text-xs font-semibold p-3 rounded-xl">{{ message }}</div>

          <Button type="submit" variant="amber" size="md" block :disabled="saving">
            <Save class="w-4 h-4" />
            {{ saving ? 'جارٍ الحفظ...' : 'حفظ التعديلات' }}
          </Button>
        </form>
      </Card>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import api from '@/lib/api'
import { Save } from 'lucide-vue-next'
import PageHeader from '@/components/PageHeader.vue'
import Card from '@/components/Card.vue'
import Button from '@/components/Button.vue'
import LoadingState from '@/components/LoadingState.vue'

const setting = ref(null)
const loading = ref(true)
const saving = ref(false)
const error = ref('')
const message = ref('')

const form = ref({ money_amount: '', point_amount: '', agency_commission_amount: 0, usd_to_sar_rate: 3.75 })

const examplePoints = computed(() => {
  const money = Number(setting.value?.money_amount) || 0
  const points = Number(setting.value?.point_amount) || 0
  return money > 0 ? ((points / money) * 100).toFixed(2) : '0.00'
})

const formPreviewPoints = computed(() => {
  const money = Number(form.value.money_amount) || 0
  const points = Number(form.value.point_amount) || 0
  return money > 0 ? ((points / money) * 100).toFixed(2) : '0.00'
})

const loadSetting = async () => {
  loading.value = true
  try {
    const response = await api.get('/point-settings')
    setting.value = response.data
    form.value.money_amount = response.data.money_amount
    form.value.point_amount = response.data.point_amount
    form.value.agency_commission_amount = response.data.agency_commission_amount ?? 0
    form.value.usd_to_sar_rate = response.data.usd_to_sar_rate ?? 3.75
  } catch (err) {
    error.value = 'فشل تحميل إعدادات النقاط'
  } finally {
    loading.value = false
  }
}

onMounted(loadSetting)

const submitForm = async () => {
  saving.value = true
  error.value = ''
  message.value = ''
  try {
    const response = await api.put('/point-settings', {
      money_amount: form.value.money_amount,
      point_amount: form.value.point_amount,
      agency_commission_amount: form.value.agency_commission_amount,
      usd_to_sar_rate: form.value.usd_to_sar_rate,
    })
    setting.value = response.data.setting
    form.value.agency_commission_amount = response.data.setting?.agency_commission_amount ?? 0
    form.value.usd_to_sar_rate = response.data.setting?.usd_to_sar_rate ?? 3.75
    message.value = response.data.message || 'تم تحديث الإعدادات بنجاح'
  } catch (err) {
    error.value = err.response?.data?.message || 'فشل تحديث سعر التحويل'
  } finally {
    saving.value = false
  }
}

const formatDate = (date) => new Date(date).toLocaleString('ar-SA')
</script>

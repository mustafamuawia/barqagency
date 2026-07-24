<template>
  <div class="max-w-3xl mx-auto p-4 md:p-6 space-y-6">

    <PageHeader title="الملف الشخصي" subtitle="إدارة معلوماتك الشخصية وكلمة المرور" />

    <Card>
      <div class="flex items-center gap-3 mb-5">
        <div class="w-12 h-12 rounded-2xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-600 font-black text-lg">
          {{ initial }}
        </div>
        <div>
          <p class="text-sm font-bold text-slate-900">{{ auth.user?.name }}</p>
          <Badge :color="roleBadge.color">{{ roleBadge.label }}</Badge>
        </div>
      </div>

      <form @submit.prevent="handleProfileSubmit" class="space-y-5">
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1.5">الاسم</label>
          <input v-model="profileForm.name" type="text" required
            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1.5">البريد الإلكتروني</label>
          <input v-model="profileForm.email" type="email" required
            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400">
        </div>

        <p v-if="profileError" class="bg-red-100 text-red-700 text-xs font-semibold p-3 rounded-xl">{{ profileError }}</p>
        <p v-if="profileSuccess" class="bg-emerald-100 text-emerald-700 text-xs font-semibold p-3 rounded-xl">{{ profileSuccess }}</p>

        <Button type="submit" variant="amber" size="md" block :disabled="profileSubmitting">
          <Save class="w-4 h-4" />
          {{ profileSubmitting ? 'جارٍ الحفظ...' : 'حفظ التغييرات' }}
        </Button>
      </form>
    </Card>

    <Card>
      <h3 class="text-sm font-bold text-slate-900 mb-5 flex items-center gap-2">
        <KeyRound class="w-4 h-4 text-amber-500" />
        تغيير كلمة المرور
      </h3>

      <form @submit.prevent="handlePasswordSubmit" class="space-y-5">
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1.5">كلمة المرور الحالية</label>
          <input v-model="passwordForm.currentPassword" type="password" required
            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1.5">كلمة المرور الجديدة</label>
          <input v-model="passwordForm.password" type="password" required minlength="8"
            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400">
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1.5">تأكيد كلمة المرور الجديدة</label>
          <input v-model="passwordForm.passwordConfirmation" type="password" required minlength="8"
            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400">
        </div>

        <p v-if="passwordError" class="bg-red-100 text-red-700 text-xs font-semibold p-3 rounded-xl">{{ passwordError }}</p>
        <p v-if="passwordSuccess" class="bg-emerald-100 text-emerald-700 text-xs font-semibold p-3 rounded-xl">{{ passwordSuccess }}</p>

        <Button type="submit" variant="dark" size="md" block :disabled="passwordSubmitting">
          <KeyRound class="w-4 h-4" />
          {{ passwordSubmitting ? 'جارٍ التحديث...' : 'تغيير كلمة المرور' }}
        </Button>
      </form>
    </Card>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { Save, KeyRound } from 'lucide-vue-next'
import PageHeader from '@/components/PageHeader.vue'
import Card from '@/components/Card.vue'
import Button from '@/components/Button.vue'
import Badge from '@/components/Badge.vue'

const auth = useAuthStore()

const profileForm = ref({
  name: auth.user?.name || '',
  email: auth.user?.email || '',
})
const profileSubmitting = ref(false)
const profileError = ref('')
const profileSuccess = ref('')

const passwordForm = ref({ currentPassword: '', password: '', passwordConfirmation: '' })
const passwordSubmitting = ref(false)
const passwordError = ref('')
const passwordSuccess = ref('')

const initial = computed(() => (auth.user?.name || '؟').charAt(0))

const ROLE_LABELS = {
  super_admin: { label: 'مدير عام', color: 'purple' },
  admin: { label: 'مدير', color: 'blue' },
  b2b: { label: 'وكيل سفر', color: 'amber' },
}

const roleBadge = computed(() => ROLE_LABELS[auth.user?.user_type] || { label: auth.user?.user_type || '', color: 'slate' })

const handleProfileSubmit = async () => {
  profileSubmitting.value = true
  profileError.value = ''
  profileSuccess.value = ''
  const result = await auth.updateProfile(profileForm.value.name, profileForm.value.email)
  if (result.success) {
    profileSuccess.value = 'تم تحديث بياناتك بنجاح'
  } else {
    profileError.value = result.message
  }
  profileSubmitting.value = false
}

const handlePasswordSubmit = async () => {
  passwordSubmitting.value = true
  passwordError.value = ''
  passwordSuccess.value = ''
  const result = await auth.updatePassword(
    passwordForm.value.currentPassword,
    passwordForm.value.password,
    passwordForm.value.passwordConfirmation,
  )
  if (result.success) {
    passwordSuccess.value = result.message || 'تم تغيير كلمة المرور بنجاح'
    passwordForm.value = { currentPassword: '', password: '', passwordConfirmation: '' }
  } else {
    passwordError.value = result.message
  }
  passwordSubmitting.value = false
}
</script>

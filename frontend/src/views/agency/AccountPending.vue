<template>
  <div class="max-w-lg mx-auto p-4 md:p-6">
    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/80 text-center space-y-6">

      <!-- Rejected -->
      <template v-if="auth.agencyStatus === 'rejected'">
        <div class="w-14 h-14 bg-red-500 text-white rounded-2xl flex items-center justify-center mx-auto shadow-md">
          <XCircle class="w-7 h-7" />
        </div>
        <div class="space-y-1">
          <h2 class="text-xl font-black text-slate-900">تم رفض طلب تسجيل الوكالة</h2>
          <p class="text-xs text-slate-500">لم تتم الموافقة على حساب وكالتك في منصة برق</p>
        </div>
        <div v-if="auth.user?.agency_rejection_reason" class="bg-red-50 border border-red-200 text-red-700 text-xs font-semibold p-4 rounded-2xl text-right">
          <span class="block text-[10px] text-red-400 mb-1">سبب الرفض</span>
          {{ auth.user.agency_rejection_reason }}
        </div>
        <p class="text-xs text-slate-400">لأي استفسار، يرجى التواصل مع فريق الدعم.</p>
      </template>

      <!-- Not verified -->
      <template v-else-if="!auth.emailVerified">
        <div class="w-14 h-14 bg-[#0f172a] text-amber-400 rounded-2xl flex items-center justify-center mx-auto shadow-md">
          <MailCheck class="w-7 h-7" />
        </div>
        <div class="space-y-1">
          <h2 class="text-xl font-black text-slate-900">يرجى تفعيل بريدك الإلكتروني</h2>
          <p class="text-xs text-slate-500">
            أرسلنا رابط تفعيل إلى <span class="font-bold text-slate-700">{{ auth.user?.email }}</span>. افتح بريدك واضغط على رابط التفعيل لإكمال التسجيل.
          </p>
        </div>

        <div v-if="message" class="text-xs font-semibold p-3 rounded-2xl" :class="messageClass">{{ message }}</div>

        <button
          @click="resend"
          :disabled="resending"
          class="w-full bg-[#0f172a] hover:bg-[#1e293b] disabled:opacity-50 text-white font-bold py-3 rounded-2xl text-xs sm:text-sm transition shadow-md active:scale-[0.99]"
        >
          {{ resending ? 'جارٍ الإرسال...' : 'إعادة إرسال رابط التفعيل' }}
        </button>
      </template>

      <!-- Pending admin approval -->
      <template v-else>
        <div class="w-14 h-14 bg-amber-500 text-slate-900 rounded-2xl flex items-center justify-center mx-auto shadow-md">
          <Clock class="w-7 h-7" />
        </div>
        <div class="space-y-1">
          <h2 class="text-xl font-black text-slate-900">حسابك بانتظار موافقة الإدارة</h2>
          <p class="text-xs text-slate-500">تم تفعيل بريدك الإلكتروني بنجاح. سيقوم فريقنا بمراجعة طلبك والموافقة عليه قريباً، وستصلك رسالة عند اعتماد الحساب.</p>
        </div>
      </template>

      <button
        @click="logout"
        class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-3 rounded-2xl text-xs sm:text-sm transition"
      >
        تسجيل الخروج
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { MailCheck, Clock, XCircle } from 'lucide-vue-next'

const auth = useAuthStore()
const router = useRouter()

const resending = ref(false)
const message = ref('')
const messageClass = ref('')

const resend = async () => {
  resending.value = true
  message.value = ''
  const result = await auth.resendVerification()
  message.value = result.message
  messageClass.value = result.success ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'
  resending.value = false
}

const logout = async () => {
  await auth.logout()
  router.push('/login')
}
</script>

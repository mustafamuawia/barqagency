<template>
  <div class="bg-slate-100 text-slate-800 min-h-screen flex flex-col justify-between antialiased" dir="rtl" style="font-family: 'Cairo', sans-serif;">

    <div class="min-w-0">
      <!-- Header -->
      <header class="bg-[#0f172a] text-white py-2.5 sm:py-3 px-3 sm:px-4 md:px-8 flex items-center justify-between gap-2 shadow-md">
        <div class="flex items-center gap-2 min-w-0">
          <router-link to="/login" class="bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold px-3 sm:px-5 py-2 rounded-xl text-[11px] sm:text-sm border border-slate-700 transition whitespace-nowrap">
            تسجيل الدخول
          </router-link>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <div class="text-right min-w-0">
            <h1 class="text-base sm:text-2xl font-black tracking-wide text-amber-500 leading-tight whitespace-nowrap truncate">Barq Agency</h1>
            <p class="hidden sm:block text-[9px] text-slate-400 font-light -mt-1 whitespace-nowrap">B2B Travel & Tourism Portal</p>
          </div>
          <div class="bg-amber-500 text-slate-900 p-1.5 rounded-lg flex items-center justify-center shrink-0">
            <BarqLogo class="w-5 h-5 sm:w-6 sm:h-6" />
          </div>
        </div>
      </header>

      <!-- Main -->
      <main class="max-w-md mx-auto my-8 px-4">
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/80 text-center space-y-6">

          <!-- Icon Badge -->
          <div
            class="w-12 h-12 rounded-xl flex items-center justify-center mx-auto shadow-md"
            :class="success ? 'bg-emerald-500 text-white' : error ? 'bg-red-500 text-white' : 'bg-[#0f172a] text-amber-400'"
          >
            <CheckCircle v-if="success" class="w-6 h-6" />
            <XCircle v-else-if="error" class="w-6 h-6" />
            <MailCheck v-else class="w-6 h-6" />
          </div>

          <!-- Heading -->
          <div class="space-y-1">
            <h2 class="text-2xl font-black text-slate-900">تفعيل البريد الإلكتروني</h2>
            <p v-if="loading" class="text-xs text-slate-400">جارٍ التحقق من رابط التفعيل...</p>
          </div>

          <!-- Success -->
          <div v-if="success" class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold px-4 py-3 rounded-2xl text-right">
            {{ success }}
          </div>

          <!-- Error -->
          <div v-if="error" class="bg-red-50 border border-red-200 text-red-600 text-xs font-semibold px-4 py-3 rounded-2xl text-right">
            {{ error }}
          </div>

          <router-link
            :to="auth.isAuthenticated ? '/agency' : '/login'"
            class="block w-full bg-[#0f172a] hover:bg-[#1e293b] text-white font-bold py-3.5 rounded-2xl text-xs sm:text-sm transition shadow-md active:scale-[0.99]"
          >
            {{ auth.isAuthenticated ? 'الذهاب إلى حسابي' : 'الذهاب لتسجيل الدخول' }}
          </router-link>

        </div>
      </main>
    </div>

    <!-- Footer -->
    <footer class="text-center py-4 text-xs text-slate-400">
      <p>© 2026 Barq Agency. All rights reserved.</p>
    </footer>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { MailCheck, CheckCircle, XCircle } from 'lucide-vue-next'
import BarqLogo from '@/components/BarqLogo.vue'

const route = useRoute()
const auth = useAuthStore()

const loading = ref(true)
const error = ref('')
const success = ref('')

onMounted(async () => {
  const email = route.query.email || ''
  const token = route.query.token || ''

  if (!email || !token) {
    error.value = 'رابط التفعيل غير صالح.'
    loading.value = false
    return
  }

  const result = await auth.verifyEmail(email, token)

  if (result.success) {
    success.value = result.message
  } else {
    error.value = result.message
  }

  loading.value = false
})
</script>

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
          <div class="w-12 h-12 bg-[#0f172a] text-amber-400 rounded-xl flex items-center justify-center mx-auto shadow-md">
            <Mail class="w-6 h-6" />
          </div>

          <!-- Heading -->
          <div class="space-y-1">
            <h2 class="text-2xl font-black text-slate-900">نسيت كلمة المرور؟</h2>
            <p class="text-xs text-slate-400">أدخل بريدك الإلكتروني وسنرسل لك رابطاً لإعادة تعيين كلمة المرور</p>
          </div>

          <!-- Success -->
          <div v-if="success" class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold px-4 py-3 rounded-2xl text-right">
            {{ success }}
          </div>

          <!-- Error -->
          <div v-if="error" class="bg-red-50 border border-red-200 text-red-600 text-xs font-semibold px-4 py-3 rounded-2xl text-right">
            {{ error }}
          </div>

          <!-- Form -->
          <form v-if="!success" @submit.prevent="handleSubmit" class="space-y-4 text-right">
            <div class="space-y-1">
              <label class="block text-xs font-bold text-slate-600 mr-1">البريد الإلكتروني</label>
              <input
                v-model="email"
                type="email"
                required
                placeholder="admin@barqa.local"
                class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-xs sm:text-sm text-slate-800 font-medium focus:outline-none focus:ring-2 focus:ring-amber-500 focus:bg-white transition"
              />
            </div>

            <button
              type="submit"
              :disabled="loading"
              class="w-full bg-[#0f172a] hover:bg-[#1e293b] disabled:opacity-50 text-white font-bold py-3.5 rounded-2xl text-xs sm:text-sm transition shadow-md active:scale-[0.99] mt-2"
            >
              {{ loading ? 'جارٍ الإرسال...' : 'إرسال رابط إعادة التعيين' }}
            </button>
          </form>

          <router-link to="/login" class="block text-xs text-amber-600 font-bold hover:underline">
            العودة لتسجيل الدخول
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
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { Mail } from 'lucide-vue-next'
import BarqLogo from '@/components/BarqLogo.vue'

const auth = useAuthStore()

const loading = ref(false)
const error = ref('')
const success = ref('')
const email = ref('')

const handleSubmit = async () => {
  loading.value = true
  error.value = ''

  const result = await auth.forgotPassword(email.value)

  if (result.success) {
    success.value = result.message
  } else {
    error.value = result.message
  }

  loading.value = false
}
</script>

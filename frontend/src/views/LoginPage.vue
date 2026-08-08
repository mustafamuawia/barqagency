<template>
  <div class="bg-slate-100 text-slate-800 min-h-screen flex flex-col justify-between antialiased" dir="rtl" style="font-family: 'Cairo', sans-serif;">

    <div class="min-w-0">
      <!-- Header -->
      <header class="bg-[#0f172a] text-white py-2.5 sm:py-3 px-3 sm:px-4 md:px-8 flex items-center justify-between gap-2 shadow-md">
        <!-- Left: Action Buttons -->
        <div class="flex items-center gap-1.5 sm:gap-2 min-w-0">
          <router-link to="/register" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-extrabold px-3 sm:px-5 py-2 rounded-xl text-[11px] sm:text-sm transition shadow-sm whitespace-nowrap">
            إنشاء حساب مجاناً
          </router-link>
          <a href="#" class="bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold px-3 sm:px-5 py-2 rounded-xl text-[11px] sm:text-sm border border-slate-700 transition whitespace-nowrap">
            تسجيل الدخول
          </a>
        </div>

        <!-- Right: Logo -->
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
            <KeyRound class="w-6 h-6" />
          </div>

          <!-- Heading -->
          <div class="space-y-1">
            <h2 class="text-2xl font-black text-slate-900">تسجيل دخول الوكيل</h2>
            <p class="text-xs text-slate-400">يرجى تسجيل الدخول إلى منصة Barq Agency B2B لتتمكن من البحث والدفع</p>
          </div>

          <!-- Error -->
          <div v-if="error" class="bg-red-50 border border-red-200 text-red-600 text-xs font-semibold px-4 py-3 rounded-2xl text-right">
            {{ error }}
          </div>

          <!-- Form -->
          <form @submit.prevent="handleLogin" class="space-y-4 text-right">
            <div class="space-y-1">
              <label class="block text-xs font-bold text-slate-600 mr-1">البريد الإلكتروني للوكالة</label>
              <input
                v-model="form.email"
                type="email"
                required
                placeholder="admin@barqa.local"
                class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-xs sm:text-sm text-slate-800 font-medium focus:outline-none focus:ring-2 focus:ring-amber-500 focus:bg-white transition"
              />
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-bold text-slate-600 mr-1">كلمة المرور</label>
              <input
                v-model="form.password"
                type="password"
                required
                class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-xs sm:text-sm text-slate-800 font-medium tracking-widest focus:outline-none focus:ring-2 focus:ring-amber-500 focus:bg-white transition"
              />
            </div>

            <button
              type="submit"
              :disabled="loading"
              class="w-full bg-[#0f172a] hover:bg-[#1e293b] disabled:opacity-50 text-white font-bold py-3.5 rounded-2xl text-xs sm:text-sm transition shadow-md active:scale-[0.99] mt-2"
            >
              {{ loading ? 'جارٍ تسجيل الدخول...' : 'دخول لوحة التحكم B2B' }}
            </button>

            <router-link to="/forgot-password" class="block text-xs text-amber-600 font-bold hover:underline">
              نسيت كلمة المرور؟
            </router-link>
          </form>

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
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { KeyRound } from 'lucide-vue-next'
import BarqLogo from '@/components/BarqLogo.vue'

const router = useRouter()
const auth = useAuthStore()

const loading = ref(false)
const error = ref('')
const form = ref({ email: '', password: '' })

const handleLogin = async () => {
  loading.value = true
  error.value = ''

  const result = await auth.login(form.value.email, form.value.password)

  if (result.success) {
    if (auth.isAdmin) {
      router.push('/admin')
    } else if (auth.isAgency) {
      router.push('/agency')
    } else {
      error.value = 'لا توجد صلاحية وصول لهذا الحساب'
    }
  } else {
    error.value = result.message
  }

  loading.value = false
}
</script>

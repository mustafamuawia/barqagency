<template>
  <div class="bg-slate-100 text-slate-800 min-h-screen flex flex-col justify-between antialiased" dir="rtl" style="font-family: 'Cairo', sans-serif;">

    <div>
      <!-- Header -->
      <header class="bg-[#0f172a] text-white py-3 px-4 md:px-8 flex items-center justify-between shadow-md">
        <div class="flex items-center gap-2">
          <router-link to="/login" class="bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold px-5 py-2 rounded-xl text-xs sm:text-sm border border-slate-700 transition">
            تسجيل الدخول
          </router-link>
        </div>

        <div class="flex items-center gap-2">
          <div class="text-right">
            <h1 class="text-2xl font-black tracking-wide text-amber-500 leading-tight">Barq Agency</h1>
            <p class="text-[9px] text-slate-400 font-light -mt-1">B2B Travel & Tourism Portal</p>
          </div>
          <div class="bg-amber-500 text-slate-900 p-1.5 rounded-lg flex items-center justify-center">
            <PlaneTakeoff class="w-6 h-6" />
          </div>
        </div>
      </header>

      <!-- Main -->
      <main class="max-w-md mx-auto my-8 px-4">
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/80 text-center space-y-6">

          <!-- Icon Badge -->
          <div class="w-12 h-12 bg-[#0f172a] text-amber-400 rounded-xl flex items-center justify-center mx-auto shadow-md">
            <Lock class="w-6 h-6" />
          </div>

          <!-- Heading -->
          <div class="space-y-1">
            <h2 class="text-2xl font-black text-slate-900">إعادة تعيين كلمة المرور</h2>
            <p class="text-xs text-slate-400">أدخل كلمة مرور جديدة لحسابك</p>
          </div>

          <div v-if="!token || !email" class="bg-red-50 border border-red-200 text-red-600 text-xs font-semibold px-4 py-3 rounded-2xl text-right">
            رابط إعادة التعيين غير صالح. يرجى طلب رابط جديد.
          </div>

          <template v-else>
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
                <label class="block text-xs font-bold text-slate-600 mr-1">كلمة المرور الجديدة</label>
                <input
                  v-model="password"
                  type="password"
                  required
                  minlength="8"
                  class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-xs sm:text-sm text-slate-800 font-medium tracking-widest focus:outline-none focus:ring-2 focus:ring-amber-500 focus:bg-white transition"
                />
              </div>

              <div class="space-y-1">
                <label class="block text-xs font-bold text-slate-600 mr-1">تأكيد كلمة المرور</label>
                <input
                  v-model="passwordConfirmation"
                  type="password"
                  required
                  minlength="8"
                  class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-xs sm:text-sm text-slate-800 font-medium tracking-widest focus:outline-none focus:ring-2 focus:ring-amber-500 focus:bg-white transition"
                />
              </div>

              <button
                type="submit"
                :disabled="loading"
                class="w-full bg-[#0f172a] hover:bg-[#1e293b] disabled:opacity-50 text-white font-bold py-3.5 rounded-2xl text-xs sm:text-sm transition shadow-md active:scale-[0.99] mt-2"
              >
                {{ loading ? 'جارٍ الحفظ...' : 'حفظ كلمة المرور الجديدة' }}
              </button>
            </form>
          </template>

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
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { PlaneTakeoff, Lock } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const token = route.query.token || ''
const email = route.query.email || ''

const loading = ref(false)
const error = ref('')
const success = ref('')
const password = ref('')
const passwordConfirmation = ref('')

const handleSubmit = async () => {
  if (password.value !== passwordConfirmation.value) {
    error.value = 'كلمتا المرور غير متطابقتين'
    return
  }

  loading.value = true
  error.value = ''

  const result = await auth.resetPassword(token, email, password.value, passwordConfirmation.value)

  if (result.success) {
    success.value = result.message
    setTimeout(() => router.push('/login'), 1500)
  } else {
    error.value = result.message
  }

  loading.value = false
}
</script>

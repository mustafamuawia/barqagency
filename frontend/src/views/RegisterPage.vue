<template>
  <div class="bg-slate-100 text-slate-800 font-sans min-h-screen flex flex-col justify-between antialiased" dir="rtl">
    <div>
      <!-- Top Header Navigation -->
      <header class="bg-[#0f172a] text-white py-3 px-4 md:px-8 flex items-center justify-between shadow-md">
        <div class="flex items-center gap-2">
          <router-link to="/register" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-extrabold px-5 py-2 rounded-xl text-xs sm:text-sm transition shadow-sm">
            إنشاء حساب مجاناً
          </router-link>
          <router-link to="/login" class="bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold px-5 py-2 rounded-xl text-xs sm:text-sm border border-slate-700 transition">
            تسجيل الدخول
          </router-link>
        </div>

        <div class="flex items-center gap-2">
          <div class="text-right">
            <h1 class="text-2xl font-black tracking-wide text-amber-500 leading-tight">برق</h1>
            <p class="text-[9px] text-slate-400 font-light -mt-1">بوابة وكلاء B2B للسفر والسياحة</p>
          </div>
          <div class="bg-amber-500 text-slate-900 p-1.5 rounded-lg font-black text-xl flex items-center justify-center">
            <PlaneTakeoff class="w-6 h-6" />
          </div>
        </div>
      </header>

      <!-- Main Content Container -->
      <main class="max-w-md mx-auto my-8 px-4">
        <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border border-slate-200/80 text-center space-y-6">
          <div class="w-12 h-12 bg-[#0f172a] text-amber-400 rounded-xl flex items-center justify-center mx-auto shadow-md">
            <UserPlus class="w-6 h-6" />
          </div>

          <div class="space-y-1">
            <h2 class="text-2xl font-black text-slate-900">إنشاء حساب وكيل جديد</h2>
            <p class="text-xs text-slate-400">سجل مكتب السياحة أو شركتك واستمتع بأقوى عروض الربط مع دافيل</p>
          </div>

          <div v-if="errorMessage" class="p-3 rounded-2xl text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-300">
            {{ errorMessage }}
          </div>

          <div v-if="successMessage" class="p-3 rounded-2xl text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-300">
            {{ successMessage }}
          </div>

          <form @submit.prevent="handleRegister" class="space-y-4 text-right">
            <div class="space-y-1">
              <label class="block text-xs font-bold text-slate-600 mr-1">اسم مكتب السياحة / الاسم بالكامل</label>
              <input 
                v-model="form.agency_name"
                type="text" 
                placeholder="مكتب الرحالة لخدمات السفر"
                class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-xs sm:text-sm text-slate-800 font-medium placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:bg-white transition"
                required
              >
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-bold text-slate-600 mr-1">البريد الإلكتروني للشركة</label>
              <input 
                v-model="form.email"
                type="email" 
                placeholder="travel@example.com"
                class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-xs sm:text-sm text-slate-800 font-medium placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:bg-white transition"
                required
              >
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-bold text-slate-600 mr-1">كلمة المرور</label>
              <input 
                v-model="form.password"
                type="password" 
                placeholder="كلمة المرور (3 أحرف على الأقل)"
                class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-xs sm:text-sm text-slate-800 font-medium placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:bg-white transition"
                required
              >
            </div>

            <button 
              type="submit" 
              :disabled="loading"
              class="w-full bg-[#0f172a] hover:bg-[#1e293b] text-white font-bold py-3.5 rounded-2xl text-xs sm:text-sm transition shadow-md active:scale-[0.99] mt-2 flex items-center justify-center gap-2 disabled:opacity-50"
            >
              <span>{{ loading ? 'جاري التسجيل...' : 'تأكيد وبدء التجربة المباشرة' }}</span>
            </button>
          </form>
        </div>
      </main>
    </div>

    <footer class="text-center py-4 text-xs text-slate-400">
      <p>© 2026 برق بزنس. جميع الحقوق محفوظة.</p>
    </footer>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { PlaneTakeoff, UserPlus } from 'lucide-vue-next'
import api from '@/lib/api'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const form = reactive({
  agency_name: '',
  email: '',
  password: '',
})

const handleRegister = async () => {
  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await api.post('/register', form)
    
    if (response.data.token) {
      authStore.token = response.data.token
      authStore.user = response.data.user
      localStorage.setItem('auth_token', response.data.token)
    }

    successMessage.value = 'تم إنشاء حساب الوكالة بنجاح! يرجى تفعيل بريدك الإلكتروني عبر الرابط المرسل إليك لإكمال التسجيل.'

    setTimeout(() => {
      router.push('/agency')
    }, 1800)
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'حدث خطأ أثناء إكمال التسجيل.'
  } finally {
    loading.value = false
  }
}
</script>

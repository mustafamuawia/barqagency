<template>
  <div class="bg-slate-100 text-slate-800 min-h-screen flex flex-col justify-between antialiased" dir="rtl" style="font-family: 'Cairo', sans-serif;">

    <div>
      <!-- Top Header -->
      <header class="bg-[#0f172a] text-white py-3 px-4 md:px-8 flex items-center justify-between shadow-md">
        <div class="flex items-center gap-2">
          <router-link to="/login" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-extrabold px-5 py-2 rounded-xl text-xs sm:text-sm transition shadow-sm">
            تسجيل الدخول
          </router-link>
          <router-link to="/register" class="bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold px-4 py-2 rounded-xl text-xs sm:text-sm border border-slate-700 transition">
            إنشاء حساب وكالة
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

      <!-- Secondary anchor nav -->
      <nav class="bg-white border-b border-slate-200 px-4 md:px-8 py-2.5 flex items-center gap-6 text-xs md:text-sm font-semibold shadow-sm overflow-x-auto">
        <a href="#search" class="flex items-center gap-1.5 text-amber-600 border-b-2 border-amber-500 pb-1 font-bold whitespace-nowrap">
          <Search class="w-4 h-4" />
          البحث عن رحلات
        </a>
        <a href="#how-it-works" class="flex items-center gap-1.5 text-slate-600 hover:text-slate-900 transition whitespace-nowrap">
          <Workflow class="w-4 h-4" />
          كيف تعمل المنصة
        </a>
        <a href="#features" class="flex items-center gap-1.5 text-slate-600 hover:text-slate-900 transition whitespace-nowrap">
          <Sparkles class="w-4 h-4" />
          المميزات
        </a>
        <a href="#destinations" class="flex items-center gap-1.5 text-slate-600 hover:text-slate-900 transition whitespace-nowrap">
          <MapPin class="w-4 h-4" />
          الوجهات الرائجة
        </a>
      </nav>

      <!-- HERO SECTION -->
      <section class="max-w-6xl mx-auto px-4 md:px-6 pt-6 relative">

        <!-- Hero Banner -->
        <div class="relative bg-slate-950 text-white rounded-3xl overflow-hidden pt-12 pb-24 md:pb-32 px-6 text-center shadow-xl border border-slate-800">
          <div class="absolute inset-0 opacity-25 bg-[url('https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?auto=format&fit=crop&w=1200&q=80')] bg-cover bg-center mix-blend-luminosity"></div>

          <div class="relative z-10 max-w-3xl mx-auto space-y-3">
            <div class="inline-flex items-center gap-1.5 bg-amber-500/20 border border-amber-500/40 text-amber-400 text-[11px] font-extrabold px-3 py-1 rounded-full">
              <span>✈️ منصة Barq Agency لوكلاء السفر B2B</span>
            </div>
            <h2 class="text-2xl md:text-4xl font-black text-white leading-tight">
              إدارة حجوزات عملائك بثقة ومرونة مالية كاملة
            </h2>
            <p class="text-xs md:text-sm text-slate-300 max-w-xl mx-auto font-light leading-relaxed">
              أنشئ طلب حجز، استلم عرض سعر مخصصًا من فريقنا، ووافق عليه بدفع فوري من محفظتك الرقمية أو ضمن حد ائتمانك.
            </p>
            <div class="flex items-center justify-center gap-3 pt-2">
              <router-link to="/register" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-extrabold px-8 py-3 rounded-2xl text-sm transition shadow-md">
                ابدأ الآن كوكالة سفر
              </router-link>
              <router-link to="/login" class="bg-white/10 hover:bg-white/20 text-white font-bold px-8 py-3 rounded-2xl text-sm border border-white/20 transition">
                تسجيل الدخول
              </router-link>
            </div>
          </div>
        </div>

        <!-- Floating Flight Search Widget -->
        <div id="search" class="max-w-5xl mx-auto -mt-16 md:-mt-20 relative z-20 bg-white rounded-3xl p-5 md:p-7 shadow-xl border border-slate-200/80 space-y-5 scroll-mt-24">

          <!-- Trip Type Tabs -->
          <div class="flex items-center justify-start gap-2 bg-slate-100 p-1.5 rounded-2xl w-fit text-xs font-bold text-slate-600">
            <button
              type="button"
              @click="tripType = 'roundtrip'"
              class="px-4 py-2 rounded-xl transition"
              :class="tripType === 'roundtrip' ? 'bg-white text-slate-900 shadow-sm' : 'hover:text-slate-900'"
            >
              ذهاب وعودة
            </button>
            <button
              type="button"
              @click="tripType = 'oneway'"
              class="px-4 py-2 rounded-xl transition"
              :class="tripType === 'oneway' ? 'bg-white text-slate-900 shadow-sm' : 'hover:text-slate-900'"
            >
              ذهاب فقط
            </button>
            <button
              type="button"
              @click="tripType = 'multi'"
              class="px-4 py-2 rounded-xl transition"
              :class="tripType === 'multi' ? 'bg-white text-slate-900 shadow-sm' : 'hover:text-slate-900'"
            >
              وجهات متعددة
            </button>
          </div>

          <!-- Search Form Grid -->
          <form @submit.prevent="onSearch" class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">

            <!-- From -->
            <div class="md:col-span-3 space-y-1">
              <label class="block text-[11px] font-bold text-slate-500">من</label>
              <div class="relative">
                <input
                  v-model="searchForm.from"
                  type="text"
                  placeholder="مثال: CAI"
                  class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-xs md:text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500"
                >
                <PlaneTakeoff class="w-4 h-4 text-slate-400 absolute left-3 top-3.5" />
              </div>
            </div>

            <!-- Swap -->
            <div class="hidden md:flex md:col-span-1 justify-center pb-1">
              <button type="button" @click="swapAirports" class="p-2.5 bg-slate-100 hover:bg-slate-200 border border-slate-200 rounded-full text-slate-600 transition">
                <ArrowLeftRight class="w-4 h-4" />
              </button>
            </div>

            <!-- To -->
            <div class="md:col-span-3 space-y-1">
              <label class="block text-[11px] font-bold text-slate-500">إلى</label>
              <div class="relative">
                <input
                  v-model="searchForm.to"
                  type="text"
                  placeholder="مثال: JED"
                  class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3 text-xs md:text-sm font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500"
                >
                <PlaneLanding class="w-4 h-4 text-slate-400 absolute left-3 top-3.5" />
              </div>
            </div>

            <!-- Departure Date -->
            <div class="space-y-1" :class="tripType === 'roundtrip' ? 'md:col-span-2' : 'md:col-span-4'">
              <label class="block text-[11px] font-bold text-slate-500">تاريخ الذهاب</label>
              <div class="relative">
                <input
                  v-model="searchForm.departDate"
                  type="date"
                  class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-3 py-3 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500"
                >
                <Calendar class="w-4 h-4 text-slate-400 absolute left-3 top-3.5 pointer-events-none" />
              </div>
            </div>

            <!-- Return Date -->
            <div v-if="tripType === 'roundtrip'" class="md:col-span-2 space-y-1">
              <label class="block text-[11px] font-bold text-slate-500">تاريخ العودة</label>
              <div class="relative">
                <input
                  v-model="searchForm.returnDate"
                  type="date"
                  class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-3 py-3 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500"
                >
                <Calendar class="w-4 h-4 text-slate-400 absolute left-3 top-3.5 pointer-events-none" />
              </div>
            </div>

            <!-- Cabin & Passengers -->
            <div class="md:col-span-3 space-y-1">
              <label class="block text-[11px] font-bold text-slate-500">الدرجة والركاب</label>
              <select
                v-model="searchForm.cabinPassengers"
                class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-3 py-3 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500"
              >
                <option value="1-economy">1 مسافر / سياحية</option>
                <option value="2-economy">2 مسافر / سياحية</option>
                <option value="1-business">1 مسافر / درجة الأعمال</option>
              </select>
            </div>

            <!-- Search Button -->
            <div class="md:col-span-9">
              <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-slate-950 font-black py-3.5 rounded-2xl text-xs md:text-sm flex items-center justify-center gap-2 transition shadow-md">
                <Search class="w-5 h-5" />
                ابحث عن رحلات
              </button>
            </div>
          </form>

          <div class="bg-slate-50 border border-slate-200/80 rounded-2xl p-3 text-center text-[11px] text-slate-600">
            سجّل وكالتك أو ادخل إلى حسابك لإتمام الحجز الفعلي بعد اختيار العرض المناسب
          </div>

          <!-- Search Results -->
          <div v-if="searched" class="space-y-3 pt-2 border-t border-slate-100">
            <div v-if="searching" class="text-center py-6 text-xs text-slate-400 flex items-center justify-center gap-2">
              <Loader2 class="w-4 h-4 animate-spin" />
              جارٍ البحث عن أفضل العروض...
            </div>

            <div v-else-if="searchError" class="p-3 rounded-xl text-xs font-semibold bg-red-100 text-red-700 text-center">
              {{ searchError }}
            </div>

            <div v-else-if="!results.length" class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 text-center text-xs text-slate-500">
              لا توجد عروض متاحة على هذا المسار حاليًا، جرّب مسارًا آخر مثل CAI → JED أو KRT → DXB
            </div>

            <div v-else class="space-y-2">
              <p class="text-[11px] font-bold text-slate-500">{{ results.length }} عرض متاح</p>
              <div
                v-for="r in results"
                :key="r.id"
                class="flex flex-wrap items-center justify-between gap-3 bg-slate-50 border border-slate-200/80 rounded-2xl p-4"
              >
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center shrink-0">
                    <PlaneTakeoff class="w-5 h-5" />
                  </div>
                  <div>
                    <p class="font-bold text-sm text-slate-900">{{ r.airline }}</p>
                    <p class="text-[11px] text-slate-500 font-mono">
                      {{ r.origin }} → {{ r.destination }} · {{ r.cabin_class }}<span v-if="r.duration_minutes"> · {{ formatDuration(r.duration_minutes) }}</span>
                    </p>
                  </div>
                </div>
                <div class="flex items-center gap-3">
                  <div class="text-left">
                    <p class="text-lg font-black text-emerald-600 font-mono">{{ formatPrice(r.total_price, r.currency) }}</p>
                    <p class="text-[10px] text-slate-400">{{ r.passengers }} مسافر × {{ formatPrice(r.unit_price, r.currency) }}</p>
                  </div>
                  <router-link to="/register" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-4 py-2 rounded-xl text-xs whitespace-nowrap transition">
                    احجز الآن
                  </router-link>
                </div>
              </div>
            </div>
          </div>
        </div>

      </section>

      <!-- HOW IT WORKS -->
      <section id="how-it-works" class="max-w-6xl mx-auto px-4 md:px-6 pt-16 pb-6 scroll-mt-24">
        <div class="text-center mb-6">
          <h3 class="text-lg font-extrabold text-slate-900">كيف تعمل المنصة؟</h3>
          <p class="text-xs text-slate-400">ثلاث خطوات بسيطة من الطلب حتى تأكيد الحجز</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="bg-white border border-slate-200 rounded-2xl p-5 text-center space-y-2 shadow-sm">
            <div class="w-11 h-11 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center mx-auto font-black">١</div>
            <h4 class="font-bold text-sm text-slate-900 flex items-center justify-center gap-1.5">
              <FileText class="w-4 h-4 text-amber-600" />
              أنشئ طلب حجز
            </h4>
            <p class="text-xs text-slate-500">أدخل بيانات رحلة عميلك وعدد المسافرين خلال دقائق</p>
          </div>
          <div class="bg-white border border-slate-200 rounded-2xl p-5 text-center space-y-2 shadow-sm">
            <div class="w-11 h-11 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center mx-auto font-black">٢</div>
            <h4 class="font-bold text-sm text-slate-900 flex items-center justify-center gap-1.5">
              <BadgeDollarSign class="w-4 h-4 text-blue-600" />
              استلم عرض السعر
            </h4>
            <p class="text-xs text-slate-500">يقوم فريقنا بتسعير الطلب ويصلك عرض السعر مباشرة على حسابك</p>
          </div>
          <div class="bg-white border border-slate-200 rounded-2xl p-5 text-center space-y-2 shadow-sm">
            <div class="w-11 h-11 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center mx-auto font-black">٣</div>
            <h4 class="font-bold text-sm text-slate-900 flex items-center justify-center gap-1.5">
              <CheckCircle2 class="w-4 h-4 text-emerald-600" />
              وافق وادفع من المحفظة
            </h4>
            <p class="text-xs text-slate-500">اعتمد العرض ويُخصم المبلغ تلقائيًا من رصيدك أو حد الائتمان المتاح</p>
          </div>
        </div>
      </section>

      <!-- FEATURES -->
      <section id="features" class="max-w-6xl mx-auto px-4 md:px-6 pt-10 pb-6 scroll-mt-20">
        <div class="text-center mb-6">
          <h3 class="text-lg font-extrabold text-slate-900">لماذا Barq Agency؟</h3>
          <p class="text-xs text-slate-400">كل ما تحتاجه وكالتك لإدارة الحجوزات والماليات في مكان واحد</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div class="bg-white rounded-3xl p-5 md:p-6 shadow-sm border border-slate-200 text-center space-y-3">
            <div class="w-11 h-11 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center mx-auto">
              <ClipboardList class="w-5 h-5" />
            </div>
            <h4 class="font-bold text-sm text-slate-900">إدارة الطلبات</h4>
            <p class="text-xs text-slate-500">أنشئ طلب حجز واستلم عرض السعر من الإدارة خلال دقائق</p>
          </div>
          <div class="bg-white rounded-3xl p-5 md:p-6 shadow-sm border border-slate-200 text-center space-y-3">
            <div class="w-11 h-11 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center mx-auto">
              <Wallet class="w-5 h-5" />
            </div>
            <h4 class="font-bold text-sm text-slate-900">محفظة رقمية</h4>
            <p class="text-xs text-slate-500">تتبع رصيدك، حد الائتمان، وسجل معاملاتك المالية أولاً بأول</p>
          </div>
          <div class="bg-white rounded-3xl p-5 md:p-6 shadow-sm border border-slate-200 text-center space-y-3">
            <div class="w-11 h-11 bg-red-100 text-red-600 rounded-xl flex items-center justify-center mx-auto">
              <RotateCcw class="w-5 h-5" />
            </div>
            <h4 class="font-bold text-sm text-slate-900">استرداد سهل</h4>
            <p class="text-xs text-slate-500">اطلب استرداد أي حجز مؤكد وتابع حالته حتى إضافة المبلغ لمحفظتك</p>
          </div>
          <div class="bg-white rounded-3xl p-5 md:p-6 shadow-sm border border-slate-200 text-center space-y-3">
            <div class="w-11 h-11 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center mx-auto">
              <ShieldCheck class="w-5 h-5" />
            </div>
            <h4 class="font-bold text-sm text-slate-900">أمان وموثوقية</h4>
            <p class="text-xs text-slate-500">تشفير للبيانات الحساسة وسجل تدقيق كامل لكل عملية على حسابك</p>
          </div>
        </div>
      </section>

      <!-- DESTINATIONS -->
      <section id="destinations" class="max-w-6xl mx-auto px-4 md:px-6 py-10 space-y-4 scroll-mt-20">
        <div class="text-center">
          <h3 class="text-lg font-extrabold text-slate-900">وجهات رائجة لعملائك</h3>
          <p class="text-xs text-slate-400">من أكثر الوجهات طلبًا عبر وكالات Barq Agency</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
          <div v-for="dest in destinations" :key="dest.name" class="group relative rounded-3xl overflow-hidden h-72 shadow-md cursor-pointer border border-slate-200">
            <img :src="dest.image" :alt="dest.name" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-slate-950/20 to-transparent"></div>
            <div class="absolute bottom-4 right-4 text-white">
              <h4 class="text-lg font-black">{{ dest.name }}</h4>
              <p class="text-[11px] text-amber-400 font-bold">وجهة مميزة</p>
            </div>
          </div>
        </div>
      </section>
    </div>

    <!-- FOOTER -->
    <footer class="bg-[#0b132b] text-slate-300 text-xs border-t border-slate-800 pt-12 pb-6 mt-6">
      <div class="max-w-6xl mx-auto px-4 md:px-6 grid grid-cols-1 md:grid-cols-4 gap-8 pb-10 border-b border-slate-800/80">

        <!-- Brand -->
        <div class="space-y-3">
          <div class="flex items-center gap-2">
            <div class="bg-amber-500 text-slate-900 p-1.5 rounded-lg flex items-center justify-center">
              <PlaneTakeoff class="w-5 h-5" />
            </div>
            <h3 class="text-xl font-black text-amber-500">Barq Agency</h3>
          </div>
          <p class="text-slate-400 text-xs leading-relaxed">
            بوابة B2B لوكلاء السفر لإدارة طلبات الحجز، عروض الأسعار، والمحفظة الرقمية في مكان واحد.
          </p>
        </div>

        <!-- Quick Links -->
        <div class="space-y-3">
          <h4 class="font-bold text-white text-sm">روابط سريعة</h4>
          <ul class="space-y-2 text-slate-400">
            <li><router-link to="/login" class="hover:text-amber-400 transition">تسجيل الدخول</router-link></li>
            <li><router-link to="/register" class="hover:text-amber-400 transition">إنشاء حساب وكالة</router-link></li>
            <li><a href="#how-it-works" class="hover:text-amber-400 transition">كيف تعمل المنصة</a></li>
            <li><a href="#features" class="hover:text-amber-400 transition">المميزات</a></li>
          </ul>
        </div>

        <!-- Support -->
        <div class="space-y-3">
          <h4 class="font-bold text-white text-sm">الدعم والمساعدة</h4>
          <ul class="space-y-2 text-slate-400">
            <li><router-link to="/forgot-password" class="hover:text-amber-400 transition">استعادة كلمة المرور</router-link></li>
            <li><a href="#" class="hover:text-amber-400 transition">سياسة الاسترجاع والإلغاء</a></li>
            <li><a href="#" class="hover:text-amber-400 transition">الشروط والأحكام</a></li>
            <li><a href="#" class="hover:text-amber-400 transition">سياسة الخصوصية</a></li>
          </ul>
        </div>

        <!-- Contact -->
        <div class="space-y-3">
          <h4 class="font-bold text-white text-sm">تواصل معنا</h4>
          <ul class="space-y-2 text-slate-400">
            <li class="flex items-center gap-2">
              <Mail class="w-4 h-4 text-amber-500" />
              <span class="font-mono">support@barqa.local</span>
            </li>
            <li class="flex items-center gap-2">
              <Clock class="w-4 h-4 text-amber-500" />
              <span>الدعم متاح لجميع الوكالات المسجلة</span>
            </li>
          </ul>
        </div>

      </div>

      <div class="max-w-6xl mx-auto px-4 md:px-6 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-[11px] text-slate-500">
        <p>© 2026 Barq Agency. All rights reserved.</p>
      </div>
    </footer>

  </div>
</template>

<script setup>
import { ref } from 'vue'
import api from '@/lib/api'
import {
  PlaneTakeoff, PlaneLanding, ClipboardList, Wallet, RotateCcw, ShieldCheck,
  Workflow, Sparkles, MapPin, FileText, BadgeDollarSign, CheckCircle2,
  Mail, Clock, Search, ArrowLeftRight, Calendar, Loader2,
} from 'lucide-vue-next'

const destinations = [
  { name: 'جدة', image: 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?auto=format&fit=crop&w=600&q=80' },
  { name: 'دبي', image: 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?auto=format&fit=crop&w=600&q=80' },
  { name: 'إسطنبول', image: 'https://images.unsplash.com/photo-1524231757912-21f4fe3a7200?auto=format&fit=crop&w=600&q=80' },
  { name: 'القاهرة', image: 'https://images.unsplash.com/photo-1572252821128-d762e5b8d2bb?auto=format&fit=crop&w=600&q=80' },
]

const tripType = ref('roundtrip')
const searchForm = ref({
  from: '',
  to: '',
  departDate: '',
  returnDate: '',
  cabinPassengers: '1-economy',
})

const swapAirports = () => {
  const from = searchForm.value.from
  searchForm.value.from = searchForm.value.to
  searchForm.value.to = from
}

const searching = ref(false)
const searched = ref(false)
const searchError = ref('')
const results = ref([])

const onSearch = async () => {
  const origin = searchForm.value.from.trim().toUpperCase()
  const destination = searchForm.value.to.trim().toUpperCase()

  searchError.value = ''
  if (origin.length !== 3 || destination.length !== 3) {
    searched.value = true
    results.value = []
    searchError.value = 'يرجى إدخال رمز مطار صحيح مكوّن من 3 أحرف لكل من نقطة الانطلاق والوصول (مثال: CAI)'
    return
  }
  if (origin === destination) {
    searched.value = true
    results.value = []
    searchError.value = 'يجب أن تختلف نقطة الانطلاق عن نقطة الوصول'
    return
  }

  const [passengers, cabin] = searchForm.value.cabinPassengers.split('-')

  searching.value = true
  searched.value = true
  try {
    const response = await api.get('/flight-search', {
      params: {
        origin,
        destination,
        trip_type: tripType.value,
        cabin,
        passengers: Number(passengers),
      },
    })
    results.value = response.data.results
  } catch (err) {
    searchError.value = err.response?.data?.message || 'تعذر إتمام البحث، يرجى المحاولة مرة أخرى'
    results.value = []
  } finally {
    searching.value = false
  }
}

const formatPrice = (value, currency = 'USD') =>
  new Intl.NumberFormat('en-US', { style: 'currency', currency: currency || 'USD' }).format(value || 0)

const formatDuration = (minutes) => {
  const h = Math.floor(minutes / 60)
  const m = minutes % 60
  return `${h}س ${m}د`
}
</script>

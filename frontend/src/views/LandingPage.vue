<template>
  <div class="bg-slate-100 text-slate-800 min-h-screen flex flex-col justify-between antialiased" dir="rtl" style="font-family: 'Cairo', sans-serif;">

    <div class="min-w-0">
      <!-- Top Header -->
      <header class="bg-[#0f172a] text-white py-2.5 sm:py-3 px-3 sm:px-4 md:px-8 flex items-center justify-between gap-2 shadow-md">
        <div class="flex items-center gap-1.5 sm:gap-2 min-w-0">
          <span class="hidden sm:block text-slate-300 text-xs font-semibold truncate max-w-[160px]">{{ authStore.user?.name }}</span>
          <button @click="logout" class="bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold px-2.5 sm:px-4 py-2 rounded-xl text-[11px] sm:text-sm border border-slate-700 transition whitespace-nowrap">
            تسجيل الخروج
          </button>
          <router-link to="/agency" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-extrabold px-3 sm:px-5 py-2 rounded-xl text-[11px] sm:text-sm transition shadow-sm whitespace-nowrap">
            لوحة التحكم
          </router-link>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <div class="text-right min-w-0">
            <h1 class="text-base sm:text-2xl font-black tracking-wide text-amber-500 leading-tight whitespace-nowrap truncate">برق</h1>
            <p class="hidden sm:block text-[9px] text-slate-400 font-light -mt-1 whitespace-nowrap">B2B Travel & Tourism Portal</p>
          </div>
          <div class="bg-amber-500 text-slate-900 p-1.5 rounded-lg flex items-center justify-center shrink-0">
            <BarqLogo class="w-5 h-5 sm:w-6 sm:h-6" />
          </div>
        </div>
      </header>

      <!-- Secondary nav: segmented pill style (matches the trip-type control used inside the app) -->
      <nav class="bg-white border-b border-slate-200 px-4 md:px-8 py-2.5 flex items-center justify-center">
        <div class="flex items-center flex-wrap sm:flex-nowrap justify-center gap-1 bg-slate-100 p-1.5 rounded-2xl text-[11px] md:text-xs font-bold text-slate-600 max-w-full">
          <a href="#search" class="flex items-center gap-1.5 px-3 sm:px-3.5 py-2 rounded-xl bg-white text-amber-600 shadow-sm whitespace-nowrap">
            <Search class="w-3.5 h-3.5" />
            البحث عن رحلات
          </a>
          <a href="#how-it-works" class="flex items-center gap-1.5 px-3 sm:px-3.5 py-2 rounded-xl hover:text-slate-900 transition whitespace-nowrap">
            <Workflow class="w-3.5 h-3.5" />
            كيف تعمل المنصة
          </a>
          <a href="#features" class="flex items-center gap-1.5 px-3 sm:px-3.5 py-2 rounded-xl hover:text-slate-900 transition whitespace-nowrap">
            <Sparkles class="w-3.5 h-3.5" />
            المميزات
          </a>
          <a href="#destinations" class="flex items-center gap-1.5 px-3 sm:px-3.5 py-2 rounded-xl hover:text-slate-900 transition whitespace-nowrap">
            <MapPin class="w-3.5 h-3.5" />
            الوجهات الرائجة
          </a>
        </div>
      </nav>

      <!-- HERO SECTION: split layout, branded flight-path graphic instead of a stock photo -->
      <section class="max-w-6xl mx-auto px-4 md:px-6 pt-10 pb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-center">

          <!-- Copy + CTA -->
          <div class="space-y-4 text-center md:text-right">
            <div class="inline-flex items-center gap-1.5 bg-amber-100 border border-amber-200 text-amber-700 text-[11px] font-extrabold px-3 py-1 rounded-full">
              منصة B2B مخصصة لوكلاء السفر
            </div>
            <h2 class="text-2xl md:text-4xl font-black text-slate-900 leading-tight">
              إدارة حجوزات عملائك بثقة ومرونة مالية كاملة
            </h2>
            <p class="text-xs md:text-sm text-slate-500 max-w-md mx-auto md:mx-0 font-light leading-relaxed">
              أنشئ طلب حجز، استلم عرض سعر مخصصًا من فريقنا، ووافق عليه بدفع فوري من محفظتك الرقمية أو ضمن حد ائتمانك.
            </p>
            <div class="flex items-center justify-center md:justify-start gap-3 pt-2">
              <router-link to="/agency/orders/new" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-extrabold px-7 py-3 rounded-2xl text-sm transition shadow-md">
                طلب حجز جديد
              </router-link>
              <a href="#search" class="bg-slate-900 hover:bg-slate-800 text-white font-bold px-7 py-3 rounded-2xl text-sm transition">
                ابحث عن رحلة
              </a>
            </div>
          </div>

          <!-- Branded decorative panel -->
          <div class="relative bg-slate-950 rounded-3xl overflow-hidden h-64 md:h-72 shadow-xl border border-slate-800">
            <div class="absolute inset-0 opacity-[0.15]" style="background-image: radial-gradient(circle at 1px 1px, #f59e0b 1px, transparent 0); background-size: 22px 22px;"></div>

            <svg class="absolute inset-0 w-full h-full" viewBox="0 0 400 300" fill="none" preserveAspectRatio="none">
              <path d="M50 250 C 130 220, 170 120, 300 70" stroke="#f59e0b" stroke-width="2" stroke-dasharray="6 6" opacity="0.6" />
              <circle cx="50" cy="250" r="5" fill="#f59e0b" opacity="0.9" />
            </svg>
            <PlaneTakeoff class="w-6 h-6 text-amber-400 absolute rotate-[-35deg]" style="top: 52px; left: 275px;" />

            <div class="absolute top-4 right-4 bg-white/10 backdrop-blur border border-white/15 rounded-2xl px-3 py-2 flex items-center gap-2 text-[11px] text-white font-bold">
              <BadgeDollarSign class="w-4 h-4 text-amber-400" />
              تسعير فوري من فريقنا
            </div>
            <div class="absolute bottom-16 left-4 bg-white/10 backdrop-blur border border-white/15 rounded-2xl px-3 py-2 flex items-center gap-2 text-[11px] text-white font-bold">
              <Wallet class="w-4 h-4 text-amber-400" />
              دفع من المحفظة أو حد الائتمان
            </div>
            <div class="absolute bottom-4 right-4 bg-white/10 backdrop-blur border border-white/15 rounded-2xl px-3 py-2 flex items-center gap-2 text-[11px] text-white font-bold">
              <ShieldCheck class="w-4 h-4 text-amber-400" />
              تشفير وسجل تدقيق كامل
            </div>
          </div>

        </div>
      </section>

      <!-- FLIGHT SEARCH: boarding-pass styled card -->
      <section id="search" class="max-w-5xl mx-auto px-4 md:px-6 pt-4 pb-10 scroll-mt-24">
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 overflow-hidden">

          <!-- Ticket header -->
          <div class="bg-[#0f172a] px-5 md:px-7 py-4 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
              <div class="w-9 h-9 bg-amber-500 text-slate-900 rounded-xl flex items-center justify-center shrink-0">
                <Search class="w-4 h-4" />
              </div>
              <div>
                <h3 class="text-sm font-extrabold text-white">ابحث عن رحلة</h3>
                <p class="text-[11px] text-slate-400">أسعار حية، وحجز فعلي بعد تسجيل الدخول كوكالة</p>
              </div>
            </div>

            <!-- Trip Type Tabs -->
            <div class="flex items-center gap-1 bg-white/10 p-1 rounded-2xl text-[11px] font-bold text-slate-300">
              <button
                type="button"
                @click="tripType = 'roundtrip'"
                class="px-3.5 py-2 rounded-xl transition"
                :class="tripType === 'roundtrip' ? 'bg-amber-500 text-slate-950 shadow-sm' : 'hover:text-white'"
              >
                ذهاب وعودة
              </button>
              <button
                type="button"
                @click="tripType = 'oneway'"
                class="px-3.5 py-2 rounded-xl transition"
                :class="tripType === 'oneway' ? 'bg-amber-500 text-slate-950 shadow-sm' : 'hover:text-white'"
              >
                ذهاب فقط
              </button>
              <button
                type="button"
                @click="tripType = 'multi'"
                class="px-3.5 py-2 rounded-xl transition"
                :class="tripType === 'multi' ? 'bg-amber-500 text-slate-950 shadow-sm' : 'hover:text-white'"
              >
                وجهات متعددة
              </button>
            </div>
          </div>

          <form @submit.prevent="onSearch" class="p-5 md:p-7 space-y-4">

            <!-- Route strip -->
            <div class="relative bg-slate-50 border border-slate-200/80 rounded-2xl px-4 md:px-6 py-4 flex items-center gap-3">
              <div class="flex-1 min-w-0">
                <label class="block text-[10px] font-bold text-slate-400 mb-0.5">من</label>
                <input
                  v-model="searchForm.from"
                  type="text"
                  placeholder="CAI"
                  class="w-full bg-transparent text-base md:text-xl font-black text-slate-900 uppercase tracking-wide focus:outline-none placeholder:text-slate-300"
                >
              </div>

              <button type="button" @click="swapAirports" class="shrink-0 w-9 h-9 rounded-full bg-white border border-slate-200 text-amber-600 flex items-center justify-center shadow-sm hover:bg-amber-50 transition">
                <ArrowLeftRight class="w-4 h-4" />
              </button>

              <div class="flex-1 min-w-0 text-left">
                <label class="block text-[10px] font-bold text-slate-400 mb-0.5">إلى</label>
                <input
                  v-model="searchForm.to"
                  type="text"
                  placeholder="JED"
                  class="w-full bg-transparent text-base md:text-xl font-black text-slate-900 uppercase tracking-wide text-left focus:outline-none placeholder:text-slate-300"
                >
              </div>
            </div>

            <!-- Perforated divider -->
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-slate-100 border border-slate-200 shrink-0"></span>
              <div class="flex-1 border-t border-dashed border-slate-200"></div>
              <span class="w-2.5 h-2.5 rounded-full bg-slate-100 border border-slate-200 shrink-0"></span>
            </div>

            <!-- Dates, cabin & submit -->
            <div class="flex flex-wrap items-end gap-3">
              <div class="flex-1 min-w-[130px] space-y-1">
                <label class="block text-[11px] font-bold text-slate-500">تاريخ الذهاب</label>
                <input
                  v-model="searchForm.departDate"
                  type="date"
                  :min="todayStr()"
                  class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-3 py-3 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500"
                >
              </div>

              <div v-if="tripType === 'roundtrip'" class="flex-1 min-w-[130px] space-y-1">
                <label class="block text-[11px] font-bold text-slate-500">تاريخ العودة</label>
                <input
                  v-model="searchForm.returnDate"
                  type="date"
                  :min="searchForm.departDate"
                  class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-3 py-3 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500"
                >
              </div>

              <div class="flex-1 min-w-[160px] space-y-1">
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

              <div class="w-full md:w-auto">
                <button type="submit" class="w-full md:w-auto bg-amber-500 hover:bg-amber-600 text-slate-950 font-black px-8 py-3.5 rounded-2xl text-xs md:text-sm flex items-center justify-center gap-2 transition shadow-md whitespace-nowrap">
                  <Search class="w-5 h-5" />
                  ابحث عن رحلات
                </button>
              </div>
            </div>
          </form>

          <div class="bg-slate-50 border-t border-slate-200/80 px-5 md:px-7 py-3 text-center text-[11px] text-slate-600">
            سجّل وكالتك أو ادخل إلى حسابك لإتمام الحجز الفعلي بعد اختيار العرض المناسب
          </div>

          <!-- Search Results -->
          <div v-if="searched" class="px-5 md:px-7 pb-5 md:pb-7 space-y-3 pt-2 border-t border-slate-100">
            <div v-if="searching" class="space-y-2">
              <div
                v-for="i in 3"
                :key="i"
                class="flex flex-wrap items-center justify-between gap-3 bg-slate-50 border border-slate-200/80 rounded-2xl p-4 animate-pulse"
              >
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 bg-slate-200 rounded-xl shrink-0"></div>
                  <div class="space-y-2">
                    <div class="h-3.5 bg-slate-200 rounded-full" style="width: 7rem"></div>
                    <div class="h-2.5 bg-slate-200/70 rounded-full" style="width: 9rem"></div>
                  </div>
                </div>
                <div class="space-y-2">
                  <div class="h-4 bg-slate-200 rounded-full ms-auto" style="width: 5rem"></div>
                  <div class="h-2.5 bg-slate-200/70 rounded-full ms-auto" style="width: 6rem"></div>
                </div>
              </div>
            </div>

            <div v-else-if="searchError" class="p-3 rounded-xl text-xs font-semibold bg-red-100 text-red-700 text-center">
              {{ searchError }}
            </div>

            <div v-else-if="!results.length" class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 text-center text-xs text-slate-500">
              لا توجد عروض متاحة على هذا المسار حاليًا، جرّب مسارًا آخر مثل CAI → JED أو KRT → DXB
            </div>

            <div v-else class="space-y-2">
              <div
                v-if="expiredBanner"
                class="flex items-center justify-between gap-2 bg-amber-50 border border-amber-300 rounded-xl px-4 py-2.5 text-xs"
              >
                <span class="text-amber-800 font-semibold">⚠️ انتهت صلاحية العرض السابق — تم تحديث النتائج بأحدث الأسعار المتاحة</span>
                <button @click="expiredBanner = false" class="text-amber-500 hover:text-amber-700 font-bold shrink-0">✕</button>
              </div>
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
                    <p class="text-lg font-black text-emerald-600 font-mono">{{ formatPoints(r.total_price_sar) }}</p>
                    <template v-if="rate.agency_commission_amount > 0">
                      <p class="text-[10px] text-sky-600 font-bold font-mono">+ {{ formatPoints(rate.agency_commission_amount) }} عمولة</p>
                      <p class="text-[11px] font-black text-slate-700 font-mono">= {{ formatPoints(r.total_price_sar + rate.agency_commission_amount) }} للعميل</p>
                    </template>
                    <p class="text-[10px] text-slate-400">{{ r.passengers }} مسافر × {{ formatPoints(r.unit_price_sar) }}</p>
                    <p class="text-[10px] text-slate-400 font-mono">{{ r.duffel_total_amount }} {{ r.duffel_total_currency }}</p>
                  </div>
                  <button
                    @click="openBooking(r)"
                    class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-4 py-2 rounded-xl text-xs whitespace-nowrap transition"
                  >
                    احجز الآن
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- HOW IT WORKS: connected stepper -->
      <section id="how-it-works" class="max-w-6xl mx-auto px-4 md:px-6 pt-10 pb-6 scroll-mt-24">
        <div class="text-center mb-10">
          <h3 class="text-lg font-extrabold text-slate-900">كيف تعمل المنصة؟</h3>
          <p class="text-xs text-slate-400">ثلاث خطوات بسيطة من الطلب حتى تأكيد الحجز</p>
        </div>

        <div class="relative">
          <div class="hidden md:block absolute top-6 right-[16.5%] left-[16.5%] h-0.5 bg-slate-200"></div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 relative">
            <div class="text-center space-y-3">
              <div class="w-12 h-12 bg-amber-500 text-white rounded-full flex items-center justify-center mx-auto font-black shadow-md relative z-10">١</div>
              <h4 class="font-bold text-sm text-slate-900 flex items-center justify-center gap-1.5">
                <FileText class="w-4 h-4 text-amber-600" />
                أنشئ طلب حجز
              </h4>
              <p class="text-xs text-slate-500 max-w-[220px] mx-auto">أدخل بيانات رحلة عميلك وعدد المسافرين خلال دقائق</p>
            </div>
            <div class="text-center space-y-3">
              <div class="w-12 h-12 bg-blue-500 text-white rounded-full flex items-center justify-center mx-auto font-black shadow-md relative z-10">٢</div>
              <h4 class="font-bold text-sm text-slate-900 flex items-center justify-center gap-1.5">
                <BadgeDollarSign class="w-4 h-4 text-blue-600" />
                استلم عرض السعر
              </h4>
              <p class="text-xs text-slate-500 max-w-[220px] mx-auto">يقوم فريقنا بتسعير الطلب ويصلك عرض السعر مباشرة على حسابك</p>
            </div>
            <div class="text-center space-y-3">
              <div class="w-12 h-12 bg-emerald-500 text-white rounded-full flex items-center justify-center mx-auto font-black shadow-md relative z-10">٣</div>
              <h4 class="font-bold text-sm text-slate-900 flex items-center justify-center gap-1.5">
                <CheckCircle2 class="w-4 h-4 text-emerald-600" />
                وافق وادفع من المحفظة
              </h4>
              <p class="text-xs text-slate-500 max-w-[220px] mx-auto">اعتمد العرض ويُخصم المبلغ تلقائيًا من رصيدك أو حد الائتمان المتاح</p>
            </div>
          </div>
        </div>
      </section>

      <!-- FEATURES: bento layout, one highlighted card + three supporting cards -->
      <section id="features" class="max-w-6xl mx-auto px-4 md:px-6 pt-10 pb-6 scroll-mt-20">
        <div class="text-center mb-6">
          <h3 class="text-lg font-extrabold text-slate-900">لماذا برق؟</h3>
          <p class="text-xs text-slate-400">كل ما تحتاجه وكالتك لإدارة الحجوزات والماليات في مكان واحد</p>
        </div>

        <div class="space-y-4">
          <div class="bg-[#0f172a] rounded-3xl p-6 md:p-8 shadow-sm flex flex-col md:flex-row items-center gap-6">
            <div class="w-14 h-14 bg-amber-500 text-slate-900 rounded-2xl flex items-center justify-center shrink-0">
              <Wallet class="w-7 h-7" />
            </div>
            <div class="text-center md:text-right flex-1">
              <h4 class="font-extrabold text-base text-white">محفظة رقمية وحد ائتمان مرن</h4>
              <p class="text-xs text-slate-400 mt-1">تتبع رصيدك وحد ائتمانك وسجل معاملاتك المالية بالكامل، وادفع فورًا لحظة اعتماد عرض السعر — بدون فواتير ورقية أو انتظار</p>
            </div>
            <router-link to="/register" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-6 py-3 rounded-2xl text-xs whitespace-nowrap transition shrink-0">
              أنشئ حساب وكالة
            </router-link>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-3xl p-5 md:p-6 shadow-sm border border-slate-200 text-center space-y-3">
              <div class="w-11 h-11 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center mx-auto">
                <ClipboardList class="w-5 h-5" />
              </div>
              <h4 class="font-bold text-sm text-slate-900">إدارة الطلبات</h4>
              <p class="text-xs text-slate-500">أنشئ طلب حجز واستلم عرض السعر من الإدارة خلال دقائق</p>
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
        </div>
      </section>

      <!-- DESTINATIONS: branded gradient cards, no stock photography -->
      <section id="destinations" class="max-w-6xl mx-auto px-4 md:px-6 py-10 space-y-4 scroll-mt-20">
        <div class="text-center">
          <h3 class="text-lg font-extrabold text-slate-900">وجهات رائجة لعملائك</h3>
          <p class="text-xs text-slate-400">من أكثر الوجهات طلبًا عبر وكالات برق</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div
            v-for="dest in destinations"
            :key="dest.name"
            class="group relative rounded-3xl overflow-hidden h-40 shadow-sm cursor-pointer border border-slate-200 flex items-center px-6"
            :class="dest.gradient"
          >
            <div class="absolute inset-0 opacity-[0.12]" style="background-image: radial-gradient(circle at 1px 1px, #fff 1px, transparent 0); background-size: 18px 18px;"></div>
            <PlaneTakeoff class="w-24 h-24 text-white/10 absolute -left-4 -bottom-6 rotate-[-20deg] group-hover:rotate-0 transition duration-500" />
            <div class="relative z-10 text-white">
              <p class="text-[11px] font-bold text-white/70 font-mono">{{ dest.code }}</p>
              <h4 class="text-xl font-black">{{ dest.name }}</h4>
              <p class="text-[11px] text-amber-300 font-bold mt-1">وجهة مميزة</p>
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
              <BarqLogo class="w-5 h-5" />
            </div>
            <h3 class="text-xl font-black text-amber-500">برق</h3>
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
        <p>© 2026 برق بزنس. جميع الحقوق محفوظة.</p>
      </div>
    </footer>

  <!-- Booking Modal -->
  <Teleport to="body">
    <div
      v-if="bookingModal.open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4"
      @click.self="closeBooking"
    >
      <div class="bg-white rounded-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-2xl">
        <!-- Header -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
          <div>
            <p class="font-black text-slate-900 text-sm">{{ bookingModal.result?.airline }}</p>
            <p class="text-[11px] text-slate-500 font-mono mt-0.5">
              {{ bookingModal.result?.origin }} → {{ bookingModal.result?.destination }} ·
              {{ formatPoints(bookingModal.result?.total_price_sar) }}
            </p>
          </div>
          <button @click="closeBooking" class="text-slate-400 hover:text-slate-700 text-xl font-bold leading-none">✕</button>
        </div>

        <form @submit.prevent="submitBooking" class="px-5 py-4 space-y-5">
          <!-- One section per passenger -->
          <div
            v-for="(p, i) in bookingForm.passengers"
            :key="i"
            class="space-y-3 border border-slate-200 rounded-xl p-4"
          >
            <p class="font-bold text-xs text-slate-700">مسافر {{ i + 1 }}</p>

            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="text-[10px] font-semibold text-slate-500 block mb-1">اللقب</label>
                <select v-model="p.title" required class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400">
                  <option value="mr">Mr</option>
                  <option value="ms">Ms</option>
                  <option value="mrs">Mrs</option>
                  <option value="miss">Miss</option>
                  <option value="dr">Dr</option>
                </select>
              </div>
              <div>
                <label class="text-[10px] font-semibold text-slate-500 block mb-1">الجنس</label>
                <select v-model="p.gender" required class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400">
                  <option value="m">ذكر</option>
                  <option value="f">أنثى</option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="text-[10px] font-semibold text-slate-500 block mb-1">الاسم الأول</label>
                <input v-model="p.given_name" required placeholder="John" class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
              </div>
              <div>
                <label class="text-[10px] font-semibold text-slate-500 block mb-1">الاسم الأخير</label>
                <input v-model="p.family_name" required placeholder="Doe" class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
              </div>
            </div>

            <div>
              <label class="text-[10px] font-semibold text-slate-500 block mb-1">تاريخ الميلاد</label>
              <input v-model="p.born_on" type="date" required class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
            </div>

            <div class="grid grid-cols-2 gap-2">
              <div>
                <label class="text-[10px] font-semibold text-slate-500 block mb-1">البريد الإلكتروني</label>
                <input v-model="p.email" type="email" required placeholder="john@example.com" class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
              </div>
              <div>
                <label class="text-[10px] font-semibold text-slate-500 block mb-1">رقم الهاتف</label>
                <input v-model="p.phone_number" required placeholder="+966500000000" class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
              </div>
            </div>

            <!-- Passport — required or optional depending on the selected offer -->
            <div v-if="bookingModal.passportRequired" class="space-y-2 border border-amber-200 bg-amber-50/50 rounded-xl p-3">
              <p class="text-[10px] font-bold text-amber-700 flex items-center gap-1">
                <span>وثيقة الهوية</span>
                <span class="text-red-500">*</span>
                <span class="font-normal text-amber-600">(مطلوبة لهذه الرحلة)</span>
              </p>
              <div class="grid grid-cols-2 gap-2">
                <div class="col-span-2">
                  <label class="text-[10px] font-semibold text-slate-500 block mb-1">رقم الجواز <span class="text-red-500">*</span></label>
                  <input v-model="p.passport_number" placeholder="AB1234567" class="w-full border border-slate-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
                </div>
                <div>
                  <label class="text-[10px] font-semibold text-slate-500 block mb-1">تاريخ الانتهاء <span class="text-red-500">*</span></label>
                  <input v-model="p.passport_expiry" type="date" class="w-full border border-slate-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
                </div>
                <div>
                  <label class="text-[10px] font-semibold text-slate-500 block mb-1">دولة الإصدار <span class="text-red-500">*</span></label>
                  <input v-model="p.issuing_country_code" placeholder="SA" maxlength="2" class="w-full border border-slate-300 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
                </div>
              </div>
            </div>

            <details v-else class="text-xs">
              <summary class="text-[10px] font-semibold text-slate-500 cursor-pointer select-none">جواز السفر (اختياري)</summary>
              <div class="mt-2 grid grid-cols-2 gap-2">
                <div class="col-span-2">
                  <label class="text-[10px] font-semibold text-slate-500 block mb-1">رقم الجواز</label>
                  <input v-model="p.passport_number" placeholder="AB1234567" class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
                </div>
                <div>
                  <label class="text-[10px] font-semibold text-slate-500 block mb-1">تاريخ الانتهاء</label>
                  <input v-model="p.passport_expiry" type="date" class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
                </div>
                <div>
                  <label class="text-[10px] font-semibold text-slate-500 block mb-1">دولة الإصدار (2 حرف)</label>
                  <input v-model="p.issuing_country_code" placeholder="SA" maxlength="2" class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400" />
                </div>
              </div>
            </details>
          </div>

          <!-- Price breakdown -->
          <div class="bg-slate-950 rounded-2xl p-4 space-y-2 text-sm">
            <div class="flex items-center justify-between">
              <span class="text-xs text-slate-400">سعر التذكرة</span>
              <span class="font-mono font-bold text-white">{{ formatPoints(bookingModal.result?.total_price_sar) }}</span>
            </div>
            <template v-if="rate.agency_commission_amount > 0">
              <div class="flex items-center justify-between">
                <span class="text-xs text-sky-400">عمولة الوكالة</span>
                <span class="font-mono font-bold text-sky-400">+ {{ formatPoints(commissionAmount) }}</span>
              </div>
              <div class="border-t border-slate-700 pt-2 flex items-center justify-between">
                <span class="text-xs text-slate-300 font-semibold">السعر المقترح للعميل</span>
                <span class="font-mono font-black text-emerald-400 text-base">{{ formatPoints(clientTotalAmount) }}</span>
              </div>
              <p class="text-[10px] text-slate-500 text-center">المبلغ المخصوم من محفظتك: {{ formatPoints(clientTotalAmount) }} فقط</p>
            </template>
            <template v-else>
              <div class="border-t border-slate-700 pt-2 flex items-center justify-between">
                <span class="text-xs text-slate-400">يُخصم من المحفظة</span>
                <span class="font-mono font-black text-emerald-400 text-base">{{ formatPoints(bookingModal.result?.total_price_sar) }}</span>
              </div>
            </template>
          </div>

          <!-- Error -->
          <p v-if="bookingModal.error" class="text-xs text-red-600 font-semibold text-center">{{ bookingModal.error }}</p>

          <!-- Submit -->
          <button
            type="submit"
            :disabled="bookingModal.loading"
            class="w-full bg-amber-500 hover:bg-amber-400 disabled:opacity-50 text-slate-950 font-extrabold py-3 rounded-xl text-sm transition"
          >
            {{ bookingModal.loading ? 'جارٍ الحجز…' : `تأكيد الحجز · ${formatPoints(clientTotalAmount)}` }}
          </button>
        </form>
      </div>
    </div>

    <!-- Success Modal -->
    <div
      v-if="bookingSuccess.open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4"
    >
      <div class="bg-white rounded-2xl w-full max-w-sm p-6 shadow-2xl text-center space-y-4">
        <div class="w-14 h-14 bg-emerald-100 rounded-full flex items-center justify-center mx-auto">
          <CheckCircle2 class="w-8 h-8 text-emerald-500" />
        </div>
        <h3 class="font-black text-slate-900 text-base">تم الحجز بنجاح!</h3>
        <p class="text-xs text-slate-500">رقم الحجز: <span class="font-bold text-slate-800 font-mono tracking-wider">{{ bookingSuccess.reference }}</span></p>
        <p class="text-xs text-slate-500">رقم الطلب: <span class="font-bold text-slate-800">{{ bookingSuccess.orderNumber }}</span></p>
        <div class="bg-slate-900 rounded-xl p-3 space-y-1.5 text-right">
          <div class="flex items-center justify-between">
            <span class="text-[10px] text-slate-400">الخصم من المحفظة</span>
            <span class="font-mono font-bold text-white text-xs">{{ formatPoints(bookingSuccess.deductedSar) }}</span>
          </div>
          <div v-if="commissionAmount > 0" class="flex items-center justify-between">
            <span class="text-[10px] text-sky-400">عمولة الوكالة</span>
            <span class="font-mono font-bold text-sky-400 text-xs">+ {{ formatPoints(commissionAmount) }}</span>
          </div>
          <div v-if="commissionAmount > 0" class="flex items-center justify-between border-t border-slate-700 pt-1.5">
            <span class="text-[10px] font-bold text-amber-400">السعر المقترح للعميل</span>
            <span class="font-mono font-black text-amber-400 text-xs">{{ formatPoints(bookingSuccess.deductedSar + commissionAmount) }}</span>
          </div>
        </div>
        <!-- Show e-ticket numbers if available in the booking response -->
        <div v-if="bookingSuccess.documents.length" class="bg-slate-50 rounded-xl p-3 space-y-1 text-right">
          <p class="text-[10px] font-bold text-slate-500 mb-1">أرقام التذاكر الإلكترونية</p>
          <div v-for="doc in bookingSuccess.documents" :key="doc.unique_identifier" class="text-xs font-mono text-slate-800">
            {{ doc.unique_identifier }}
          </div>
        </div>
        <div class="flex flex-col gap-2 w-full">
          <button
            @click="openTicket()"
            :disabled="ticketModal.loading"
            class="w-full bg-slate-900 hover:bg-slate-700 disabled:opacity-60 text-white font-bold py-2.5 rounded-xl text-sm transition flex items-center justify-center gap-2"
          >
            <template v-if="ticketModal.loading">
              <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
              جارٍ تحميل التذكرة…
            </template>
            <template v-else>
              <FileText class="w-4 h-4" /> تحميل التذكرة PDF
            </template>
          </button>
          <button @click="bookingSuccess.open = false" class="w-full bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold py-2.5 rounded-xl text-sm transition">
            حسناً
          </button>
        </div>
      </div>
    </div>
    <!-- Ticket Modal -->
    <div
      v-if="ticketModal.open"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4"
      @click.self="ticketModal.open = false"
    >
      <div class="bg-white rounded-2xl w-full max-w-md max-h-[90vh] overflow-y-auto shadow-2xl">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
          <p class="font-black text-slate-900 text-sm">التذكرة الإلكترونية</p>
          <button @click="ticketModal.open = false" class="text-slate-400 hover:text-slate-700 text-xl font-bold leading-none">✕</button>
        </div>

        <div class="px-5 py-4">
          <div v-if="ticketModal.loading" class="text-center text-sm text-slate-500 py-8">جارٍ جلب بيانات التذكرة…</div>
          <div v-else-if="ticketModal.error" class="text-center text-sm text-red-600 py-8">{{ ticketModal.error }}</div>
          <div v-else-if="ticketModal.data" class="space-y-4">

            <!-- Header info -->
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex items-center justify-between">
              <div class="text-right">
                <p class="text-[10px] text-amber-700 font-semibold">رقم الحجز (PNR)</p>
                <p class="font-black text-xl text-amber-900 font-mono tracking-widest">{{ ticketModal.data.booking_reference }}</p>
              </div>
              <div class="text-right">
                <p class="text-[10px] text-slate-500 font-semibold">رقم الطلب</p>
                <p class="font-bold text-xs text-slate-800">{{ ticketModal.data.order_number }}</p>
              </div>
            </div>

            <!-- Passengers -->
            <div v-if="ticketModal.data.passengers?.length" class="space-y-1">
              <p class="text-[10px] font-bold text-slate-500">المسافرون</p>
              <div v-for="(p, i) in ticketModal.data.passengers" :key="i" class="flex items-center gap-2 text-xs text-slate-800">
                <span class="w-5 h-5 bg-slate-100 rounded-full flex items-center justify-center text-[10px] font-bold shrink-0">{{ i + 1 }}</span>
                {{ p.name }}
              </div>
            </div>

            <!-- E-Tickets -->
            <div v-if="ticketModal.data.documents?.length" class="space-y-2">
              <p class="text-[10px] font-bold text-slate-500">أرقام التذاكر الإلكترونية</p>
              <div
                v-for="doc in ticketModal.data.documents"
                :key="doc.ticket_number"
                class="bg-slate-50 border border-slate-200 rounded-xl p-3"
              >
                <p class="text-xs font-bold text-slate-800 font-mono">{{ doc.ticket_number }}</p>
                <p class="text-[10px] text-slate-500">{{ doc.passenger_names?.join('، ') }}</p>
              </div>
            </div>

            <!-- Segments / Itinerary -->
            <div v-if="ticketModal.data.segments?.length" class="space-y-2">
              <p class="text-[10px] font-bold text-slate-500">تفاصيل الرحلة</p>
              <div
                v-for="(seg, i) in ticketModal.data.segments"
                :key="i"
                class="border border-slate-200 rounded-xl p-3 space-y-1"
              >
                <div class="flex items-center justify-between">
                  <span class="text-[10px] font-semibold text-slate-500">{{ seg.airline }}</span>
                  <span class="text-[10px] font-mono font-bold text-slate-700 bg-slate-100 px-2 py-0.5 rounded-lg">{{ seg.flight_number }}</span>
                </div>
                <div class="flex items-center gap-2 text-xs font-bold text-slate-900">
                  <span>{{ seg.origin }}</span>
                  <span class="text-slate-400">→</span>
                  <span>{{ seg.destination }}</span>
                </div>
                <div class="grid grid-cols-2 gap-1 text-[10px] text-slate-500">
                  <div>
                    <span class="font-semibold">المغادرة: </span>
                    <span class="font-mono">{{ seg.departing_at ? seg.departing_at.replace('T', ' ').slice(0, 16) : '—' }}</span>
                  </div>
                  <div>
                    <span class="font-semibold">الوصول: </span>
                    <span class="font-mono">{{ seg.arriving_at ? seg.arriving_at.replace('T', ' ').slice(0, 16) : '—' }}</span>
                  </div>
                </div>
                <p v-if="seg.aircraft" class="text-[10px] text-slate-400">{{ seg.aircraft }}</p>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>

  </Teleport>

  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api, { API_BASE_URL } from '@/lib/api'
import { useAuthStore } from '@/stores/auth'
import { usePointRate } from '@/composables/usePointRate'
import BarqLogo from '@/components/BarqLogo.vue'
import {
  PlaneTakeoff, ClipboardList, Wallet, RotateCcw, ShieldCheck,
  Workflow, Sparkles, MapPin, FileText, BadgeDollarSign, CheckCircle2,
  Mail, Clock, Search, ArrowLeftRight,
} from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()
const { rate, loadRate } = usePointRate()

onMounted(loadRate)

const commissionAmount = computed(() => rate.value.agency_commission_amount ?? 0)

const clientTotalAmount = computed(() => {
  const price = bookingModal.value.result?.total_price_sar ?? 0
  return price + commissionAmount.value
})

const logout = async () => {
  await authStore.logout()
  router.push('/login')
}

const destinations = [
  { name: 'جدة', code: 'JED', gradient: 'bg-gradient-to-br from-amber-500 to-amber-700' },
  { name: 'دبي', code: 'DXB', gradient: 'bg-gradient-to-br from-slate-800 to-slate-950' },
  { name: 'إسطنبول', code: 'IST', gradient: 'bg-gradient-to-br from-blue-700 to-slate-900' },
  { name: 'القاهرة', code: 'CAI', gradient: 'bg-gradient-to-br from-emerald-700 to-slate-900' },
]

const todayStr = () => new Date().toISOString().slice(0, 10)

const tripType = ref('roundtrip')
const searchForm = ref({
  from: '',
  to: '',
  departDate: todayStr(),
  returnDate: todayStr(),
  cabinPassengers: '1-economy',
})

watch(() => searchForm.value.departDate, (newDepart) => {
  if (newDepart && searchForm.value.returnDate < newDepart) {
    searchForm.value.returnDate = newDepart
  }
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
  expiredBanner.value = false
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
  if (!searchForm.value.departDate || (tripType.value === 'roundtrip' && !searchForm.value.returnDate)) {
    searched.value = true
    results.value = []
    searchError.value = 'يرجى تحديد تاريخ السفر'
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
        trip_type: tripType.value === 'multi' ? 'oneway' : tripType.value,
        cabin,
        passengers: Number(passengers),
        departure_date: searchForm.value.departDate,
        return_date: tripType.value === 'roundtrip' ? searchForm.value.returnDate : undefined,
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

// --- Booking ---
const bookingModal = ref({ open: false, result: null, loading: false, error: '', passportRequired: false, supportedDocTypes: ['passport'] })
const bookingForm = ref({ passengers: [] })
const bookingSuccess = ref({ open: false, reference: '', orderNumber: '', deductedSar: 0, duffelOrderId: '', documents: [], localOrderId: null })
const ticketModal = ref({ open: false, loading: false, error: '', data: null })
const expiredBanner = ref(false)

const blankPassenger = () => ({
  title: 'mr', gender: 'm', given_name: '', family_name: '',
  born_on: '', email: '', phone_number: '',
  passport_number: '', passport_expiry: '', issuing_country_code: '',
})

const openBooking = (result) => {
  bookingModal.value = {
    open: true,
    result,
    loading: false,
    error: '',
    passportRequired: result.passport_required ?? false,
    supportedDocTypes: result.supported_doc_types ?? ['passport'],
  }
  bookingForm.value.passengers = Array.from(
    { length: result.passengers },
    () => blankPassenger()
  )
}

const closeBooking = () => {
  bookingModal.value.open = false
}

const submitBooking = async () => {
  const r = bookingModal.value.result
  bookingModal.value.error = ''

  // If the offer requires identity documents, validate before hitting the API.
  if (bookingModal.value.passportRequired) {
    for (let i = 0; i < bookingForm.value.passengers.length; i++) {
      const p = bookingForm.value.passengers[i]
      if (!p.passport_number || !p.passport_expiry || !p.issuing_country_code) {
        bookingModal.value.error = `يرجى إدخال بيانات جواز السفر الكاملة للمسافر ${i + 1} — هذه الرحلة تتطلب وثيقة هوية`
        return
      }
    }
  }

  // If the user provided partial passport data (only some fields), reject it.
  for (let i = 0; i < bookingForm.value.passengers.length; i++) {
    const p = bookingForm.value.passengers[i]
    const filled = [p.passport_number, p.passport_expiry, p.issuing_country_code].filter(Boolean).length
    if (filled > 0 && filled < 3) {
      bookingModal.value.error = `بيانات جواز السفر غير مكتملة للمسافر ${i + 1} — أدخل رقم الجواز وتاريخ الانتهاء ودولة الإصدار معاً أو اتركها فارغة`
      return
    }
  }

  bookingModal.value.loading = true

  const passengers = bookingForm.value.passengers.map((p, i) => {
    const duffelP = r.duffel_passengers?.[i] ?? {}
    const payload = {
      duffel_passenger_id: duffelP.id,
      duffel_type: duffelP.type ?? 'adult',
      title: p.title,
      given_name: p.given_name,
      family_name: p.family_name,
      born_on: p.born_on,
      email: p.email,
      phone_number: p.phone_number,
      gender: p.gender,
    }
    if (p.passport_number && p.passport_expiry && p.issuing_country_code) {
      payload.identity_documents = [{
        type: 'passport',
        unique_identifier: p.passport_number,
        expires_on: p.passport_expiry,
        issuing_country_code: p.issuing_country_code.toUpperCase(),
      }]
    }
    return payload
  })

  try {
    const res = await api.post('/duffel/book', {
      offer_id: r.offer_id,
      duffel_total_amount: r.duffel_total_amount,
      duffel_total_currency: r.duffel_total_currency,
      total_price_sar: r.total_price_sar,
      passengers,
    })
    bookingModal.value.open = false
    bookingSuccess.value = {
      open: true,
      reference: res.data.booking?.booking_reference ?? '—',
      orderNumber: res.data.booking?.order_number ?? '—',
      deductedSar: res.data.booking?.deducted_sar ?? 0,
      duffelOrderId: res.data.booking?.duffel_order_id ?? '',
      documents: res.data.booking?.documents ?? [],
      localOrderId: res.data.booking?.local_order_id ?? null,
    }
  } catch (err) {
    if (err.response?.status === 409 && err.response?.data?.expired) {
      bookingModal.value.error = 'انتهت صلاحية هذا العرض. جارٍ تحديث نتائج البحث…'
      expiredBanner.value = true
      await onSearch()
      bookingModal.value.open = false
    } else {
      bookingModal.value.error = err.response?.data?.message ?? 'فشل الحجز، يرجى المحاولة مرة أخرى'
    }
  } finally {
    bookingModal.value.loading = false
  }
}

const openTicket = async () => {
  ticketModal.value = { open: false, loading: true, error: '', data: null }
  try {
    const orderId = bookingSuccess.value.localOrderId
    const token = authStore.token
    const url = `${API_BASE_URL}/duffel/orders/${orderId}/ticket/pdf`
    const res = await fetch(url, { headers: { Authorization: `Bearer ${token}`, Accept: 'application/pdf' } })
    if (!res.ok) throw new Error('فشل تحميل التذكرة')
    const blob = await res.blob()
    const a = document.createElement('a')
    a.href = URL.createObjectURL(blob)
    a.download = `ticket-${bookingSuccess.value.reference ?? orderId}.pdf`
    a.click()
    URL.revokeObjectURL(a.href)
    bookingSuccess.value.open = false
  } catch (err) {
    ticketModal.value.error = err.message ?? 'تعذر تحميل التذكرة'
    ticketModal.value.open = true
  } finally {
    ticketModal.value.loading = false
  }
}

const formatPrice = (value, currency = 'USD') =>
  new Intl.NumberFormat('en-US', { style: 'currency', currency: currency || 'USD' }).format(value || 0)

const formatPoints = (value) =>
  new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(value || 0) + ' نقطة'

const formatDuration = (minutes) => {
  const h = Math.floor(minutes / 60)
  const m = minutes % 60
  return `${h}س ${m}د`
}
</script>

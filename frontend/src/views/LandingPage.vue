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
            <BarqLogo class="w-6 h-6" />
          </div>
        </div>
      </header>

      <!-- Secondary nav: segmented pill style (matches the trip-type control used inside the app) -->
      <nav class="bg-white border-b border-slate-200 px-4 md:px-8 py-2.5 flex items-center justify-center">
        <div class="flex items-center gap-1 bg-slate-100 p-1.5 rounded-2xl text-[11px] md:text-xs font-bold text-slate-600 overflow-x-auto max-w-full">
          <a href="#search" class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-white text-amber-600 shadow-sm whitespace-nowrap">
            <Search class="w-3.5 h-3.5" />
            البحث عن رحلات
          </a>
          <a href="#how-it-works" class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl hover:text-slate-900 transition whitespace-nowrap">
            <Workflow class="w-3.5 h-3.5" />
            كيف تعمل المنصة
          </a>
          <a href="#features" class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl hover:text-slate-900 transition whitespace-nowrap">
            <Sparkles class="w-3.5 h-3.5" />
            المميزات
          </a>
          <a href="#destinations" class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl hover:text-slate-900 transition whitespace-nowrap">
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
              <router-link to="/register" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-extrabold px-7 py-3 rounded-2xl text-sm transition shadow-md">
                ابدأ الآن كوكالة سفر
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
                  class="w-full bg-slate-50 border border-slate-200 rounded-2xl px-3 py-3 text-xs font-bold text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500"
                >
              </div>

              <div v-if="tripType === 'roundtrip'" class="flex-1 min-w-[130px] space-y-1">
                <label class="block text-[11px] font-bold text-slate-500">تاريخ العودة</label>
                <input
                  v-model="searchForm.returnDate"
                  type="date"
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
          <h3 class="text-lg font-extrabold text-slate-900">لماذا Barq Agency؟</h3>
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
          <p class="text-xs text-slate-400">من أكثر الوجهات طلبًا عبر وكالات Barq Agency</p>
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
import BarqLogo from '@/components/BarqLogo.vue'
import {
  PlaneTakeoff, ClipboardList, Wallet, RotateCcw, ShieldCheck,
  Workflow, Sparkles, MapPin, FileText, BadgeDollarSign, CheckCircle2,
  Mail, Clock, Search, ArrowLeftRight, Loader2,
} from 'lucide-vue-next'

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

const formatPrice = (value, currency = 'USD') =>
  new Intl.NumberFormat('en-US', { style: 'currency', currency: currency || 'USD' }).format(value || 0)

const formatDuration = (minutes) => {
  const h = Math.floor(minutes / 60)
  const m = minutes % 60
  return `${h}س ${m}د`
}
</script>

<template>
  <div class="bg-slate-100 text-slate-800 min-h-screen flex flex-col justify-between antialiased" dir="rtl" style="font-family: 'Cairo', sans-serif;">

    <div>
      <!-- Header -->
      <header class="bg-[#0f172a] text-white py-3 px-4 md:px-8 flex items-center justify-between shadow-md">
        <div class="flex items-center gap-2">
          <router-link to="/login" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-extrabold px-5 py-2 rounded-xl text-xs sm:text-sm transition shadow-sm">
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

      <!-- Hero -->
      <section class="max-w-5xl mx-auto px-4 pt-14 pb-10 text-center">
        <div class="w-14 h-14 bg-[#0f172a] text-amber-400 rounded-2xl flex items-center justify-center mx-auto shadow-md mb-6">
          <PlaneTakeoff class="w-7 h-7" />
        </div>
        <h2 class="text-3xl sm:text-4xl font-black text-slate-900 leading-tight">
          منصة إدارة السفر للوكلاء<br class="hidden sm:block" />
          <span class="text-amber-500">B2B بين الوكالات والإدارة</span>
        </h2>
        <p class="text-sm text-slate-500 mt-4 max-w-lg mx-auto">
          أنشئ طلبات الحجز، استلم عروض الأسعار، وأدر محفظتك المالية بكل سهولة من مكان واحد
        </p>
        <div class="flex items-center justify-center gap-3 mt-8">
          <router-link to="/login" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-extrabold px-8 py-3 rounded-2xl text-sm transition shadow-md">
            دخول لوحة التحكم
          </router-link>
        </div>
      </section>

      <!-- Features -->
      <section class="max-w-5xl mx-auto px-4 pb-14">
        <div class="grid sm:grid-cols-3 gap-4">
          <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 text-center space-y-3">
            <div class="w-11 h-11 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center mx-auto">
              <ClipboardList class="w-5 h-5" />
            </div>
            <h3 class="font-bold text-sm text-slate-900">إدارة الطلبات</h3>
            <p class="text-xs text-slate-500">أنشئ طلب حجز واستلم عرض السعر من الإدارة خلال دقائق</p>
          </div>
          <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 text-center space-y-3">
            <div class="w-11 h-11 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center mx-auto">
              <Wallet class="w-5 h-5" />
            </div>
            <h3 class="font-bold text-sm text-slate-900">محفظة رقمية</h3>
            <p class="text-xs text-slate-500">تتبع رصيدك، حد الائتمان، وسجل معاملاتك المالية أولاً بأول</p>
          </div>
          <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-200 text-center space-y-3">
            <div class="w-11 h-11 bg-red-100 text-red-600 rounded-xl flex items-center justify-center mx-auto">
              <RotateCcw class="w-5 h-5" />
            </div>
            <h3 class="font-bold text-sm text-slate-900">استرداد سهل</h3>
            <p class="text-xs text-slate-500">اطلب استرداد أي حجز مؤكد وتابع حالته حتى إضافة المبلغ لمحفظتك</p>
          </div>
        </div>
      </section>
    </div>

    <!-- Footer -->
    <footer class="text-center py-4 text-xs text-slate-400">
      <p>© 2026 Barq Agency. All rights reserved.</p>
    </footer>

  </div>
</template>

<script setup>
import { PlaneTakeoff, ClipboardList, Wallet, RotateCcw } from 'lucide-vue-next'
</script>

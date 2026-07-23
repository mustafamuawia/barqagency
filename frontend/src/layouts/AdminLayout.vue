<template>
  <div class="bg-slate-50 text-slate-800 font-sans min-h-screen flex flex-col" dir="rtl" style="font-family: 'Cairo', sans-serif;">

    <!-- Overlay -->
    <div
      id="sidebarOverlay"
      @click="closeSidebar"
      :class="sidebarOpen ? 'opacity-100' : 'opacity-0 pointer-events-none'"
      class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-40 transition-opacity duration-300"
    ></div>

    <!-- Admin Sidebar Drawer -->
    <aside
      :class="sidebarOpen ? 'translate-x-0' : 'translate-x-full'"
      class="fixed top-0 right-0 h-full w-72 bg-[#0b132b] text-white z-50 shadow-2xl transform transition-transform duration-300 ease-in-out flex flex-col justify-between border-l border-slate-800"
    >
      <div>
        <!-- Sidebar Header -->
        <div class="p-5 flex items-center justify-between border-b border-slate-800/80">
          <div class="flex items-center gap-2">
            <div class="bg-amber-500 text-slate-900 p-1.5 rounded-lg flex items-center justify-center">
              <ShieldCheck class="w-5 h-5" />
            </div>
            <div>
              <h2 class="text-lg font-black tracking-wide text-amber-400">إدارة النظام</h2>
              <p class="text-[10px] text-slate-400 -mt-1">صلاحيات المدير العام</p>
            </div>
          </div>
          <button @click="closeSidebar" class="p-1.5 text-slate-400 hover:text-white hover:bg-slate-800 rounded-lg transition">
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Admin Profile -->
        <div class="m-4 p-3 bg-slate-800/60 rounded-2xl border border-slate-700/60 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-400 font-bold text-sm">
            م
          </div>
          <div class="text-xs overflow-hidden">
            <p class="font-bold text-white truncate">{{ auth.user?.name || 'مدير المنصة' }}</p>
            <p class="text-slate-400 text-[10px] font-mono truncate">{{ auth.user?.email || 'admin@barq.com' }}</p>
          </div>
        </div>

        <!-- Nav Links -->
        <nav class="px-3 space-y-1 text-xs font-semibold">
          <router-link
            to="/admin"
            exact
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition"
            :class="$route.path === '/admin' ? 'bg-amber-500 text-slate-950 font-bold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
            @click="closeSidebar"
          >
            <LayoutDashboard class="w-4 h-4" />
            <span>لوحة التحكم الرئيسية</span>
          </router-link>

          <router-link
            to="/admin/orders"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition"
            :class="$route.path.startsWith('/admin/orders') ? 'bg-amber-500 text-slate-950 font-bold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
            @click="closeSidebar"
          >
            <Ticket class="w-4 h-4 text-amber-400" />
            <span>قائمة الطلبات والحجوزات</span>
          </router-link>

          <router-link
            to="/admin/wallet/topup"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition"
            :class="$route.path === '/admin/wallet/topup' ? 'bg-amber-500 text-slate-950 font-bold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
            @click="closeSidebar"
          >
            <Wallet class="w-4 h-4 text-emerald-400" />
            <span>إيداعات المحافظ والشحن</span>
          </router-link>

          <router-link
            to="/admin/refunds"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition"
            :class="$route.path.startsWith('/admin/refunds') ? 'bg-amber-500 text-slate-950 font-bold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
            @click="closeSidebar"
          >
            <RotateCcw class="w-4 h-4 text-red-400" />
            <span>طلبات الاسترداد</span>
          </router-link>

          <router-link
            to="/admin/agencies"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition"
            :class="$route.path === '/admin/agencies' ? 'bg-amber-500 text-slate-950 font-bold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
            @click="closeSidebar"
          >
            <Users class="w-4 h-4 text-blue-400" />
            <span>إدارة وكلاء السفر (B2B)</span>
          </router-link>

          <div class="pt-2 my-2 border-t border-slate-800">
            <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-slate-200 transition">
              <Settings class="w-4 h-4" />
              <span>إعدادات الربط (Duffel API)</span>
            </a>
            <a href="#" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-slate-200 transition">
              <ShieldAlert class="w-4 h-4" />
              <span>سجل نشاطات النظام (Logs)</span>
            </a>
          </div>
        </nav>
      </div>

      <!-- Sidebar Footer -->
      <div class="p-4 border-t border-slate-800/80 bg-slate-900/50">
        <button
          @click="logout"
          class="w-full bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/30 font-bold py-2.5 px-4 rounded-xl text-xs flex items-center justify-center gap-2 transition"
        >
          <LogOut class="w-4 h-4" />
          <span>تسجيل الخروج الآمن</span>
        </button>
      </div>
    </aside>

    <!-- Top Header -->
    <header class="bg-[#0f172a] text-white py-3 px-4 md:px-8 flex items-center justify-between border-b border-slate-800 shadow-md sticky top-0 z-30">
      <div class="flex items-center gap-4">
        <button
          @click="openSidebar"
          class="p-2 hover:bg-slate-800 rounded-lg text-slate-300 transition focus:outline-none focus:ring-2 focus:ring-amber-500"
        >
          <Menu class="w-6 h-6" />
        </button>
        <div class="flex items-center gap-2">
          <div class="bg-amber-500 text-slate-900 p-1.5 rounded-lg flex items-center justify-center">
            <PlaneTakeoff class="w-6 h-6" />
          </div>
          <div>
            <h1 class="text-2xl font-black tracking-wide text-amber-500">Barq Agency</h1>
            <p class="text-[10px] text-slate-400 font-light -mt-1">B2B Travel & Tourism Portal</p>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-3">
        <div class="bg-slate-800 text-slate-200 font-medium px-3 py-1.5 rounded-xl text-sm flex items-center gap-2 border border-slate-700">
          <Shield class="w-4 h-4 text-emerald-400" />
          لوحة الإدارة النشطة
        </div>
        <div class="bg-emerald-950/60 border border-emerald-500/30 text-emerald-400 px-3 py-1 rounded-xl text-xs flex flex-col items-end">
          <span class="text-[10px] text-emerald-500/80 font-normal">إجمالي خزانة المنصة</span>
          <span class="font-bold font-mono">$45,200.00</span>
        </div>
      </div>
    </header>

    <!-- Page Content -->
    <main class="flex-1">
      <router-view />
    </main>

    <!-- Footer -->
    <footer class="bg-[#0b132b] text-slate-400 text-xs border-t border-slate-800 py-6 text-center">
      <p>© 2026 Barq Agency. All rights reserved.</p>
    </footer>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  Menu, X, ShieldCheck, LayoutDashboard, Ticket, Wallet, Users,
  Settings, ShieldAlert, LogOut, PlaneTakeoff, Shield, RotateCcw
} from 'lucide-vue-next'

const router = useRouter()
const auth = useAuthStore()
const sidebarOpen = ref(false)

const openSidebar = () => { sidebarOpen.value = true }
const closeSidebar = () => { sidebarOpen.value = false }

const logout = async () => {
  await auth.logout()
  router.push('/login')
}
</script>

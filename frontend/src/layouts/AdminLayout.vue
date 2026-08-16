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
      class="fixed top-0 right-0 h-full w-72 bg-[#0b132b] text-white z-50 shadow-2xl transform transition-transform duration-300 ease-in-out flex flex-col border-l border-slate-800"
    >
      <div class="flex-shrink-0">
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
      </div>

      <!-- Nav Links (scrollable) -->
      <nav class="sidebar-nav flex-1 overflow-y-auto px-3 space-y-1 text-xs font-semibold">
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

          <router-link
            v-if="auth.isSuperAdmin"
            to="/admin/users"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition"
            :class="$route.path.startsWith('/admin/users') ? 'bg-amber-500 text-slate-950 font-bold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
            @click="closeSidebar"
          >
            <UsersRound class="w-4 h-4 text-amber-400" />
            <span>إدارة المستخدمين</span>
          </router-link>

          <router-link
            v-if="auth.isSuperAdmin"
            to="/admin/settings"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition"
            :class="$route.path.startsWith('/admin/settings') ? 'bg-amber-500 text-slate-950 font-bold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
            @click="closeSidebar"
          >
            <Settings class="w-4 h-4 text-amber-400" />
            <span>إعدادات النقاط</span>
          </router-link>

          <router-link
            to="/admin/reports"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition"
            :class="$route.path.startsWith('/admin/reports') ? 'bg-amber-500 text-slate-950 font-bold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
            @click="closeSidebar"
          >
            <FileText class="w-4 h-4 text-purple-400" />
            <span>التقارير المالية</span>
          </router-link>

          <router-link
            to="/admin/audit-log"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition"
            :class="$route.path.startsWith('/admin/audit-log') ? 'bg-amber-500 text-slate-950 font-bold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
            @click="closeSidebar"
          >
            <History class="w-4 h-4 text-cyan-400" />
            <span>سجل النشاطات</span>
          </router-link>

          <router-link
            to="/admin/profile"
            class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition"
            :class="$route.path === '/admin/profile' ? 'bg-amber-500 text-slate-950 font-bold shadow-sm' : 'text-slate-300 hover:bg-slate-800 hover:text-white'"
            @click="closeSidebar"
          >
            <UserCog class="w-4 h-4 text-slate-400" />
            <span>الملف الشخصي</span>
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

      <!-- Sidebar Footer -->
      <div class="p-4 border-t border-slate-800/80 bg-slate-900/50 flex-shrink-0">
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
    <header class="bg-[#0f172a] text-white py-2.5 sm:py-3 px-3 sm:px-4 md:px-8 flex items-center justify-between gap-2 border-b border-slate-800 shadow-md sticky top-0 z-30">
      <div class="flex items-center gap-2 sm:gap-4 min-w-0">
        <button
          @click="openSidebar"
          class="p-2 hover:bg-slate-800 rounded-lg text-slate-300 transition focus:outline-none focus:ring-2 focus:ring-amber-500 shrink-0"
        >
          <Menu class="w-5 h-5 sm:w-6 sm:h-6" />
        </button>
        <div class="flex items-center gap-2 min-w-0">
          <div class="bg-amber-500 text-slate-900 p-1.5 rounded-lg flex items-center justify-center shrink-0">
            <BarqLogo class="w-5 h-5 sm:w-6 sm:h-6" />
          </div>
          <div class="min-w-0">
            <h1 class="text-base sm:text-2xl font-black tracking-wide text-amber-500 leading-tight whitespace-nowrap truncate">Barq Agency</h1>
            <p class="hidden sm:block text-[10px] text-slate-400 font-light -mt-1 whitespace-nowrap">B2B Travel & Tourism Portal</p>
          </div>
        </div>
      </div>

      <div class="flex items-center gap-2 sm:gap-3 shrink-0">
        <div class="hidden lg:flex bg-slate-800 text-slate-200 font-medium px-3 py-1.5 rounded-xl text-sm items-center gap-2 border border-slate-700 whitespace-nowrap">
          <Shield class="w-4 h-4 text-emerald-400" />
          لوحة الإدارة النشطة
        </div>
        <NotificationBell />
      </div>
    </header>

    <!-- Page Content -->
    <main class="flex-1 min-w-0">
      <router-view />
    </main>

    <!-- Footer -->
    <footer class="bg-[#0b132b] text-slate-400 text-xs border-t border-slate-800 py-6 text-center">
      <p>© 2026 Barq Agency. All rights reserved.</p>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useNotificationsStore } from '@/stores/notifications'
import NotificationBell from '@/components/NotificationBell.vue'
import {
  Menu, X, ShieldCheck, LayoutDashboard, Ticket, Wallet, Users, UsersRound,
  Settings, ShieldAlert, LogOut, Shield, RotateCcw, FileText, History, UserCog
} from 'lucide-vue-next'
import BarqLogo from '@/components/BarqLogo.vue'

const router = useRouter()
const auth = useAuthStore()
const notifications = useNotificationsStore()
const sidebarOpen = ref(false)

const openSidebar = () => { sidebarOpen.value = true }
const closeSidebar = () => { sidebarOpen.value = false }

const logout = async () => {
  notifications.disconnectRealtime()
  await auth.logout()
  router.push('/login')
}

onMounted(() => {
  if (auth.user?.id) {
    notifications.init(auth.user.id, auth.token, 'admin')
  }
})

onUnmounted(() => {
  notifications.disconnectRealtime()
})
</script>

<style scoped>
.sidebar-nav {
  scrollbar-width: thin;
  scrollbar-color: rgb(51 65 85) transparent;
}
.sidebar-nav::-webkit-scrollbar {
  width: 6px;
}
.sidebar-nav::-webkit-scrollbar-track {
  background: transparent;
}
.sidebar-nav::-webkit-scrollbar-thumb {
  background-color: rgb(51 65 85);
  border-radius: 9999px;
}
.sidebar-nav::-webkit-scrollbar-thumb:hover {
  background-color: rgb(71 85 105);
}
</style>

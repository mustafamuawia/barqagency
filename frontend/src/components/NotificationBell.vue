<template>
  <div class="relative">
    <button
      @click="toggle"
      class="relative p-2 hover:bg-slate-800 rounded-lg text-slate-300 transition focus:outline-none focus:ring-2 focus:ring-amber-500"
    >
      <Bell class="w-5 h-5" />
      <span
        v-if="notifications.unreadCount > 0"
        class="absolute -top-0.5 -left-0.5 bg-red-500 text-white text-[9px] font-bold rounded-full min-w-[16px] h-4 px-1 flex items-center justify-center"
      >
        {{ notifications.unreadCount > 9 ? '9+' : notifications.unreadCount }}
      </span>
    </button>

    <!-- Outside-click catcher -->
    <div v-if="open" class="fixed inset-0 z-40" @click="open = false"></div>

    <!-- Dropdown -->
    <div
      v-if="open"
      class="fixed sm:absolute left-3 right-3 sm:right-auto sm:left-0 mt-2 sm:w-80 w-auto bg-white rounded-2xl shadow-xl border border-slate-200 z-50 text-slate-800 overflow-hidden"
      dir="rtl"
    >
      <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
        <h3 class="font-bold text-sm text-slate-900">الإشعارات</h3>
        <button
          v-if="notifications.unreadCount > 0"
          @click="notifications.markAllRead"
          class="text-[11px] text-amber-600 font-bold hover:underline"
        >
          تحديد الكل كمقروء
        </button>
      </div>

      <div class="max-h-96 overflow-y-auto">
        <div v-if="notifications.loading" class="p-6 text-center text-xs text-slate-400">جارٍ التحميل...</div>

        <div v-else-if="notifications.items.length === 0" class="p-6 text-center text-xs text-slate-400">
          لا توجد إشعارات
        </div>

        <button
          v-for="item in notifications.items"
          :key="item.id"
          @click="handleClick(item)"
          class="w-full text-right px-4 py-3 border-b border-slate-50 hover:bg-slate-50 transition flex items-start gap-3"
          :class="!item.read_at ? 'bg-amber-50/50' : ''"
        >
          <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0" :class="iconBg(item.data.type)">
            <component :is="icon(item.data.type)" class="w-4 h-4" :class="iconColor(item.data.type)" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs font-bold text-slate-800 truncate">{{ item.data.title }}</p>
            <p class="text-[11px] text-slate-500 line-clamp-2">{{ item.data.message }}</p>
            <p class="text-[10px] text-slate-400 mt-0.5">{{ relativeTime(item.created_at) }}</p>
          </div>
          <span v-if="!item.read_at" class="w-2 h-2 rounded-full bg-amber-500 shrink-0 mt-1"></span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Bell, Ticket, Wallet, RotateCcw, Users } from 'lucide-vue-next'
import { useNotificationsStore } from '@/stores/notifications'

const router = useRouter()
const notifications = useNotificationsStore()
const open = ref(false)

const toggle = () => {
  open.value = !open.value
  if (open.value) {
    notifications.loadNotifications()
  }
}

const handleClick = async (item) => {
  if (!item.read_at) {
    await notifications.markRead(item.id)
  }
  open.value = false
  if (item.data.link) {
    router.push(item.data.link)
  }
}

const ICONS = { order: Ticket, deposit: Wallet, refund: RotateCcw, agency: Users }
const ICON_BG = { order: 'bg-amber-100', deposit: 'bg-emerald-100', refund: 'bg-red-100', agency: 'bg-blue-100' }
const ICON_COLOR = { order: 'text-amber-600', deposit: 'text-emerald-600', refund: 'text-red-600', agency: 'text-blue-600' }

const icon = (type) => ICONS[type] || Bell
const iconBg = (type) => ICON_BG[type] || 'bg-slate-100'
const iconColor = (type) => ICON_COLOR[type] || 'text-slate-600'

const relativeTime = (dateStr) => {
  const diffMs = Date.now() - new Date(dateStr).getTime()
  const minutes = Math.floor(diffMs / 60000)
  if (minutes < 1) return 'الآن'
  if (minutes < 60) return `منذ ${minutes} دقيقة`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `منذ ${hours} ساعة`
  const days = Math.floor(hours / 24)
  return `منذ ${days} يوم`
}

onMounted(() => {
  notifications.loadUnreadCount()
})
</script>

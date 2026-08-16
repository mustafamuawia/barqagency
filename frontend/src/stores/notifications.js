import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/lib/api'
import { initEcho, disconnectEcho } from '@/lib/echo'

export const useNotificationsStore = defineStore('notifications', () => {
  const items = ref([])
  const unreadCount = ref(0)
  const loading = ref(false)
  const connected = ref(false)
  const basePath = ref('/admin/notifications')
  const eventName = ref('admin.notification')

  const loadNotifications = async (page = 1) => {
    loading.value = true
    try {
      const response = await api.get(basePath.value, { params: { page } })
      items.value = response.data.data
    } catch (err) {
      console.error('Failed to load notifications', err)
    } finally {
      loading.value = false
    }
  }

  const loadUnreadCount = async () => {
    try {
      const response = await api.get(`${basePath.value}/unread-count`)
      unreadCount.value = response.data.unread_count
    } catch (err) {
      console.error('Failed to load unread count', err)
    }
  }

  const markRead = async (id) => {
    const item = items.value.find((n) => n.id === id)
    try {
      await api.put(`${basePath.value}/${id}/read`)
      if (item && !item.read_at) {
        item.read_at = new Date().toISOString()
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      }
    } catch (err) {
      console.error('Failed to mark notification read', err)
    }
  }

  const markAllRead = async () => {
    try {
      await api.put(`${basePath.value}/read-all`)
      items.value.forEach((n) => {
        if (!n.read_at) n.read_at = new Date().toISOString()
      })
      unreadCount.value = 0
    } catch (err) {
      console.error('Failed to mark all notifications read', err)
    }
  }

  const connectRealtime = (userId, token) => {
    if (connected.value || !userId || !token) return

    const echo = initEcho(token)
    echo.private(`App.Models.User.${userId}`).listen(`.${eventName.value}`, () => {
      unreadCount.value++
      loadNotifications()
    })
    connected.value = true
  }

  const disconnectRealtime = () => {
    disconnectEcho()
    connected.value = false
  }

  /** @param {'admin'|'agency'} role */
  const init = (userId, token, role = 'admin') => {
    basePath.value = `/${role}/notifications`
    eventName.value = `${role}.notification`
    loadNotifications()
    loadUnreadCount()
    connectRealtime(userId, token)
  }

  return {
    items,
    unreadCount,
    loading,
    loadNotifications,
    loadUnreadCount,
    markRead,
    markAllRead,
    init,
    disconnectRealtime,
  }
})

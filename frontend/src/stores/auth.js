import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/lib/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('auth_token'))
  const isAuthenticated = computed(() => !!token.value)

  const login = async (email, password) => {
    try {
      const response = await api.post('/login', { email, password })
      token.value = response.data.token
      user.value = response.data.user
      localStorage.setItem('auth_token', token.value)
      return { success: true }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Login failed',
      }
    }
  }

  const logout = async () => {
    try {
      await api.post('/logout')
    } catch (error) {
      // ignore
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
    }
  }

  const fetchCurrentUser = async () => {
    if (!token.value) return
    try {
      const response = await api.get('/user')
      user.value = response.data.user
    } catch (error) {
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
    }
  }

  const isSuperAdmin = computed(() => user.value?.user_type === 'super_admin')
  const isAdmin = computed(() => ['super_admin', 'admin'].includes(user.value?.user_type))
  const isAgency = computed(() => user.value?.user_type === 'b2b')

  return {
    user,
    token,
    isAuthenticated,
    isSuperAdmin,
    isAdmin,
    isAgency,
    login,
    logout,
    fetchCurrentUser,
  }
})

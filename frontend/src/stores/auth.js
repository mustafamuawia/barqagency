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

  const forgotPassword = async (email) => {
    try {
      const response = await api.post('/forgot-password', { email })
      return { success: true, message: response.data.message }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'تعذر إرسال طلب إعادة التعيين',
      }
    }
  }

  const resetPassword = async (token, email, password, passwordConfirmation) => {
    try {
      const response = await api.post('/reset-password', {
        token,
        email,
        password,
        password_confirmation: passwordConfirmation,
      })
      return { success: true, message: response.data.message }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'تعذرت إعادة تعيين كلمة المرور',
      }
    }
  }

  const updateProfile = async (name, email) => {
    try {
      const response = await api.put('/profile', { name, email })
      user.value = response.data.user
      return { success: true }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'تعذر تحديث الملف الشخصي',
      }
    }
  }

  const updatePassword = async (currentPassword, password, passwordConfirmation) => {
    try {
      const response = await api.put('/profile/password', {
        current_password: currentPassword,
        password,
        password_confirmation: passwordConfirmation,
      })
      return { success: true, message: response.data.message }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'تعذر تغيير كلمة المرور',
      }
    }
  }

  const verifyEmail = async (email, token) => {
    try {
      const response = await api.post('/verify-email', { email, token })
      if (user.value) {
        user.value = response.data.user
      }
      return { success: true, message: response.data.message }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'تعذر تفعيل البريد الإلكتروني',
      }
    }
  }

  const resendVerification = async () => {
    try {
      const response = await api.post('/resend-verification')
      return { success: true, message: response.data.message }
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'تعذر إرسال رابط التفعيل',
      }
    }
  }

  const isSuperAdmin = computed(() => user.value?.user_type === 'super_admin')
  const isAdmin = computed(() => ['super_admin', 'admin'].includes(user.value?.user_type))
  const isAgency = computed(() => user.value?.user_type === 'b2b')
  const agencyStatus = computed(() => user.value?.agency_status)
  const emailVerified = computed(() => !!user.value?.email_verified)

  return {
    user,
    token,
    isAuthenticated,
    isSuperAdmin,
    isAdmin,
    isAgency,
    agencyStatus,
    emailVerified,
    login,
    logout,
    fetchCurrentUser,
    forgotPassword,
    resetPassword,
    updateProfile,
    updatePassword,
    verifyEmail,
    resendVerification,
  }
})

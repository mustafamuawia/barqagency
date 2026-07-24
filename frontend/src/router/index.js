import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/',
    component: () => import('@/views/LandingPage.vue'),
    meta: { public: true },
  },
  {
    path: '/login',
    component: () => import('@/views/LoginPage.vue'),
    meta: { public: true },
  },
  {
    path: '/register',
    component: () => import('@/views/RegisterPage.vue'),
    meta: { public: true },
  },
  {
    path: '/forgot-password',
    component: () => import('@/views/ForgotPasswordPage.vue'),
    meta: { public: true },
  },
  {
    path: '/reset-password',
    component: () => import('@/views/ResetPasswordPage.vue'),
    meta: { public: true },
  },
  {
    path: '/admin',
    component: () => import('@/layouts/AdminLayout.vue'),
    meta: { requireRole: 'admin' },
    children: [
      { path: '', name: 'admin.dashboard', component: () => import('@/views/admin/Dashboard.vue') },
      { path: 'orders', name: 'admin.orders', component: () => import('@/views/admin/OrderQueue.vue') },
      { path: 'orders/:id/price', name: 'admin.orders.price', component: () => import('@/views/admin/PricingForm.vue'), props: true },
      { path: 'agencies', name: 'admin.agencies', component: () => import('@/views/admin/AgencyList.vue') },
      { path: 'wallet/topup', name: 'admin.wallet.topup', component: () => import('@/views/admin/WalletTopUp.vue') },
      { path: 'refunds', name: 'admin.refunds', component: () => import('@/views/admin/RefundQueue.vue') },
      { path: 'refunds/:id', name: 'admin.refunds.review', component: () => import('@/views/admin/RefundReview.vue'), props: true },
      { path: 'reports', name: 'admin.reports', component: () => import('@/views/admin/Reports.vue') },
      { path: 'audit-log', name: 'admin.audit-log', component: () => import('@/views/admin/AuditLog.vue') },
      { path: 'profile', name: 'admin.profile', component: () => import('@/views/ProfilePage.vue') },
    ],
  },
  {
    path: '/agency',
    component: () => import('@/layouts/AgencyLayout.vue'),
    meta: { requireRole: 'agency' },
    children: [
      { path: '', name: 'agency.dashboard', component: () => import('@/views/agency/Dashboard.vue') },
      { path: 'orders', name: 'agency.orders', component: () => import('@/views/agency/OrderList.vue') },
      { path: 'orders/new', name: 'agency.orders.new', component: () => import('@/views/agency/OrderForm.vue') },
      { path: 'orders/:id', name: 'agency.orders.show', component: () => import('@/views/agency/OrderDetail.vue'), props: true },
      { path: 'wallet', name: 'agency.wallet', component: () => import('@/views/agency/WalletDashboard.vue') },
      { path: 'refunds', name: 'agency.refunds', component: () => import('@/views/agency/RefundList.vue') },
      { path: 'refunds/new', name: 'agency.refunds.new', component: () => import('@/views/agency/RefundForm.vue') },
      { path: 'reports', name: 'agency.reports', component: () => import('@/views/agency/Reports.vue') },
      { path: 'profile', name: 'agency.profile', component: () => import('@/views/ProfilePage.vue') },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (to.meta.public) {
    return true
  }

  if (!auth.user && auth.token) {
    await auth.fetchCurrentUser()
  }

  if (!auth.isAuthenticated) {
    return { path: '/login' }
  }

  if (to.meta.requireRole === 'admin' && !auth.isAdmin) {
    return { path: '/login' }
  }

  if (to.meta.requireRole === 'agency' && !auth.isAgency) {
    return { path: '/login' }
  }

  return true
})

export default router

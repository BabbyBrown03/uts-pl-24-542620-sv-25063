import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '@/views/LoginView.vue'
import DashboardView from '@/views/DashboardView.vue'

const routes = [
  { path: '/', redirect: '/barang' },
  { path: '/login', name: 'login', component: LoginView, meta: { guestOnly: true } },
  {
    path: '/barang',
    name: 'barang',
    component: DashboardView,
    meta: { requiresAuth: true },
  },
  { path: '/:pathMatch(.*)*', redirect: '/barang' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

// Guard: seluruh halaman selain /login wajib punya token.
router.beforeEach((to) => {
  const hasToken = !!localStorage.getItem('token')
  if (to.meta.requiresAuth && !hasToken) {
    return { name: 'login' }
  }
  if (to.meta.guestOnly && hasToken) {
    return { name: 'barang' }
  }
  return true
})

export default router
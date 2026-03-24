import { createRouter, createWebHistory } from 'vue-router'
import { useAuth } from './composables/useAuth'

import LoginView from './views/LoginView.vue'
import DashboardView from './views/DashboardView.vue'
import SiteDetailView from './views/SiteDetailView.vue'

const routes = [
  { path: '/login', name: 'login', component: LoginView, meta: { guest: true } },
  { path: '/', name: 'dashboard', component: DashboardView },
  { path: '/sites/:id', name: 'site', component: SiteDetailView, props: true },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach(async (to) => {
  const { user, fetchUser } = useAuth()

  if (!user.value) {
    await fetchUser()
  }

  if (!to.meta.guest && !user.value) {
    return { name: 'login' }
  }

  if (to.meta.guest && user.value) {
    return { name: 'dashboard' }
  }
})

export default router


import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/pages/auth/Login.vue'),
    meta: { layout: 'auth', guestOnly: true },
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('@/pages/auth/Register.vue'),
    meta: { layout: 'auth', guestOnly: true },
  },
  {
    path: '/',
    name: 'dashboard',
    component: () => import('@/pages/dashboard/Dashboard.vue'),
    meta: { layout: 'dashboard', requiresAuth: true },
  },
  {
    path: '/projects',
    name: 'projects',
    component: () => import('@/pages/projects/Projects.vue'),
    meta: { layout: 'dashboard', requiresAuth: true },
  },
  {
    path: '/projects/:id',
    name: 'project-details',
    component: () => import('@/pages/projects/ProjectDetails.vue'),
    meta: { layout: 'dashboard', requiresAuth: true },
  },
  {
    path: '/calendar',
    name: 'calendar',
    component: () => import('@/pages/calendar/Calendar.vue'),
    meta: { layout: 'dashboard', requiresAuth: true },
  },
  {
    path: '/reports',
    name: 'reports',
    component: () => import('@/pages/reports/Reports.vue'),
    meta: { layout: 'dashboard', requiresAuth: true },
  },
  {
    path: '/profile',
    name: 'profile',
    component: () => import('@/pages/profile/Profile.vue'),
    meta: { layout: 'dashboard', requiresAuth: true },
  },
  {
    path: '/settings',
    name: 'settings',
    component: () => import('@/pages/settings/Settings.vue'),
    meta: { layout: 'dashboard', requiresAuth: true },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: { name: 'dashboard' },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  },
})

router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login' }
  }

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }
})

export default router

import { createRouter, createWebHistory } from 'vue-router'
import LibraryView from '@/views/LibraryView.vue'
import SignInView from '@/views/SignInView.vue'
import SignUpView from '@/views/SignUpView.vue'
import ProfileView from '@/views/ProfileView.vue'
import NotebookView from '@/views/NotebookView.vue'
import { useAuthStore } from '@/stores/auth'

export const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'home', component: LibraryView, meta: { requiresAuth: true } },
    { path: '/notebooks/:id', name: 'notebook', component: NotebookView, meta: { requiresAuth: true } },
    { path: '/profile', name: 'profile', component: ProfileView, meta: { requiresAuth: true } },
    { path: '/sign-in', name: 'sign-in', component: SignInView, meta: { public: true } },
    { path: '/sign-up', name: 'sign-up', component: SignUpView, meta: { public: true } },
  ],
})

// Route guard (Phase 004). Routes are private by default: a new route must opt
// OUT with meta.public, so forgetting the flag fails closed rather than open.
// Phase 010's /shared/:token viewer is the deliberate public exception.
router.beforeEach(async (to) => {
  const auth = useAuthStore()

  // Wait for the stored bearer to be validated before deciding (boot race).
  if (!auth.ready) await auth.restore()

  if (to.meta.public) {
    return auth.isAuthenticated && to.name !== 'sign-up' ? { name: 'home' } : true
  }

  if (!auth.isAuthenticated) {
    return { name: 'sign-in', query: { redirect: to.fullPath } }
  }

  return true
})

import { ref } from 'vue'
import { useApi, onApiUnauthorized } from './useApi'
import type { User } from '../types'

const user = ref<User | null>(null)
const loading = ref(false)
let hasChecked = false

// Clear auth state on any 401 — router guard handles the redirect
onApiUnauthorized(() => { user.value = null })

export function useAuth() {
  const api = useApi()

  async function fetchUser() {
    if (hasChecked) return
    hasChecked = true
    try {
      const data = await api.get<{ user: User }>('/user')
      user.value = data.user
    } catch {
      user.value = null
    }
  }

  async function login(email: string, password: string) {
    loading.value = true
    try {
      // Get CSRF cookie first
      await fetch('/sanctum/csrf-cookie', { credentials: 'include' })
      const data = await api.post<{ user: User }>('/login', { email, password })
      user.value = data.user
      hasChecked = true
      return true
    } catch (e) {
      throw e
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    await api.post('/logout')
    user.value = null
    hasChecked = false
  }

  return { user, loading, fetchUser, login, logout }
}


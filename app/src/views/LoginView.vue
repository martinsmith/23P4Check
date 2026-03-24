<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'

const router = useRouter()
const { login, loading } = useAuth()

const email = ref('')
const password = ref('')
const error = ref('')

async function handleSubmit() {
  error.value = ''
  try {
    await login(email.value, password.value)
    router.push({ name: 'dashboard' })
  } catch (e: any) {
    error.value = e.message || 'Login failed'
  }
}
</script>

<template>
  <div class="login-page">
    <div class="login-card">
      <h1>23P4 Check</h1>
      <p class="subtitle">Sign in to your account</p>

      <form @submit.prevent="handleSubmit">
        <div class="field">
          <label for="email">Email</label>
          <input id="email" v-model="email" type="email" required autofocus placeholder="admin@23p4.local" />
        </div>

        <div class="field">
          <label for="password">Password</label>
          <input id="password" v-model="password" type="password" required placeholder="password" />
        </div>

        <p v-if="error" class="error">{{ error }}</p>

        <button type="submit" :disabled="loading">
          {{ loading ? 'Signing in…' : 'Sign in' }}
        </button>
      </form>
    </div>
  </div>
</template>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--surface-0);
}

.login-card {
  width: 100%;
  max-width: 380px;
  padding: 2.5rem;
  border-radius: 12px;
  background: var(--surface-1);
  box-shadow: 0 4px 24px oklch(0 0 0 / 0.08);
}

h1 {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0 0 0.25rem;
  color: var(--text-primary);
}

.subtitle {
  color: var(--text-secondary);
  margin: 0 0 1.5rem;
  font-size: 0.875rem;
}

.field {
  margin-bottom: 1rem;
}

label {
  display: block;
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--text-secondary);
  margin-bottom: 0.25rem;
}

input {
  width: 100%;
  padding: 0.625rem 0.75rem;
  border: 1px solid var(--border);
  border-radius: 8px;
  font-size: 0.9375rem;
  background: var(--surface-0);
  color: var(--text-primary);
  transition: border-color 0.15s;
}

input:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px oklch(0.6 0.15 250 / 0.15);
}

.error {
  color: var(--danger);
  font-size: 0.8125rem;
  margin: 0 0 0.75rem;
}

button {
  width: 100%;
  padding: 0.625rem;
  border: none;
  border-radius: 8px;
  background: var(--accent);
  color: white;
  font-size: 0.9375rem;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.15s;
}

button:hover:not(:disabled) {
  opacity: 0.9;
}

button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>


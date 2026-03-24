<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '../composables/useApi'
import { useAuth } from '../composables/useAuth'
import type { Site } from '../types'

const props = defineProps<{ id: string }>()
const router = useRouter()
const api = useApi()
const { user, logout } = useAuth()

const site = ref<Site | null>(null)
const loading = ref(true)
const scanning = ref(false)
const error = ref('')

async function loadSite() {
  loading.value = true
  error.value = ''
  try {
    const data = await api.get<{ site: Site }>(`/sites/${props.id}`)
    site.value = data.site
  } catch (e: any) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

async function triggerScan() {
  scanning.value = true
  error.value = ''
  try {
    const data = await api.post<{ site: Site }>(`/sites/${props.id}/scan`)
    site.value = data.site
  } catch (e: any) {
    error.value = e.message
  } finally {
    scanning.value = false
  }
}

async function completeFinding(findingId: number) {
  await api.post(`/sites/${props.id}/findings/${findingId}/complete`)
  await loadSite()
}

async function deleteSite() {
  if (!confirm('Delete this site?')) return
  await api.del(`/sites/${props.id}`)
  router.push({ name: 'dashboard' })
}

async function handleLogout() {
  await logout()
  router.push({ name: 'login' })
}

onMounted(loadSite)
</script>

<template>
  <div class="site-detail">
    <header class="topbar">
      <div class="topbar-left">
        <router-link to="/" class="back">&larr;</router-link>
        <h1>{{ site?.url || '' }}</h1>
      </div>
      <div class="user-area">
        <span class="user-name">{{ user?.name }}</span>
        <button class="btn-ghost" @click="handleLogout">Sign out</button>
      </div>
    </header>

    <main v-if="loading" class="loading-state">
      <p>Loading…</p>
    </main>

    <main v-else-if="!site && error" class="error-state">
      <p class="error">{{ error }}</p>
      <router-link to="/" class="btn-primary">Back to dashboard</router-link>
    </main>

    <main v-else-if="site">
      <div class="actions">
        <button class="btn-primary" @click="triggerScan" :disabled="scanning">
          {{ scanning ? 'Scanning…' : 'Run scan' }}
        </button>
        <button class="btn-danger-ghost" @click="deleteSite">Delete site</button>
      </div>
      <p v-if="error" class="error">{{ error }}</p>

      <p v-if="site.last_scanned_at" class="last-scan">
        Last scanned {{ new Date(site.last_scanned_at).toLocaleString() }}
      </p>

      <section class="findings">
        <h2>Findings ({{ site.findings?.length || 0 }})</h2>
        <p v-if="!site.findings?.length" class="empty">No findings yet. Run a scan to check this site.</p>

        <div v-for="f in site.findings" :key="f.id" class="finding-card" :class="f.severity">
          <div class="finding-header">
            <span class="severity-badge" :class="f.severity">{{ f.severity }}</span>
            <span class="check-slug">{{ f.check }}</span>
            <span class="status-badge" :class="f.status">{{ f.status }}</span>
          </div>
          <p class="finding-desc">{{ f.message }}</p>

          <div v-if="f.tasks.length" class="tasks">
            <div v-for="t in f.tasks" :key="t.id" class="task" :class="{ done: t.completed }">
              <span class="task-check">{{ t.completed ? '✓' : '○' }}</span>
              <span>{{ t.description }}</span>
            </div>
          </div>

          <div class="finding-actions">
            <button v-if="f.status === 'open'" class="btn-sm" @click="completeFinding(f.id)">Mark fixed</button>
          </div>
        </div>
      </section>
    </main>
  </div>
</template>

<style scoped>
.site-detail { min-height: 100vh; background: var(--surface-0); }
.topbar {
  display: flex; align-items: center; justify-content: space-between;
  padding: 1rem 2rem; border-bottom: 1px solid var(--border); background: var(--surface-1);
}
.topbar-left { display: flex; align-items: center; gap: 0.75rem; }
.back { text-decoration: none; font-size: 1.25rem; color: var(--text-secondary); }
.topbar h1 { font-size: 1.125rem; margin: 0; color: var(--text-primary); }
.user-area { display: flex; align-items: center; gap: 1rem; }
.user-name { font-size: 0.875rem; color: var(--text-secondary); }
.btn-ghost {
  background: none; border: 1px solid var(--border); border-radius: 6px;
  padding: 0.375rem 0.75rem; font-size: 0.8125rem; color: var(--text-secondary); cursor: pointer;
}

main { max-width: 720px; margin: 0 auto; padding: 2rem 1rem; }
.loading-state, .error-state { text-align: center; padding: 4rem 1rem; color: var(--text-secondary); }
.error-state .error { margin-bottom: 1rem; }
.actions { display: flex; gap: 0.75rem; margin-bottom: 1rem; }
.btn-primary {
  padding: 0.5rem 1.25rem; border: none; border-radius: 8px;
  background: var(--accent); color: white; font-weight: 600; cursor: pointer;
}
.btn-primary:disabled { opacity: 0.6; }
.btn-danger-ghost {
  padding: 0.5rem 1rem; border: 1px solid var(--danger); border-radius: 8px;
  background: none; color: var(--danger); font-size: 0.875rem; cursor: pointer;
}
.error { color: var(--danger); font-size: 0.8125rem; }
.last-scan { font-size: 0.8125rem; color: var(--text-tertiary); margin-bottom: 1.5rem; }

.findings h2 { font-size: 1rem; margin: 0 0 1rem; color: var(--text-primary); }
.empty { color: var(--text-secondary); text-align: center; padding: 2rem 0; }

.finding-card {
  padding: 1rem 1.25rem; border-radius: 10px; background: var(--surface-1);
  border: 1px solid var(--border); margin-bottom: 0.75rem;
}
.finding-header { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; }
.severity-badge {
  font-size: 0.6875rem; font-weight: 700; text-transform: uppercase;
  padding: 0.15rem 0.4rem; border-radius: 4px;
}
.severity-badge.high { background: oklch(0.9 0.08 25); color: oklch(0.4 0.15 25); }
.severity-badge.medium { background: oklch(0.9 0.05 60); color: oklch(0.4 0.12 60); }
.severity-badge.low { background: oklch(0.9 0.04 210); color: oklch(0.4 0.08 210); }
.check-slug { font-size: 0.8125rem; font-weight: 600; color: var(--text-primary); }
.status-badge { font-size: 0.6875rem; margin-left: auto; }
.status-badge.open { color: var(--danger); }
.status-badge.fixed { color: oklch(0.5 0.15 150); }
.finding-desc { font-size: 0.875rem; color: var(--text-secondary); margin: 0 0 0.75rem; }

.tasks { display: flex; flex-direction: column; gap: 0.25rem; margin-bottom: 0.75rem; }
.task { display: flex; align-items: center; gap: 0.5rem; font-size: 0.8125rem; color: var(--text-secondary); }
.task.done { text-decoration: line-through; opacity: 0.6; }
.task-check { width: 1rem; text-align: center; }

.btn-sm {
  padding: 0.3rem 0.75rem; border: 1px solid var(--border); border-radius: 6px;
  background: none; font-size: 0.75rem; color: var(--text-secondary); cursor: pointer;
}
.btn-sm:hover { border-color: var(--accent); color: var(--accent); }
</style>


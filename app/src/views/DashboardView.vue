<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '../composables/useApi'
import { useAuth } from '../composables/useAuth'
import type { Site } from '../types'

const router = useRouter()
const api = useApi()
const { user, logout } = useAuth()

const sites = ref<Site[]>([])
const newUrl = ref('')
const adding = ref(false)
const error = ref('')
const scanningId = ref<number | null>(null)

async function loadSites() {
  const data = await api.get<{ sites: Site[] }>('/sites')
  sites.value = data.sites
}

async function addSite() {
  if (!newUrl.value) return
  adding.value = true
  error.value = ''
  try {
    await api.post('/sites', { url: newUrl.value })
    newUrl.value = ''
    await loadSites()
  } catch (e: any) {
    error.value = e.message
  } finally {
    adding.value = false
  }
}

async function scanSite(siteId: number) {
  scanningId.value = siteId
  try {
    await api.post(`/sites/${siteId}/scan`)
    router.push({ name: 'site', params: { id: siteId } })
  } catch (e: any) {
    error.value = e.message
    scanningId.value = null
  }
}

async function handleLogout() {
  await logout()
  router.push({ name: 'login' })
}

onMounted(loadSites)
</script>

<template>
  <div class="dashboard">
    <header class="topbar">
      <h1>23P4 Check</h1>
      <div class="user-area">
        <span class="user-name">{{ user?.name }}</span>
        <button class="btn-ghost" @click="handleLogout">Sign out</button>
      </div>
    </header>

    <main>
      <section class="add-site">
        <form @submit.prevent="addSite" class="add-form">
          <input v-model="newUrl" type="url" placeholder="https://example.com" required />
          <button type="submit" :disabled="adding">{{ adding ? 'Adding…' : 'Add site' }}</button>
        </form>
        <p v-if="error" class="error">{{ error }}</p>
      </section>

      <section class="sites-list">
        <p v-if="sites.length === 0" class="empty">No sites yet. Add one above to get started.</p>

        <div v-for="site in sites" :key="site.id" class="site-card">
          <!-- Not yet scanned -->
          <template v-if="!site.last_scanned_at && scanningId !== site.id">
            <span class="site-url">{{ site.url }}</span>
            <button class="btn-scan" @click="scanSite(site.id)">Scan</button>
          </template>

          <!-- Currently scanning -->
          <template v-else-if="scanningId === site.id">
            <span class="site-url">{{ site.url }}</span>
            <span class="scanning-state"><span class="spinner" /> Scanning…</span>
          </template>

          <!-- Already scanned -->
          <template v-else>
            <router-link :to="{ name: 'site', params: { id: site.id } }" class="site-url site-link">{{ site.url }}</router-link>
            <span class="scanned-badge" :title="new Date(site.last_scanned_at!).toLocaleString()">Scanned</span>
            <span class="issues">
              <span v-if="site.open_findings_count" class="badge warn">
                {{ site.open_findings_count }} issue{{ site.open_findings_count === 1 ? '' : 's' }}
              </span>
              <span v-else class="badge ok">All clear</span>
            </span>
            <button class="btn-scan-again" @click="scanSite(site.id)">Scan again</button>
          </template>
        </div>
      </section>
    </main>
  </div>
</template>

<style scoped>
.dashboard {
  min-height: 100vh;
  background: var(--surface-0);
}

.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 2rem;
  border-bottom: 1px solid var(--border);
  background: var(--surface-1);
}

.topbar h1 { font-size: 1.125rem; margin: 0; color: var(--text-primary); }

.user-area { display: flex; align-items: center; gap: 1rem; }
.user-name { font-size: 0.875rem; color: var(--text-secondary); }

.btn-ghost {
  background: none; border: 1px solid var(--border); border-radius: 6px;
  padding: 0.375rem 0.75rem; font-size: 0.8125rem; color: var(--text-secondary);
  cursor: pointer;
}
.btn-ghost:hover { border-color: var(--text-secondary); }

main { max-width: 720px; margin: 0 auto; padding: 2rem 1rem; }

.add-form { display: flex; gap: 0.5rem; }
.add-form input {
  flex: 1; padding: 0.625rem 0.75rem; border: 1px solid var(--border);
  border-radius: 8px; font-size: 0.9375rem; background: var(--surface-1);
  color: var(--text-primary);
}
.add-form input:focus { outline: none; border-color: var(--accent); }
.add-form button {
  padding: 0.625rem 1.25rem; border: none; border-radius: 8px;
  background: var(--accent); color: white; font-weight: 600; font-size: 0.9375rem;
  cursor: pointer; white-space: nowrap;
}
.add-form button:disabled { opacity: 0.6; }

.error { color: var(--danger); font-size: 0.8125rem; margin-top: 0.5rem; }

.sites-list { margin-top: 2rem; display: flex; flex-direction: column; gap: 0.75rem; }

.empty { color: var(--text-secondary); text-align: center; padding: 3rem 0; }

.site-card {
  display: flex; align-items: center; gap: 1rem;
  padding: 1rem 1.25rem; border-radius: 10px; background: var(--surface-1);
  border: 1px solid var(--border);
  transition: border-color 0.15s, box-shadow 0.15s;
}
.site-card:hover { border-color: var(--accent); box-shadow: 0 2px 12px oklch(0 0 0 / 0.05); }

.site-url { font-weight: 600; color: var(--text-primary); font-size: 0.9375rem; flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.site-link { text-decoration: none; color: var(--text-primary); }
.site-link:hover { color: var(--accent); }

.scanned-badge {
  font-size: 0.75rem; font-weight: 600; padding: 0.2rem 0.5rem; border-radius: 999px;
  background: oklch(0.9 0.05 150); color: oklch(0.35 0.12 150);
  white-space: nowrap; cursor: default;
}
.issues { white-space: nowrap; }

.badge {
  font-size: 0.75rem; font-weight: 600; padding: 0.2rem 0.5rem; border-radius: 999px;
}
.badge.warn { background: oklch(0.9 0.05 60); color: oklch(0.4 0.12 60); }
.badge.ok { background: oklch(0.9 0.05 150); color: oklch(0.35 0.12 150); }

.btn-scan, .btn-scan-again {
  padding: 0.375rem 1rem; border: none; border-radius: 999px;
  background: var(--accent); color: white; font-weight: 600; font-size: 0.8125rem;
  cursor: pointer; transition: opacity 0.15s; white-space: nowrap;
}
.btn-scan:hover, .btn-scan-again:hover { opacity: 0.85; }
.btn-scan-again { background: none; border: 1px solid var(--border); color: var(--text-secondary); }
.btn-scan-again:hover { border-color: var(--accent); color: var(--accent); }

.scanning-state {
  display: flex; align-items: center; gap: 0.5rem;
  font-size: 0.8125rem; color: var(--text-tertiary);
}
.spinner {
  width: 1rem; height: 1rem; border-radius: 50%;
  border: 2.5px solid var(--border); border-top-color: var(--accent);
  animation: spin 0.7s linear infinite; display: inline-block;
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>


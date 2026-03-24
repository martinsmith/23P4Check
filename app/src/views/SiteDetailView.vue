<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
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

const failedFindings = computed(() =>
  (site.value?.findings ?? []).filter(f => f.status === 'open')
)

const passedFindings = computed(() =>
  (site.value?.findings ?? []).filter(f => f.status === 'passed')
)

const checkMeta: Record<string, { label: string; category: string }> = {
  missing_title: { label: 'Title Tag', category: 'Content' },
  long_title: { label: 'Title Tag', category: 'Content' },
  title: { label: 'Title Tag', category: 'Content' },
  missing_meta_desc: { label: 'Meta Description', category: 'Content' },
  long_meta_desc: { label: 'Meta Description', category: 'Content' },
  meta_description: { label: 'Meta Description', category: 'Content' },
  missing_h1: { label: 'H1 Heading', category: 'Content' },
  multiple_h1: { label: 'H1 Heading', category: 'Content' },
  h1: { label: 'H1 Heading', category: 'Content' },
  slow_ttfb: { label: 'Time to First Byte', category: 'Performance' },
  moderate_ttfb: { label: 'Time to First Byte', category: 'Performance' },
  ttfb: { label: 'Time to First Byte', category: 'Performance' },
  no_https: { label: 'HTTPS', category: 'Security' },
  https: { label: 'HTTPS', category: 'Security' },
  not_indexable: { label: 'Homepage Indexability', category: 'Technical' },
  noindex_directive: { label: 'Homepage Indexability', category: 'Technical' },
  indexability: { label: 'Homepage Indexability', category: 'Technical' },
  missing_viewport: { label: 'Viewport Meta Tag', category: 'Technical' },
  viewport: { label: 'Viewport Meta Tag', category: 'Technical' },
  missing_canonical: { label: 'Canonical URL', category: 'Technical' },
  canonical: { label: 'Canonical URL', category: 'Technical' },
  missing_lang: { label: 'Language Attribute', category: 'Technical' },
  lang_attribute: { label: 'Language Attribute', category: 'Technical' },
  missing_charset: { label: 'Character Encoding', category: 'Technical' },
  charset: { label: 'Character Encoding', category: 'Technical' },
  missing_analytics: { label: 'Analytics Tracking', category: 'Analytics' },
  analytics: { label: 'Analytics Tracking', category: 'Analytics' },
  missing_gsc: { label: 'Search Console', category: 'Visibility' },
  gsc_verification: { label: 'Search Console', category: 'Visibility' },
  missing_structured_data: { label: 'Structured Data', category: 'Technical' },
  structured_data: { label: 'Structured Data', category: 'Technical' },
  missing_sitemap: { label: 'XML Sitemap', category: 'Technical' },
  xml_sitemap: { label: 'XML Sitemap', category: 'Technical' },
  missing_robots_txt: { label: 'robots.txt', category: 'Technical' },
  robots_txt: { label: 'robots.txt', category: 'Technical' },
}

function checkLabel(slug: string): string {
  return checkMeta[slug]?.label ?? slug.replace(/_/g, ' ')
}

function checkCategory(slug: string): string {
  return checkMeta[slug]?.category ?? 'Other'
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
      <div class="page-header">
        <div class="page-header-left">
          <h2 class="site-title">{{ site.name || site.url }}</h2>
          <p v-if="site.last_scanned_at" class="last-scan">
            Last scanned {{ new Date(site.last_scanned_at).toLocaleString() }}
          </p>
        </div>
        <div class="actions">
          <button class="btn-primary" @click="triggerScan" :disabled="scanning">
            <span v-if="scanning" class="spinner" /> {{ scanning ? 'Scanning…' : 'Run scan' }}
          </button>
          <button class="btn-danger-ghost" @click="deleteSite">Delete</button>
        </div>
      </div>
      <p v-if="error" class="error">{{ error }}</p>

      <!-- Summary bar -->
      <div v-if="site.findings?.length" class="summary-bar">
        <div class="summary-stat" :class="{ 'has-issues': failedFindings.length }">
          <span class="summary-number">{{ failedFindings.length }}</span>
          <span class="summary-label">{{ failedFindings.length === 1 ? 'Issue' : 'Issues' }}</span>
        </div>
        <div class="summary-stat passed">
          <span class="summary-number">{{ passedFindings.length }}</span>
          <span class="summary-label">Passed</span>
        </div>
        <div class="summary-stat">
          <span class="summary-number">{{ (site.findings ?? []).length }}</span>
          <span class="summary-label">Total Checks</span>
        </div>
      </div>

      <!-- Issues -->
      <section v-if="failedFindings.length" class="findings-section">
        <h3 class="section-title">Issues to Fix</h3>
        <div class="card-grid">
          <div v-for="f in failedFindings" :key="f.id" class="check-card issue">
            <div class="card-top">
              <span class="category-badge">{{ checkCategory(f.check) }}</span>
              <span class="status-pill severity" :class="f.severity">{{ f.severity }}</span>
            </div>
            <h4 class="card-title">{{ checkLabel(f.check) }}</h4>
            <p class="card-desc">{{ f.message }}</p>
            <div v-if="f.tasks.length" class="card-tasks">
              <div v-for="t in f.tasks" :key="t.id" class="task-item" :class="{ done: t.completed }">
                <span class="task-icon">{{ t.completed ? '✓' : '○' }}</span>
                <span>{{ t.description }}</span>
              </div>
            </div>
            <button v-if="f.status === 'open'" class="btn-fix" @click="completeFinding(f.id)">Mark fixed</button>
          </div>
        </div>
      </section>

      <!-- Passed -->
      <section v-if="passedFindings.length" class="findings-section">
        <h3 class="section-title section-title-passed">Passed Checks</h3>
        <div class="card-grid">
          <div v-for="f in passedFindings" :key="f.id" class="check-card pass">
            <div class="card-top">
              <span class="category-badge">{{ checkCategory(f.check) }}</span>
              <span class="status-pill passed">✓ Passed</span>
            </div>
            <h4 class="card-title">{{ checkLabel(f.check) }}</h4>
            <p class="card-desc">{{ f.message }}</p>
          </div>
        </div>
      </section>

      <p v-if="!site.findings?.length" class="empty">No findings yet. Run a scan to check this site.</p>
    </main>
  </div>
</template>

<style scoped>
/* Layout */
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

main { max-width: 960px; margin: 0 auto; padding: 2rem 1.5rem; }
.loading-state, .error-state { text-align: center; padding: 4rem 1rem; color: var(--text-secondary); }
.error-state .error { margin-bottom: 1rem; }
.error { color: var(--danger); font-size: 0.8125rem; }

/* Page header */
.page-header {
  display: flex; align-items: flex-start; justify-content: space-between;
  margin-bottom: 1.5rem; gap: 1rem;
}
.site-title { font-size: 1.25rem; font-weight: 700; margin: 0; color: var(--text-primary); }
.last-scan { font-size: 0.8125rem; color: var(--text-tertiary); margin-top: 0.25rem; }
.actions { display: flex; gap: 0.5rem; flex-shrink: 0; }
.btn-primary {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.5rem 1.25rem; border: none; border-radius: 8px;
  background: var(--accent); color: white; font-weight: 600; cursor: pointer;
  font-size: 0.875rem;
}
.btn-primary:disabled { opacity: 0.6; }
.btn-danger-ghost {
  padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 8px;
  background: none; color: var(--text-secondary); font-size: 0.8125rem; cursor: pointer;
}
.btn-danger-ghost:hover { border-color: var(--danger); color: var(--danger); }

/* Summary bar */
.summary-bar {
  display: flex; gap: 1rem; margin-bottom: 2rem;
  padding: 1rem 1.5rem; background: var(--surface-1);
  border: 1px solid var(--border); border-radius: 12px;
}
.summary-stat { text-align: center; flex: 1; }
.summary-number { display: block; font-size: 1.5rem; font-weight: 700; color: var(--text-primary); }
.summary-label { font-size: 0.75rem; color: var(--text-tertiary); text-transform: uppercase; letter-spacing: 0.05em; }
.summary-stat.has-issues .summary-number { color: var(--danger); }
.summary-stat.passed .summary-number { color: oklch(0.5 0.18 150); }

/* Section titles */
.section-title {
  font-size: 0.875rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: 0.06em; color: var(--text-tertiary);
  margin: 0 0 1rem; padding-bottom: 0.5rem;
  border-bottom: 1px solid var(--border);
}
.section-title-passed { color: oklch(0.45 0.12 150); }

/* Card grid */
.card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 0.75rem;
}
.findings-section { margin-bottom: 2.5rem; }

/* Check card */
.check-card {
  padding: 1.25rem; border-radius: 12px; background: var(--surface-1);
  border: 1px solid var(--border);
  display: flex; flex-direction: column;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.check-card:hover { box-shadow: 0 2px 12px oklch(0 0 0 / 0.06); }

.card-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; }

.category-badge {
  font-size: 0.6875rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: 0.04em; color: var(--text-tertiary);
}

.status-pill {
  font-size: 0.6875rem; font-weight: 700; text-transform: uppercase;
  padding: 0.2rem 0.5rem; border-radius: 999px;
}
.status-pill.passed { background: oklch(0.92 0.06 150); color: oklch(0.4 0.15 150); }
.status-pill.severity.high { background: oklch(0.92 0.06 25); color: oklch(0.45 0.15 25); }
.status-pill.severity.medium { background: oklch(0.92 0.05 60); color: oklch(0.45 0.12 60); }
.status-pill.severity.low { background: oklch(0.92 0.04 250); color: oklch(0.45 0.1 250); }

.card-title { font-size: 0.9375rem; font-weight: 600; margin: 0 0 0.375rem; color: var(--text-primary); }
.card-desc { font-size: 0.8125rem; color: var(--text-secondary); margin: 0; line-height: 1.5; flex: 1; }

/* Passed card accent */
.check-card.pass { border-color: oklch(0.85 0.08 150); }

/* Issue card accent */
.check-card.issue { border-left: 3px solid; }
.check-card.issue:has(.severity.high) { border-left-color: oklch(0.55 0.2 25); }
.check-card.issue:has(.severity.medium) { border-left-color: oklch(0.6 0.15 60); }
.check-card.issue:has(.severity.low) { border-left-color: oklch(0.6 0.12 250); }

/* Tasks inside cards */
.card-tasks {
  display: flex; flex-direction: column; gap: 0.25rem;
  margin-top: 0.75rem; padding-top: 0.75rem;
  border-top: 1px solid var(--border);
}
.task-item {
  display: flex; align-items: center; gap: 0.4rem;
  font-size: 0.8125rem; color: var(--text-secondary);
}
.task-item.done { text-decoration: line-through; opacity: 0.6; }
.task-icon { width: 1rem; text-align: center; flex-shrink: 0; }

.btn-fix {
  margin-top: 0.75rem; align-self: flex-start;
  padding: 0.3rem 0.75rem; border: 1px solid var(--border); border-radius: 6px;
  background: none; font-size: 0.75rem; color: var(--text-secondary); cursor: pointer;
}
.btn-fix:hover { border-color: var(--accent); color: var(--accent); }

.empty { color: var(--text-secondary); text-align: center; padding: 3rem 0; }

/* Spinner */
.spinner {
  width: 0.875rem; height: 0.875rem; border-radius: 50%;
  border: 2px solid oklch(1 0 0 / 0.3); border-top-color: white;
  animation: spin 0.6s linear infinite; display: inline-block;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* Dark mode overrides */
@media (prefers-color-scheme: dark) {
  .check-card.pass { border-color: oklch(0.35 0.06 150); }
  .status-pill.passed { background: oklch(0.25 0.06 150); color: oklch(0.75 0.12 150); }
  .status-pill.severity.high { background: oklch(0.25 0.06 25); color: oklch(0.75 0.12 25); }
  .status-pill.severity.medium { background: oklch(0.25 0.05 60); color: oklch(0.75 0.1 60); }
  .status-pill.severity.low { background: oklch(0.25 0.04 250); color: oklch(0.7 0.08 250); }
  .section-title-passed { color: oklch(0.65 0.12 150); }
  .summary-stat.passed .summary-number { color: oklch(0.65 0.15 150); }
}
</style>


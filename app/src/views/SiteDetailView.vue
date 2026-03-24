<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '../composables/useApi'
import { useAuth } from '../composables/useAuth'
import type { Site, Mission } from '../types'

const props = defineProps<{ id: string }>()
const router = useRouter()
const api = useApi()
const { user, logout } = useAuth()

const site = ref<Site | null>(null)
const loading = ref(true)
const scanning = ref(false)
const saving = ref(false)
const error = ref('')
const activeTab = ref<'health' | 'growth'>('health')

const missions = ref<Mission[]>([])
const generatingMissions = ref(false)

// Business context form
const bizForm = ref({ business_type: '', location: '', service_area: '' })
const competitorInputs = ref<string[]>([''])

function initBizForm() {
  if (!site.value) return
  bizForm.value = {
    business_type: site.value.business_type || '',
    location: site.value.location || '',
    service_area: site.value.service_area || '',
  }
  const domains = (site.value.competitors || []).map(c => c.domain)
  competitorInputs.value = domains.length ? domains : ['']
}

const hasBusinessContext = computed(() =>
  site.value?.business_type || site.value?.location
)

function addCompetitor() {
  if (competitorInputs.value.length < 5) {
    competitorInputs.value.push('')
  }
}

function removeCompetitor(i: number) {
  competitorInputs.value.splice(i, 1)
  if (competitorInputs.value.length === 0) competitorInputs.value.push('')
}

async function saveBusinessContext() {
  saving.value = true
  error.value = ''
  try {
    const competitors = competitorInputs.value.map(d => d.trim()).filter(Boolean)
    const data = await api.put<{ site: Site }>(`/sites/${props.id}`, {
      ...bizForm.value,
      competitors,
    })
    site.value = { ...site.value!, ...data.site }
    initBizForm()
    // Auto-generate missions when business context is saved
    await generateMissions()
  } catch (e: any) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}

async function loadSite() {
  loading.value = true
  error.value = ''
  try {
    const data = await api.get<{ site: Site }>(`/sites/${props.id}`)
    site.value = data.site
    initBizForm()
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

async function loadMissions() {
  try {
    const data = await api.get<{ missions: Mission[] }>(`/sites/${props.id}/missions`)
    missions.value = data.missions
  } catch {}
}

async function generateMissions() {
  generatingMissions.value = true
  try {
    const data = await api.post<{ missions: Mission[] }>(`/sites/${props.id}/missions/generate`)
    missions.value = data.missions
  } catch (e: any) {
    error.value = e.message
  } finally {
    generatingMissions.value = false
  }
}

async function toggleStep(mission: Mission, stepId: number) {
  try {
    const data = await api.post<{ mission: Mission }>(`/sites/${props.id}/missions/${mission.id}/steps/${stepId}/toggle`)
    const idx = missions.value.findIndex(m => m.id === mission.id)
    if (idx !== -1) missions.value[idx] = data.mission
  } catch (e: any) {
    error.value = e.message
  }
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
  title: { label: 'Title Tag', category: 'Content' },
  meta_description: { label: 'Meta Description', category: 'Content' },
  h1: { label: 'H1 Heading', category: 'Content' },
  ttfb: { label: 'Time to First Byte', category: 'Performance' },
  https: { label: 'HTTPS', category: 'Security' },
  indexability: { label: 'Homepage Indexability', category: 'Technical' },
  viewport: { label: 'Viewport Meta Tag', category: 'Technical' },
  canonical: { label: 'Canonical URL', category: 'Technical' },
  lang_attribute: { label: 'Language Attribute', category: 'Technical' },
  charset: { label: 'Character Encoding', category: 'Technical' },
  analytics: { label: 'Analytics Tracking', category: 'Analytics' },
  gsc_verification: { label: 'Search Console', category: 'Visibility' },
  structured_data: { label: 'Structured Data', category: 'Technical' },
  xml_sitemap: { label: 'XML Sitemap', category: 'Technical' },
  robots_txt: { label: 'robots.txt', category: 'Technical' },
  google_business_profile: { label: 'Google Business Profile', category: 'Visibility' },
}

function checkLabel(slug: string): string {
  return checkMeta[slug]?.label ?? slug.replace(/_/g, ' ')
}

function checkCategory(slug: string): string {
  return checkMeta[slug]?.category ?? 'Other'
}

const missionProgress = computed(() => {
  const total = missions.value.reduce((sum, m) => sum + m.steps.length, 0)
  const done = missions.value.reduce((sum, m) => sum + m.steps.filter(s => s.completed).length, 0)
  return { total, done, pct: total ? Math.round((done / total) * 100) : 0 }
})

const pendingMissions = computed(() => missions.value.filter(m => m.status !== 'completed'))
const completedMissions = computed(() => missions.value.filter(m => m.status === 'completed'))

const categoryLabels: Record<string, string> = {
  local_seo: 'Local SEO',
  content: 'Content',
  technical: 'Technical',
  tracking: 'Tracking',
}

onMounted(async () => {
  await loadSite()
  if (site.value) await loadMissions()
})
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
          <button class="btn-primary" @click="triggerScan" :disabled="scanning" v-if="activeTab === 'health'">
            <span v-if="scanning" class="spinner" /> {{ scanning ? 'Scanning…' : 'Run scan' }}
          </button>
          <button class="btn-danger-ghost" @click="deleteSite">Delete</button>
        </div>
      </div>
      <p v-if="error" class="error">{{ error }}</p>

      <!-- Tabs -->
      <nav class="tabs">
        <button class="tab" :class="{ active: activeTab === 'health' }" @click="activeTab = 'health'">
          Website Visibility
        </button>
        <button class="tab" :class="{ active: activeTab === 'growth' }" @click="activeTab = 'growth'">
          Growth Plan
          <span v-if="!hasBusinessContext" class="tab-badge">Setup</span>
        </button>
      </nav>

      <!-- ===== TAB: Website Visibility ===== -->
      <div v-if="activeTab === 'health'">
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
      </div>

      <!-- ===== TAB: Growth Plan ===== -->
      <div v-if="activeTab === 'growth'" class="growth-tab">
        <div class="growth-intro">
          <h3>Tell us about your business</h3>
          <p>This information helps us generate targeted growth missions and track the keywords that matter to you.</p>
        </div>

        <form class="biz-form" @submit.prevent="saveBusinessContext">
          <div class="form-group">
            <label for="business_type">Business Type</label>
            <input id="business_type" v-model="bizForm.business_type" type="text" placeholder="e.g. Plumber, Accountant, Restaurant" />
          </div>
          <div class="form-group">
            <label for="location">Location</label>
            <input id="location" v-model="bizForm.location" type="text" placeholder="e.g. Manchester, UK" />
          </div>
          <div class="form-group">
            <label for="service_area">Service Area</label>
            <input id="service_area" v-model="bizForm.service_area" type="text" placeholder="e.g. Greater Manchester, North West England" />
          </div>

          <div class="form-group">
            <label>Competitors <span class="hint">(up to 5 domains)</span></label>
            <div v-for="(_, i) in competitorInputs" :key="i" class="competitor-row">
              <input v-model="competitorInputs[i]" type="text" placeholder="e.g. competitor.com" />
              <button type="button" class="btn-icon" @click="removeCompetitor(i)" title="Remove">✕</button>
            </div>
            <button v-if="competitorInputs.length < 5" type="button" class="btn-add-competitor" @click="addCompetitor">+ Add competitor</button>
          </div>

          <button type="submit" class="btn-primary" :disabled="saving">
            <span v-if="saving" class="spinner" /> {{ saving ? 'Saving…' : (hasBusinessContext ? 'Update' : 'Save & Unlock Growth Plan') }}
          </button>
        </form>

        <!-- Missions -->
        <div v-if="hasBusinessContext && missions.length" class="missions-section">
          <div class="missions-header">
            <div>
              <h3 class="section-title">Your Growth Missions</h3>
              <p class="missions-subtitle">{{ missionProgress.done }}/{{ missionProgress.total }} steps completed ({{ missionProgress.pct }}%)</p>
            </div>
            <button class="btn-ghost" @click="generateMissions" :disabled="generatingMissions">
              <span v-if="generatingMissions" class="spinner" /> {{ generatingMissions ? 'Updating…' : '↻ Refresh' }}
            </button>
          </div>

          <div class="progress-bar-track">
            <div class="progress-bar-fill" :style="{ width: missionProgress.pct + '%' }" />
          </div>

          <!-- Active missions -->
          <div v-if="pendingMissions.length" class="mission-list">
            <div v-for="m in pendingMissions" :key="m.id" class="mission-card" :class="m.status">
              <div class="mission-card-top">
                <span class="category-badge">{{ categoryLabels[m.category] || m.category }}</span>
                <span class="mission-type-badge" :class="m.type">{{ m.type }}</span>
              </div>
              <h4 class="mission-title">{{ m.title }}</h4>
              <p class="mission-desc">{{ m.description }}</p>
              <div class="mission-steps">
                <div
                  v-for="s in m.steps"
                  :key="s.id"
                  class="step-item"
                  :class="{ done: s.completed }"
                  @click="toggleStep(m, s.id)"
                >
                  <span class="step-check">{{ s.completed ? '✓' : '○' }}</span>
                  <span>{{ s.description }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Completed missions -->
          <div v-if="completedMissions.length" class="completed-section">
            <h3 class="section-title section-title-passed">Completed Missions</h3>
            <div class="mission-list">
              <div v-for="m in completedMissions" :key="m.id" class="mission-card completed">
                <div class="mission-card-top">
                  <span class="category-badge">{{ categoryLabels[m.category] || m.category }}</span>
                  <span class="status-pill passed">✓ Done</span>
                </div>
                <h4 class="mission-title">{{ m.title }}</h4>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty state when context saved but no missions generated yet -->
        <div v-else-if="hasBusinessContext && !missions.length" class="growth-status">
          <div class="status-card">
            <span class="status-icon">✓</span>
            <div>
              <h4>Business context saved</h4>
              <p>{{ site.business_type }} · {{ site.location }}</p>
            </div>
          </div>
          <button class="btn-primary" @click="generateMissions" :disabled="generatingMissions" style="margin-top: 1rem;">
            <span v-if="generatingMissions" class="spinner" /> {{ generatingMissions ? 'Generating…' : 'Generate Growth Missions' }}
          </button>
        </div>
      </div>
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

/* Tabs */
.tabs {
  display: flex; gap: 0; margin-bottom: 1.5rem;
  border-bottom: 2px solid var(--border);
}
.tab {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.625rem 1.25rem; border: none; background: none;
  font-size: 0.875rem; font-weight: 600; color: var(--text-tertiary);
  cursor: pointer; border-bottom: 2px solid transparent;
  margin-bottom: -2px; transition: color 0.15s, border-color 0.15s;
}
.tab:hover { color: var(--text-primary); }
.tab.active { color: var(--accent); border-bottom-color: var(--accent); }
.tab-badge {
  font-size: 0.625rem; font-weight: 700; text-transform: uppercase;
  padding: 0.1rem 0.4rem; border-radius: 999px;
  background: oklch(0.92 0.05 60); color: oklch(0.45 0.12 60);
}

/* Growth Plan tab */
.growth-tab { max-width: 640px; }
.growth-intro { margin-bottom: 1.5rem; }
.growth-intro h3 { font-size: 1.125rem; font-weight: 700; margin: 0 0 0.375rem; color: var(--text-primary); }
.growth-intro p { font-size: 0.875rem; color: var(--text-secondary); line-height: 1.5; }

.biz-form { display: flex; flex-direction: column; gap: 1.25rem; margin-bottom: 2rem; }
.form-group { display: flex; flex-direction: column; gap: 0.375rem; }
.form-group label { font-size: 0.8125rem; font-weight: 600; color: var(--text-primary); }
.form-group .hint { font-weight: 400; color: var(--text-tertiary); }
.form-group input {
  padding: 0.5rem 0.75rem; border: 1px solid var(--border); border-radius: 8px;
  background: var(--surface-1); color: var(--text-primary); font-size: 0.875rem;
  outline: none; transition: border-color 0.15s;
}
.form-group input:focus { border-color: var(--accent); }
.form-group input::placeholder { color: var(--text-tertiary); }

.competitor-row { display: flex; gap: 0.5rem; margin-bottom: 0.375rem; }
.competitor-row input { flex: 1; }
.btn-icon {
  width: 2rem; height: 2rem; display: flex; align-items: center; justify-content: center;
  border: 1px solid var(--border); border-radius: 6px; background: none;
  color: var(--text-tertiary); cursor: pointer; font-size: 0.75rem; flex-shrink: 0;
}
.btn-icon:hover { border-color: var(--danger); color: var(--danger); }
.btn-add-competitor {
  align-self: flex-start; padding: 0.3rem 0.75rem; border: 1px dashed var(--border);
  border-radius: 6px; background: none; font-size: 0.8125rem; color: var(--text-secondary);
  cursor: pointer;
}
.btn-add-competitor:hover { border-color: var(--accent); color: var(--accent); }

.growth-status { margin-top: 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
.status-card {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 1rem 1.25rem; background: var(--surface-1);
  border: 1px solid oklch(0.85 0.08 150); border-radius: 12px;
}
.status-icon {
  width: 2rem; height: 2rem; display: flex; align-items: center; justify-content: center;
  border-radius: 50%; background: oklch(0.92 0.06 150); color: oklch(0.4 0.15 150);
  font-size: 0.875rem; font-weight: 700; flex-shrink: 0;
}
.status-card h4 { font-size: 0.875rem; font-weight: 600; margin: 0; color: var(--text-primary); }
.status-card p { font-size: 0.8125rem; color: var(--text-secondary); margin: 0; }

/* Missions */
.missions-section { margin-top: 2rem; }
.missions-header {
  display: flex; align-items: flex-start; justify-content: space-between;
  margin-bottom: 1rem;
}
.missions-subtitle { font-size: 0.8125rem; color: var(--text-secondary); margin-top: 0.25rem; }

.progress-bar-track {
  height: 6px; background: var(--border); border-radius: 999px;
  margin-bottom: 1.5rem; overflow: hidden;
}
.progress-bar-fill {
  height: 100%; background: oklch(0.55 0.18 150); border-radius: 999px;
  transition: width 0.3s ease;
}

.mission-list { display: flex; flex-direction: column; gap: 0.75rem; }
.mission-card {
  padding: 1.25rem; border-radius: 12px; background: var(--surface-1);
  border: 1px solid var(--border); transition: border-color 0.15s;
}
.mission-card.in_progress { border-left: 3px solid oklch(0.55 0.18 150); }
.mission-card.completed { opacity: 0.7; }

.mission-card-top {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 0.5rem;
}
.mission-type-badge {
  font-size: 0.625rem; font-weight: 700; text-transform: uppercase;
  padding: 0.15rem 0.4rem; border-radius: 999px;
}
.mission-type-badge.reactive { background: oklch(0.92 0.05 250); color: oklch(0.45 0.1 250); }
.mission-type-badge.proactive { background: oklch(0.92 0.05 300); color: oklch(0.45 0.1 300); }

.mission-title { font-size: 0.9375rem; font-weight: 600; margin: 0 0 0.375rem; color: var(--text-primary); }
.mission-desc { font-size: 0.8125rem; color: var(--text-secondary); margin: 0 0 0.75rem; line-height: 1.5; }

.mission-steps {
  display: flex; flex-direction: column; gap: 0.25rem;
  padding-top: 0.75rem; border-top: 1px solid var(--border);
}
.step-item {
  display: flex; align-items: flex-start; gap: 0.5rem;
  font-size: 0.8125rem; color: var(--text-secondary);
  padding: 0.375rem 0.25rem; border-radius: 6px; cursor: pointer;
  transition: background 0.1s;
}
.step-item:hover { background: oklch(0 0 0 / 0.03); }
.step-item.done { text-decoration: line-through; opacity: 0.6; }
.step-check {
  width: 1.125rem; height: 1.125rem; display: flex; align-items: center;
  justify-content: center; flex-shrink: 0; font-size: 0.75rem;
}
.step-item.done .step-check { color: oklch(0.5 0.18 150); }

.completed-section { margin-top: 2rem; }

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
  .tab-badge { background: oklch(0.25 0.05 60); color: oklch(0.75 0.1 60); }
  .status-card { border-color: oklch(0.35 0.06 150); }
  .status-icon { background: oklch(0.25 0.06 150); color: oklch(0.75 0.12 150); }
  .mission-type-badge.reactive { background: oklch(0.25 0.04 250); color: oklch(0.7 0.08 250); }
  .mission-type-badge.proactive { background: oklch(0.25 0.04 300); color: oklch(0.7 0.08 300); }
  .step-item:hover { background: oklch(1 0 0 / 0.03); }
  .progress-bar-fill { background: oklch(0.6 0.15 150); }
}
</style>


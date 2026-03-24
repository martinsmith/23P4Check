export interface User {
  id: number
  name: string
  email: string
}

export interface Task {
  id: number
  finding_id: number
  description: string
  completed: boolean
  sort: number
}

export interface Finding {
  id: number
  site_id: number
  check: string
  message: string
  severity: 'high' | 'medium' | 'low'
  status: 'open' | 'fixed' | 'passed'
  tasks: Task[]
  created_at: string
}

export interface Competitor {
  id: number
  site_id: number
  domain: string
}

export interface Site {
  id: number
  user_id: number
  url: string
  name: string | null
  business_type: string | null
  location: string | null
  service_area: string | null
  last_scanned_at: string | null
  open_findings_count?: number
  findings?: Finding[]
  competitors?: Competitor[]
  created_at: string
  updated_at: string
}

export interface MissionStep {
  id: number
  mission_id: number
  description: string
  sort: number
  completed: boolean
}

export interface Mission {
  id: number
  site_id: number
  slug: string
  title: string
  description: string
  category: string
  type: 'reactive' | 'proactive'
  priority: number
  status: 'pending' | 'in_progress' | 'completed'
  steps: MissionStep[]
  created_at: string
  updated_at: string
}

export interface ScanResult {
  status?: number
  ttfb_ms?: number
  title?: string
  meta_description?: string
  h1_count?: number
  h1_text?: string | null
  is_https?: boolean
  error?: string
}

export interface DashboardData {
  visibility_score: number
  checks: { passed: number; failed: number; total: number }
  missions: {
    total: number
    completed: number
    steps: { total: number; completed: number }
    pct: number
  }
  trend: Array<{
    passed: number
    failed: number
    total: number
    score: number
    date: string
  }>
}

export interface CompetitorResult {
  competitor_id: number
  domain: string
  results: Record<string, boolean> | null
  passed: number | null
  failed: number | null
  total: number | null
  scanned_at: string | null
}

export interface CompetitorComparisonData {
  own: {
    results: Record<string, boolean>
    passed: number
    failed: number
    total: number
  }
  competitors: CompetitorResult[]
}

export interface SerpKeyword {
  id: number
  phrase: string
}

export interface SerpResultEntry {
  id: number
  keyword: string
  position: number | null
  result_url: string | null
  snippet: string | null
  total_results: number | null
  checked_at: string
}

export interface SerpHistoryData {
  keywords: SerpKeyword[]
  history: SerpResultEntry[]
}


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
  status: 'open' | 'fixed'
  tasks: Task[]
  created_at: string
}

export interface Site {
  id: number
  user_id: number
  url: string
  name: string | null
  last_scanned_at: string | null
  open_findings_count?: number
  findings?: Finding[]
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


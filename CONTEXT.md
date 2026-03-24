# 23P4Check — Agent Context Reference

## What This Is
A rebuild of 23P4SEO. Decoupled Laravel 13 JSON API + Vue 3 / TypeScript SPA.
Principle: "Clarity → Action" — scan a site, show findings, give actionable tasks.

## Credentials
- Login: `admin@23p4.local` / `password`
- Seeder: `api/database/seeders/DatabaseSeeder.php`

## Dev Environment
- **Backend**: Laravel 13 via DDEV (`ddev start` from project root)
- **Frontend**: Vue 3 + Vite (`cd app && npm run dev` — runs on localhost:5173 or 5174)
- **Proxy**: Vite proxies `/api` and `/sanctum` to `https://23p4check.ddev.site`
- **Auth**: Sanctum SPA (stateful cookies, not tokens). `useApi.ts` sends `X-XSRF-TOKEN` header.

## Key Config
- `api/.env`: `SANCTUM_STATEFUL_DOMAINS=localhost:5173,localhost:5174` / `SESSION_DOMAIN=` (empty)
- `app/vite.config.ts`: proxy with `cookieDomainRewrite: ''` so cookies work on localhost

## Database Schema (source of truth)
### sites table
`id, user_id, url, name (nullable), last_scanned_at (nullable), score (smallint nullable — unused, reserved), timestamps`
- Composite index: none yet beyond foreign key

### findings table
`id, site_id, check (string), severity (string: high/medium/low), status (string, default 'open'), message (text), meta (json nullable), timestamps`
- Composite index: `(site_id, check, status)` — covers Scanner existence check + open findings queries

### tasks table
`id, finding_id, sort (tinyint default 0), description (text), completed (bool default false), timestamps`

### keywords table *(future — migration + model exist, no API routes yet)*
`id, site_id, phrase, volume (nullable), difficulty (nullable), timestamps`

## File Map

### Backend (api/)
| File | Purpose |
|------|---------|
| `app/Http/Controllers/AuthController.php` | login / logout / user |
| `app/Http/Controllers/SiteController.php` | CRUD sites, loads findings with open_findings_count |
| `app/Http/Controllers/ScanController.php` | POST /sites/{id}/scan — runs Scanner; POST complete finding |
| `app/Services/Scanner.php` | Single-pass SEO checks (title, meta desc, h1, ttfb, https) |
| `app/Models/User.php` | Has `sites()` relationship |
| `app/Models/Site.php` | Has `findings()` relationship |
| `app/Models/Finding.php` | fillable: `check, severity, status, message, meta`. Has `tasks()` |
| `app/Models/Task.php` | fillable: `sort, description, completed` |
| `routes/api.php` | All API routes |
| `database/backup.sql` | Latest DB export |

### Frontend (app/)
| File | Purpose |
|------|---------|
| `src/views/LoginView.vue` | Login form |
| `src/views/DashboardView.vue` | Site list — unscanned shows Scan btn, scanned shows row layout |
| `src/views/SiteDetailView.vue` | Findings + tasks for a site |
| `src/composables/useApi.ts` | Fetch wrapper — handles CSRF cookie + XSRF-TOKEN header; clears auth on 401 |
| `src/composables/useAuth.ts` | Reactive auth state, fetchUser (with hasChecked guard), login, logout |
| `src/router.ts` | 3 routes (login, dashboard, site detail) with auth guard |
| `src/types/index.ts` | TypeScript interfaces (User, Task, Finding, Site, ScanResult) |
| `src/style.css` | OKLCH CSS variables, dark mode support |
| `vite.config.ts` | Proxy config for DDEV backend |

## Column Name Mapping (IMPORTANT — past source of bugs)
The Scanner service, Eloquent models, and TS interfaces MUST all agree:
- Finding: `check` (not check_slug), `message` (not description)
- Task: `description` (not title), `completed` (not done)

## Tests
- **PHPUnit**: `cd api && php artisan test` — 12 feature tests covering auth, site CRUD, scan, complete finding.
- Uses SQLite in-memory (see `phpunit.xml`), `RefreshDatabase`, `Http::fake()` for scanner.

## Pending / Next Steps
1. **Git**: Need to `git add -A && git commit && git push`.
2. **Future features**: keyword tracking, scheduled scans, more SEO checks.
3. **Score column**: In migration but unused — reserved for future computed site health score.

## Gotchas Solved
- **Auth refresh loop**: Don't use `window.location.href` on 401 — let Vue Router guard handle it.
- **CORS/cookies**: Clear `SESSION_DOMAIN` in .env, use `cookieDomainRewrite` in Vite proxy.
- **Column mismatches**: Always check migration → model $fillable → Scanner create() → TS interface.
- **Circular dependency (useApi ↔ useAuth)**: `useApi` exports `onApiUnauthorized()` callback; `useAuth` registers it at module scope.
- **Login rate limiting**: `throttle:5,1` on POST /login.
- **Removed recheckFinding**: Was re-scanning entire site — "Run scan" already covers this.


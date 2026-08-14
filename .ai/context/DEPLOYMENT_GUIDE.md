# ALALAY: Production Deployment Guide
**Municipality of General Mamerto Natividad, Nueva Ecija**
**Railway (PaaS) — FrankenPHP + MySQL 8 — single-domain monolithic Laravel + Inertia + Vue 3**

---

## Overview

ALALAY is a **monolithic Laravel 12 application** that serves the Vue 3 + Inertia frontend from
Laravel itself. There is **no separate frontend/API split** — one domain serves both, so CORS,
two-subdomain cookie sharing, and a separate Nginx/static-file host are all unnecessary.

Production is deployed on **Railway**, a PaaS, because the application hard-requires **MySQL**
(five migrations use `ALTER TABLE ... MODIFY COLUMN ... ENUM(...)` behind a
`DB::getDriverName() !== 'mysql'` guard — Render has no managed MySQL, Railway does).

XAMPP is used **for local development only** and is never used in production.

---

## Production Architecture

```
Railway project "alalay"
├── Service: web (Railpack)        # FrankenPHP: HTTP server + PHP-FPM
│     buildCommand: bash ./railway/init.sh
│     preDeployCommand (dashboard): config/event/route/view caches + migrate --force + db:seed --force
│     healthcheck (dashboard): /up
│     variables: RAILPACK_SKIP_MIGRATIONS=true   # stop Railpack auto migrate+seed on start
├── Service: worker (Railpack)     # php artisan queue:work database
│     startCommand (dashboard): bash ./railway/worker.sh
│     variables: RAILPACK_SKIP_MIGRATIONS=true
├── Service: cron (Railpack)       # php artisan schedule:run every 60s
│     startCommand (dashboard): bash ./railway/cron.sh
│     variables: RAILPACK_SKIP_MIGRATIONS=true
├── Plugin: MySQL                   # managed MySQL 8, referenced via ${{MySQL.*}} env refs
└── Config: railway.json            # Railpack build only (builder + buildCommand); deploy settings are set per-service in the dashboard, and must be chained with `&&` (schema: single string)
```

Key files in the repo:

| File | Purpose |
|---|---|
| `railway.json` | Railpack **build** config only (builder `RAILPACK` + `buildCommand`); deploy commands/healthcheck are set per-service in the dashboard |
| `railway/init.sh` | Build step: `composer install --no-dev --optimize-autoloader`, `npm ci`, `npm run build` |
| `railway/worker.sh` | DB queue worker (tries=3, max-time=3600) |
| `railway/cron.sh` | 60-second `php artisan schedule:run` loop |
| `php.ini` | FrankenPHP runtime overrides (opcache, memory, upload limits, timezone) |

---

## Why Railway (and not Render / VPS)

- **MySQL is mandatory.** Five migrations (e.g. `2026_08_01_000001_workflow_policy_changes.php`)
  run `ALTER TABLE ... MODIFY COLUMN ... ENUM(...)` only when `DB::getDriverName() === 'mysql'`.
  Render provides PostgreSQL/Redis only → the app cannot run there without a rewrite.
- **Railway** ships a managed MySQL plugin and Railpack auto-detects Laravel (FrankenPHP,
  document root `public/`). A custom `buildCommand` in `railway.json` replaces Railpack's
  auto-build, so artisan caches run in **preDeploy (runtime)** where the real environment exists.
- A self-managed VPS is still viable (the older Nginx/Supervisor/Certbot approach), but adds
  server maintenance for no benefit on a municipal workload.

### Railpack facts that shaped this setup

- Builder `RAILPACK`; `RAILPACK_PHP_ROOT_DIR` can relocate the docroot; `RAILPACK_PHP_EXTENSIONS`
  can add PHP extensions.
- A custom `buildCommand` skips Railpack's automatic Laravel install step → **run artisan caches
  at runtime (preDeploy), never in build**.
- Railpack merges a repo-root `php.ini` over its default (the default has no `[opcache]` section
  and sets `expose_php=On`).
- An empty web **Start Command** lets Railpack use its detected FrankenPHP entrypoint.
- **`RAILPACK_SKIP_MIGRATIONS=true` is REQUIRED on every service.** Railpack's default Laravel
  start script runs `migrate --isolated --seed --force` at container boot — with no custom start
  command that would re-run migrations AND seeders on every start/restart. Migrations + seeds are
  done once in the web service's `preDeployCommand`; the flag makes the start script skip them.
- **Seeding is idempotent by design.** All seeders use `updateOrInsert`/`firstOrCreate` keyed on
  unique columns (`category_name`, `code_type`, `setting_key`, `doc_name`, `email`), and
  `DatabaseSeeder` deliberately **excludes** `ApplicationDemoSeeder` — production never gets demo
  applications. `db:seed --force` runs on every deploy as a safe no-op after the first. Reference
  data (categories, required documents, assistance codes, settings, SMS templates, admin accounts)
  is therefore guaranteed present on a fresh DB.

---

## Environment (production `.env`)

All secrets live in Railway's service **Variables**, not in the repo. Paste the ready-made block in
`railway/.env.production` (gitignored — real values) into each service's **RAW Editor**. The table
below documents the production values.

| Key | Production value | Notes |
|---|---|---|
| `APP_NAME` | `ALALAY` | |
| `APP_ENV` | `production` | |
| `APP_DEBUG` | `false` | PBD-2 (NPC) |
| `APP_URL` | `https://<your-railway-domain>` | HTTPS enforced by Railway edge |
| `APP_KEY` | fresh, generated | `php artisan key:generate` |
| `APP_LOCALE` | `en` | |
| `APP_MAINTENANCE_SECRET` | set | bypass maintenance via `/up?secret=` |
| `DB_CONNECTION` | `mysql` | |
| `DB_HOST` / `DB_PORT` | `${{MySQL.HOST}}` / `${{MySQL.PORT}}` | Railway env refs |
| `DB_DATABASE` / `DB_USERNAME` | `${{MySQL.DATABASE}}` / `${{MySQL.USERNAME}}` | |
| `DB_PASSWORD` | `${{MySQL.PASSWORD}}` | never write a literal |
| `SESSION_DRIVER` | `database` | |
| `SESSION_SECURE_COOKIE` | `true` | cookie over HTTPS only |
| `SESSION_DOMAIN` | (empty) | single domain — no cross-subdomain cookie needed |
| `QUEUE_CONNECTION` | `database` | |
| `CACHE_STORE` | `database` | |
| `FILESYSTEM_DISK` | `supabase` | all file storage is signed-URL backed |
| `MAIL_MAILER` | `smtp` | OTP + password reset |
| `MAIL_HOST` / `MAIL_PORT` / `MAIL_USERNAME` / `MAIL_PASSWORD` / `MAIL_ENCRYPTION` | provider values | TLS |
| `MAIL_FROM_ADDRESS` | `noreply@gmn.gov.ph` | |
| `SUPABASE_URL` | project URL | |
| `SUPABASE_KEY` | service-role key | |
| `SUPABASE_SECRET` | service-role secret | S3 credentials |
| `SUPABASE_STORAGE_REGION` | `ap-northeast-2` | match your Supabase project region |
| `SUPABASE_STORAGE_BUCKET` | `alalay-docs` | private bucket; app issues signed URLs |
| `SUPABASE_STORAGE_ENDPOINT` | `https://<ref>.supabase.co/storage/v1/s3` | |
| `SMS_DRIVER` | `philsms` | switch from `log` |
| `PHILSMS_API_TOKEN` | provider token | |
| `SMS_SENDER_NAME` | `PhilSMS` | account is not authorized for `ALALAY` |
| `SMS_API_ENDPOINT` | `https://dashboard.philsms.com/api/v3/sms/send` | |
| `BACKUP_ENCRYPT_PASS` | strong passphrase | AES-256 backup encryption |
| `BACKUP_RETENTION_DAYS` | `30` | |
| `SUPABASE_BACKUP_BUCKET` | `alalay-backups` | separate bucket, private |

> No volume is required. Nothing in the application writes to `public/` or `storage/app/` at
> runtime — documents and backups live in Supabase (signed URLs / S3 uploads). Build-time writes
> (compiled assets) ship in the image.

---

## Deploy flow (what actually happens)

1. Push to the branch wired to Railway; Railpack builds the image.
2. **Build** runs `railway/init.sh`:
   - `composer install --no-dev --optimize-autoloader`
   - `npm ci && npm run build` (Vite output into `public/build`, committed into the image)
   - Migrations are **not** run here (no prod env/db during build).
3. **PreDeploy** (web service, dashboard command, runtime env present):
   `config:cache && event:cache && route:cache && view:cache`, then `migrate --force`,
   then `db:seed --force` (idempotent; no demo applications — `ApplicationDemoSeeder` is excluded).
4. **Start**:
   - web: Railpack's FrankenPHP entrypoint (docroot `public/`, healthcheck hits `/up`).
     `RAILPACK_SKIP_MIGRATIONS=true` prevents Railpack from re-running migrate/seed at boot.
   - worker: `railway/worker.sh` → `queue:work database`.
   - cron: `railway/cron.sh` → loops `php artisan schedule:run` every 60 s.

### Scheduler jobs (defined in `routes/console.php`)

- `backup:run` — daily 02:00 (encrypted MySQL dump → `alalay-backups` bucket, 30-day retention).
- `backup:verify` — weekly Sunday 03:00 (restores into `alalay_backup_test` to prove recoverability).

---

## Security hardening checklist (production)

| Item | Where | NPC ref |
|---|---|---|
| `APP_ENV=production`, `APP_DEBUG=false` | Railway variables | PBD-2 |
| HTTPS enforced | Railway edge (default) | ACC-1 |
| Security response headers | Laravel `trustProxies(at: '*')` in `bootstrap/app.php` + app middleware; HSTS via Railpack | PBD-2 |
| `.env` never served | FrankenPHP docroot is `public/` only | ACC-1 |
| `composer install --no-dev` | build script | PBD-2 |
| Queue worker auto-restart | Railway `restartPolicyType: ON_FAILURE` | BCP-1 |
| Daily encrypted backup + weekly verify | scheduler (above) | BCP-1 |
| Signed-URL-only document access | `SignedUrlService`; buckets are private | TRF-2 |
| Append-only audit_logs / reviews | app-layer policy (DB-level REVOKE applies to self-managed MySQL) | MSC-3 |
| Login rate limiting | `throttle` on login routes | ACC-6 |
| Session cookie flags | `SESSION_SECURE_COOKIE=true`, same-site `lax` | ACC-1 |
| MySQL creds via env refs only | never literals in repo/guide | ACC-1 |

---

## Go-live checklist

```
PRE-DEPLOYMENT
  [ ] Railway project created (Empty Project)
  [ ] MySQL plugin added (Railway)
  [ ] App service added 3× (each "+ New → GitHub Repo", same repo/branch — one service per click), renamed web/worker/cron
  [ ] .gov.ph domain requested from DICT (MC 005 s. 2020)
  [ ] Custom domain attached to the web service; DNS CNAME set
  [ ] Supabase project created: alalay-docs (private) + alalay-backups (private) buckets
  [ ] PhilSMS token + SMTP credentials obtained

VARIABLES (per service)
  [ ] All keys from the environment table set on the web/worker/cron services
  [ ] APP_KEY generated once
  [ ] RAILPACK_SKIP_MIGRATIONS=true on ALL services (web, worker, cron)
  [ ] PHILSMS token, SUPABASE keys, SMTP, BACKUP_ENCRYPT_PASS
  [ ] DB_* as ${{MySQL.*}} references

APPLICATION (all happens automatically in preDeploy)
  [ ] Migrations run via preDeploy
  [ ] Reference data + admin accounts seeded via `db:seed --force` (idempotent, demo excluded)
  [ ] Vite assets built during deploy; first load shows styled login page

VERIFICATION
  [ ] GET /up returns 200 (healthcheck green)
  [ ] Login + OTP flow works; SMS actually delivers
  [ ] Document upload → signed URL → view/download works
  [ ] Queue worker processing (SendSmsJob, BackupDatabaseJob)
  [ ] Scheduler: backup:run writes to alalay-backups (check Supabase)
  [ ] All role panels reachable per role matrix
  [ ] Admin force-logout / session revocation works
```

---

## Local vs production comparison

| Concern | Local (XAMPP / `php artisan serve`) | Production (Railway) |
|---|---|---|
| Web server | Laravel dev server / XAMPP Apache | FrankenPHP (HTTP + PHP-FPM) |
| PHP | XAMPP bundled | Railway container (8.3) + repo `php.ini` |
| Database | local MySQL (or sqlite in `.env.example`) | Railway MySQL 8 |
| Frontend | Vite dev server (5173) | Built by `npm run build`, served by Laravel |
| Queue worker | manual `php artisan queue:work` | Railway worker service (auto-restart) |
| Scheduler | manual | Railway cron service |
| HTTPS | none | Railway edge (auto) |
| `APP_DEBUG` | `true` | `false` |
| File storage | local `storage/` | Supabase (private, signed URLs) |
| Backups | manual | daily encrypted + weekly verify |
| SMS | `SMS_DRIVER=log` | `SMS_DRIVER=philsms` |

---

## Notes / deferred items

- **Dependency advisories** (not yet fixed): composer — guzzlehttp/guzzle 7.13.1 (high),
  phpspreadsheet via maatwebsite/excel 3.1.69 (SSRF `WEBSERVICE()`); npm — pdfjs-dist 6.1.200,
  nanoid 3.3.16, dompurify. Fix in a dedicated dependency pass before go-live.
- **Frontend bundle**: no `manualChunks` — Vite already lazy-loads routes and the heavy libs
  (chart.js via `primevue/chart` dynamic import, pdfjs/html2canvas via page-level dynamic imports).
- **Redis** (cache/session/queue) was intentionally deferred; the `database` drivers are fine for
  the expected municipal workload.

---

*Document prepared for AI consumption and system development reference — ALALAY System, Municipality of General Mamerto Natividad, Nueva Ecija.*

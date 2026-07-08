# ALALAY: Collaboration Guide

**Municipality of General Mamerto Natividad, Nueva Ecija**
**Inertia.js Monolith — Laravel 12 + Vue 3 + PrimeVue Sakai + MySQL + Supabase**

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Repository & Branching Strategy](#2-repository--branching-strategy)
3. [Prerequisites](#3-prerequisites)
4. [Local Setup (First Time)](#4-local-setup-first-time)
5. [Daily Development Workflow](#5-daily-development-workflow)
6. [Avoiding Merge Conflicts](#6-avoiding-merge-conflicts)
7. [Coding Standards & Conventions](#7-coding-standards--conventions)
8. [Common Issues & Fixes](#8-common-issues--fixes)

---

## 1. Project Overview

| Aspect | Value |
| --- | --- |
| **System** | ALALAY — AICS Digital Management & Notification System |
| **Pattern** | Inertia.js monolith (one Laravel project serves everything) |
| **Backend** | Laravel 12 / PHP 8.2 |
| **Frontend** | Vue 3 + PrimeVue 4 + Sakai template + Tailwind CSS 3 |
| **Database** | MySQL 8.x |
| **File Storage** | Supabase Storage (S3-compatible) |
| **Auth** | Laravel Fortify + Email OTP MFA |
| **SMS** | PhilSMS API (via queued job) |
| **Queue/Cache/Session** | `database` driver |

### Roles

| Role | Route Prefix | Middleware |
| --- | --- | --- |
| Public (applicant) | `/` | none |
| Admin | `/admin` | `role:admin` |
| AICS Staff | `/aics` | `role:aics_staff` |
| MSWDO | `/mswdo` | `role:mswdo` |
| Accountant | `/accountant` | `role:accountant` |
| Treasurer | `/treasurer` | `role:treasurer` |
| Mayor's Office | `/mayors-office` | `role:mayors_office` |

---

## 2. Repository & Branching Strategy

### Remote

```text
origin  https://github.com/jnpl-dev/ALALAY.git
```

### Branches

```text
main        — Production-ready code. Protected. No direct commits.
dev         — Active development. All feature branches merge here.
feature/*   — Short-lived branches for individual tasks (e.g., feature/aics-encode).
```

### Rules

- **Never commit directly to `main`** or `dev` — always use a feature branch and pull request.
- Create feature branches from the latest `dev`:

  ```bash
  git checkout dev
  git pull origin dev
  git checkout -b feature/your-feature-name
  ```

- Keep feature branches short-lived (1–3 days max).
- Before merging, rebase your branch onto the latest `dev`:

  ```bash
  git fetch origin
  git rebase origin/dev
  ```

- Squash commits on merge to keep `dev` history clean.

### Commit Message Conventions

Use conventional commits:

```text
type(scope): Brief description

Examples:
feat(aics): add walk-in application encoding
fix(scanner): correct page orientation on double capture
refactor(voucher): extract voucher generation service
chore(deps): update primevue to 4.5.5
docs(collab): add collaboration guide
```

Types: `feat`, `fix`, `refactor`, `chore`, `docs`, `test`, `style`, `perf`.

---

## 3. Prerequisites

Install these on your machine before cloning:

| Tool | Version (minimum) | Notes |
| --- | --- | --- |
| **PHP** | 8.2.x | Required extensions: curl, fileinfo, gd, intl, mbstring, mysqli, openssl, pdo_mysql, zip, bcmath |
| **Composer** | 2.5+ | PHP package manager |
| **Node.js** | 20.x LTS | Includes npm |
| **XAMPP** | 8.x (with MySQL 8) | Or any MySQL 8 server |
| **Git** | Latest | |
| **Ngrok** | Latest | Only needed for testing camera/mobile on your phone |

Required PHP extensions (XAMPP usually includes these):

- `curl`, `fileinfo`, `gd`, `intl`, `mbstring`, `mysqli`, `openssl`, `pdo_mysql`, `zip`, `bcmath`

---

## 4. Local Setup (First Time)

### 4.1 Clone the Repository

```bash
git clone https://github.com/jnpl-dev/ALALAY.git
cd ALALAY
git checkout dev
```

### 4.2 Install PHP Dependencies

```bash
composer install
```

If you get a `ext-gd` error (common on Windows), use:

```bash
composer install --ignore-platform-req=ext-gd
```

### 4.3 Install JavaScript Dependencies

```bash
npm install
```

### 4.4 Environment Configuration

```bash
cp .env.example .env
```

Edit `.env` with your local settings:

```ini
APP_NAME=ALALAY
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=alalay
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

FILESYSTEM_DISK=local

# Skip these for local — they use defaults:
MAIL_MAILER=log
SMS_DRIVER=log
```

**Do not commit your `.env`** — it is gitignored.

### 4.5 Generate App Key

```bash
php artisan key:generate
```

### 4.6 Create Database

Open phpMyAdmin or run:

```sql
CREATE DATABASE alalay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4.7 Run Migrations & Seeders

```bash
php artisan migrate
php artisan db:seed
```

### 4.8 Start Development Servers

**Option A — using the custom script (rails serve + queue + pail + vite concurrently):**

```bash
composer run dev
```

**Option B — manual (four terminals):**

```bash
# Terminal 1: Laravel dev server
php artisan serve

# Terminal 2: Vite dev server
npm run dev

# Terminal 3: Queue worker (for SMS jobs)
php artisan queue:work

# Terminal 4: Log watcher (optional)
php artisan pail
```

### 4.9 PrimeVue Sakai Template

The Sakai template files live in `/sakai/` and are **gitignored** (too large). Each developer must download them separately:

1. Download from the PrimeVue Sakai repository or get a copy from another team member.
2. Extract to `/sakai/` in the project root.
3. The build pipeline (`npm run dev` / `npm run build`) will compile the Sakai SCSS automatically.

> If the Sakai SCSS is missing, the app will still load but use fallback PrimeVue styles without the Sakai layout. Layout files (`AppLayout.vue`, `AppSidebar.vue`, etc.) are in `resources/js/Layouts/` and are **tracked in Git**.

### 4.10 Verify It Works

Visit `http://localhost:8000` in your browser. You should see the ALALAY landing page.

Default admin login credentials (after seeding):

- **Email:** `admin@alalay.gmn.gov.ph`
- **Password:** `Admin@123456` (or the password set in `AdminSeeder`)

---

## 5. Daily Development Workflow

### 5.1 Every Session

```bash
# 1. Get latest dev
git checkout dev
git pull origin dev

# 2. Create a feature branch
git checkout -b feature/your-task

# 3. Make changes, commit often
git add -p    # stage in hunks
git commit -m "feat(scope): message"

# 4. When done, rebase onto latest dev
git fetch origin
git rebase origin/dev

# 5. Push and create a pull request
git push origin feature/your-task
# → Open GitHub → New Pull Request → base: dev ← compare: feature/your-task
```

### 5.2 Running the App

```bash
php artisan serve      # Backend on http://localhost:8000
npm run dev            # Frontend hot-reload
```

Vite will inject the HMR script. Edits to `.vue` files reflect instantly in the browser.

### 5.3 Migration Changes

If a teammate added a migration, run:

```bash
git pull origin dev
php artisan migrate
```

If you need to roll back and re-run:

```bash
php artisan migrate:fresh --seed
```

> **Warning:** `migrate:fresh` drops all tables. Only run on local, never on production.

### 5.4 Adding New Dependencies

```bash
# PHP package
composer require package/name

# NPM package
npm install package-name

# Always commit composer.json / package.json and the lock file.
```

### 5.5 Testing Mobile / Camera

Camera access (`getUserMedia`) requires HTTPS. On localhost it works without HTTPS (browsers treat localhost as secure). On your phone:

```bash
ngrok http 8000
```

Open the generated `https://*.ngrok-free.dev` URL on your phone.

---

## 6. Avoiding Merge Conflicts

### 6.1 Divide by Route Prefix, Not by Layer

The project is organized by **role prefix** (`/aics`, `/mswdo`, `/admin`, etc.). To avoid conflicts:

| Developer | Works On | Touches |
| --- | --- | --- |
| Dev A | AICS Staff panel | `routes/web.php` (aics group), `app/Http/Controllers/Aics/`, `resources/js/Pages/Aics/` |
| Dev B | MSWDO panel | `routes/web.php` (mswdo group), `app/Http/Controllers/Mswdo/`, `resources/js/Pages/Mswdo/` |
| Dev C | Admin panel | `routes/web.php` (admin group), `app/Http/Controllers/Admin/`, `resources/js/Pages/Admin/` |
| Dev D | Public/Treasurer | `routes/web.php` (public + treasurer groups), respective Controllers/Pages |

**Key rule:** If two people need to touch the same file, talk first and coordinate.

### 6.2 Files That Frequently Conflict and How to Handle Them

| File | Why It Conflicts | Strategy |
| --- | --- | --- |
| `routes/web.php` | Everyone adds routes here | Work in **one role group at a time**. Each role group is separated clearly. If two people add routes in different groups, Git merges cleanly. If same group, coordinate. |
| `.env` | Gitignored — no conflict | Each dev has their own. Never commit. |
| `composer.json` / `package.json` | Adding dependencies | Pull dev first, add your dep, commit. Lock files are binary but mergeable. |
| `database/migrations/` | Sequential timestamps | Name files with different timestamps or use separate migration files. Never edit an existing migration that has been merged to dev. |
| `resources/js/Components/` | Shared components | Communicate changes. If refactoring a shared component, do it in a dedicated branch. |

### 6.3 Practical Tips

- **Pull `dev` before starting each session.** Always.
- **Rebase, don't merge**, when updating your feature branch. Merging creates unnecessary merge commits.
- **Commit small, focused changes.** A commit should do one thing.
- **Write `.vue` and controller changes together in one commit** — they are two sides of the same feature.
- **Never edit a migration after it's merged to `dev`.** Create a new migration instead.
- **Use `git add -p`** to stage only the relevant hunks, avoiding accidental commits.

### 6.4 When a Conflict Happens

```bash
# After rebase or merge, Git tells you which files conflicted.
# Open each file, look for <<<<<<<, =======, >>>>>> markers.
# Choose which side to keep, or write a combination.
# Remove the conflict markers.

git add resolved-file.php
git rebase --continue   # or git merge --continue
```

If you're unsure how to resolve, stop and ask the team. Never force-push a broken rebase.

---

## 7. Coding Standards & Conventions

### 7.1 Laravel / PHP

- **Models**: Use `HasUuids` trait, `$keyType = 'string'`, `$incrementing = false`.
- **Encrypted fields**: `claimant_address`, `claimant_phone`, `claimant_email`, `beneficiary_address` must use `protected $casts = ['field' => 'encrypted']`.
- **No raw SQL** — always Eloquent or Query Builder.
- **Eager load** relationships with `->with([...])`.
- **`audit_logs` and `reviews` tables**: append-only. Never UPDATE or DELETE. Only INSERT (`create()`).
- **Controllers**: Every mutating method must:
  1. Use a Form Request class for validation
  2. Call `$this->authorize()` (Policy check)
  3. INSERT a `reviews` row for workflow actions
  4. Write to `audit_logs`
  5. Return `redirect()->back()->with('success', '...')`
- **File uploads**: Always use `FileUploadService`. Files go to Supabase Storage via the `supabase` disk.
- **File viewing**: Always use `SignedUrlService` — never expose raw Supabase paths.
- **SMS**: Always dispatch via `SendSmsJob::dispatch($application, $triggerEvent)`.
- **Naming**: `snake_case` for DB columns, route params, JS variables. `PascalCase` for Vue components and PHP classes. `kebab-case` for route URL segments.

### 7.2 Vue 3 / Frontend

- Always use `<script setup>` Composition API — never Options API.
- Page components in `resources/js/Pages/` receive ALL data via `defineProps()` — never fetch in `onMounted()`.
- Use `useForm()` from `@inertiajs/vue3` for every form (no axios).
- Display validation errors via `form.errors.fieldName`.
- Use PrimeVue components (`DataTable`, `Dialog`, `Button`, `Toast`, `InputText`) — never rebuild what PrimeVue provides.
- Use `route()` helper from Ziggy for all links — never hardcode a URL.
- Sakai layout conventions: `class="card"` (no extra padding like `p-6`), `<hr class="border-surface">` (not `<Divider />`), `text-muted-color` for muted text (not `text-gray-500`), `grid grid-cols-12 gap-8` as page wrapper.

### 7.3 Database

- Only use these status strings (never invent new ones):
  `submitted`, `screening`, `returned_to_applicant`, `mswdo_review`, `social_case_study_uploaded`, `assistance_coding`, `voucher_creation`, `voucher_checking`, `voucher_returned`, `with_treasurer`, `budget_checking`, `on_hold`, `cheque_ready`, `claimed`
- No hard deletes on applications, vouchers, reviews, or audit_logs ever.

### 7.4 Architecture Rules (Inertia Monolith)

| Correct | Wrong |
| --- | --- |
| `return Inertia::render('Path/Page', [...])` | `return response()->json([...])` |
| `useForm({}).post(route('...'))` | `axios.post('/api/...')` |
| `<Link :href="route('name')">` | `<RouterLink to="/...">` |
| Controller → Inertia page | No REST API, no `/api/` routes |
| Laravel `web.php` handles ALL routing | No Vue Router |

---

## 8. Common Issues & Fixes

### 8.1 XAMPP / Windows

| Problem | Solution |
| --- | --- |
| `ext-gd` missing during `composer install` | `composer install --ignore-platform-req=ext-gd` |
| MySQL won't start | Check XAMPP Control Panel → MySQL → Start. Port 3306 might be in use. |
| `php` not recognized | Add PHP path to Windows PATH (e.g., `C:\xampp\php`) |
| `npm` not recognized | Install Node.js from <https://nodejs.org> (LTS version 20.x) |
| `APP_KEY` is empty | Run `php artisan key:generate` |

### 8.2 After Pulling Changes

| Situation | Command |
| --- | --- |
| New PHP dependencies | `composer install` |
| New JS dependencies | `npm install` |
| New migrations | `php artisan migrate` |
| New seeder data | `php artisan db:seed` (skips already-seeded data) |
| Reset everything local | `php artisan migrate:fresh --seed` (drops all tables!) |
| Frontend changes not showing | `npm run build` (or keep `npm run dev` running) |

### 8.3 Camera / DocumentScanner

| Problem | Solution |
| --- | --- |
| Camera doesn't open on phone | Use `ngrok http 8000` and open the HTTPS URL |
| "Camera denied" on laptop | Allow camera access in browser settings, or use the fallback file input |
| Scanner shows "Not Secure" | The page must be served over HTTPS (localhost is exempt) |
| PDF generation fails | Run `npm install jspdf` if not installed |

### 8.4 Supabase Storage

Supabase credentials are in `.env` but may not be configured for all developers. If `SUPABASE_KEY` / `SUPABASE_SECRET` are empty:

- File uploads fall back to the `local` disk (configure `FILESYSTEM_DISK=local` in `.env`)
- Signed URLs will not work — document viewing falls back to direct download
- Ask the project lead for Supabase credentials if you need to test file uploads

### 8.5 SMS

SMS is currently in `log` driver mode (`SMS_DRIVER=log`). Messages are written to the Laravel log instead of being sent. To test actual SMS:

```bash
# 1. Get a PhilSMS API token from the project lead
# 2. Update .env:
SMS_DRIVER=philsms
PHILSMS_API_TOKEN=your-token-here

# 3. Run the queue worker:
php artisan queue:work
```

---

*Document prepared for the ALALAY development team — Municipality of General Mamerto Natividad, Nueva Ecija.*

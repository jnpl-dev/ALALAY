# ALALAY: Finalized Tech Stack & System Design Specification
**Inertia.js Monolith — Laravel 12 + Vue 3 + PrimeVue Sakai + MySQL + Supabase Storage**
**Municipality of General Mamerto Natividad, Nueva Ecija**

---

## Confirmed Local Environment

```
Composer version 2.9.5 2026-01-29 11:40:53
PHP version 8.2.12 (C:\xampp1\php\php.exe)
```

PHP 8.2.12 satisfies Laravel 12's requirement of `^8.2`, so **no PHP upgrade is needed**. All package versions below are pinned to versions confirmed compatible with PHP 8.2.x and Composer 2.9.x, to avoid the dependency resolution errors encountered during initial setup.

### Required PHP Extensions (verify each is enabled in `php.ini`)

Open `C:\xampp1\php\php.ini` and ensure these lines are **uncommented** (no `;` prefix):

```ini
extension=curl
extension=fileinfo
extension=gd
extension=intl
extension=mbstring
extension=mysqli
extension=openssl
extension=pdo_mysql
extension=zip
extension=bcmath
```

After editing `php.ini`, restart Apache in the XAMPP Control Panel. Verify with:

```bash
php -m
```

This should list all extensions above. Missing `fileinfo`, `pdo_mysql`, or `intl` is the most common cause of Laravel installer failures on XAMPP/Windows.

---

## Architecture Decision

ALALAY uses the **Inertia.js monolith architecture**:

- **One Laravel project** serves everything — routing, auth, data, and Vue 3 pages
- **No REST API layer** — Laravel controllers pass data directly to Vue components as Inertia props
- **No CORS** — same domain, same Laravel session
- **No Vue Router** — Laravel `web.php` handles all routing
- **Feels like a SPA to users** — no full page reloads, smooth navigation
- **One deployment** — one server, one domain, one Nginx block

### Why Inertia over a Separated SPA

| Concern | Inertia Monolith | Separated SPA |
|---|---|---|
| Deployment | One Laravel app, one domain | Two apps, two domains, two configs |
| Auth | Standard Laravel session — just works | Sanctum SPA + CSRF + CORS debugging |
| Local dev | One server (`php artisan serve` + `npm run dev`) | Two servers always running |
| Fortify + MFA | Official Inertia starter kit support | Manual Vue implementation |
| Performance | Faster first load — data arrives with page | Extra round trip for data after JS loads |
| Solo developer | Significantly simpler | Higher cognitive overhead |

---

## Finalized Tech Stack with Pinned Versions and Usage

### Backend

| Layer | Technology | Pinned Version | What It's Used For |
|---|---|---|---|
| Framework | **Laravel** | `^12.0` | The core PHP framework. Handles routing, the ORM (Eloquent), validation, queues, scheduling, and serves as the foundation everything else plugs into. |
| SPA Bridge | **Inertia.js (Laravel adapter)** | `inertiajs/inertia-laravel: ^2.0` | Lets Laravel controllers return Vue 3 page components directly (`Inertia::render()`) instead of JSON or Blade views. This is what makes the monolith architecture possible — no separate API needed. |
| Authentication Scaffolding | **Laravel Fortify** | `^1.21` | Provides ready-made backend logic for login, logout, password reset, email verification, and two-factor authentication. You only need to wire it to your own Vue pages instead of building auth logic from scratch. |
| Session Auth | **Laravel Session** (database driver) | Built into Laravel | Keeps users logged in. Session data is stored in the `sessions` MySQL table instead of files, which lets the Admin revoke a specific user's session at any time (NPC ACC-9 compliance). |
| MFA / Two-Factor | **Email OTP (Laravel Mail)** | Built into Laravel | Sends a 6-digit one-time passcode to the user's registered email upon login. Verified via `EmailOtpService` — no authenticator app needed. |
| Authorization | **Laravel Policies + Gates** | Built into Laravel | Defines exactly which role can view, approve, or return which model (e.g., only MSWDO can create a voucher). Enforces the "need to know" access rule required by NPC ACC-2. |
| Form Validation | **Laravel Form Requests** | Built into Laravel | Validates every form submission (applications, approvals, returns, user creation) server-side before anything touches the database. |
| File Storage Driver | **league/flysystem-aws-s3-v3** | `^3.29` | Lets Laravel's `Storage` facade talk to Supabase Storage using the S3-compatible API, so file uploads/downloads work the same way Laravel normally works with AWS S3. |
| Image Handling | **intervention/image** | `^3.7` | Validates that uploaded images are genuine image files (not disguised malicious files) and can resize/compress profile pictures before upload. |
| CSV/Excel Export | **maatwebsite/excel** | `^3.1` | Powers the "Export to CSV" button across Admin audit logs, application tables, and voucher tables. |
| Route Helper for JS | **tightenco/ziggy** | `^2.4` | Exposes all your Laravel named routes (e.g., `aics.applications.approve`) to Vue/JavaScript via a `route()` helper, so you never hardcode URLs in Vue components. |
| Queue System | **Laravel Queues** (database driver) | Built into Laravel | Runs slow tasks (sending SMS, daily backups) in the background instead of making the user wait for them to finish before the page responds. |
| Task Scheduling | **Laravel Scheduler** | Built into Laravel | Automatically runs the daily backup job, flags records past their retention period, and resets stale `is_online` flags — all on a timer, no manual triggering needed. |
| Mailer / OTP | **Laravel Mailer** | Built into Laravel | Sends password reset links and email verification messages via SMTP. |
| Rate Limiting | **Laravel Rate Limiter** | Built into Laravel | Blocks brute-force login attempts by limiting how many login tries are allowed per minute from the same source. |
| Logging | **Laravel Log (Monolog)** | Built into Laravel | Records application errors and exceptions to `storage/logs/laravel.log` — separate from the `audit_logs` database table, which tracks user actions instead. |

---

### Frontend

| Layer | Technology | Pinned Version | What It's Used For |
|---|---|---|---|
| SPA Bridge | **@inertiajs/vue3** | `^2.0` | The client-side half of Inertia. Receives the page data Laravel sends and renders the correct Vue 3 component — this is what makes navigation feel instant without full page reloads. |
| Framework | **Vue 3** | `^3.4` | The JavaScript framework all of ALALAY's UI is built in, using the Composition API (`<script setup>`) style throughout for cleaner, more readable components. |
| UI Component Library | **PrimeVue** | `^4.2` | Supplies all the pre-built UI pieces — data tables, dialogs, dropdowns, buttons, toasts — so you don't have to build these from scratch. |
| Admin Template | **PrimeVue Sakai** | Latest (template, not a package) | A free, pre-designed admin panel layout (sidebar, topbar, page structure) built specifically for PrimeVue. Gives ALALAY a polished look without designing the layout yourself. |
| Icon Set | **PrimeIcons** | `^7.0` | The icon library that ships with and matches PrimeVue's visual style (used in buttons, sidebar menu, status badges, etc). |
| State Management | **Pinia** | `^2.2` | Stores small pieces of global state that need to persist across page visits, such as the logged-in user's info and any in-app notification badges. Most of ALALAY's data doesn't need this since Inertia already delivers page data directly. |
| Form Handling | **Inertia `useForm()`** | Included in `@inertiajs/vue3` | Manages form state, automatically shows server-side validation errors next to the right field, and tracks a loading state — replacing the need for Axios or a separate validation library. |
| Vue Composition Utilities | **@vueuse/core** | `^11.0` | A toolbox of ready-made composables (e.g., debounce, clipboard copy, window size detection) that would otherwise need to be written by hand. |
| Date Formatting | **dayjs** | `^1.11` | Lightweight library used to format every timestamp shown in the UI into Philippine Standard Time (UTC+8). |
| Build Tool | **Vite** (via `laravel-vite-plugin`) | `laravel-vite-plugin: ^1.0`, `vite: ^5.4` | Compiles and bundles all Vue/JS/CSS, and provides instant hot-reload during development so changes show up in the browser without a manual refresh. |
| Vue Plugin for Vite | **@vitejs/plugin-vue** | `^5.1` | Teaches Vite how to understand and compile `.vue` single-file components. |
| CSS Framework | **Tailwind CSS** | `^3.4` | Utility-class CSS framework that PrimeVue Sakai's layout is built on top of; used for spacing, layout, and custom styling outside of PrimeVue's own components. |
| CSS Build Tools | **postcss**, **autoprefixer** | `postcss: ^8.4`, `autoprefixer: ^10.4` | Required by Tailwind to process the CSS during the build step and automatically add vendor prefixes for browser compatibility. |

> **Deliberately not used:** `vue-router` (Laravel routes handle this instead), `axios` (Inertia's `useForm()` replaces it for all form submissions), `vee-validate` (Laravel Form Requests handle all validation server-side).

---

### Database

| Layer | Technology | Notes | Usage |
|---|---|---|---|
| DBMS | **MySQL 8.x** (via XAMPP) | XAMPP locally, standalone MySQL 8.x in production | Stores all of ALALAY's data — applications, users, vouchers, audit logs, everything. |
| ORM | **Laravel Eloquent** | Built into Laravel | Lets you query and manipulate database records using PHP objects and methods instead of writing raw SQL. |
| Migrations | **Laravel Migrations** | Built into Laravel | Version-controls every change to the database structure, so the schema can be recreated identically on any machine by running `php artisan migrate`. |
| Seeders | **Laravel Seeders** | Built into Laravel | Pre-fills the database with starting data — the default Admin account, AICS assistance categories, required documents, and system settings — so the app isn't empty on first run. |
| UUID Primary Keys | **Laravel `HasUuids` trait** | Built into Laravel | Generates random UUID strings instead of sequential numbers (1, 2, 3...) for every record's ID — harder to guess and safer for a public-facing tracking system. |
| Sensitive Field Encryption | **Laravel `encrypted` cast** | Built into Laravel | Automatically encrypts and decrypts specific database columns (claimant/beneficiary phone, address, email) on the fly, so even direct database access doesn't expose readable personal data (NPC STO-2). |
| Soft Deletes | **Laravel `SoftDeletes` trait** | Built into Laravel | Applied only to the `User` model — deactivating a user marks them as deleted without permanently erasing their record, preserving accountability in the audit trail. |

---

### File Storage

| Layer | Technology | Usage |
|---|---|---|
| Provider | **Supabase Storage** | Hosts every uploaded file — supporting documents, social case study scans, voucher images — outside of the local server, in private (non-public) storage buckets. |
| Laravel Integration | **league/flysystem-aws-s3-v3** | The bridge package that lets Laravel's built-in file storage commands work with Supabase, since Supabase Storage speaks the same protocol as Amazon S3. |
| File Access Method | **Signed URLs** (via `SignedUrlService`) | Generates a temporary, expiring link to a private file whenever someone needs to view it — files are never made permanently public or directly accessible by guessing a URL. |
| Path Convention | `{bucket}/{table}/{application_id}/{filename}` | Keeps every file organized by which application it belongs to, making lookup and (if ever needed) bulk deletion straightforward. |

---

### Authentication & Security

| Layer | Measure | Usage |
|---|---|---|
| Session | **Laravel Session** (database driver) | Standard Laravel login state — no separate token system needed since frontend and backend are the same app. |
| MFA | **Laravel Mail (Email OTP)** | Requires every internal staff account (Admin, AICS Staff, MSWDO, Accountant, Treasurer) to enter a 6-digit code sent to their registered email in addition to their password — required by NPC ACC-6 since the system handles sensitive personal data. |
| Password Hashing | **bcrypt** (cost factor 12) | Laravel's default — passwords are never stored as plain text, only as irreversible hashes. |
| Password Policy | **Laravel `Password` validation rule** | Forces every password to be at least 12 characters, mix upper/lowercase, include numbers and symbols, and not appear in known data breach lists (NPC STO-3). |
| Role Middleware | **Custom `RoleMiddleware`** | Checks which role a logged-in user has before letting them reach a route — e.g., blocks an AICS Staff account from opening Accountant pages. |
| Model-Level Authorization | **Laravel Policies** | A second layer of protection beyond route middleware — even if someone reaches a controller, the Policy double-checks they're allowed to act on that specific record. |
| HTTPS | **Enforced via Nginx in production** | Encrypts all traffic between the browser and server so data can't be intercepted on the network. |
| CSRF Protection | **Laravel CSRF middleware** | Automatically blocks forged form submissions coming from outside the app — enabled by default on all web routes. |
| XSS Protection | **Vue 3 auto-escaping** | Vue automatically escapes any data rendered in templates, preventing malicious scripts from executing even if somehow stored in the database. |
| AUP Acknowledgment | **`users.acceptable_use_policy_accepted_at`** column | Forces every new staff account to read and accept usage rules before they can access any panel, satisfying NPC ACC-5. |
| Session Revocation | **Admin "Revoke Sessions" action** | Lets the Admin instantly force-logout any user (e.g., a lost device or resignation) by deleting their session record from the database. |

---

### SMS Notification

| Layer | Technology | Usage |
|---|---|---|
| Provider | **Semaphore SMS API** (recommended for PH) | Sends the actual SMS messages to applicants at each key stage — submission complete, under review, resubmission needed, cheque ready. |
| Integration | **Custom `SmsService` + `SendSmsJob`** | `SmsService` builds the request to Semaphore; `SendSmsJob` runs it in the background queue so sending an SMS never slows down the page the staff member is using. |
| Queue Driver | **Laravel Queues** (database driver) | Stores pending SMS jobs in the `jobs` table until a queue worker process picks them up and sends them. |
| Notification Log | **`sms_notifications` table** | Keeps a permanent record of every SMS attempt — what was sent, to whom, and whether it succeeded or failed — for troubleshooting and accountability. |

---

### Development Environment

| Layer | Technology | Confirmed Version | Usage |
|---|---|---|---|
| Local Server Stack | **XAMPP** | Includes Apache + MySQL + PHP | Provides the local web server and database needed to run Laravel on a Windows machine without installing each piece separately. |
| PHP | **PHP** | `8.2.12` (confirmed) | The language Laravel itself runs on. 8.2.12 satisfies Laravel 12's `^8.2` requirement — no upgrade needed. |
| Composer | **Composer** | `2.9.5` (confirmed) | The package manager that downloads and manages all PHP/Laravel dependencies listed in `composer.json`. |
| Node.js | **Node.js** | `20.x LTS` (recommended) | Required to run `npm` and Vite, which compile all the Vue/JS/CSS frontend code. |
| Package Manager (JS) | **npm** | Bundled with Node.js | Downloads and manages all frontend dependencies listed in `package.json`. |
| Local Dev Commands | `php artisan serve` + `npm run dev` | — | Two terminals: one runs the Laravel backend, the other runs Vite's dev server for instant frontend hot-reload. |

---

## `composer.json` — Pinned Dependency Versions

Use these exact version constraints to avoid the dependency resolution conflicts encountered during initial setup with Composer 2.9.5 and PHP 8.2.12:

```json
{
  "require": {
    "php": "^8.2",
    "laravel/framework": "^12.0",
    "laravel/fortify": "^1.21",
    "laravel/tinker": "^2.9",
    "inertiajs/inertia-laravel": "^2.0",
    "tightenco/ziggy": "^2.4",
    "league/flysystem-aws-s3-v3": "^3.29",
    "intervention/image": "^3.7",
    "maatwebsite/excel": "^3.1"
  },
  "require-dev": {
    "fakerphp/faker": "^1.23",
    "laravel/pail": "^1.1",
    "laravel/sail": "^1.26",
    "mockery/mockery": "^1.6",
    "nunomaduro/collision": "^8.1",
    "phpunit/phpunit": "^11.0.1"
  }
}
```

### Installation Order (to avoid conflicts)

```bash
# 1. Create the Laravel project first, alone
composer create-project laravel/laravel alalay "12.*"
cd alalay

# 2. Install Inertia server-side adapter
composer require inertiajs/inertia-laravel

# 3. Install Fortify (auth scaffolding)
composer require laravel/fortify
php artisan fortify:install

# 4. MFA is handled via Email OTP (built into Laravel Mail) — no extra packages needed

# 5. Install file storage driver
composer require league/flysystem-aws-s3-v3 "^3.0" --with-all-dependencies

# 6. Install remaining utility packages one at a time
composer require intervention/image
composer require maatwebsite/excel
composer require tightenco/ziggy
```

> **Why one at a time:** Installing packages individually (rather than all at once in `composer.json`) makes it much easier to identify exactly which package caused a dependency conflict if Composer fails — a common issue on Windows/XAMPP setups with PHP 8.2.

---

## `package.json` — Pinned Dependency Versions

```json
{
  "dependencies": {
    "@inertiajs/vue3": "^2.0",
    "vue": "^3.4",
    "primevue": "^4.2",
    "primeicons": "^7.0",
    "pinia": "^2.2",
    "@vueuse/core": "^11.0",
    "dayjs": "^1.11"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^5.1",
    "laravel-vite-plugin": "^1.0",
    "vite": "^5.4",
    "tailwindcss": "^3.4",
    "autoprefixer": "^10.4",
    "postcss": "^8.4"
  }
}
```

### Installation Order

```bash
# 1. Core Inertia + Vue
npm install @inertiajs/vue3 vue@^3.4

# 2. PrimeVue
npm install primevue primeicons

# 3. State and utilities
npm install pinia @vueuse/core dayjs

# 4. Build tooling (dev dependencies)
npm install -D @vitejs/plugin-vue laravel-vite-plugin vite

# 5. Tailwind CSS
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
```

---

## Common XAMPP/Windows Setup Issues and Fixes

| Problem | Likely Cause | Fix |
|---|---|---|
| `composer create-project` fails or hangs | Missing `fileinfo` or `openssl` extension | Enable both in `php.ini`, restart Apache |
| `Class "PDO" not found` | `pdo_mysql` not enabled | Enable `extension=pdo_mysql` in `php.ini` |
| `intl` extension errors during install | `intl` not enabled | Enable `extension=intl` in `php.ini` |
| Composer memory limit errors | XAMPP's default PHP memory limit too low | Set `memory_limit = 512M` in `php.ini` |
| `npm run dev` works but page is blank | Vite manifest not found | Run `npm run build` once, or ensure `php artisan serve` and `npm run dev` are both running |
| MySQL connection refused | MySQL service not started in XAMPP | Start MySQL from XAMPP Control Panel before running `php artisan migrate` |
| `artisan` commands fail silently | Wrong PHP version in system PATH | Always run PHP commands using the full XAMPP path or ensure `C:\xampp1\php` is first in your PATH |

---

## Environment Variables (`.env`)

```env
# Application
APP_NAME="ALALAY"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database (XAMPP)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=alalay
DB_USERNAME=root
DB_PASSWORD=

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=120

# Queue
QUEUE_CONNECTION=database

# Cache
CACHE_DRIVER=file

# Mail (OTP, Password Reset)
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@gmn.gov.ph
MAIL_FROM_NAME="ALALAY System"

# Supabase Storage
SUPABASE_URL=
SUPABASE_KEY=
SUPABASE_STORAGE_BUCKET=alalay-docs
SUPABASE_STORAGE_ENDPOINT=https://<project-ref>.supabase.co/storage/v1/s3
SUPABASE_STORAGE_REGION=ap-southeast-1

# SMS API
SMS_API_KEY=
SMS_API_ENDPOINT=
SMS_SENDER_NAME=ALALAY
```

---

## Production `.env` Additions

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://alalay.gmn.gov.ph

SESSION_DOMAIN=.gmn.gov.ph
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

---

## How Inertia Replaces the API Layer

### Instead of this (SPA pattern):
```javascript
// Vue component — SPA
const applications = ref([])
onMounted(async () => {
  const res = await axios.get('/api/v1/aics/applications?status=pending')
  applications.value = res.data.data.items
})
```

### You write this (Inertia pattern):
```php
// Laravel controller
public function index()
{
    $applications = Application::where('status', 'submitted')
        ->with(['category'])
        ->paginate(15);

    return Inertia::render('Aics/Applications/Index', [
        'applications' => $applications,
    ]);
}
```

```vue
<!-- Vue page component — data arrives as props, no API call needed -->
<script setup>
const props = defineProps({
  applications: Object,
})
</script>

<template>
  <DataTable :value="props.applications.data" />
</template>
```

### Form submissions with Inertia `useForm()`:
```vue
<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({
  remarks: '',
  resubmission_docs_required: [],
})

const submit = () => {
  form.post(route('aics.applications.approve', { id: props.application.id }), {
    onSuccess: () => { /* toast, reset, etc. */ },
  })
}
</script>
```

- `form.processing` — loading state (automatic)
- `form.errors` — server validation errors (automatic)
- `form.reset()` — clears form fields
- No Axios, no try/catch, no manual error handling

---

*Document prepared for AI consumption and development reference — ALALAY System, Municipality of General Mamerto Natividad, Nueva Ecija.*

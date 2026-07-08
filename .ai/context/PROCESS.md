# ALALAY: Development Process Checklist

**Inertia.js Monolith — Laravel 12 + Vue 3 + PrimeVue Sakai**
**Municipality of General Mamerto Natividad, Nueva Ecija**

---

## How to Use This Document

- Work **top to bottom** — each phase depends on the previous one.
- Check off `[ ]` items as you complete them.
- Reference the `.ai/context/` documents for each task.
- Use `.ai/skills/` files before writing any boilerplate code.

---

## Reference Documents

| Document | Purpose |
| --- | --- |
| `.ai/context/01_user_story.md` | Workflow, stages, actors, business rules |
| `.ai/context/02_schema_dictionary.md` | Database schema |
| `.ai/context/03_panels_pages_content.md` | UI spec per role |
| `.ai/context/04_npc_compliance.md` | Security requirements |
| `.ai/context/05_tech_stack.md` | Technology decisions and conventions |
| `.ai/context/06_inertia_controller_props.md` | Props passed per page per controller |
| `.ai/context/07_role_permission_matrix.md` | Role × model × action access table |
| `.ai/context/08_error_handling_spec.md` | Error scenarios and UI behavior |
| `.ai/context/09_sms_templates.md` | SMS content and variables |
| `.ai/context/10_file_handling_spec.md` | Upload rules and Supabase paths |
| `.ai/context/11_reference_code_spec.md` | Reference code format and generation |
| `.ai/context/12_notification_flow.md` | Event → side effects map |
| `.ai/context/13_seeder_data.md` | Actual seed data |
| `.ai/context/14_ui_component_library.md` | Reusable Vue component specs |
| `.ai/context/15_analytics_queries.md` | Dashboard query logic |

---

## Phase 0 — Project Setup

### 0.1 Repository

- [x] Create Git repository: `git init`
- [x] Add `.gitignore` — default Laravel + `/sakai` (template download)
- [x] Create `main` branch (production) and `dev` branch (active development)
- [x] Push `main` + `dev` to GitHub (`https://github.com/jnpl-dev/ALALAY.git`) — PAT auth
- [ ] Set branch protection on `main` (requires GitHub branch protection rules)

### 0.2 Laravel Project

- [x] Create Laravel 12 project: `composer create-project laravel/laravel alalay`
- [x] Configure `.env`: `APP_NAME=ALALAY`, DB credentials, `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, `MAIL_MAILER=smtp`, `MAIL_HOST=smtp.gmail.com`, `MAIL_PORT=587`, `MAIL_USERNAME`, `MAIL_PASSWORD` (Gmail App Password), `MAIL_ENCRYPTION=tls`
- [x] Create MySQL database in XAMPP: `CREATE DATABASE alalay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
- [x] Verify DB connection: `php artisan migrate`

### 0.3 Install Laravel Packages

- [x] `composer require inertiajs/inertia-laravel`
- [x] `composer require laravel/fortify`

- [x] `composer require league/flysystem-aws-s3-v3`
- [x] `composer require intervention/image`
- [x] `composer require maatwebsite/excel` (used `--ignore-platform-req=ext-gd`)
- [x] `composer require tightenco/ziggy` (used `--ignore-platform-req=ext-gd`)
- [x] Publish Inertia middleware: `php artisan inertia:middleware`
- [x] Register `HandleInertiaRequests` in `bootstrap/app.php` (Laravel 12 uses `bootstrap/app.php` instead of `Kernel.php`)
- [x] Publish Fortify config: `php artisan vendor:publish --provider="Laravel\Fortify\FortifyServiceProvider"`

### 0.4 Install Frontend Packages

- [x] `npm install @inertiajs/vue3 vue@3`
- [x] `npm install primevue primeicons`
- [x] `npm install pinia @vueuse/core dayjs`
- [x] `npm install -D @vitejs/plugin-vue laravel-vite-plugin`
- [x] `npm install -D tailwindcss@3 postcss autoprefixer && npx tailwindcss init`
- [x] Configure `vite.config.js` (Vue + Laravel plugins, `@` alias)
- [x] Configure `resources/js/app.js` Inertia bootstrap (Pinia, PrimeVue unstyled, Toast, Confirm)
- [x] Create `postcss.config.js` for Tailwind v3
- [x] Create root Blade template `resources/views/app.blade.php` with `@inertia` directive
- [x] Verify: `php artisan serve` + `npm run dev` — app loads in browser
- [x] Note: Camera (getUserMedia) testing on phone requires HTTPS — use `ngrok http 8000` during development; open ngrok HTTPS URL on phone browser. Desktop localhost works without ngrok (localhost is treated as a secure context).
- [x] Configure `TrustProxies` in `bootstrap/app.php` (`trustProxies(at: '*')`) — ngrok sends `X-Forwarded-Proto: https`; without it Laravel generates `http://` asset URLs that mobile browsers block as mixed content

### 0.5 Create Folder Structure

- [x] Create `.ai/` directory at project root
- [x] Copy context documents into `.ai/context/` (10 files: USER_STORY, SCHEMA, CONTENT, NPC_SECURITY_GUIDELINE, TECH_STACK, PROCESS, API_CONTRACTS, CATDOCU_SEEDER, FOLDER_STRUCTURE, DEPLOYMENT_GUIDE)
- [x] Create `.ai/skills/backend/` and `.ai/skills/frontend/` directories
- [x] Create `app/Services/` directory
- [x] Create `app/Jobs/` directory
- [x] Create `app/Observers/` directory
- [x] Create `app/Policies/` directory
- [x] Create `scripts/` directory
- [x] Create `resources/js/Components/`, `Layouts/`, `Composables/`, `Stores/`, `Utils/`

### 0.5b Test Page (Verification)

- [x] Update `routes/web.php` to return `Inertia::render('Welcome')`
- [x] Create `resources/js/Pages/Welcome.vue` — sample page with Tailwind layout + PrimeVue Button
- [x] Verify Vue 3, Inertia, PrimeVue, and Tailwind all render correctly
- Note: This test page is temporary and will be replaced by actual application pages in Phase 4

### 0.6 Configure PrimeVue Sakai

- [x] Download PrimeVue Sakai template to `sakai/` directory
- [x] Install `@primeuix/themes` package
- [x] Apply Sakai layout components to `resources/js/Layouts/`
  - Updated import paths (`@/layout/` → `./`)
  - Replaced `vue-router` (`useRoute`, `router-link`) with Inertia equivalents (`usePage`, `Link`)
  - Replaced `<router-view />` with `<slot />` for Inertia compatibility
- [x] Wire Sakai theme preset in `app.js` — `theme: { preset: Aura }` from `@primeuix/themes/aura`
- [x] Fetch Sakai layout SCSS from `primefaces/sakai-assets` Git submodule — placed at `resources/js/layout/scss/layout/` (14 files: `layout.scss`, `_core.scss`, `_topbar.scss`, `_menu.scss`, `_main.scss`, `_footer.scss`, `_responsive.scss`, `_mixins.scss`, `_typography.scss`, `_utils.scss`, `_preloading.scss`, `variables/_common.scss`, `variables/_light.scss`, `variables/_dark.scss`)
- [x] Install `sass` dev dependency for SCSS compilation
- [x] Import `styles.scss` in `app.js` to compile layout styles
- [x] Add missing PrimeVue utility classes to `_utils.scss` (`text-muted-color`, `border-surface`, `text-surface-*` with dark mode variants)
- [x] Adapt AppConfigurator.vue — replace PrimeVue utility classes (`bg-surface-0`, `border-surface`, `text-muted-color`) with CSS variable inline styles for Tailwind v3 compatibility
- [x] Dashboard.vue rewritten in Sakai widget style (`.card` grid, icon circles, notification lists)
- [x] Verify build passes (977 modules, SCSS compiled to CSS bundle)

---

## Phase 1 — Database

### 1.1 Migrations (in this exact order)

- [x] `users` — extend default; add role, status, is_online, profile_picture_*, acceptable_use_policy_accepted_at, deleted_at; remove `name`
- [x] `sessions` — extend default with user_id FK
- [x] `assistance_categories`
- [x] `required_documents`
- [x] `applications`
- [x] `application_documents`
- [x] `reviews` — no `updated_at`
- [x] `social_case_studies`
- [x] `assistance_code_references`
- [x] `assistance_codes`
- [x] `vouchers`
- [x] `audit_logs` — no `updated_at`; append-only
- [x] `sms_notifications`
- [x] `system_settings`
- [x] `jobs`: `php artisan queue:table`
- [x] `failed_jobs`: `php artisan queue:failed-table`
- [x] Run all: `php artisan migrate`
- [x] Verify all tables in phpMyAdmin

### 1.2 Models

- [x] `User` — `HasUuids`, `SoftDeletes`, `encrypted` casts (address, phone, email fields)
- [x] `AssistanceCategory` — `HasUuids`
- [x] `RequiredDocument` — `HasUuids`
- [x] `Application` — `HasUuids`, encrypted casts on PII fields
- [x] `ApplicationDocument` — `HasUuids`
- [x] `Review` — `HasUuids`, no soft deletes, no `updated_at`
- [x] `SocialCaseStudy` — `HasUuids`
- [x] `AssistanceCodeReference` — `HasUuids`
- [x] `AssistanceCode` — `HasUuids`
- [x] `Voucher` — `HasUuids`
- [x] `AuditLog` — `HasUuids`, `public $timestamps = false`, manual `created_at`
- [x] `SmsNotification` — `HasUuids`
- [x] `SystemSetting` — `HasUuids`
- [x] Define all Eloquent relationships per schema dictionary
- [x] Define local query scopes per model (`scopeByStatus`, `scopeToday`, `scopeActive`)

### 1.3 Seeders

- [x] `AdminSeeder` — default admin user with temporary password
- [x] `AssistanceCategorySeeder` — real GMN AICS categories (per `13_seeder_data.md`)
- [x] `RequiredDocumentSeeder` — required docs per category
- [x] `AssistanceCodeReferenceSeeder` — standard code types and default amounts
- [x] `SystemSettingsSeeder` — system name, colors, SMS templates, file size limit
- [x] `DatabaseSeeder` — calls all in correct order
- [x] Run: `php artisan db:seed`
- [x] Verify seeded data in phpMyAdmin

---

## Phase 2 — Backend Foundation

### 2.1 Inertia Setup

- [x] Configure `HandleInertiaRequests` middleware — shares `auth.user`, `flash`, `ziggy` props
- [x] Root Blade layout `resources/views/app.blade.php` exists with `@routes` and `@inertia`
- [x] `npm run build` passes — frontend compiles with all pages
- [x] Verify Inertia page renders with shared props in Vue DevTools (manual — requires browser)

### 2.2 Authentication (Fortify + Inertia)

- [x] Configure `FortifyServiceProvider` — register view routes returning Inertia pages:
  - Login → `Auth/Login.vue`
  - Email OTP challenge → `Auth/EmailOtpChallenge.vue`
  - Password reset → `Auth/ForgotPassword.vue`, `Auth/ResetPassword.vue`
- [x] Disable Fortify's public registration — users created by Admin only
- [x] Switch from TOTP to Email OTP — removed `google2fa-laravel` and `bacon/bacon-qr-code`
- [x] Implement Email OTP flow: `EmailOtpService` (generate/verify/invalidate), `SendOtpMail` mailable, `email_otps` table
- [x] Implement OTP challenge flow: `LoginController` (email+password → OTP), `OtpChallengeController` (verify/resend)
- [x] Implement password reset flow via Laravel Mailer
- [x] Test full login → OTP challenge → AUP → panel redirect flow

### 2.3 Middleware

- [x] `RoleMiddleware` — checks `auth()->user()->role`; aborts with 403 (Inertia renders as Error page)
- [x] `AuditLogMiddleware` — writes to `audit_logs` on POST/PUT/PATCH/DELETE; captures user_id, role, module (from route name prefix), action (from route name), ip_address, user_agent; extracts entity_type/entity_id from route parameters; human-readable description format
- [x] `EnsureAupAccepted` — redirects to `/acceptable-use-policy` if `acceptable_use_policy_accepted_at` is null
- [x] Register all middleware in `bootstrap/app.php` (Laravel 12) — `role` and `aup.accepted` aliases; `AuditLogMiddleware` appended to web group
- [x] Apply middleware to route groups in `web.php` — all role panel routes grouped with correct `role:X` middleware

### 2.4 Authorization (Policies)

- [x] `ApplicationPolicy` — `viewAny`/`view` for all internal roles; `approve`/`returnApp` for AICS (status=submitted) and MSWDO (status=mswdo_review)
- [x] `VoucherPolicy` — `viewAny`/`view` for MSWDO/Accountant/Treasurer/Mayor; `create` for MSWDO; `approve`/`returnVoucher` for Accountant; `acknowledge`/`hold` for Treasurer; `markReady`/`reEvaluate` not used
- [x] `AssistanceCodePolicy` — `viewAny`/`view` for internal roles; `create` for AICS only
- [x] `SocialCaseStudyPolicy` — `viewAny`/`view` for AICS/MSWDO; `create` for MSWDO only
- [x] `AuditLogPolicy` — `viewAny`/`export` for Admin only
- [x] `UserPolicy` — `viewAny`/`create`/`delete`/`toggleStatus`/`revokeSessions` for Admin; `view`/`update` for Admin or self; Admin cannot delete/toggle/revoke own session
- [x] `SystemSettingPolicy` — `viewAny`/`update` for Admin only
- [x] Register all in `AppServiceProvider` (extends `AuthServiceProvider` with `$policies` property)
- [ ] Test each policy with correct and incorrect roles (requires controller integration)

### 2.5 Core Services

- [x] `AuditLogger` — `log(action, module, entityType, entityId, description)` creates `audit_logs` row with user, role, IP, user agent
- [x] `SmsService` — wraps PhilSMS API (`POST /api/v3/sms/send`, Bearer token auth); driver=`log` by default (writes to log + `sms_notifications`), driver=`philsms` when `PHILSMS_API_TOKEN` is set. Config in `config/sms.php`
- [x] `SendSmsJob` — queued; reads template from `system_settings` (fallback to hardcoded defaults); builds message with `{reference_code}`, `{claimant_name}`, `{track_url}`, `{remarks}` placeholders; calls `SmsService`; retries on failure
- [x] `FileUploadService` — validates file size against `system_settings.max_file_size_kb`; uploads to `{table}/{entityId}/{filename}` on Supabase Storage
- [x] `SignedUrlService` — generates temporary signed URL via `Storage::disk('supabase')->temporaryUrl()` with configurable expiry (default 15 min)
- [x] `ReferenceCodeService` — generates `GMN-YYYY-XXXXXX` format (6 random uppercase alphanumeric); checks uniqueness against `applications.reference_code`
- [x] Configure Supabase Storage disk in `config/filesystems.php` (`driver => s3` with Supabase endpoint)
- [ ] Test file upload to Supabase and signed URL generation (requires Supabase credentials)
- [ ] Test SMS job dispatch and queue processing (`php artisan queue:work`)

---

## Phase 3 — Controllers (Inertia::render)

All controllers return `Inertia::render('Path/To/Page', [...props])` or redirects.
Read `.ai/context/06_inertia_controller_props.md` before building each controller.

### 3.1 Public Controllers

- [x] `Public/CategoryController@index` — renders `Public/Apply.vue` with categories + required docs
- [x] `Public/ApplicationController@store` — handles application submission + document uploads (via DocumentScanner); generates reference code; dispatches SMS; redirects with success flash
- [x] `Public/ApplicationController@track` — renders `Public/Track.vue`
- [x] `Public/ApplicationController@show` — returns application data + review trail for tracking
- [x] `Public/ApplicationController@resubmit` — handles document resubmission; dispatches SMS

### 3.2 Auth Controllers

- [x] `Auth/LoginController` — login (email + password → OTP challenge); logs `auth.logout` audit on destroy
- [x] `Auth/OtpChallengeController` — OTP verify + resend; logs `auth.login` audit on successful verify
- [x] `Auth/PasswordResetController` — forgot + reset password (via Fortify)
- [x] `AupController@show` — renders `Auth/AcceptableUsePolicy.vue`
- [x] `AupController@accept` — records `acceptable_use_policy_accepted_at`; logs `auth.aup_accepted` audit; redirects to role dashboard

### 3.3 Shared Controllers

- [x] `Shared/AccountController@edit` — renders `Auth/AccountSettings.vue` with user data
- [x] `Shared/AccountController@update` — handles profile + password + profile picture update (uploads to Supabase S3 bucket; avatar display via proxy endpoint → signed URL redirect — ON HOLD: browser shows initial letter instead of image)

### 3.4 Admin Controllers

- [x] `Admin/DashboardController@index` — KPIs + recent activity feed (generic Dashboard.vue)
- [x] Root `DashboardController@index` — redirects `/dashboard` to role-specific dashboard (admin/aics/mswdo/accountant/treasurer/mayors-office)
- [x] `Admin/AnalyticsController@index` — user stats + app stats + recent activity with date filter
- [x] `Admin/UserController` — full CRUD (index, create, store, edit, update, toggleStatus, revokeSessions) — stub methods exist
- [x] `Admin/AuditLogController@index` — paginated + filterable + CSV export — stub exists
- [x] `Admin/SystemSettingController` — index + update — stub exists
- [x] `Admin/AssistanceCategoryController` — full CRUD + search/paginate — implemented
- [x] `Admin/RequiredDocumentController` — full CRUD + search + category filter — implemented
- [x] `Admin/AssistanceCodeReferenceController` — full CRUD + search/paginate — implemented

### 3.5 AICS Staff Controllers

- [x] `Aics/DashboardController@index` — renders `Aics/Dashboard.vue` with AICS-specific KPIs (Total/Pending Review/Forwarded/Returned) + recent applications + quick actions; category_name fix
- [x] `Aics/AnalyticsController@index` — 4 KPIs (Total/Pending/Forwarded/Returned) + by-status breakdown + monthly trends + recent apps; category_name fix
- [x] `Aics/ApplicationController@index` — tabbed by status prop — stub exists
- [x] `Aics/ApplicationController@show` — full application + documents + review trail — stub exists
- [x] `Aics/ApplicationController@documentUrl` — returns signed URL for document viewer — stub exists
- [x] `Aics/ApplicationController@approve` — approves; writes review; dispatches SMS; redirects — stub exists
- [x] `Aics/ApplicationController@return` — returns; writes review; dispatches SMS; redirects — stub exists
- [x] `Aics/AssistanceCodeController@index` — stub exists
- [x] `Aics/AssistanceCodeController@show` — application + social case study signed URL + review trail — stub exists
- [x] `Aics/AssistanceCodeController@store` — saves assistance code; updates status; redirects — stub exists

### 3.6 MSWDO Controllers

- [x] `Mswdo/DashboardController@index` — renders generic Dashboard.vue
- [x] `Mswdo/AnalyticsController@index`
- [x] `Mswdo/ApplicationController@index` — stub exists
- [x] `Mswdo/ApplicationController@show` — stub exists
- [x] `Mswdo/ApplicationController@approve` — captures social case study (via DocumentScanner); writes review; dispatches SMS — stub exists
- [x] `Mswdo/ApplicationController@return` — stub exists
- [x] `Mswdo/VoucherController@index` — stub exists
- [x] `Mswdo/VoucherController@show` — application + SCS + assistance code + review trail — stub exists
- [x] `Mswdo/VoucherController@store` — captures voucher (via DocumentScanner); updates status; redirects — stub exists

### 3.7 Accountant Controllers

- [x] `Accountant/DashboardController@index` — renders generic Dashboard.vue
- [x] `Accountant/AnalyticsController@index` — category_name fix
- [x] `Accountant/VoucherController@index` — stub exists
- [x] `Accountant/VoucherController@show` — stub exists
- [x] `Accountant/VoucherController@approve` — stub exists
- [x] `Accountant/VoucherController@return` — stub exists

### 3.8 Treasurer Controllers

- [x] `Treasurer/DashboardController@index` — renders generic Dashboard.vue
- [x] `Treasurer/AnalyticsController@index` — category_name fix
- [x] `Treasurer/ChequeController@index` — 3-tab index (Pending/Ready/On Hold)
- [x] `Treasurer/ChequeController@show` — application + assistance code + voucher + review trail
- [x] `Treasurer/ChequeController@acknowledge` — approve → `cheque_ready` (with SMS)
- [x] `Treasurer/ChequeController@hold` — hold → `on_hold` (with remarks)
- `Treasurer/BudgetController` — **not used**; `budget_checking` step removed from workflow

### 3.9 Mayor's Office Controllers

- [x] `MayorsOffice/DashboardController@index` — renders generic Dashboard.vue (consolidated KPIs)
- [x] `MayorsOffice/AnalyticsController@index` — consolidated charts; category_name fix

---

## Phase 4 — Frontend (Vue 3 + Inertia Pages)

### 4.1 Foundation

- [x] Configure `HandleInertiaRequests` shared props — shares `auth.user`, `flash`, `ziggy` (Ziggy routes available via `route()` in Vue)
- [x] Create `useAuth` composable — reads from `$page.props.auth`; computed helpers for role checks, full name
- [x] Create `useToast` composable — wraps PrimeVue `useToast()`; exposes `success()`, `error()`, `warn()`, `info()`
- [x] Create `useConfirm` composable — wraps PrimeVue `useConfirm()`; exposes `require()`, `destroy()`, `approve()`
- [x] Create `useFileViewer` composable — reactive `viewerState` ref; exposes `open(url, title)`, `close()`
- [x] Create `useDocumentScanner` composable — camera access, frame capture, enhancement pipeline (downscale → grayscale → contrast stretch → adaptive threshold → JPEG export)
- [x] Create `useStatusLabel` composable — delegates to `statusLabels.js`; exposes `label(status)` and `severity(status)`
- [x] Create `Utils/statusLabels.js` — full 14-status map with `{ label, severity }` pairs; `getStatusLabel()` export
- [x] Create `Utils/formatDate.js` — dayjs PST (`Asia/Manila`) with `formatDate`, `formatDateTime`, `formatRelative`, `formatDateShort`, `formatDateFull`, `now()`
- [x] Create `Utils/formatCurrency.js` — `formatCurrency(value)` → `'PHP 1,234.56'`
- [x] Create `Utils/constants.js` — `APPLICATION_STATUSES`, `ROLES`, `USER_STATUSES`, `SUBMISSION_TYPES`
- [x] Create flash message handling — `AppLayout.vue` watches `$page.props.flash` and calls `useToast().success()` / `.error()` on mount and on every navigation
- [x] Create `AppLayout` (Sakai persistent layout) — all 8 panel pages use `defineOptions({ layout: AppLayout })` for true SPA navigation (layout stays mounted, only content swaps)
- [x] Create `PublicLayout.vue` — Tailwind-only header (brand + nav: Home / Apply / Track / Login) + slot + footer; no PrimeVue dependency
- [x] Create `AuthLayout.vue` — centered card on gradient bg; used by Login, OTP, AUP, Password reset
- [x] Verify Ziggy `route()` helper available in Vue: `import { route } from 'ziggy-js'`
- [x] Sakai sidebar submenu added — Settings parent with collapsible children (System Settings, Assistance Categories, Required Documents, Code References); uses `path` property for activePath tracking

### 4.2 Auth Pages

- [x] `Auth/Login.vue` — email + password + `useForm()`; error display per field
- [x] `Auth/EmailOtpChallenge.vue` — 6-digit OTP input via InputOtp + resend button + `useForm()`
- [x] `Auth/ForgotPassword.vue` — email input
- [x] `Auth/ResetPassword.vue` — new password form
- [x] `Auth/AcceptableUsePolicy.vue` — AUP text + acknowledge button + `useForm()`
- [x] `Auth/AccountSettings.vue` — name + email form with save (persistent layout); edit/save/cancel buttons at top right matching SystemSettings layout
- [x] Test full auth flow: login → OTP → AUP → dashboard

### 4.3 Public Pages

- [x] `Public/Apply.vue` — multi-step form (standalone Tailwind):
  - [x] Step 1: Category selection (card grid) with required docs per category
  - [x] Step 2: Claimant + beneficiary fields (PSGC address, server error display, same-address toggle)
  - [x] Step 3: Document capture via DocumentScanner per required document (one DocumentScanner per document; fallback file input shown when camera unavailable)
  - [x] Step 4: Summary confirmation (claimant, beneficiary, document thumbnails)
  - [x] Step 5: Success — display reference code + copy button + track/apply-again links
  - [x] Uses `useForm()` for submission + `usePsgcAddress()` for address selectors; auto-selects Nueva Ecija → General Mamerto Natividad on mount
- [x] `Public/Track.vue` — reference code input + application status + review trail (standalone Tailwind); current step hides timestamp; `claimed` status shows as completed (green checkmark) with `claimed_at` timestamp

### 4.4 Shared Components

- [x] `Components/Common/AppKpiCard.vue` — props: `title`, `value`, `icon`, `color`; 5 severity color maps
- [x] `Components/Common/AppStatusBadge.vue` — props: `status`; reads from `getStatusLabel()`; 5 severity color maps
- [x] `Components/Common/AppDateFilter.vue` — emits: `filter-changed`; 5 presets (Today → This Year) + custom range
- [x] `Components/Common/AppConfirmModal.vue` — renders `<ConfirmDialog />`
- [x] `Components/Common/AppExportButton.vue` — triggers CSV download via `window.open()` (bypasses Inertia)
- [x] `Components/Common/AppEmptyState.vue` — props: `icon`, `message`; named slot for children
- [x] `Components/Application/ReviewTrail.vue` — props: `reviews`; chronological list with stage/decision/remarks/date; name formatted as `Last, FI MI.` with `whitespace-nowrap` (no space between given name initials); supports `assistance_coding`, `voucher_created` (green, label "Created"), `on_hold` (yellow, label "On Hold") labels
- [x] `Components/Application/ApplicationInfo.vue` — props: `application`; claimant + beneficiary + reference + category display
- [x] `Components/Application/DocumentList.vue` — props: `documents`; emits: `view`; per-doc view button
- [x] `Components/Application/DocumentViewer.vue` — Teleported modal; props: `url`, `title`, `documents`, `currentIndex`; image/iframe viewer; full-screen (`fixed inset-0`) with prev/next navigation
- [x] `Components/Application/DocumentScanner.vue` — camera capture with guide overlay (SVG mask),
  enhancement pipeline (5-step: downscale 1200px → grayscale → contrast stretch → adaptive threshold
  40×10 → JPEG 0.88), preview/recapture/confirm, fallback file input `image/jpeg,image/png` only.
  Zero PrimeVue dependency — pure Tailwind + canvas JS. Works in both public and dashboard pages.
- [x] `Components/Application/ReturnModal.vue` — PrimeVue Dialog; props: `visible`, `requiredDocuments`; emits: `confirmed` with remarks + document_ids
- [x] `Components/Charts/LineChart.vue`, `BarChart.vue`, `DonutChart.vue` — presentational wrappers; table fallback until chart.js installed

### 4.5 Admin Panel Pages

- [x] `Admin/Dashboard.vue` — KPI cards + recent/unusual activity tables + system status (persistent layout via `defineOptions`); generic page shared by all non-AICS roles via root DashboardController redirect
- [x] `Admin/Analytics.vue` — 4 stat cards (users, active, inactive, apps) + system overview + activity (persistent layout)
- [x] `Admin/Users/Index.vue` — PrimeVue DataTable + search + filter + Add User button + `useToast()`/`useConfirm()` composables + `formatDate()`
- [x] `Admin/Users/Create.vue` — user form + `useForm()`
- [x] `Admin/Users/Edit.vue` — pre-populated user form + toggle status + revoke sessions
- [x] `Admin/AssistanceCategories/Index.vue`, `Create.vue`, `Edit.vue` — DataTable + search + status Tag + CRUD forms
- [x] `Admin/RequiredDocuments/Index.vue`, `Create.vue`, `Edit.vue` — DataTable + search + category Select filter + mandatory/status Tags + CRUD forms
- [x] `Admin/AssistanceCodeReferences/Index.vue`, `Create.vue`, `Edit.vue` — DataTable + search + InputNumber currency + status Tag + CRUD forms
- [x] `Admin/AuditLogs.vue` — filterable table + CSV export; colored Tag badges for role/module/action; `window.open` download (bypasses Inertia)
- [x] `Admin/SystemSettings.vue` — grouped settings form with edit/save/cancel toggle pattern; InputSwitch for boolean values

### 4.6 AICS Staff Panel Pages

- [x] `Aics/Dashboard.vue` — dedicated AICS page (persistent layout); 4 KPIs (Total/Pending Review/Forwarded/Returned) + recent applications table + user card + quick actions
- [x] `Aics/Analytics.vue` — 4 KPIs (Total/Pending/Forwarded/Returned) + by-status breakdown + monthly trends + recent apps table (persistent layout)
- [x] `Aics/Applications/Index.vue` — PrimeVue TabView (Pending/Screened/Returned + search + category filter) + DataTable per tab
- [x] `Aics/Applications/Review.vue` — ApplicationInfo + DocumentList/DocumentViewer + ReviewTrail + Approve (ConfirmDialog) + Return (ReturnModal)
- [x] `Aics/AssistanceCodes/Index.vue` — TabView (Pending/Coded + search + category filter); "Coded" tab status column shows `<Tag value="Coded" severity="info" />`
- [x] `Aics/AssistanceCodes/Code.vue` — application info + document thumbnail grid + SCS with DocumentMeta/Viewer + ReviewTrail + code type dropdown + amount field + submit

### 4.7 MSWDO Panel Pages

- [x] `Mswdo/Dashboard.vue` — shared Dashboard.vue (persistent layout)
- [x] `Mswdo/Analytics.vue` — 4 stat cards (apps, validated, returned, vouchers) + validation + pending (persistent layout)
- [x] `Mswdo/Applications/Index.vue` — TabView (For Review/SCS Uploaded/Returned) with search + category filter + pagination; SCS tab shows "Case Study Uploaded" Tag
- [x] `Mswdo/Applications/Review.vue`
  - [x] ApplicationInfo + document thumbnail grid (PDF icon for PDFs) + DocumentViewer (full-screen overlay with prev/next)
  - [x] Social Case Study with DocumentMeta (uploaded_by, conducted_at, page_count, file_size_label) + View Case Study button via DocumentViewer
  - [x] ReviewTrail right panel
  - [x] DocumentScanner multi/a4 for SCS capture + Approve (ConfirmDialog) + Return (ReturnModal)
- [x] `Mswdo/Vouchers/Index.vue` — TabView (To Create / Created); "To Create" shows `voucher_creation` + `voucher_returned`; "Created" shows `voucher_checking` with "Voucher Created" Tag
- [x] `Mswdo/Vouchers/Create.vue`
  - [x] Application info + assistance code details + Social Case Study with DocumentMeta/Viewer
  - [x] Previous Voucher DocumentMeta + Viewer (only for `voucher_returned` re-creation)
  - [x] DocumentScanner single/a4 for voucher capture + submit (only when `canEdit` — `voucher_creation` or `voucher_returned`)
  - [x] ReviewTrail right panel

### 4.8 Accountant Panel Pages

- [x] `Accountant/Dashboard.vue` — shared Dashboard.vue (persistent layout)
- [x] `Accountant/Analytics.vue` — 4 stat cards (vouchers, approved, returned,
  total PHP) + voucher trends + transactions (persistent layout)
- [x] `Accountant/Vouchers/Index.vue` — TabView (Pending/Approved/Returned) with search + category filter + DataTable; status Tags: "Approved" / "Returned"
- [x] `Accountant/Vouchers/Review.vue` — ApplicationInfo + AssistanceCode + Voucher DocumentMeta/Viewer + ReviewTrail + remarks + approve/return (ConfirmDialog)

### 4.9 Treasurer Panel Pages

- [x] `Treasurer/Dashboard.vue` — shared Dashboard.vue (persistent layout)
- [x] `Treasurer/Analytics.vue` — 4 stat cards (cheques, acknowledged, total PHP,
  pending) + disbursement + recent (persistent layout)
- [x] `Treasurer/Cheques/Index.vue` — TabView (Pending/Ready/On Hold) with search + category filter + DataTable; status Tags: "Cheque Ready" / "On Hold"
- [x] `Treasurer/Cheques/Review.vue` — ApplicationInfo + AssistanceCode + Voucher DocumentMeta/Viewer + ReviewTrail + contextual action buttons:
  - `with_treasurer`: "Acknowledge & Ready" (→ `cheque_ready`, SMS) / "Acknowledge & Hold" (dialog → `on_hold`)
  - `on_hold`: "Acknowledge & Ready" (→ `cheque_ready`, SMS)
  - `cheque_ready`: "Mark as Complete" (→ `claimed`, sets `claimed_at`, no duplicate review entry)
- `Treasurer/Budget/*.vue` — **not used**; `budget_checking` stage removed from workflow

### 4.10 Mayor's Office Panel Pages

- [x] `MayorsOffice/Dashboard.vue` — shared Dashboard.vue (persistent layout)
- [x] `MayorsOffice/Analytics.vue` — 4 stat cards (apps, approved, disbursed PHP,
  beneficiaries) + overview + reports (persistent layout)

---

---

## Phase 4.5 — Document Scanner Overhaul

**Spec: `SCANNER_MODIFICATIONS.md`** — Camera-based document capture replacing all `<input type="file">` with `DocumentScanner.vue`; output changes from JPEG to A4 portrait PDF.

### 4.5.1 Database

- [x] Migration: add `capture_type` (string: single/double/multi) and `scanner_size` (string: a4/card/half_sheet) to `required_documents` table
- [x] Update `RequiredDocumentSeeder` — all 21 documents populated with `capture_type` and `scanner_size` per the config table in the spec; includes `SET FOREIGN_KEY_CHECKS=0` to handle FK constraint during re-seed
- [x] Update `RequiredDocument` model fillable array — added `capture_type`, `scanner_size`
- [x] Run: `php artisan migrate` + `php artisan db:seed --class=RequiredDocumentSeeder`

### 4.5.2 Frontend — New/Modified Composables & Components

- [x] `npm install jspdf` — PDF generation library (23 packages, 0 vulnerabilities)
- [ ] `npm install vue-pdf-embed` (optional) — richer PDF viewer for staff review pages
- [x] Rewrite `useDocumentScanner.js` — full enhancement pipeline (downscale → grayscale → contrast
  stretch → adaptive threshold → JPEG), `capturedPages` array management, final PDF blob generation
  via `jsPDF`, reactive state (`isScanning`, `isProcessing`, `previewUrl`, `cameraError`,
  `hasCapture`, `isComplete`, `pdfBlob`), methods (`startCamera`, `capture`, `retakeLast`, `addPage`,
  `confirmAll`, `stopCamera`, `reset`). Accepts `captureType` param for `isComplete` computed.
- [x] Rewrite `DocumentScanner.vue` — new props (`docName`, `required`, `captureType`, `scannerSize`,
  `modelValue`), emits (`update:modelValue`, `captured`, `cleared`), 6 states (rotate hint → camera
  active → processing → preview → document complete → camera denied/fallback), 3 scanner presets
  (`SCANNER_PRESETS` — a4 portrait, card landscape, half_sheet landscape) with SVG mask overlay
  cutout, 3 capture type UX flows (single: 1 click → complete; double: front+back with auto-reopen;
  multi: loop + Add Another Page / Done)

### 4.5.3 Frontend — Affected Pages

- [x] `Public/Apply.vue` — Step 3: paginated one-doc-at-a-time flow; full-viewport camera overlay via Teleport; doc name + required badge shown; multi/double capture fixed; images stored raw, PDF deferred to submit; Step 4: image thumbnails from captured data; submit progress modal with per-doc conversion bar; toast notifications
- [x] `Public/Track.vue` — Resubmission `DocumentScanner` receives `captureType`/`scannerSize` from backend; redundant doc name wrapper removed; `onDocCapture` extracts `.file` from payload; progress modal on submit; redirects to tracked application after resubmit with toast
- [x] `DocumentViewer.vue` — Already handles PDFs via `<iframe>` fallback
- [x] `DocumentScanner.vue` — Teleport full-viewport camera; proper multi/double/single state machine; emits structured `{file, preview, pageCount, pages}`; raw image stored during scan, PDF generated on confirm
- [x] `useDocumentScanner.js` — `confirmPages()` separates from `generatePdfBlob()`; `isConfirmed` flag added
- [ ] MSWDO review/voucher pages — Not implemented yet (Phase 4.7)

### 4.5.4 Backend — MIME Validation

- [x] `StoreApplicationRequest.php` — `documents.*` MIME: `pdf|max:10240`
- [x] `ApplicationController@resubmit` — `documents.*` MIME: `pdf|max:10240`
- [x] `FileUploadService.php` — default `allowed_mimes` includes `application/pdf`; removed unused `ImageManager` import
- [x] `ApplicationController@store` — added missing `use StoreApplicationRequest` import
- [x] `ApplicationController@show` — `resubmission_docs_required` now reads `capture_type`/`scanner_size` from `requiredDocument` relation (was reading from `ApplicationDocument` which doesn't have those fields)
- [ ] MSWDO form requests — Not implemented yet (Phase 4.7)

### 4.5.5 Testing

- [x] A4 portrait preset — tested on device
- [x] Card landscape preset — tested on device
- [x] Half-sheet landscape preset — tested on device
- [x] Single capture type — tested
- [x] Double capture (front/back) — tested
- [x] Multi capture (3+ pages) — tested
- [x] Camera denied → fallback file input converts image to PDF — tested
- [x] PDF output is A4 portrait — tested
- [x] Resubmission flow: returned → rescans flagged docs → submits — tested
- [x] Submit progress modal with per-doc conversion bar — tested
- [x] Toast notifications on apply + track — tested
- [ ] MSWDO SCS upload and voucher upload via DocumentScanner — Not implemented yet (Phase 4.7)

---

## Phase 5 — Integration & Workflow Testing

### 5.1 End-to-End Workflow

- [ ] Submit application (Apply page) → verify reference code + SMS
- [ ] Track application (Track page) → verify status `submitted`
- [ ] AICS Staff approve → verify status `mswdo_review` + SMS + review trail entry
- [ ] AICS Staff return → verify applicant sees return notice + SMS
- [ ] Applicant resubmits → verify new documents captured via DocumentScanner with `is_resubmission = 1`
- [ ] MSWDO approve + capture SCS (via DocumentScanner) → verify status `assistance_coding` + SMS
- [ ] AICS Staff create assistance code → verify status `voucher_creation`
- [ ] MSWDO create voucher → verify status `voucher_checking`
- [ ] Accountant approve voucher → verify status `with_treasurer`
- [ ] Accountant return voucher → verify MSWDO can re-create (version increments)
- [ ] Treasurer acknowledge & ready → verify status `cheque_ready` + SMS
- [ ] Treasurer acknowledge & hold → verify status `on_hold`
- [ ] Treasurer re-evaluate (from on_hold) → verify status `cheque_ready` + SMS
- [ ] Treasurer mark as complete → verify status `claimed`, `claimed_at` set, no duplicate review entry
- [ ] `budget_checking` status is **not used** — Treasurer handles ready/hold directly
- [ ] Walk-in submission (AICS Staff encodes) → verify `submission_type = 'walk_in'`, `encoded_by` set

### 5.2 Role Access Control

- [ ] Admin cannot access any workflow panel URL — redirected
- [ ] AICS Staff cannot access MSWDO, Accountant, Treasurer, Admin URLs
- [ ] MSWDO cannot access AICS, Accountant, Treasurer, Admin URLs
- [ ] Accountant cannot access AICS, MSWDO, Treasurer, Admin URLs
- [ ] Treasurer cannot access AICS, MSWDO, Accountant, Admin URLs
- [ ] Mayor's Office cannot access any action routes — view only
- [ ] Unauthenticated users redirected to login for all protected routes

### 5.3 Security Testing

- [ ] CSRF protection — POST without CSRF token returns 419
- [ ] Rate limiting on login — 5 failed attempts triggers throttle
- [ ] MFA enforced — login without MFA code fails if MFA enabled
- [ ] Session revocation — Admin force-logout invalidates session immediately
- [ ] Encrypted fields stored as ciphertext in MySQL — verify in phpMyAdmin
- [ ] Signed URL expires — accessing expired URL fails
- [ ] `audit_logs` written on every mutating action
- [ ] `audit_logs` and `reviews` cannot be updated or deleted (test directly via DB)
- [ ] File type validation — non PDF/image rejected
- [ ] File size limit enforced per system settings

### 5.4 SMS + Queue Testing

- [ ] `php artisan queue:work` running
- [ ] Submit application → `sms_notifications` row created → status changes to `sent`
- [ ] All 4 trigger events fire: `submission_complete`, `application_under_review`, `resubmission_needed`, `cheque_claiming`
- [ ] SMS failure → retry logic → `failed_jobs` table entry

### 5.5 Audit Log Testing

- [x] Login/logout/AUP acceptance now logged via `AuditLog::create` directly in controllers
- [x] OTP verify route named `otp.verify` — fixes blank module/action entries caused by AuditLogMiddleware firing after `Auth::login()` on unnamed routes
- [ ] Login → `action = 'login'` in `audit_logs`
- [ ] Approve application → entry with `entity_type = 'application'`
- [ ] Export CSV → export action logged
- [ ] Failed login → `action = 'login_failed'`, `user_id = null`

---

## Phase 6 — UI/UX Polish

- [ ] Apply PrimeVue Sakai theme colors matching `system_settings.primary_color`
- [ ] All DataTables: pagination, search, column filters, CSV export button
- [ ] All review pages: ReviewTrail right panel (newest first)
- [ ] All irreversible actions: PrimeVue ConfirmDialog before proceeding
- [ ] All successful Inertia actions: flash message displayed as PrimeVue Toast
- [ ] All timestamps: PST (UTC+8) via `formatDate.js`
- [ ] Inline document viewer: PDF + image on all review pages
- [ ] Status labels: human-readable text from `statusLabels.js`
- [ ] Loading states: Inertia `form.processing` used on all submit buttons
- [ ] Error states: `form.errors.field` displayed below each form field
- [ ] Empty states: `AppEmptyState.vue` when tables have no data
- [ ] Test on tablet resolution (staff may use tablets)

---

## Phase 7 — Pre-Deployment

### 7.1 Code Cleanup

- [ ] Remove all `console.log()` and `dd()` / `dump()` statements
- [ ] `composer audit` — fix vulnerabilities
- [ ] `npm audit` — fix vulnerabilities
- [ ] Remove unused packages

### 7.2 Production Build

- [ ] `npm run build` — verify `/public/build` generated
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan view:cache`
- [ ] `php artisan event:cache`

### 7.3 Final Security Review

- [ ] `APP_DEBUG=false`
- [ ] All sensitive fields encrypted
- [ ] Password policy enforced
- [ ] MFA working for all internal roles
- [ ] Role-based access verified
- [ ] Session revocation working
- [ ] AUP acknowledgment enforced
- [ ] Audit logs append-only
- [ ] Files only via signed URLs
- [ ] HTTPS enforced

---

## Phase 8 — Production Deployment

Follow `17_deployment_guide.md`. Summary:

- [ ] Server provisioned (Ubuntu 22.04 LTS — VPS or office desktop)
- [ ] Nginx, PHP 8.2-FPM, MySQL 8.x, Supervisor, Certbot, UFW installed
- [ ] `.gov.ph` domain requested from DICT
- [ ] DNS A record pointed to server IP
- [ ] SSL certificate issued (`certbot --nginx -d alalay.gmn.gov.ph`)
- [ ] Repository cloned to `/var/www/alalay`
- [ ] `composer install --no-dev`, `npm run build`
- [ ] Production `.env` configured
- [ ] `php artisan migrate --force` + `php artisan db:seed --force`
- [ ] Laravel optimizations cached
- [ ] Supervisor queue worker configured and running
- [ ] Cron job registered for Laravel Scheduler
- [ ] Backup script deployed
- [ ] Single Nginx block configured (one domain, one SSL)
- [ ] Smoke test: app loads over HTTPS, login works, document capture (DocumentScanner) works, SMS delivered

---

## Phase 9 — Handover

- [ ] Train Admin: User Management, Audit Logs, System Settings, Session Revocation
- [ ] Train AICS Staff: Screening, walk-in encoding, assistance coding
- [ ] Train MSWDO: Review, SCS capture (DocumentScanner), voucher creation
- [ ] Train Accountant: Voucher review, budget checking
- [ ] Train Treasurer: Voucher acknowledgment
- [ ] Document server credentials securely (not in Git)
- [ ] Document backup restore procedure — test with IT officer
- [ ] Submit ALALAY for NPC registration as data processing system
- [ ] File PIA with DPO

---

## Phase Summary

| Phase | Description | Depends On |
| --- | --- | --- |
| 0 | Project Setup | Nothing |
| 1 | Database — Migrations, Models, Seeders | Phase 0 |
| 2 | Backend Foundation — Auth, Middleware, Policies, Services | Phase 1 |
| 3 | Controllers — All Inertia::render() controllers | Phase 2 |
| 4 | Frontend — All Vue 3 Inertia pages and components | Phase 3 |
| 5 | Integration & Workflow Testing | Phase 4 |
| 6 | UI/UX Polish | Phase 5 |
| 7 | Pre-Deployment | Phase 6 |
| 8 | Production Deployment | Phase 7 |
| 9 | Handover | Phase 8 |

---

*Document prepared for AI consumption and development reference — ALALAY System,
Municipality of General Mamerto Natividad, Nueva Ecija.*

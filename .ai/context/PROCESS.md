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
- [x] `AuditLogMiddleware` — writes to `audit_logs` on POST/PUT/PATCH/DELETE; captures user_id, role, module (from route name prefix), action (from route name), ip_address, user_agent
- [x] `EnsureAupAccepted` — redirects to `/acceptable-use-policy` if `acceptable_use_policy_accepted_at` is null
- [x] Register all middleware in `bootstrap/app.php` (Laravel 12) — `role` and `aup.accepted` aliases; `AuditLogMiddleware` appended to web group
- [x] Apply middleware to route groups in `web.php` — all role panel routes grouped with correct `role:X` middleware

### 2.4 Authorization (Policies)

- [x] `ApplicationPolicy` — `viewAny`/`view` for all internal roles; `approve`/`returnApp` for AICS (status=submitted) and MSWDO (status=mswdo_review)
- [x] `VoucherPolicy` — `viewAny`/`view` for MSWDO/Accountant/Treasurer/Mayor; `create` for MSWDO; `approve`/`returnVoucher` for Accountant; `acknowledge` for Treasurer; `markReady`/`hold`/`reEvaluate` for Accountant
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

- [x] `Auth/LoginController` — login (email + password → OTP challenge)
- [x] `Auth/OtpChallengeController` — OTP verify + resend
- [x] `Auth/PasswordResetController` — forgot + reset password (via Fortify)
- [x] `AupController@show` — renders `Auth/AcceptableUsePolicy.vue`
- [x] `AupController@accept` — records `acceptable_use_policy_accepted_at`; redirects to role dashboard

### 3.3 Shared Controllers

- [x] `Shared/AccountController@edit` — renders `Auth/AccountSettings.vue` with user data
- [x] `Shared/AccountController@update` — handles profile + password + profile picture update (uploads to Supabase S3 bucket; avatar display via proxy endpoint → signed URL redirect — ON HOLD: browser shows initial letter instead of image)

### 3.4 Admin Controllers

- [x] `Admin/DashboardController@index` — KPIs + recent activity feed (shared Dashboard.vue)
- [x] `Admin/AnalyticsController@index` — user stats + app stats + recent activity with date filter
- [x] `Admin/UserController` — full CRUD (index, create, store, edit, update, toggleStatus, revokeSessions) — stub methods exist
- [x] `Admin/AuditLogController@index` — paginated + filterable + CSV export — stub exists
- [x] `Admin/SystemSettingController` — index + update — stub exists
- [x] `Admin/AssistanceCategoryController` — full CRUD + toggle active — stub exists
- [x] `Admin/RequiredDocumentController` — full CRUD per category + toggle — stub exists
- [x] `Admin/AssistanceCodeReferenceController` — full CRUD + toggle — stub exists

### 3.5 AICS Staff Controllers

- [x] `Aics/DashboardController@index`
- [x] `Aics/AnalyticsController@index`
- [x] `Aics/ApplicationController@index` — tabbed by status prop — stub exists
- [x] `Aics/ApplicationController@show` — full application + documents + review trail — stub exists
- [x] `Aics/ApplicationController@documentUrl` — returns signed URL for document viewer — stub exists
- [x] `Aics/ApplicationController@approve` — approves; writes review; dispatches SMS; redirects — stub exists
- [x] `Aics/ApplicationController@return` — returns; writes review; dispatches SMS; redirects — stub exists
- [x] `Aics/AssistanceCodeController@index` — stub exists
- [x] `Aics/AssistanceCodeController@show` — application + social case study signed URL + review trail — stub exists
- [x] `Aics/AssistanceCodeController@store` — saves assistance code; updates status; redirects — stub exists

### 3.6 MSWDO Controllers

- [x] `Mswdo/DashboardController@index`
- [x] `Mswdo/AnalyticsController@index`
- [x] `Mswdo/ApplicationController@index` — stub exists
- [x] `Mswdo/ApplicationController@show` — stub exists
- [x] `Mswdo/ApplicationController@approve` — captures social case study (via DocumentScanner); writes review; dispatches SMS — stub exists
- [x] `Mswdo/ApplicationController@return` — stub exists
- [x] `Mswdo/VoucherController@index` — stub exists
- [x] `Mswdo/VoucherController@show` — application + SCS + assistance code + review trail — stub exists
- [x] `Mswdo/VoucherController@store` — captures voucher (via DocumentScanner); updates status; redirects — stub exists

### 3.7 Accountant Controllers

- [x] `Accountant/DashboardController@index`
- [x] `Accountant/AnalyticsController@index`
- [x] `Accountant/VoucherController@index` — stub exists
- [x] `Accountant/VoucherController@show` — stub exists
- [x] `Accountant/VoucherController@approve` — stub exists
- [x] `Accountant/VoucherController@return` — stub exists

### 3.8 Treasurer Controllers

- [x] `Treasurer/DashboardController@index`
- [x] `Treasurer/AnalyticsController@index`
- [x] `Treasurer/ChequeController@index` — stub exists
- [x] `Treasurer/ChequeController@show` — stub exists
- [x] `Treasurer/ChequeController@acknowledge` — stub exists
- [x] `Treasurer/BudgetController@index` — stub exists
- [x] `Treasurer/BudgetController@show` — stub exists
- [x] `Treasurer/BudgetController@markReady` — stub exists
- [x] `Treasurer/BudgetController@hold` — stub exists
- [x] `Treasurer/BudgetController@reEvaluate` — stub exists

### 3.9 Mayor's Office Controllers

- [x] `MayorsOffice/DashboardController@index` — consolidated KPIs
- [x] `MayorsOffice/AnalyticsController@index` — consolidated charts

---

## Phase 4 — Frontend (Vue 3 + Inertia Pages)

### 4.1 Foundation

- [x] Configure `HandleInertiaRequests` shared props — shares `auth.user`, `flash`, `ziggy` (Ziggy routes available via `route()` in Vue)
- [ ] Create `useAuth` composable — reads from `$page.props.auth`
- [ ] Create `useToast` composable — PrimeVue Toast wrapper
- [ ] Create `useConfirm` composable — PrimeVue ConfirmDialog wrapper
- [ ] Create `useFileViewer` composable — fetches signed URL; opens DocumentViewer
- [x] Create `useDocumentScanner` composable — camera access, frame capture, enhancement pipeline (downscale → grayscale → contrast stretch → adaptive threshold → JPEG export)
- [ ] Create `useStatusLabel` composable — status enum → `{ label, severity }`
- [ ] Create `Utils/statusLabels.js` — full status → display label map
- [ ] Create `Utils/formatDate.js` — dayjs PST formatter
- [ ] Create `Utils/formatCurrency.js` — PHP peso formatter
- [ ] Create `Utils/constants.js` — status arrays, role arrays
- [ ] Create flash message handling — read `$page.props.flash` on page load; show PrimeVue Toast
- [x] Create `AppLayout` (Sakai persistent layout) — all 8 panel pages use `defineOptions({ layout: AppLayout })` for true SPA navigation (layout stays mounted, only content swaps)
- [ ] Create admin-specific, auth-specific, public-specific Layout components as separate wrappers
- [x] Verify Ziggy `route()` helper available in Vue: `import { route } from 'ziggy-js'`

### 4.2 Auth Pages

- [x] `Auth/Login.vue` — email + password + `useForm()`; error display per field
- [x] `Auth/EmailOtpChallenge.vue` — 6-digit OTP input via InputOtp + resend button + `useForm()`
- [x] `Auth/ForgotPassword.vue` — email input
- [x] `Auth/ResetPassword.vue` — new password form
- [x] `Auth/AcceptableUsePolicy.vue` — AUP text + acknowledge button + `useForm()`
- [x] `Auth/AccountSettings.vue` — name + email form with save (persistent layout)
- [x] Test full auth flow: login → OTP → AUP → dashboard

### 4.3 Public Pages

- [x] `Public/Apply.vue` — multi-step form (placeholder stub):
  - [ ] Step 1: Category selection (card grid)
  - [ ] Step 2: Claimant + beneficiary fields with server error display
  - [ ] Step 3: Document capture via DocumentScanner per required document (one DocumentScanner per document; fallback file input shown when camera unavailable)
  - [ ] Step 4: Summary confirmation
  - [ ] Step 5: Success — display reference code
  - [ ] Uses `useForm()` for submission
- [x] `Public/Track.vue` — reference code input + application status + review trail (placeholder stub)

### 4.4 Shared Components

- [ ] `Components/Common/AppKpiCard.vue` — props: `title`, `value`, `icon`, `color`
- [ ] `Components/Common/AppStatusBadge.vue` — props: `status`; reads from `useStatusLabel`
- [ ] `Components/Common/AppDateFilter.vue` — emits: `filter-changed`; presets + custom range
- [ ] `Components/Common/AppConfirmModal.vue` — wraps PrimeVue ConfirmDialog
- [ ] `Components/Common/AppExportButton.vue` — triggers CSV download via `router.visit()`
- [ ] `Components/Common/AppEmptyState.vue`
- [ ] `Components/Application/ReviewTrail.vue` — props: `reviews`; chronological list
- [ ] `Components/Application/ApplicationInfo.vue` — props: `application`; claimant + beneficiary display
- [ ] `Components/Application/DocumentList.vue` — props: `documents`, `signedUrlRoute`
- [ ] `Components/Application/DocumentViewer.vue` — PDF/image inline viewer
- [x] `Components/Application/DocumentScanner.vue` — camera capture with guide overlay
  (SVG mask), enhancement pipeline (5-step: downscale 1200px → grayscale → contrast
  stretch → adaptive threshold 40×10 → JPEG 0.88), preview/recapture/confirm, fallback
  file input `image/jpeg,image/png` only. Zero PrimeVue dependency — pure Tailwind +
  canvas JS. Works in both public (Tailwind-only) and dashboard (PrimeVue) pages.
- [ ] `Components/Application/ReturnModal.vue` — props: `requiredDocuments`; emits: `confirmed`
- [ ] `Components/Charts/LineChart.vue`, `BarChart.vue`, `DonutChart.vue`

### 4.5 Admin Panel Pages

- [x] `Admin/Dashboard.vue` — KPI cards + recent/unusual activity tables + system status (persistent layout via `defineOptions`)
- [x] `Admin/Analytics.vue` — 4 stat cards (users, active, inactive, apps) + system overview + activity (persistent layout)
- [ ] `Admin/Users/Index.vue` — PrimeVue DataTable + search + filter + Add User button
- [ ] `Admin/Users/Create.vue` — user form + `useForm()`
- [ ] `Admin/Users/Edit.vue` — pre-populated user form + toggle status + revoke sessions
- [ ] `Admin/AuditLogs.vue` — filterable table + export
- [ ] `Admin/SystemSettings.vue` — grouped settings form sections

### 4.6 AICS Staff Panel Pages

- [ ] `Aics/Dashboard.vue`
- [x] `Aics/Analytics.vue` — 4 stat cards (apps, pending, approved, codes) + trends + recent (persistent layout)
- [ ] `Aics/Applications/Index.vue` — PrimeVue TabView (Pending/Screened/Returned) + DataTable per tab
- [ ] `Aics/Applications/Review.vue`
  - [ ] ApplicationInfo component
  - [ ] DocumentList + DocumentViewer
  - [ ] ReviewTrail right panel
  - [ ] Approve button + confirm modal
  - [ ] Return button → ReturnModal
- [ ] `Aics/AssistanceCodes/Index.vue` — TabView (Pending/Coded)
- [ ] `Aics/AssistanceCodes/Code.vue`
  - [ ] Application info display
  - [ ] Social case study inline viewer
  - [ ] ReviewTrail right panel
  - [ ] Assistance code type dropdown (auto-fills amount)
  - [ ] Amount field + submit button

### 4.7 MSWDO Panel Pages

- [ ] `Mswdo/Dashboard.vue`
- [x] `Mswdo/Analytics.vue` — 4 stat cards (apps, validated, returned, vouchers) + validation + pending (persistent layout)
- [ ] `Mswdo/Applications/Index.vue` — TabView (Screened/Approved/Returned)
- [ ] `Mswdo/Applications/Review.vue`
  - [ ] ApplicationInfo + DocumentList + DocumentViewer
  - [ ] ReviewTrail right panel
  - [ ] Next button → social case study capture step (via DocumentScanner — MSWDO scans the printed SCS) → submit
  - [ ] Return button → ReturnModal
- [ ] `Mswdo/Vouchers/Index.vue` — TabView (Pending/Created)
- [ ] `Mswdo/Vouchers/Create.vue`
  - [ ] Step 1: Application info + SCS viewer + assistance code details + ReviewTrail
  - [ ] Step 2: Voucher document capture (via DocumentScanner — MSWDO scans the
    physical voucher) + adjustment remarks + submit

### 4.8 Accountant Panel Pages

- [ ] `Accountant/Dashboard.vue`
- [x] `Accountant/Analytics.vue` — 4 stat cards (vouchers, approved, returned,
  total PHP) + voucher trends + transactions (persistent layout)
- [ ] `Accountant/Vouchers/Index.vue` — TabView (Pending/Approved/Returned)
- [ ] `Accountant/Vouchers/Review.vue` — voucher viewer + summary + ReviewTrail + approve/return

### 4.9 Treasurer Panel Pages

- [ ] `Treasurer/Dashboard.vue`
- [x] `Treasurer/Analytics.vue` — 4 stat cards (cheques, acknowledged, total PHP,
  pending) + disbursement + recent (persistent layout)
- [ ] `Treasurer/Cheques/Index.vue` — TabView (Pending/Ready/On Hold)
- [ ] `Treasurer/Cheques/Review.vue` — voucher viewer + summary + ReviewTrail + acknowledge
- [ ] `Treasurer/Budget/Index.vue` — TabView (Pending/Cheque Ready/On Hold)
- [ ] `Treasurer/Budget/Check.vue` — voucher + application summary + ReviewTrail + mark ready/hold/re-evaluate

### 4.10 Mayor's Office Panel Pages

- [ ] `MayorsOffice/Dashboard.vue` — consolidated KPIs + activity table + category table
- [x] `MayorsOffice/Analytics.vue` — 4 stat cards (apps, approved, disbursed PHP,
  beneficiaries) + overview + reports (persistent layout)

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
- [ ] Treasurer acknowledge → verify status `budget_checking`
- [ ] Treasurer mark cheque ready → verify status `cheque_ready` + SMS
- [ ] Treasurer put on hold → verify status `on_hold`
- [ ] Treasurer re-evaluate on-hold → mark cheque ready
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

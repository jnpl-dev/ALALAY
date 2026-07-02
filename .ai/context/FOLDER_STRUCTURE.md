# ALALAY: Folder Structure Specification
**Inertia.js Monolith — Single Laravel Project**
**Municipality of General Mamerto Natividad, Nueva Ecija**

---

## Overview

ALALAY is a single Laravel 12 project. Laravel handles routing, auth, data, and serves Vue 3 pages via Inertia.js. There is no separate frontend project, no REST API, no second server, and no second deployment.

```
alalay/                           ← Single Git repository, single Laravel project
├── .ai/                          ← AI context + skills (vibe coding brain)
├── app/                          ← Laravel application logic
├── bootstrap/
├── config/
├── database/
├── public/                       ← Web root — Nginx points here
├── resources/
│   ├── js/                       ← Vue 3 + PrimeVue Sakai lives here
│   └── css/
├── routes/
│   └── web.php                   ← All routes — no api.php needed
├── scripts/                      ← Shell scripts (backup, deploy)
├── storage/
├── tests/
├── vendor/
├── .env
├── .env.example
├── artisan
├── composer.json
├── package.json                  ← One package.json for the whole project
├── vite.config.js
└── README.md
```

---

## `.ai/` — Vibe Coding Brain

The entry point for every AI coding session. Read `AGENTS.md` first at the start of every session.

```
.ai/
├── AGENTS.md                          ← Master index — read this first every session
│
├── context/                           ← System-level specification documents
│   ├── 01_user_story.md
│   ├── 02_schema_dictionary.md
│   ├── 03_panels_pages_content.md
│   ├── 04_npc_compliance.md
│   ├── 05_tech_stack.md
│   ├── 06_inertia_controller_props.md ← Replaces api_contracts.md — props per page
│   ├── 07_role_permission_matrix.md
│   ├── 08_error_handling_spec.md
│   ├── 09_sms_templates.md
│   ├── 10_file_handling_spec.md
│   ├── 11_reference_code_spec.md
│   ├── 12_notification_flow.md
│   ├── 13_seeder_data.md
│   ├── 14_ui_component_library.md
│   ├── 15_analytics_queries.md
│   ├── 16_dev_checklist.md
│   └── 17_deployment_guide.md
│
└── skills/                            ← Atomic AI skill instruction files
    ├── SKILLS_INDEX.md
    ├── backend/
    │   ├── create-migration.md
    │   ├── create-model.md
    │   ├── create-controller.md       ← Inertia::render() pattern
    │   ├── create-form-request.md
    │   ├── create-policy.md
    │   ├── create-service.md
    │   ├── create-job.md
    │   ├── create-observer.md
    │   ├── write-audit-log.md
    │   ├── write-review-trail.md
    │   ├── handle-file-upload.md
    │   ├── generate-signed-url.md
    │   └── handle-sms.md
    └── frontend/
        ├── create-page.md             ← Inertia page component pattern
        ├── create-layout.md
        ├── create-store.md            ← Pinia store pattern
        ├── create-composable.md
        ├── use-inertia-form.md        ← useForm() pattern (replaces Axios)
        ├── use-inertia-link.md        ← <Link> and router.visit() pattern
        ├── use-shared-props.md        ← Accessing Inertia shared props (auth user, flash)
        ├── use-datatable.md
        ├── use-review-trail.md
        ├── use-document-viewer.md
        ├── use-confirm-modal.md
        ├── use-toast.md
        ├── use-file-upload.md
        ├── use-status-badge.md
        ├── use-kpi-card.md
        ├── use-chart.md
        └── use-date-filter.md
```

### `AGENTS.md` Content

```markdown
# ALALAY — AI Agent Master Index

## What is ALALAY?
ALALAY is a digital AICS management and notification system for the Municipality
of General Mamerto Natividad, Nueva Ecija. Built as an Inertia.js monolith:
Laravel 12 (routing, auth, data, controllers) + Vue 3 + PrimeVue Sakai (pages/UI)
+ MySQL (XAMPP/VPS) + Supabase Storage (files).

## Architecture: Inertia.js Monolith
- ONE Laravel project — no separate frontend project
- NO REST API — controllers return Inertia::render() with props
- NO Vue Router — Laravel web.php handles all routes
- NO Axios — Inertia useForm() handles all form submissions
- NO CORS — same domain, same Laravel session
- Auth = standard Laravel session (Fortify) — no Sanctum

## Before Writing Any Code
1. Read context/05_tech_stack.md — architecture and conventions
2. Read context/02_schema_dictionary.md — database schema
3. Read the relevant context file for the feature you are building
4. Load the relevant skill file from skills/ before writing boilerplate

## Context Files
01 — User story and workflow
02 — Database schema (ALWAYS read before migrations/models)
03 — Panels, pages, UI spec (read before frontend work)
04 — NPC compliance (read before any auth/access/security work)
05 — Tech stack (ALWAYS read — conventions apply everywhere)
06 — Inertia controller props (read before controllers or page components)
07 — Role permission matrix (read before Policies or route middleware)
08 — Error handling spec (read before error handling code)
09 — SMS templates (read before SendSmsJob)
10 — File handling (read before any upload controller or component)
11 — Reference code spec (read before ReferenceCodeService)
12 — Notification flow (read before any decision action controller)
13 — Seeder data (read before writing seeders)
14 — UI component library (read before building any panel page)
15 — Analytics queries (read before dashboard/analytics controllers)
16 — Dev checklist (track progress)
17 — Deployment guide (read before production setup)

## Key Conventions (non-negotiable)
- All PKs are UUIDs — HasUuids trait; $keyType = 'string'; $incrementing = false
- Controllers return Inertia::render('Path/To/Page', ['prop' => $data])
- All mutating actions write to audit_logs via AuditLogMiddleware or Observer
- All workflow decision actions insert into reviews table
- File access ONLY via signed URLs — never expose raw Supabase paths
- audit_logs and reviews are append-only — never UPDATE or DELETE rows
- Sensitive fields use encrypted cast: address, phone, email fields
- SMS always queued via SendSmsJob — never called synchronously
- Role middleware at route group level — never check role inside controller
- Laravel Policies for model authorization — never check role inside controller
- Flash messages via Inertia shared props — not session flash directly in Vue
- Form submissions via Inertia useForm() — not Axios
- Navigation via <Link> component or router.visit() — not window.location
```

---

## `app/` — Laravel Application

```
app/
├── Console/
│   └── Kernel.php                     # Scheduler: backup, retention, is_online reset
│
├── Exceptions/
│   └── Handler.php                    # Global exception handler
│                                      # Inertia errors rendered as Inertia pages
│                                      # (not JSON) — use HandleInertiaRequests
│
├── Http/
│   ├── Controllers/
│   │   ├── Auth/                      # Fortify view controllers (login, MFA, etc.)
│   │   │   ├── AuthenticatedSessionController.php
│   │   │   ├── LoginController.php
│   │   │   ├── OtpChallengeController.php
│   │   │   ├── PasswordResetController.php
│   │   │   └── EmailVerificationController.php
│   │   ├── Admin/
│   │   │   ├── DashboardController.php
│   │   │   ├── AnalyticsController.php
│   │   │   ├── UserController.php
│   │   │   ├── AuditLogController.php
│   │   │   ├── SystemSettingController.php
│   │   │   ├── AssistanceCategoryController.php
│   │   │   ├── RequiredDocumentController.php
│   │   │   └── AssistanceCodeReferenceController.php
│   │   ├── Aics/
│   │   │   ├── DashboardController.php
│   │   │   ├── AnalyticsController.php
│   │   │   ├── ApplicationController.php
│   │   │   └── AssistanceCodeController.php
│   │   ├── Mswdo/
│   │   │   ├── DashboardController.php
│   │   │   ├── AnalyticsController.php
│   │   │   ├── ApplicationController.php
│   │   │   └── VoucherController.php
│   │   ├── Accountant/
│   │   │   ├── DashboardController.php
│   │   │   ├── AnalyticsController.php
│   │   │   ├── VoucherController.php
│   │   │   └── BudgetController.php
│   │   ├── Treasurer/
│   │   │   ├── DashboardController.php
│   │   │   ├── AnalyticsController.php
│   │   │   └── ChequeController.php
│   │   ├── MayorsOffice/
│   │   │   ├── DashboardController.php
│   │   │   └── AnalyticsController.php
│   │   ├── Public/
│   │   │   ├── CategoryController.php
│   │   │   └── ApplicationController.php
│   │   └── Shared/
│   │       └── AccountController.php
│   │
│   ├── Middleware/
│   │   ├── HandleInertiaRequests.php  # Inertia middleware — shared props (auth user, flash)
│   │   ├── RoleMiddleware.php         # Checks user role; redirects 403 as Inertia error page
│   │   ├── AuditLogMiddleware.php     # Writes to audit_logs on POST/PUT/PATCH/DELETE
│   │   └── EnsureAupAccepted.php     # Redirects to AUP page if not yet acknowledged
│   │
│   └── Requests/
│       ├── Auth/
│       │   └── LoginRequest.php
│       ├── Admin/
│       │   ├── CreateUserRequest.php
│       │   ├── UpdateUserRequest.php
│       │   └── UpdateSystemSettingRequest.php
│       ├── Aics/
│       │   ├── ApproveApplicationRequest.php
│       │   ├── ReturnApplicationRequest.php
│       │   └── CreateAssistanceCodeRequest.php
│       ├── Mswdo/
│       │   ├── ApproveApplicationRequest.php
│       │   ├── ReturnApplicationRequest.php
│       │   └── CreateVoucherRequest.php
│       ├── Accountant/
│       │   ├── ApproveVoucherRequest.php
│       │   ├── ReturnVoucherRequest.php
│       │   ├── MarkChequeReadyRequest.php
│       │   └── HoldApplicationRequest.php
│       ├── Treasurer/
│       │   └── AcknowledgeVoucherRequest.php
│       ├── Public/
│       │   ├── SubmitApplicationRequest.php
│       │   └── ResubmitDocumentsRequest.php
│       └── Shared/
│           └── UpdateAccountRequest.php
│
├── Jobs/
│   ├── SendSmsJob.php
│   └── BackupDatabaseJob.php
│
├── Models/
│   ├── User.php
│   ├── AssistanceCategory.php
│   ├── RequiredDocument.php
│   ├── Application.php
│   ├── ApplicationDocument.php
│   ├── Review.php
│   ├── SocialCaseStudy.php
│   ├── AssistanceCodeReference.php
│   ├── AssistanceCode.php
│   ├── Voucher.php
│   ├── AuditLog.php
│   ├── SmsNotification.php
│   └── SystemSetting.php
│
├── Observers/
│   └── ApplicationObserver.php
│
├── Policies/
│   ├── ApplicationPolicy.php
│   ├── VoucherPolicy.php
│   ├── AssistanceCodePolicy.php
│   ├── SocialCaseStudyPolicy.php
│   ├── AuditLogPolicy.php
│   ├── UserPolicy.php
│   └── SystemSettingPolicy.php
│
├── Providers/
│   ├── AppServiceProvider.php
│   ├── AuthServiceProvider.php        # Policy registrations
│   └── FortifyServiceProvider.php     # Fortify feature customizations
│
└── Services/
    ├── AuditLogger.php
    ├── SmsService.php
    ├── FileUploadService.php
    ├── SignedUrlService.php
    └── ReferenceCodeService.php
```

---

## `resources/js/` — Vue 3 + PrimeVue Sakai

```
resources/js/
├── app.js                             # Inertia + Vue 3 bootstrap entry point
│
├── Components/                        # Shared reusable Vue components
│   ├── Common/
│   │   ├── AppKpiCard.vue
│   │   ├── AppStatusBadge.vue         # Status enum → label + color
│   │   ├── AppDateFilter.vue          # Period presets + custom date range
│   │   ├── AppConfirmModal.vue        # PrimeVue ConfirmDialog wrapper
│   │   ├── AppExportButton.vue        # CSV export trigger
│   │   └── AppEmptyState.vue
│   ├── Application/
│   │   ├── ReviewTrail.vue            # Chronological review trail right panel
│   │   ├── ApplicationInfo.vue        # Claimant + beneficiary display
│   │   ├── DocumentList.vue           # Uploaded docs list + view buttons
│   │   ├── DocumentViewer.vue         # Inline PDF/image viewer via signed URL
│   │   ├── DocumentScanner.vue         # Camera capture with guide overlay + enhancement pipeline
│   │   └── ReturnModal.vue            # Return modal: doc checklist + remarks
│   └── Charts/
│       ├── LineChart.vue
│       ├── BarChart.vue
│       └── DonutChart.vue
│
├── Composables/
│   ├── useAuth.js                     # Reads auth user from Inertia shared props
│   ├── useToast.js                    # PrimeVue Toast wrapper
│   ├── useConfirm.js                  # PrimeVue ConfirmDialog wrapper
│   ├── useFileViewer.js               # Fetches signed URL; opens DocumentViewer
│   ├── useDocumentScanner.js          # Camera access, frame capture, enhancement pipeline (downscale → grayscale → contrast stretch → adaptive threshold → JPEG export)
│   ├── useStatusLabel.js              # Maps status enum → { label, severity }
│   └── useDateFilter.js               # Date filter state and period presets
│
├── Layouts/
│   ├── PublicLayout.vue               # Apply page, Track page
│   ├── AuthLayout.vue                 # Login, MFA, password reset pages
│   ├── AdminLayout.vue                # PrimeVue Sakai sidebar layout for Admin
│   ├── AicsLayout.vue
│   ├── MswdoLayout.vue
│   ├── AccountantLayout.vue
│   ├── TreasurerLayout.vue
│   └── MayorsOfficeLayout.vue
│
├── Pages/                             # Inertia page components (route-level)
│   ├── Auth/
│   │   ├── Login.vue
│   │   ├── EmailOtpChallenge.vue
│   │   ├── ForgotPassword.vue
│   │   ├── ResetPassword.vue
│   │   ├── AcceptableUsePolicy.vue    # AUP acknowledgment — shown on first login

│   ├── Public/
│   │   ├── Apply.vue                  # Multi-step application form
│   │   └── Track.vue                  # Reference code lookup + status + resubmit
│   ├── Admin/
│   │   ├── Dashboard.vue
│   │   ├── Analytics.vue
│   │   ├── Users/
│   │   │   ├── Index.vue              # User management table
│   │   │   ├── Create.vue             # Add user form
│   │   │   └── Edit.vue               # Edit user form
│   │   ├── AuditLogs.vue
│   │   ├── SystemSettings.vue
│   │   └── AccountSettings.vue
│   ├── Aics/
│   │   ├── Dashboard.vue
│   │   ├── Analytics.vue
│   │   ├── Applications/
│   │   │   ├── Index.vue              # Tabbed: Pending / Screened / Returned
│   │   │   └── Review.vue             # Application review + approve/return
│   │   ├── AssistanceCodes/
│   │   │   ├── Index.vue              # Tabbed: Pending / Coded
│   │   │   └── Code.vue               # Assistance coding form
│   │   └── AccountSettings.vue
│   ├── Mswdo/
│   │   ├── Dashboard.vue
│   │   ├── Analytics.vue
│   │   ├── Applications/
│   │   │   ├── Index.vue
│   │   │   └── Review.vue             # Review + social case study capture (DocumentScanner)
│   │   ├── Vouchers/
│   │   │   ├── Index.vue
│   │   │   └── Create.vue             # Two-step: info view → voucher capture (DocumentScanner)
│   │   └── AccountSettings.vue
│   ├── Accountant/
│   │   ├── Dashboard.vue
│   │   ├── Analytics.vue
│   │   ├── Vouchers/
│   │   │   ├── Index.vue
│   │   │   └── Review.vue
│   │   ├── Budget/
│   │   │   ├── Index.vue
│   │   │   └── Check.vue
│   │   └── AccountSettings.vue
│   ├── Treasurer/
│   │   ├── Dashboard.vue
│   │   ├── Analytics.vue
│   │   ├── Cheques/
│   │   │   ├── Index.vue
│   │   │   └── Review.vue
│   │   └── AccountSettings.vue
│   └── MayorsOffice/
│       ├── Dashboard.vue
│       ├── Analytics.vue
│       └── AccountSettings.vue
│
├── Stores/                            # Pinia — for global persistent state only
│   ├── auth.store.js                  # Auth user (mirrors Inertia shared props)
│   └── notification.store.js          # In-app notification bell state
│
└── Utils/
    ├── constants.js                   # Status arrays, role arrays, enums
    ├── statusLabels.js                # Status → { label, severity } map
    ├── formatDate.js                  # dayjs PST (UTC+8) formatter
    └── formatCurrency.js              # PHP peso formatter
```

---

## `routes/web.php` — Route Structure

```php
<?php
// All routes in one file — no api.php needed

// Public routes (no auth)
Route::get('/', [CategoryController::class, 'index'])->name('home');
Route::get('/apply', [ApplicationController::class, 'create'])->name('apply');
Route::post('/apply', [ApplicationController::class, 'store'])->name('apply.store');
Route::get('/track', [ApplicationController::class, 'track'])->name('track');
Route::get('/track/{reference_code}', [ApplicationController::class, 'show'])->name('track.show');
Route::post('/track/{reference_code}/resubmit', [ApplicationController::class, 'resubmit'])->name('track.resubmit');

// Auth routes (Fortify handles these automatically)
// /login, /logout, /forgot-password, /reset-password
// /auth/login, /auth/otp-challenge, /auth/logout

// AUP route (auth but AUP not yet accepted)
Route::middleware('auth')->group(function () {
    Route::get('/acceptable-use-policy', [AupController::class, 'show'])->name('aup.show');
    Route::post('/acceptable-use-policy', [AupController::class, 'accept'])->name('aup.accept');
});

// Protected routes (auth + AUP accepted)
Route::middleware(['auth', 'aup.accepted'])->group(function () {

    // Shared account settings (all roles)
    Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('/account', [AccountController::class, 'update'])->name('account.update');

    // Admin
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/analytics', [Admin\AnalyticsController::class, 'index'])->name('analytics');
        Route::resource('/users', Admin\UserController::class);
        Route::patch('/users/{user}/toggle-status', [Admin\UserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::delete('/users/{user}/sessions', [Admin\UserController::class, 'revokeSessions'])->name('users.revoke-sessions');
        Route::get('/audit-logs', [Admin\AuditLogController::class, 'index'])->name('audit-logs');
        Route::get('/settings', [Admin\SystemSettingController::class, 'index'])->name('settings');
        Route::put('/settings', [Admin\SystemSettingController::class, 'update'])->name('settings.update');
        Route::resource('/categories', Admin\AssistanceCategoryController::class);
        Route::resource('/categories.documents', Admin\RequiredDocumentController::class);
        Route::resource('/code-references', Admin\AssistanceCodeReferenceController::class);
    });

    // AICS Staff
    Route::middleware('role:aics_staff')->prefix('aics')->name('aics.')->group(function () {
        Route::get('/dashboard', [Aics\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/analytics', [Aics\AnalyticsController::class, 'index'])->name('analytics');
        Route::get('/applications', [Aics\ApplicationController::class, 'index'])->name('applications.index');
        Route::get('/applications/{application}', [Aics\ApplicationController::class, 'show'])->name('applications.show');
        Route::get('/applications/{application}/documents/{document}/url', [Aics\ApplicationController::class, 'documentUrl'])->name('applications.document-url');
        Route::post('/applications/{application}/approve', [Aics\ApplicationController::class, 'approve'])->name('applications.approve');
        Route::post('/applications/{application}/return', [Aics\ApplicationController::class, 'return'])->name('applications.return');
        Route::get('/assistance-codes', [Aics\AssistanceCodeController::class, 'index'])->name('assistance-codes.index');
        Route::get('/assistance-codes/{application}', [Aics\AssistanceCodeController::class, 'show'])->name('assistance-codes.show');
        Route::post('/assistance-codes/{application}/code', [Aics\AssistanceCodeController::class, 'store'])->name('assistance-codes.store');
    });

    // MSWDO
    Route::middleware('role:mswdo')->prefix('mswdo')->name('mswdo.')->group(function () {
        // ... same pattern
    });

    // Accountant
    Route::middleware('role:accountant')->prefix('accountant')->name('accountant.')->group(function () {
        // ... same pattern
    });

    // Treasurer
    Route::middleware('role:treasurer')->prefix('treasurer')->name('treasurer.')->group(function () {
        // ... same pattern
    });

    // Mayor's Office
    Route::middleware('role:mayors_office')->prefix('mayors-office')->name('mayors-office.')->group(function () {
        // ... same pattern
    });
});
```

---

## `database/` Structure

```
database/
├── migrations/                        # In dependency order
│   ├── 0001_01_01_000000_create_users_table.php
│   ├── 0001_01_01_000001_create_sessions_table.php
│   ├── 2024_01_01_000001_create_assistance_categories_table.php
│   ├── 2024_01_01_000002_create_required_documents_table.php
│   ├── 2024_01_01_000003_create_applications_table.php
│   ├── 2024_01_01_000004_create_application_documents_table.php
│   ├── 2024_01_01_000005_create_reviews_table.php
│   ├── 2024_01_01_000006_create_social_case_studies_table.php
│   ├── 2024_01_01_000007_create_assistance_code_references_table.php
│   ├── 2024_01_01_000008_create_assistance_codes_table.php
│   ├── 2024_01_01_000009_create_vouchers_table.php
│   ├── 2024_01_01_000010_create_audit_logs_table.php
│   ├── 2024_01_01_000011_create_sms_notifications_table.php
│   ├── 2024_01_01_000012_create_system_settings_table.php
│   ├── 2024_01_01_000013_create_jobs_table.php
│   └── 2024_01_01_000014_create_failed_jobs_table.php
│
└── seeders/
    ├── DatabaseSeeder.php
    ├── AdminSeeder.php
    ├── AssistanceCategorySeeder.php
    ├── RequiredDocumentSeeder.php
    ├── AssistanceCodeReferenceSeeder.php
    └── SystemSettingsSeeder.php
```

---

## `config/` — Files to Create or Modify

```
config/
├── filesystems.php    # Add 'supabase' S3 disk
├── fortify.php        # Features: twoFactorAuthentication, emailVerification
├── inertia.php        # Inertia config (testing, SSR if needed)
└── auth.php           # Standard Laravel auth — no changes needed
```

---

## `resources/css/` — Styles

```
resources/css/
├── app.css            # Tailwind base + PrimeVue Sakai theme imports
└── sakai/             # PrimeVue Sakai template CSS files
```

---

## `scripts/` — Shell Scripts

```
scripts/
├── backup.sh          # Daily encrypted mysqldump — called by Laravel Scheduler
└── deploy.sh          # git pull + composer + npm build + migrate + cache + restart worker
```

---

## `storage/` — Storage

```
storage/
├── app/public/        # Local dev file storage (php artisan storage:link)
├── framework/
│   ├── cache/
│   └── views/
└── logs/
    ├── laravel.log    # Application errors
    └── worker.log     # Queue worker output (Supervisor)
```

---

## Supabase Storage Structure (Remote)

```
Supabase Project: alalay
└── Bucket: alalay-docs (PRIVATE)
    ├── application_documents/
    │   └── {application_id}/
    │       └── {required_doc_id}_{timestamp}.{ext}
    ├── social_case_studies/
    │   └── {application_id}/
    │       └── scs_{timestamp}.{ext}
    ├── vouchers/
    │   └── {application_id}/
    │       └── voucher_v{version}_{timestamp}.{ext}
    └── profile_pictures/
        └── {user_id}/
            └── avatar_{timestamp}.{ext}
```

---

## `resources/js/app.js` — Inertia Bootstrap

```javascript
import { createApp, h } from 'vue'
import { createInertiaApp, Link, Head } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createPinia } from 'pinia'
import PrimeVue from 'primevue/config'
import Sakai from '@/presets/sakai'  // PrimeVue Sakai preset
import ToastService from 'primevue/toastservice'
import ConfirmationService from 'primevue/confirmationservice'
import 'primeicons/primeicons.css'
import '../css/app.css'

createInertiaApp({
  title: (title) => `${title} — ALALAY`,
  resolve: (name) =>
    resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
  setup({ el, App, props, plugin }) {
    const pinia = createPinia()

    createApp({ render: () => h(App, props) })
      .use(plugin)           // Inertia
      .use(pinia)            // Pinia
      .use(PrimeVue, { unstyled: true, pt: Sakai })
      .use(ToastService)
      .use(ConfirmationService)
      .component('Link', Link)
      .component('Head', Head)
      .mount(el)
  },
})
```

---

## `vite.config.js`

```javascript
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
  ],
  resolve: {
    alias: {
      '@': '/resources/js',
    },
  },
})
```

---

## `package.json` — Key Dependencies

```json
{
  "dependencies": {
    "@inertiajs/vue3": "^1.0",
    "vue": "^3.4",
    "primevue": "^4.0",
    "primeicons": "^7.0",
    "pinia": "^2.0",
    "@vueuse/core": "^10.0",
    "dayjs": "^1.11"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^5.0",
    "laravel-vite-plugin": "^1.0",
    "vite": "^5.0",
    "tailwindcss": "^3.4",
    "autoprefixer": "^10.0",
    "postcss": "^8.0"
  }
}
```

---

## `.gitignore`

```gitignore
# Environment
.env
.env.*
!.env.example

# Dependencies
/vendor
/node_modules

# Build output
/public/build
/public/hot
/public/storage

# Laravel
/storage/app/public
/storage/logs
/bootstrap/cache

# Backups
/backups
*.sql
*.sql.gz
*.sql.gz.enc

# OS
.DS_Store
Thumbs.db

# IDE
.idea/
.vscode/
*.swp
```

---

## Local Development Commands

```bash
# Start Laravel dev server
php artisan serve

# Start Vite HMR (separate terminal)
npm run dev

# Run queue worker (separate terminal — needed for SMS)
php artisan queue:work

# Run migrations fresh with seeds
php artisan migrate:fresh --seed
```

---

## Production Deployment Commands

```bash
# Clone and setup (first time)
git clone <repo> /var/www/alalay
cd /var/www/alalay
composer install --optimize-autoloader --no-dev
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# On every subsequent deploy
git pull
composer install --optimize-autoloader --no-dev
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

---

## Nginx Production Config (Single Block)

```nginx
server {
    listen 443 ssl http2;
    server_name alalay.gmn.gov.ph;

    root /var/www/alalay/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/alalay.gmn.gov.ph/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/alalay.gmn.gov.ph/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

server {
    listen 80;
    server_name alalay.gmn.gov.ph;
    return 301 https://$host$request_uri;
}
```

One domain. One SSL certificate. One Nginx block. Done.

---

*Document prepared for AI consumption and development reference — ALALAY System, Municipality of General Mamerto Natividad, Nueva Ecija.*

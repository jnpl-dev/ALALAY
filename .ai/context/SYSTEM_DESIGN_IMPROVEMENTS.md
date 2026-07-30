# ALALAY: System Design Improvements Specification
**Performance, Security Hardening, and Zero-Day Preparation**
**Municipality of General Mamerto Natividad, Nueva Ecija**

---

## Current Setup (Baseline)

```
PHP:            8.2.12 (XAMPP)
Composer:       2.9.5
Laravel:        12 (bootstrap/app.php — not Kernel.php)
Session driver: database
Queue driver:   database
Cache driver:   file
Mail:           Gmail SMTP
File storage:   Supabase Storage
Auth:           Laravel Fortify + OTP via email
Redis:          NOT USED (decision: file cache sufficient for single-municipality scale)
```

---

## IMPROVEMENT 1 — Redis (Skipped by Decision)

Redis was evaluated but skipped after analysis. At single-municipality
scale (6–7 staff, ~hundreds of applications/month), the `file` cache
driver is adequate. Same `Cache::remember()` API, zero ops cost.

The `QUEUE_CONNECTION=database` and `SESSION_DRIVER=database` remain
as-is — database-backed queues and sessions handle the current volume
without contention.

If scale grows significantly in the future, Redis can be added without
changing any application code — only `.env` driver values need to
change since all caching uses the standard `Cache::remember()` facade.

---

## IMPROVEMENT 2 — Caching Strategy (Completed)

### What is Cached

All caching uses Laravel's `Cache::remember()` facade with the `file`
driver. No Redis required. Same API, zero ops overhead.

#### 2.1 Assistance Categories (1 hour TTL)

`Public/CategoryController@index` — categories eager-loaded with
active required documents. Key: `categories.with_docs`.

Busted by: `Admin/AssistanceCategoryController`,
`Admin/RequiredDocumentController` on store/update/destroy.

#### 2.2 System Settings (30 min TTL)

`FileUploadService` — caches `max_file_size_kb` and
`allowed_mime_types`. Key: `settings.max_file_size_kb`,
`settings.allowed_mime_types`.

`SendSmsJob` — caches SMS notification templates.
Key: `settings.sms.{key}`.

Busted by: `Admin/SystemSettingController` on update.

#### 2.3 Assistance Code References (1 hour TTL)

`Aics/AssistanceCodeController` — dropdown data for coding page.
Keys: `categories.active_names`, `code_references.active`.

Busted by: `Admin/AssistanceCodeReferenceController`,
`Aics/AssistanceCodeController` on mutation.

#### 2.4 Dashboard KPI Counts (5 min TTL)

All 6 role dashboard controllers. Key pattern:
`dashboard.{role}.{YmdHi}` (time-windowed, no user-specific key).

Busted automatically by `bustPollCache()` in `HasPollCache` trait
(forget current and previous minute for all 6 roles). Also busted
by `Public/ApplicationController@store` and `@resubmit`.

#### 2.5 Analytics Chart Data (15 min TTL)

All 6 role analytics controllers. Key pattern:
`analytics.{role}.{md5(from-to)}` (date-range-aware).

Busted by same `bustPollCache()` mechanism above.

### Cache Key Naming Convention

```
{resource}.{variant}
dashboard.{role}.{YmdHi}
analytics.{role}.{md5(from-to)}

Examples:
  categories.with_docs           — categories + required docs
  categories.active_names         — category ID/name pairs
  code_references.active          — code reference dropdown
  settings.max_file_size_kb       — file upload limit
  settings.allowed_mime_types     — allowed upload types
  settings.sms.submission_complete — SMS template
  dashboard.aics.202607190510    — AICS dashboard KPIs
  analytics.admin.a1b2c3d4       — Admin analytics (date-range keyed)
```

---

## IMPROVEMENT 3 — Database Indexes (Completed)

### Composite Indexes Added

Two migrations were created and applied. Existing single-column indexes
on FK columns were kept (MySQL requires them for foreign key constraints).

#### Migration 1: `add_performance_indexes_to_alalay_tables`

| Table | Composite Index | Purpose |
|---|---|---|
| **applications** | `(status, created_at)` | Dashboard KPI counts + poll queries sorted by date |
| **applications** | `(category_id, status)` | Analytics breakdown by category |
| **reviews** | `(application_id, stage)` | Review trail filtered by stage |
| **audit_logs** | `(user_id, created_at)` | Per-user activity timeline |
| **audit_logs** | `(entity_type, entity_id)` | Entity-specific audit trail |
| **audit_logs** | `(module, action)` | Admin audit log filtering |

#### Migration 2: `add_additional_composite_indexes_to_alalay_tables`

| Table | Composite Index | Purpose |
|---|---|---|
| **users** | `(role, status)` | User listing filters (Admin panel) |
| **required_documents** | `(category_id, is_active)` | Document listing per category |
| **application_documents** | `(application_id, required_doc_id)` | De-facto unique lookup per app/doc |
| **application_documents** | `(application_id, is_resubmission)` | Resubmission filtering per app |
| **vouchers** | `(application_id, version)` | Latest voucher version lookup |
| **vouchers** | `(prepared_by, created_at)` | Staff workload reports |
| **sms_notifications** | `(status, created_at)` | Failed/pending SMS dashboard |
| **applications** | `(claimant_last_name, claimant_first_name)` | Name search (no name index existed) |
| **reviews** | `(reviewed_by, created_at)` | Staff review activity timeline |
| **social_case_studies** | `(conducted_by, created_at)` | Staff workload reports |

---

## IMPROVEMENT 4 — Security Headers

### Laravel 12 Middleware Registration

In Laravel 12, middleware is registered in `bootstrap/app.php` instead
of `Kernel.php`. Add a security headers middleware:

```php
// app/Http/Middleware/SecurityHeaders.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy — allow camera for DocumentScanner,
        // deny microphone and geolocation
        $response->headers->set(
            'Permissions-Policy',
            'camera=self, microphone=(), geolocation=()'
        );

        // Content Security Policy
        // connect-src includes Supabase Storage and PSGC API
        // img-src and media-src include blob: for scanner preview/camera feed
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline'",
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob:",
            "media-src 'self' blob:",
            "connect-src 'self' https://*.supabase.co https://psgc.gitlab.io",
            "object-src 'none'",
            "frame-ancestors 'none'",
        ]));

        return $response;
    }
}
```

**Register in `bootstrap/app.php` (Laravel 12 style):**

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    // Existing Inertia middleware registration...
    $middleware->web(append: [
        \App\Http\Middleware\HandleInertiaRequests::class,
        \App\Http\Middleware\SecurityHeaders::class,  // ADD THIS
    ]);
})
```

---

## IMPROVEMENT 5 — Login Security Enhancements

### 5.1 Login Lockout Logging

When a user is rate-limited on login, write to `audit_logs`:

```php
// In App/Providers/FortifyServiceProvider.php or bootstrap/app.php
// Add after Fortify::authenticateUsing()

RateLimiter::for('login', function (Request $request) {
    return Limit::perMinute(5)
        ->by($request->input('email') . '|' . $request->ip())
        ->response(function (Request $request) {
            // Log lockout to audit_logs
            \App\Models\AuditLog::create([
                'user_id'     => null,
                'role'        => null,
                'module'      => 'auth',
                'action'      => 'login_lockout',
                'description' => 'Login locked after 5 failed attempts for: '
                                 . $request->input('email'),
                'ip_address'  => $request->ip(),
                'user_agent'  => $request->userAgent(),
                'created_at'  => now(),
            ]);

            return response()->json([
                'message' => 'Too many login attempts. Please try again in 60 seconds.'
            ], 429);
        });
});
```

### 5.2 New IP Login Notification

When a staff member logs in from an IP address not seen before,
send them an email notification:

```php
// In the OTP verification controller — after successful OTP verify
// (since your auth uses OTP, this fires after full login is confirmed)

$knownIps = \DB::table('sessions')
    ->where('user_id', auth()->id())
    ->pluck('ip_address')
    ->toArray();

if (!in_array($request->ip(), $knownIps)) {
    auth()->user()->notify(new \App\Notifications\NewLoginDetected(
        ip: $request->ip(),
        userAgent: $request->userAgent(),
        loginAt: now(),
    ));
}
```

```php
// app/Notifications/NewLoginDetected.php
<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class NewLoginDetected extends Notification
{
    public function __construct(
        public string $ip,
        public string $userAgent,
        public \Carbon\Carbon $loginAt,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Login Detected — ALALAY')
            ->line('A login to your ALALAY account was detected from a new device or location.')
            ->line('IP Address: ' . $this->ip)
            ->line('Time: ' . $this->loginAt->setTimezone('Asia/Manila')->format('F j, Y g:i A'))
            ->line('If this was not you, contact your system administrator immediately.');
    }
}
```

---

## IMPROVEMENT 6 — Query Optimization

### 6.1 Slow Query Logger (Local Development Only)

Add this to `AppServiceProvider@boot` to catch slow queries during
development before they reach production:

```php
// app/Providers/AppServiceProvider.php
public function boot(): void
{
    if (app()->environment('local')) {
        \DB::listen(function ($query) {
            if ($query->time > 200) {  // queries slower than 200ms
                \Log::warning('Slow query detected', [
                    'sql'      => $query->sql,
                    'bindings' => $query->bindings,
                    'time'     => $query->time . 'ms',
                ]);
            }
        });
    }
}
```

### 6.2 Inertia Lazy Loading for Analytics

Analytics charts load heavy GROUP BY queries. Use `Inertia::lazy()`
so the page renders immediately and chart data loads after:

```php
// In any analytics controller
return Inertia::render('Aics/Analytics', [
    'filters' => $request->only(['period', 'date_from', 'date_to']),

    // These only execute when the Vue page explicitly requests them
    'applicationsOverTime' => Inertia::lazy(
        fn() => $this->getApplicationsOverTime($request)
    ),
    'byCategory' => Inertia::lazy(
        fn() => $this->getByCategory($request)
    ),
    'approvalVsReturn' => Inertia::lazy(
        fn() => $this->getApprovalVsReturn($request)
    ),
]);
```

In the Vue page, trigger lazy prop loading on mount:

```javascript
// In Analytics.vue
import { router, usePage } from '@inertiajs/vue3'
import { onMounted } from 'vue'

onMounted(() => {
  router.reload({
    only: ['applicationsOverTime', 'byCategory', 'approvalVsReturn']
  })
})
```

### 6.3 Eager Loading Audit (Prevent N+1)

Every controller that returns a list must use `with()`. Common
patterns in ALALAY:

```php
// Applications list — always eager load these
Application::with(['category', 'reviews' => fn($q) => $q->latest()])
    ->paginate(15);

// Reviews list — always eager load reviewer
Review::with(['reviewedBy:id,first_name,last_name'])
    ->where('application_id', $id)
    ->orderBy('created_at', 'asc')
    ->get();

// Audit logs — always eager load user
AuditLog::with(['user:id,first_name,last_name,role'])
    ->orderByDesc('created_at')
    ->paginate(50);
```

---

## IMPROVEMENT 7 — Backup Strategy

### 7.1 Current Plan (Already in Deployment Guide)

Daily `mysqldump` encrypted with AES-256, retained for 30 days.

### 7.2 Add Offsite Backup to Supabase Storage

After the local encrypted backup is created, upload it to a dedicated
Supabase Storage bucket as offsite copy:

```bash
# In scripts/backup.sh — add after local backup is created

SUPABASE_BACKUP_BUCKET="alalay-backups"
SUPABASE_URL="https://<project-ref>.supabase.co"
SUPABASE_KEY="your_supabase_service_role_key"

# Upload to Supabase Storage
curl -X POST \
  "${SUPABASE_URL}/storage/v1/object/${SUPABASE_BACKUP_BUCKET}/db/${FILENAME}" \
  -H "Authorization: Bearer ${SUPABASE_KEY}" \
  -H "Content-Type: application/octet-stream" \
  --data-binary @"${BACKUP_DIR}/${FILENAME}"

echo "Offsite backup uploaded: ${FILENAME}"
```

Create a dedicated private Supabase Storage bucket named `alalay-backups`
separate from `alalay-docs` (application files). Service role key is
used for backup uploads — never expose it in the frontend.

### 7.3 Weekly Restore Test

Add a weekly scheduled command to verify the latest backup is restorable:

```php
// app/Console/Commands/VerifyBackup.php
// This runs on a TEST database, never on the live database

protected $signature = 'backup:verify';

public function handle(): void
{
    // Get latest backup file
    $latest = collect(glob(config('backup.path') . '/*.sql.gz.enc'))
        ->sortByDesc(fn($f) => filemtime($f))
        ->first();

    if (!$latest) {
        Log::error('Backup verification failed: no backup file found.');
        return;
    }

    // Decrypt and restore to test database
    $result = shell_exec(
        "openssl enc -d -aes-256-cbc -pbkdf2 -pass pass:" .
        config('backup.encrypt_pass') .
        " -in {$latest} | gunzip | mysql -u root alalay_backup_test 2>&1"
    );

    Log::info('Backup verification completed.', ['result' => $result]);
}
```

```php
// In routes/console.php or Console/Kernel.php equivalent for Laravel 12
Schedule::command('backup:verify')->weekly()->sundays()->at('03:00');
```

---

## IMPROVEMENT 8 — Emergency Maintenance Toggle

Add a maintenance mode toggle to the Admin panel so the Admin can
immediately put the system into maintenance mode without needing
server access:

```php
// In Admin/SystemSettingController — add new method
public function toggleMaintenance(): RedirectResponse
{
    if (app()->isDownForMaintenance()) {
        Artisan::call('up');
        $message = 'System is now online.';
        $action  = 'maintenance_off';
    } else {
        Artisan::call('down', [
            '--secret' => config('app.maintenance_secret'),
            '--render' => 'errors.503',
        ]);
        $message = 'System is now in maintenance mode.';
        $action  = 'maintenance_on';
    }

    AuditLog::create([
        'user_id'     => auth()->id(),
        'role'        => auth()->user()->role,
        'module'      => 'system',
        'action'      => $action,
        'description' => $message,
        'ip_address'  => request()->ip(),
        'created_at'  => now(),
    ]);

    return redirect()->back()->with('success', $message);
}
```

Add to `.env`:
```env
APP_MAINTENANCE_SECRET=random_secret_bypass_token
```

Add to web.php (Admin route group):
```php
Route::post('/admin/maintenance/toggle',
    [Admin\SystemSettingController::class, 'toggleMaintenance'])
    ->name('admin.maintenance.toggle');
```

Add a toggle button in `Admin/SystemSettings.vue` — visible only to
Admin. Red button when system is online ("Enable Maintenance Mode"),
green button when in maintenance mode ("Bring System Online").

---

## IMPROVEMENT 9 — Zero-Day Preparation

### 9.1 Dependency Scanning in Deployment

Add to `scripts/deploy.sh`:

```bash
# Fail deployment if known PHP vulnerabilities found
composer audit --no-dev
if [ $? -ne 0 ]; then
    echo "Composer audit failed — deployment aborted."
    exit 1
fi

# Warn on npm vulnerabilities (don't fail — frontend vulns are lower risk)
npm audit --audit-level=high || echo "npm audit warnings found — review manually"
```

### 9.2 Incident Response — Emergency Commands

Document these in `.ai/context/` AND print and physically store in
the MSWDO/IT office. These are the commands needed during an incident:

```bash
# Enable maintenance mode immediately
php artisan down --secret="your_maintenance_secret"

# Force logout ALL active users (session breach)
# Run via: php artisan tinker
DB::table('sessions')->truncate();

# Check who is currently logged in
SELECT u.first_name, u.last_name, u.role, s.ip_address, s.last_activity
FROM sessions s
JOIN users u ON u.id = s.user_id
ORDER BY s.last_activity DESC;

# Check recent audit log for suspicious activity
SELECT * FROM audit_logs
ORDER BY created_at DESC
LIMIT 50;

# Restart web server
sudo systemctl restart nginx

# Restart PHP
sudo systemctl restart php8.2-fpm

# Restart queue worker
sudo supervisorctl restart alalay-worker:*

# Check application error log
tail -f /var/www/alalay/storage/logs/laravel.log

# Bring system back online
php artisan up
```

### 9.3 PII Protection in Logs

Ensure audit log `description` field never contains raw personal data.
Add this rule to your `AuditLogger` service:

```php
// app/Services/AuditLogger.php
public static function log(
    string $action,
    string $module,
    ?string $entityType = null,
    ?string $entityId   = null,
    ?string $description = null
): void {
    // Strip any potential PII patterns from description before saving
    // This is a safety net — description should never contain PII anyway
    $safeDescription = $description
        ? preg_replace('/\b(09\d{9}|\+639\d{9})\b/', '[PHONE REDACTED]', $description)
        : null;

    AuditLog::create([
        'user_id'     => auth()->id(),
        'role'        => auth()->user()?->role,
        'module'      => $module,
        'action'      => $action,
        'description' => $safeDescription,
        'entity_type' => $entityType,
        'entity_id'   => $entityId,
        'ip_address'  => request()->ip(),
        'user_agent'  => request()->userAgent(),
        'created_at'  => now(),
    ]);
}
```

---

## Implementation Checklist

Add these items to `PROCESS.md` under a new **Phase 2b — System Design
Improvements** section, between Phase 2 and Phase 3:

```
## Phase 2b — System Design Improvements

### Redis
- [x] Skipped by decision — file cache sufficient for single-municipality scale

### Caching
- [x] Add Cache::remember() to Public/CategoryController@index (1 hour)
- [x] Add Cache::remember() to system settings in FileUploadService + SendSmsJob (30 min)
- [x] Add Cache::remember() to AssistanceCodeReference dropdown queries (1 hour)
- [x] Add Cache::remember() to all dashboard KPI queries (5 min)
- [x] Add Cache::remember() to all analytics chart queries (15 min)
- [x] Add Cache::forget() in all controllers that update cached data
- [x] Add Cache::forget() to Public/ApplicationController@store + @resubmit
- [x] bustPollCache() also busts dashboard + analytics caches

### Database Indexes
- [x] Migration 1: (status, created_at), (category_id, status) on applications
      + reviews(application_id, stage) + audit_logs composite indexes
- [x] Migration 2: Additional composites on users, required_documents,
      application_documents, vouchers, sms_notifications, social_case_studies
- [x] php artisan migrate (both batches)

### Security Headers
- [x] Create app/Http/Middleware/SecurityHeaders.php
- [x] Register in bootstrap/app.php web middleware group
- [x] Verify headers in browser DevTools (curl confirmed all 5 headers)
- [x] Confirm Permissions-Policy allows camera (`camera=self`)
- [x] Confirm CSP connect-src includes Supabase and PSGC API URLs

### Login Security
- [x] Add login lockout logging to audit_logs in FortifyServiceProvider (rate limiter response callback)
- [x] Create app/Notifications/NewLoginDetected.php
- [x] Add new-IP login notification in OTP verification controller (after successful OTP verify)

### Query Optimization
- [x] Add slow query logger to AppServiceProvider@boot (local env only)
- [x] Eager loading already implemented in all index/show methods
- [x] Add Inertia::lazy() to all analytics controllers

### Backup
- [x] Create config/backup.php — centralized config for path, encryption, retention, Supabase bucket
- [x] Create scripts/backup.sh — full backup script (mysqldump → gzip → AES-256-CBC encrypt → local save → S3-compatible upload to Supabase → prune old)
- [x] Create app/Console/Commands/VerifyBackup.php — decrypts + restores latest backup to test database
- [x] Create app/Console/Commands/BackupDatabase.php — Laravel command for daily backup (mysqldump → encrypt → upload → prune)
- [x] Register daily backup:run schedule in routes/console.php (daily 02:00)
- [x] Register weekly backup:verify schedule in routes/console.php (Sundays 03:00)
- [x] Manual: Create alalay-backups private bucket in Supabase Storage

### Emergency Maintenance
- [x] Add APP_MAINTENANCE_SECRET to .env + maintenance_secret to config/app.php
- [x] Add toggleMaintenance() to Admin/SystemSettingController
- [x] Add maintenance toggle route to web.php
- [x] Add maintenance toggle button to Admin/SystemSettings.vue
- [x] Create resources/views/errors/503.blade.php
- [ ] Print emergency command reference — store physically in IT office

### Zero-Day
- [x] Add composer audit check to scripts/deploy.sh
- [x] Add PII redaction to AuditLogger::log() description field
- [x] Create .ai/context/INCIDENT_RESPONSE.md with emergency commands and incident procedures
```

---

## Priority Order

Items marked **[DONE]** are already implemented.

| Priority | Improvement | Status |
|---|---|---|---|
| 1 | Redis installation + driver switch | **SKIPPED** — file cache sufficient |
| 2 | Caching (categories, settings, KPIs, dashboard, analytics) | **[DONE]** |
| 3 | Composite database indexes | **[DONE]** |
| 4 | Security headers middleware | **[DONE]** |
| 5 | Login lockout logging / New IP notification | **[DONE]** |
| 6 | Inertia lazy loading for analytics | **[DONE]** |
| 7 | Slow query logger (local dev) | **[DONE]** |
| 8 | Offsite backup to Supabase | **[DONE]** |
| 9 | Emergency maintenance toggle | **[DONE]** |
| 10 | Backup verify command | **[DONE]** |
| 11 | Deploy script with security audit | **[DONE]** |
| 12 | PII redaction in audit logs | **[DONE]** |
| 13 | Incident response documentation | **[DONE]** |

---

*Document prepared for AI consumption and development reference — ALALAY System,
Municipality of General Mamerto Natividad, Nueva Ecija.*

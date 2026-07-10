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
Cache driver:   file (default — not yet optimized)
Mail:           Gmail SMTP
File storage:   Supabase Storage
Auth:           Laravel Fortify + OTP via email
Redis:          NOT YET INSTALLED
```

---

## IMPROVEMENT 1 — Redis (Highest Impact)

### What Changes

Replace three file/database-based drivers with Redis:

| Driver | Current | After Redis |
|---|---|---|
| Cache | `file` | `redis` |
| Session | `database` | `redis` |
| Queue | `database` | `redis` |

### Why This Matters for ALALAY

- **Cache (file → redis):** File cache reads/writes to disk on every
  request. Redis stores everything in memory — orders of magnitude faster.
  Every cached category list, system setting, and dashboard KPI reads
  from RAM instead of disk.

- **Session (database → redis):** Every page load currently runs a MySQL
  query to read the session. With Redis, session reads are memory lookups.
  On a busy office day with 6–7 staff members active simultaneously,
  this removes significant MySQL pressure.

- **Queue (database → redis):** The database queue driver polls MySQL
  every few seconds with `SELECT ... FOR UPDATE` queries. Redis uses a
  push model — jobs are processed the moment they arrive. SMS
  notifications dispatch faster. No constant MySQL polling overhead.

### Local Installation (XAMPP Windows)

Redis does not have an official Windows build but works via WSL2 or
a Windows port:

**Option A — WSL2 (Recommended for Windows dev):**
```bash
# In WSL2 terminal
sudo apt update
sudo apt install redis-server -y
sudo service redis-server start

# Verify
redis-cli ping  # should return PONG
```

**Option B — Memurai (Windows Redis-compatible server):**
Download from https://www.memurai.com — free developer edition.
Runs as a Windows service, no WSL needed.

**Option C — Use file cache locally, Redis only in production:**
Simplest approach for local dev. Keep `CACHE_DRIVER=file` in your
local `.env` and only switch to Redis in the production `.env`.
This is acceptable since local dev performance is not critical.

### Production Installation (Ubuntu — VPS or office desktop)

```bash
sudo apt update
sudo apt install redis-server -y
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Verify
redis-cli ping  # PONG

# Secure Redis — bind to localhost only (no external access)
sudo nano /etc/redis/redis.conf
# Set: bind 127.0.0.1
# Set: requirepass your_strong_redis_password

sudo systemctl restart redis-server
```

### Laravel Configuration

**Install PHP Redis client:**
```bash
composer require predis/predis
```

**Update `.env` (production):**
```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your_strong_redis_password
REDIS_PORT=6379

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

**Update `.env` (local — optional, keep file/database if no Redis locally):**
```env
CACHE_DRIVER=file
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

**Update `config/database.php` Redis connection:**
```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'predis'),
    'default' => [
        'host'     => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port'     => env('REDIS_PORT', 6379),
        'database' => 0,
    ],
    'cache' => [
        'host'     => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD', null),
        'port'     => env('REDIS_PORT', 6379),
        'database' => 1,  // separate DB from session
    ],
],
```

### Laravel 12 bootstrap/app.php — Queue Driver Note

In Laravel 12, queue configuration is in `config/queue.php`. No
bootstrap/app.php change needed for Redis queues — only `.env` change:

```env
QUEUE_CONNECTION=redis
```

### Supervisor Update for Redis Queue (Production)

Update Supervisor config to use Redis instead of database:

```ini
[program:alalay-worker]
command=php /var/www/alalay/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
```

---

## IMPROVEMENT 2 — Caching Strategy

### What to Cache in ALALAY

With Redis in place, add explicit caching to these areas:

#### 2.1 Assistance Categories + Required Documents

Fetched on every Apply page load. Changes only when Admin edits them.

```php
// In Public/CategoryController@index
$categories = Cache::remember('categories.active', 3600, function () {
    return AssistanceCategory::where('is_active', true)
        ->with(['requiredDocuments' => fn($q) => $q->where('is_active', true)
            ->orderBy('is_mandatory', 'desc')])
        ->get();
});
```

Bust when Admin updates a category or required document:
```php
// In Admin/AssistanceCategoryController — after any create/update/toggle
Cache::forget('categories.active');
```

#### 2.2 System Settings

Fetched on every page load via `HandleInertiaRequests` shared props.

```php
// In App/Services/SystemSettingService.php
public function getAll(): array
{
    return Cache::remember('system_settings.all', 1800, function () {
        return SystemSetting::pluck('setting_value', 'setting_key')->toArray();
    });
}
```

Bust when Admin saves settings:
```php
Cache::forget('system_settings.all');
```

#### 2.3 Assistance Code References

Fetched for the AICS Staff coding page dropdown. Rarely changes.

```php
$codeReferences = Cache::remember('code_references.active', 3600, function () {
    return AssistanceCodeReference::where('is_active', true)->get();
});
```

#### 2.4 Dashboard KPI Counts

Expensive COUNT queries. Acceptable to be 5 minutes behind.

```php
// In Aics/DashboardController@index
$kpis = Cache::remember("dashboard.aics.kpis." . auth()->id(), 300, function () {
    return [
        'new_today'      => Application::where('status', 'submitted')
                              ->whereDate('created_at', today())->count(),
        'total_pending'  => Application::where('status', 'submitted')->count(),
        'total_screened' => Application::where('status', 'mswdo_review')->count(),
        'total_returned' => Application::whereIn('status', ['returned_to_applicant'])->count(),
        'pending_coding' => Application::where('status', 'assistance_coding')->count(),
    ];
});
```

Bust when any application status changes:
```php
// In bustPollCache() — already defined in polling spec
Cache::forget("dashboard.aics.kpis.*");  // wildcard bust via Redis tags
```

#### 2.5 Analytics Chart Data

Heavy GROUP BY queries. 15 minutes is acceptable.

```php
$chartData = Cache::remember(
    "analytics.aics.applications_over_time.{$period}",
    900,
    fn() => $this->getApplicationsOverTime($period)
);
```

### Cache Key Naming Convention

```
{panel}.{resource}.{type}.{variant}

Examples:
  categories.active
  system_settings.all
  code_references.active
  dashboard.aics.kpis.{user_id}
  dashboard.mswdo.kpis.{user_id}
  analytics.aics.applications_over_time.{period}
  poll.aics.applications.submitted.latest_update
```

---

## IMPROVEMENT 3 — Database Indexes

### Additional Composite Indexes

Add these to existing migration files or as new standalone migrations.
These cover the most common query patterns in ALALAY:

```php
// applications table
$table->index(['status', 'created_at']);           // dashboard + poll queries
$table->index(['category_id', 'status']);          // analytics by category
$table->index(['submission_type', 'created_at']); // online vs walk-in reports
$table->index(['reviewed_by', 'status']);          // per-staff analytics

// reviews table
$table->index(['application_id', 'created_at']);  // review trail ordered by date
$table->index(['stage', 'decision']);             // analytics by stage/decision
$table->index(['reviewed_by', 'created_at']);     // per-staff review history

// audit_logs table
$table->index(['user_id', 'created_at']);         // per-user audit filter
$table->index(['module', 'action', 'created_at']); // admin audit filter
$table->index(['entity_type', 'entity_id']);      // filter by specific record

// sms_notifications table
$table->index(['status', 'created_at']);          // failed SMS retry queries
$table->index(['application_id', 'status']);      // per-application SMS history
```

### Create as a Standalone Migration

```php
// php artisan make:migration add_performance_indexes_to_alalay_tables

public function up(): void
{
    Schema::table('applications', function (Blueprint $table) {
        $table->index(['status', 'created_at'], 'idx_applications_status_created');
        $table->index(['category_id', 'status'], 'idx_applications_category_status');
        $table->index(['submission_type', 'created_at'], 'idx_applications_type_created');
        $table->index(['reviewed_by', 'status'], 'idx_applications_reviewer_status');
    });

    Schema::table('reviews', function (Blueprint $table) {
        $table->index(['application_id', 'created_at'], 'idx_reviews_app_created');
        $table->index(['stage', 'decision'], 'idx_reviews_stage_decision');
        $table->index(['reviewed_by', 'created_at'], 'idx_reviews_reviewer_created');
    });

    Schema::table('audit_logs', function (Blueprint $table) {
        $table->index(['user_id', 'created_at'], 'idx_audit_user_created');
        $table->index(['module', 'action', 'created_at'], 'idx_audit_module_action');
        $table->index(['entity_type', 'entity_id'], 'idx_audit_entity');
    });

    Schema::table('sms_notifications', function (Blueprint $table) {
        $table->index(['status', 'created_at'], 'idx_sms_status_created');
        $table->index(['application_id', 'status'], 'idx_sms_app_status');
    });
}

public function down(): void
{
    Schema::table('applications', function (Blueprint $table) {
        $table->dropIndex('idx_applications_status_created');
        $table->dropIndex('idx_applications_category_status');
        $table->dropIndex('idx_applications_type_created');
        $table->dropIndex('idx_applications_reviewer_status');
    });

    Schema::table('reviews', function (Blueprint $table) {
        $table->dropIndex('idx_reviews_app_created');
        $table->dropIndex('idx_reviews_stage_decision');
        $table->dropIndex('idx_reviews_reviewer_created');
    });

    Schema::table('audit_logs', function (Blueprint $table) {
        $table->dropIndex('idx_audit_user_created');
        $table->dropIndex('idx_audit_module_action');
        $table->dropIndex('idx_audit_entity');
    });

    Schema::table('sms_notifications', function (Blueprint $table) {
        $table->dropIndex('idx_sms_status_created');
        $table->dropIndex('idx_sms_app_status');
    });
}
```

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
- [ ] Install Redis (local: WSL2/Memurai or skip; production: Ubuntu apt)
- [ ] composer require predis/predis
- [ ] Update production .env: CACHE_DRIVER=redis, SESSION_DRIVER=redis,
      QUEUE_CONNECTION=redis, REDIS_HOST, REDIS_PASSWORD, REDIS_PORT
- [ ] Update config/database.php Redis connection with separate DB for cache
- [ ] Update Supervisor config to use redis queue driver in production
- [ ] Verify: php artisan cache:clear && php artisan config:cache

### Caching
- [ ] Add Cache::remember() to Public/CategoryController@index (1 hour)
- [ ] Add Cache::remember() to SystemSettingService (30 min)
- [ ] Add Cache::remember() to AssistanceCodeReference dropdown queries (1 hour)
- [ ] Add Cache::remember() to all dashboard KPI queries (5 min)
- [ ] Add Cache::remember() to all analytics chart queries (15 min)
- [ ] Add Cache::forget() in all Admin controllers that update cached data
- [ ] Add bustPollCache() calls to all status-changing controller actions
      (already in polling spec — combine with cache bust here)

### Database Indexes
- [ ] php artisan make:migration add_performance_indexes_to_alalay_tables
- [ ] Add composite indexes per spec above
- [ ] php artisan migrate
- [ ] Verify indexes in phpMyAdmin (Structure tab per table)

### Security Headers
- [ ] Create app/Http/Middleware/SecurityHeaders.php
- [ ] Register in bootstrap/app.php web middleware group
- [ ] Verify headers in browser DevTools (Network tab → response headers)
- [ ] Confirm Permissions-Policy allows camera (needed for DocumentScanner)
- [ ] Confirm CSP connect-src includes Supabase and PSGC API URLs

### Login Security
- [ ] Add login lockout logging to audit_logs in FortifyServiceProvider
- [ ] Create app/Notifications/NewLoginDetected.php
- [ ] Add new-IP login notification in OTP verification controller

### Query Optimization
- [ ] Add slow query logger to AppServiceProvider@boot (local env only)
- [ ] Audit all controller list methods — confirm with() on all relationships
- [ ] Add Inertia::lazy() to all analytics controllers

### Backup
- [ ] Create alalay-backups private bucket in Supabase Storage
- [ ] Add offsite backup upload to scripts/backup.sh
- [ ] Create app/Console/Commands/VerifyBackup.php
- [ ] Register weekly backup:verify schedule

### Emergency Maintenance
- [ ] Add APP_MAINTENANCE_SECRET to .env
- [ ] Add toggleMaintenance() to Admin/SystemSettingController
- [ ] Add maintenance toggle route to web.php
- [ ] Add maintenance toggle button to Admin/SystemSettings.vue
- [ ] Print emergency command reference — store physically in IT office

### Zero-Day
- [ ] Add composer audit check to deploy.sh
- [ ] Add PII redaction to AuditLogger::log() description field
- [ ] Document incident response procedure in .ai/context/
```

---

## Priority Order

If you cannot do everything at once, implement in this order:

| Priority | Improvement | Why First |
|---|---|---|
| 1 | Redis installation + driver switch | Highest impact, enables everything else |
| 2 | Caching (categories, settings, KPIs) | Immediate query reduction |
| 3 | Composite database indexes | Fixes slow queries before more data accumulates |
| 4 | Security headers middleware | One file, immediate security improvement |
| 5 | Login lockout logging | NPC compliance + audit trail completeness |
| 6 | Inertia lazy loading for analytics | Faster dashboard experience |
| 7 | Offsite backup to Supabase | Disaster recovery |
| 8 | Emergency maintenance toggle | Incident response speed |
| 9 | Query optimizer / slow query logger | Dev-time quality assurance |
| 10 | Backup verify command | Long-term reliability assurance |

---

*Document prepared for AI consumption and development reference — ALALAY System,
Municipality of General Mamerto Natividad, Nueva Ecija.*

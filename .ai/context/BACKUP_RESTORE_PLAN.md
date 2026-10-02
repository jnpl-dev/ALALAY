# Backup & Restore — Build Plan

> Standalone admin page ("Backup & Restore") + audit logging + local backup reliability.
> Tackle items **one checkbox at a time, in order**. Each item ends with its own verification before ticking.
> Do NOT commit or deploy without explicit user consent.

---

## Current state (before this plan starts)

Uncommitted changes already in the working tree from prior sessions:
- **Backup (this plan builds on these)**: Supabase upload rewritten to use `Storage::disk('supabase-backups')` facade (hand-rolled SigV4 removed), `withoutOverlapping()` added in `routes/console.php`, `supabase-backups` disk in `config/filesystems.php`, `scripts/backup.sh` deleted, `config/backup.php` supabase keys added.
- **Unrelated (privacy work)**: `PrivacyNoticeModal.vue`, `PrivacyPolicyModal.vue`, `Apply.vue`, `PublicLayout.vue`, locale files, `NPC_COMPLIANCE_CHECKLIST.md`.
- **Commit hygiene**: when consent is given, split into **separate commits** (privacy vs backup/restore) — nothing unrelated rides along. Check `git status` before staging.

---

## Part A — Backup reliability on local dev (prerequisite)

> Why: `mysqldump`/`mysql` are NOT on PATH locally (`C:/xampp1/mysql/bin`), so plain `php artisan backup:run` fails — every UI button would break too.

- [x] **A1. Binary resolution helper** — shared `resolveBinary(string $name): string` in both `BackupDatabase.php` and `VerifyBackup.php`: scan `PATH` entries for `$name`/`$name.exe`, then probe `C:/xampp1/mysql/bin`, `C:/xampp/mysql/bin`, `/usr/bin`, `/usr/local/bin`; fall back to bare name (production apt installs to `/usr/bin`).
  - [x] Use resolved paths for `mysqldump` in `app/Console/Commands/BackupDatabase.php`
  - [x] Use resolved paths for `mysql` in `app/Console/Commands/VerifyBackup.php` (3 call sites: steps 3/4/5 — command has no `mysqldump` usage)
  - [x] Verify: `php artisan config:clear && php artisan backup:run` from a **plain shell** (no PATH prefix) → success
  - **Done (2026-09-29)**: new trait `app/Console/Commands/Traits/ResolvesBinaries.php` (PATH scan → fallback dirs → bare-name; forward-slash output, `.exe` suffix on Windows). Verified: plain-shell `backup:run` → 53,456 bytes + offsite upload OK; plain-shell `backup:verify` → 22 tables; `resolveBinary` returns `C:/xampp1/mysql/bin/mysqldump.exe` / `mysql.exe` / bare fallback.

- [x] **A2. Exit codes** — change `handle(): void` → `handle(): int` in both commands; return `self::FAILURE` on every failure path.
  - [x] `BackupDatabase` — dump failure, missing encrypt pass, (decide) upload failure → should upload failure make exit nonzero? Yes, mark `self::FAILURE`
  - [x] `VerifyBackup` — "no backup files found" currently only warns → make it `self::FAILURE`
  - **Done (2026-09-29)**: `BackupDatabase` returns `FAILURE` (missing pass / dump fail / upload fail) and `$uploaded ? SUCCESS : FAILURE`; `VerifyBackup` returns `FAILURE` on missing pass, no files, all 5 step failures via `failVerify`, and catch block; `SUCCESS` after step 5. Verified exit codes: happy `backup:run`=0, happy `backup:verify`=0, missing-pass=1 (both), no-files=1, corrupt-file decrypt fail=1 (temp files cleaned), dump fail via `DB_PORT=59999`=1 (partial file removed). Full suite 73 passed.

- [x] **A3. Fix gunzip stderr bug** — `VerifyBackup.php:63` uses `gunzip -c in > out 2>&1`, which writes warnings **into the SQL file**. Capture stderr to a separate temp file (or discard), then check exit code.
  - **Done (2026-09-29)**: stderr → `<sql>.gzerr`; on failure stderr is read and appended to `failVerify` output (so error still visible), `.gzerr` unlinked on both success and failure paths. Verified: happy path 22 tables, exit 0, no `.gzerr` leftover; forced gunzip failure (encrypted non-gzip file) → error shows `gzip: ...: not in gzip format`, exit 1, temp files cleaned. Full suite 73 passed.

- [x] **A4. Verify the offsite copy** — if no local file exists (always true on Railway's ephemeral containers): download newest object from `Storage::disk('supabase-backups')` to a temp path, then run the existing 5-step restore on it.
  - [x] Verify: delete local files → `php artisan backup:verify` → restores from Supabase → 22 tables
  - **Done (2026-09-29)**: `fetchOffsiteBackup()` — lists `db/`, picks newest by `lastModified`, downloads via `readStream` to backup dir using the **original filename** (so retention glob `alalay_*` manages it, no orphan prefix); only runs when glob is empty + Supabase creds present; failures (list/read/write) logged → `FAILURE`. Verified: 0 local files → downloaded 53,456 bytes → 22 tables exit 0; no-local + no-offsite → `No backup files found locally or on Supabase.` exit 1; local files restored after test (9/9); local verify still exit 0; suite 73 passed.

- [x] **A5. Concurrency lock** — wrap `backup:run` body in `Cache::lock('backup', 600)`; same lock used by restore (C5). Scheduled `withoutOverlapping()` stays as-is (its own key).
  - **Done (2026-09-29)**: `handle()` now checks `Cache::lock('backup', 600)` before calling extracted `runBackup()`; `try { … } finally { $lock->release(); }` guarantees release. **Decision: lock contention → warn + exit 0** (a skip is not a failure — avoids paging on cron). Verified: externally-held lock → `Another backup or restore is already in progress — skipping.` exit 0; normal run → backup + upload OK, then lock re-acquirable (released via `finally`); `cache_locks` migration exists (prod `database` store supports locks, tests use `array`); suite 73 passed. C5 restore must acquire this same `backup` lock (contention → FAILURE there).

- [x] **A6. Prune end-to-end test (manual)** — `BACKUP_RETENTION_DAYS=0 php artisan backup:run` → confirm local **and** offsite prune paths fire (offsite delete has never executed) → plain `php artisan backup:run` → fresh file restored to both locations.
  - **Done (2026-09-29)**: bucket pre-checked (only dev-DB backups, no production objects → safe). Retention-0 run → 11 local `Pruned:` lines + **4 `Pruned offsite:` lines (delete path first-ever execution, confirmed)**. Known edge case (test-only, retention 0): the just-created local file was also pruned (filemtime < cutoff after second tick) while the just-uploaded offsite object survived — harmless at real retention (30d). Plain run after → fresh `alalay_2026-09-29_11-14-56.sql.gz.enc` in both locations (53,360 bytes each) → `backup:verify` 22 tables exit 0. Suite 73 passed.

- [x] **A7. Drop test DB after successful verify** — after step 5 passes in `VerifyBackup`, run `DROP DATABASE IF NOT EXISTS \`alalay_backup_test\`` so weekly prod verifies don't grow disk. Treat as a soft step: if the drop fails, log a warning but keep verify exit 0 (the restore itself passed).
  - **Done (2026-09-29)**: `dropTestDatabase()` helper runs after success log, before temp-file cleanup. Happy path: `Test database alalay_backup_test dropped.` + DB confirmed gone via CLI + verify exit 0. Soft path (forced via reflection with unreachable port): emits `Could not drop test database ... (kept for inspection).` warning, returns normally → verify exit stays 0. Suite 73 passed.

---

## Group A complete (2026-09-29)

All 7 hardening items done and verified: A1 binary resolution (plain-shell run+verify ✅), A2 exit codes (6 failure paths → 1, success → 0 ✅), A3 gunzip stderr isolation ✅, A4 offsite download-verify (0 local → Supabase → 22 tables ✅), A5 `Cache::lock('backup', 600)` (contention → warn + exit 0; `finally` release ✅), A6 prune e2e (local + first-ever offsite delete ✅), A7 soft test-DB drop ✅. Test suite 73 passed after every item.

---

## Part B — Audit logging for backups

- [x] **B1. Log inside `BackupDatabase`** (covers scheduled AND UI-triggered — single source of truth)
  - [x] Success → `AuditLog::create`: `module: 'system'`, `action: 'backup_created'`, `user_id: auth()->id()` (admin via HTTP, `null` when scheduled), `created_at: now()`, description: filename + size + upload status
  - [x] Failure → `action: 'backup_failed'`, description includes reason/exit code
  - [x] Guard `request()->ip()` for console context (nullable column)
  - **Done (2026-09-29)**: shared trait `app/Console/Commands/Traits/LogsAudit.php` (`logAudit()` wraps `AuditLog::create` in try/catch — audit failure never breaks a backup; `user_id`/`role` from `auth()->user()`, `ip_address`/`user_agent` null when `app()->runningInConsole()`). Rows: missing-pass → `backup_failed`; dump/encrypt failure → `backup_failed` (exit code + bytes); success+upload → `backup_created` (file, size, "local + Supabase"); upload fail → `backup_failed` (local copy saved). Verified live: 3 rows with `user=NULL, ip=NULL`.

- [x] **B2. Log inside `VerifyBackup`**
  - [x] `backup_verified` — description: filename + table count
  - [x] `backup_verify_failed` — description: which step failed
  - **Done (2026-09-29)**: `backup_verified` after step 5 (`Verified backup <file> — 22 tables restored.`); `backup_verify_failed` from `failVerify` (step message, e.g. `Decompression failed.`), from missing pass, and from no-files-anywhere. Verified live for both actions.

- [x] **B3. Log inside new `RestoreBackup` command** — ⚠️ **implement together with C5** (command doesn't exist until C5)
  - [x] `backup_restored` — description: source file + snapshot filename
  - [x] `backup_restore_failed` — description: reason + snapshot filename + "system left in maintenance"
  - **Done (2026-09-29)** (with C5): `backup_restored` = "Restored database from {file}; safety snapshot {snapshot} created before restore."; `backup_restore_failed` covers all 5 failure classes (invalid filename, lock held, not found, snapshot abort, import failure) — import failure includes reason + "System left in maintenance mode — roll back from snapshot {snapshot}." Verified live on both paths (C6).

- [x] **B4. Log in `BackupController::destroy`** — ⚠️ **implement together with C3** (controller doesn't exist until C3)
  - [x] `backup_deleted` — description: filename + locations removed (local/offsite/both)
  - **Done (2026-09-29)** (with C3): `"Deleted backup {file} (local + offsite)."` with `user_id`/`role`/`ip`/`user_agent` from the request. Live duplicate-check vs these routes still pending E1 (B7 note).

- [x] **B5. Skip-list** — add to `AuditLogMiddleware.php` `SKIP_ROUTES` (line ~23): `admin.backups.run`, `admin.backups.restore`, `admin.backups.destroy` (self-logged with rich descriptions; avoids duplicate generic `admin/run` rows).
  - **Done (2026-09-29)**: three route names added. ⚠️ Live duplicate check against real routes still needs C1/C3 (routes don't exist yet) — see B7.

- [x] **B6. Label maps**
  - [x] `AuditLogController::$action_label` (~line 63) += `backup_created` → "Backup Created", `backup_failed` → "Backup Failed", `backup_verified` → "Backup Verified", `backup_verify_failed` → "Backup Verify Failed", `backup_restored` → "Backup Restored", `backup_restore_failed` → "Backup Restore Failed", `backup_deleted` → "Backup Deleted"
  - [x] `resources/js/Utils/severityMappings.js::actionSeverity` += `backup_created`/`backup_verified`/`backup_restored` → `success`, `*_failed` → `danger`, `backup_deleted` → `warn`
  - **Done (2026-09-29)**: all 7 labels in controller map; severities added (`backup_created/verified/restored=success`, `*_failed=danger`, `backup_deleted=warn`). CSV export uses raw action (existing behavior for all actions — unchanged). `system` module falls back to headline → "System".

- [x] **B7. Verify** — trigger actions → entries appear on Audit Logs page, correct labels/colors, action filter dropdown includes them (DB-derived), NO duplicate rows (skip-list works).
  - **Done (2026-09-29)**: new `tests/Feature/BackupAuditTrailTest.php` (2 tests): seeds all 7 actions → GET `/admin/audit-logs` as admin → component `Admin/AuditLogs`, `actions` prop == all 7 (DB-derived filter), deferred `logs` partial-reload → all 7 `action_label`s correct + `module_label: System`; second test asserts no duplicate rows. Colors verified at source (severityMappings keys). Suite **75 passed**. ⚠️ Skip-list duplicate check vs live `admin.backups.*` routes → re-verify in C3/B4.

---

## Part C — Backend for the page

- [x] **C1. Routes** — `routes/web.php`, inside existing `role:admin` / `admin.` group (~line 119):
  ```php
  Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
  Route::post('/backups/run', [BackupController::class, 'run'])->name('backups.run');
  Route::post('/backups/restore', [BackupController::class, 'restore'])->name('backups.restore');
  Route::delete('/backups/{file}', [BackupController::class, 'destroy'])->name('backups.destroy');
  ```
  - [x] `{file}` validated against `/\Aalalay_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql\.gz\.enc\z/` + `basename()` — no path traversal. Validation applied in controller **and** in restore command (defense in depth).
  - **Done (2026-09-29)**: 4 routes registered + `use ...Admin\BackupController` import; `route:list --name=backups` shows all 4. Validation: `BackupController::FILE_RULES` regex on `file` (422 on bad input) + `basename()`; `RestoreBackup::handle` re-validates via `repo->isValidFilename()` (403-style reject + audit row).

- [x] **C2. New `app/Services/BackupFileRepository.php`**
  - [x] `paginate(search, from, to, page)`: merge `glob(config('backup.path'))` (mtime + filesize) with offsite listing via **`Storage::disk('supabase-backups')->listContents('db')`** — one ListObjectsV2 call returns path + size + lastModified (NOT `files()` + per-file `size()`/`lastModified()`, which would be ~1 HEAD API call per object per page load); try/catch → degrade to local-only on network failure; dedupe by filename → rows `{file, size, created_at, location: local|offsite|both}`
  - [x] Filters: filename-contains search, `from`/`to` on date (same convention as AuditLogs — raw `YYYY-MM-DD` strings)
  - [x] `LengthAwarePaginator::make(..., 20, page)` — same JSON shape as AuditLogs paginator
  - [x] `locate(file)`: return local path if file exists locally, else stream from Supabase to temp path
  - [x] `delete(file)`: `@unlink` local + `$disk->delete()` offsite
  - [x] `snapshot()`: extract dump→gzip→encrypt→local-save→offsite-upload logic shared by run + pre-restore snapshot
  - **Done (2026-09-29)**: `listAll()`/`filter()`/`paginate()`/`locate()`/`delete()`/`snapshot()` + `isValidFilename()` + `FILENAME_PATTERN`. Offsite listing uses **`getDriver()->listContents('db', false)`** (Laravel's FilesystemAdapter has no `listContents` — note: plan's `Storage::disk()->listContents()` isn't available; one ListObjectsV2 either way). Deviation: `LengthAwarePaginator::make()` doesn't exist in this Laravel → `new LengthAwarePaginator(...)` (same JSON shape). `snapshot()` throws `RuntimeException` on dump failure, returns `{file, path, size, uploaded, upload_error}` (caller decides policy). Verified live: merged rows `both`/`offsite`, filters, traversal rejection, `locate()` local + offsite download.

- [x] **C3. New `app/Http/Controllers/Admin/BackupController.php`** — every method: `$this->authorize(..., SystemSetting::class)` (existing admin-only policy)
  - [x] `index()` → `Inertia::render('Admin/Backups', ['files' => Inertia::defer(fn () => repo->paginate(...)), 'filters' => request()->only(['search', 'from', 'to'])])`
  - [x] `run()` → `Artisan::call('backup:run')` → flash success/error from `Artisan::output()` (global toast handles display)
  - [x] `restore()` → validate file → `Artisan::call('backup:restore', ['file' => ...])` → flash
  - [x] `destroy()` → validate → `repo->delete()` → `AuditLog::create(backup_deleted)` → flash
  - **Done (2026-09-29)**: index uses `viewAny`, run/restore/destroy use `update` on `SystemSetting::class`. `Artisan::call()` return code drives `flash.success`/`flash.error` with `Artisan::output()` as the message (matches `AppLayout.vue` toast watcher keys). `destroy` reports which locations were removed (`local + offsite`) in both the audit row and the flash; "not found → nothing deleted" error flash. B4 audit ✅.

- [x] **C4. Refactor `BackupDatabase` to use `snapshot()`** from repository (single dump pipeline, no duplicated shell-out code). Keep existing behavior: pipefail, size sanity check, forward-slash paths, manual single-quote escaping.
  - **Done (2026-09-29)**: `BackupDatabase` now constructor-injects the repo; dump/upload code deleted (incl. `uploadToSupabase`); `runBackup` wraps `repo->snapshot()` in try/catch (throws → `backup_failed` + FAILURE), prints `Offsite upload complete.`, keeps prune + audit + `$uploaded ? SUCCESS : FAILURE`. `ResolvesBinaries` trait dropped from the command (repo owns it). Verified: local+offsite output identical to pre-refactor, offsite object confirmed, suite 75 passed.

- [x] **C5. New `app/Console/Commands/RestoreBackup.php`** (`backup:restore {file}`) — **Snapshot + maintenance flow** (user-approved design):
  - [x] 1. Validate filename (defense in depth); acquire `Cache::lock('backup', 600)` or fail "backup in progress"
  - [x] 2. `repo->locate()` — download from Supabase if not local
  - [x] 3. **Pre-restore snapshot** via `snapshot()` — normal `alalay_*` filename so it shows in the table as a rollback row; upload offsite too. **Abort rule: local snapshot creation failure → ABORT, nothing touched. Offsite upload failure of the snapshot → warn only, restore proceeds** (local snapshot on the same container is the rollback net; a Supabase outage must not block restores)
  - [x] 4. If `!app()->isDownForMaintenance()`: `Artisan::call('down', ['--render' => 'errors.503', '--secret' => config('app.maintenance_secret')])` → remember `$broughtDown = true`
  - [x] 5. decrypt → gunzip (stderr handled per A3) → import: `mysql -h -P -u ... < sqlfile` (resolved binary per A1; dump contains `DROP TABLE IF EXISTS` per table). **Known limitation (accepted)**: only tables present in the dump are dropped/recreated — tables created *after* the backup are not removed. Not a `DROP DATABASE` wipe; fine for this app.
  - [x] 6. **Success** → `Artisan::call('up')` only if `$broughtDown`; audit `backup_restored` (names source + snapshot); return `self::SUCCESS`
  - [x] 7. **Failure** → **leave maintenance ON**; audit `backup_restore_failed` (reason + snapshot filename + "roll back from snapshot" guidance); return `self::FAILURE`
  - [x] 8. Always: release lock, delete temp files (decrypted/sql)
  - **Done (2026-09-29)** + two discoveries fixed during implementation:
    1. **Snapshot audit row must be written AFTER the import** — the import replaces `audit_logs` with the backup's contents, wiping any pre-import row; snapshot `backup_created` is logged right after `importFrom()` returns (both success and failure paths), `backup_restored`/`backup_restore_failed` after it. B3 ✅.
    2. **`cache_locks` resurrection bug** — the dump includes the `cache_locks` table *containing the restore's own lock row*, so the import resurrects a lock row with an older owner; owner-matched `release()` then silently deletes nothing and every later backup/restore is blocked for 600s (found via C6: restore left 1 stuck row, `backup:run` didn't). Fix in `finally`: `DB::table('cache_locks')->where('key', config('cache.prefix').'backup')->delete()` **then** `$lock->release()` — safe because the restore is the only legitimate holder; a concurrent fresh acquirer's row has a different owner so `release()` won't touch it. Verified: 0 lock rows after both success and failure runs.

- [x] **C6. Backend smoke test (local)** — `php artisan backup:restore alalay_<existing-file>` in plain shell:
  - [x] snapshot row appears in `storage/app/backups` + Supabase
  - [x] site goes into maintenance, import runs, site comes back up
  - [x] 3 audit rows appear (`backup_created` for snapshot, `backup_restored`)
  - [x] failure path: restore with corrupted file → stays in maintenance + `backup_restore_failed` row → bring online manually + verify snapshot rollback works
  - **Done (2026-09-29)**: success run — snapshot `alalay_..._11-39-38` local+Supabase, maintenance down→up, exit 0, audit rows `backup_created`(snapshot)+`backup_restored`. Failure run (corrupted file) — `Decryption failed: bad magic number`, **exit 1, maintenance stayed ON**, lock 0 rows, audit `backup_restore_failed` includes "roll back from snapshot ..." + snapshot `backup_created` row. Manual `php artisan up` → rollback restore **from the failure run's snapshot** → exit 0, maintenance off, lock 0. Corrupt test file removed after.

- [~] **C7. Security/retention hardening**
  - [x] Pass DB password via `MYSQL_PWD` env variable instead of `-p...` flag in dump/import shell commands (`BackupDatabase`, `VerifyBackup`, `RestoreBackup`) — password no longer visible in `ps` output
  - [x] Supabase bucket lifecycle rule: ~~expire objects in `alalay-backups` after 30+ days (dashboard or API)~~ — **SUPERSEDED (2026-09-29)**: research showed Supabase's lifecycle API only supports `noncurrentVersionExpiration` (previous versions, needs versioning); **current-object expiration is explicitly rejected**. Replaced by standalone `backup:prune` command (Part G) as the belt-and-braces retention safety net; in-code `pruneSupabase()` inside `backup:run` remains the primary mechanism. User chose this option.
  - **Done (2026-09-29) MYSQL_PWD part**: new trait `app/Console/Commands/Traits/InteractsWithMysql.php` (`withMysqlPassword()` exports `MYSQL_PWD` for the callback, unsets in `finally`). Applied at 6 exec sites: `BackupFileRepository::snapshot()` (dump), `VerifyBackup` create/import/count/drop, `RestoreBackup::importFrom`. Count parsing hardened (takes last numeric line — immune to client warning noise). Verified with a **temporary passworded MySQL user** (`alalay_pwdtest`, dropped after): `backup:run` OK, `backup:verify` 22 tables + drop OK, `backup:restore` exit 0 + maintenance off + lock 0; env confirmed set-inside/unset-after; `grep` shows zero `-p` password flags left in `app/`; suite 75 passed.

---

## Part D — Sidebar + UI page

- [x] **D1. Sidebar entry** — `resources/js/Layouts/AppMenu.vue`, admin array (line ~24), insert **after "Audit Logs" (line 27)**, top-level leaf (NOT inside Settings submenu):
  ```js
  { label: 'Backup & Restore', icon: 'pi pi-fw pi-database', to: route('admin.backups.index') },
  ```
  - **Done (2026-09-29)**: inserted exactly after Audit Logs line; `route()` name `admin.backups.index` matches C1.

- [x] **D2. New `resources/js/Pages/Admin/Backups.vue`** — model on `AuditLogs.vue`:
  - [x] `defineOptions({ layout: AppLayout })`; `useBreadcrumb([{ label: 'Admin' }, { label: 'Backup & Restore' }])`
  - [x] **Header row**: title "Backup & Restore" + **Backup Now** button on right — separate `useForm({})`, posts to `route('admin.backups.run')`, `preserveScroll: true`, `:loading="form.processing"`
  - [x] **Filters row**: search (`IconField` + `InputText`, applies on `@keyup.enter`) + From/To `DatePicker` pair (`dateFormat="yy-mm-dd"`, `showIcon`, `showClear`, `watch([from, to])` auto-applies) — copy AuditLogs.vue lines 103-122; `formatDateParam`/`parseDate` helpers (lines 34-44)
  - [x] **Table** inside `<Deferred data="files">` + `Skeleton` fallback:
    - [x] File column (filename, `break-all text-sm`)
    - [x] Size column (inline `formatBytes` helper)
    - [x] Created column (`formatDateTime`)
    - [x] Location column (`Tag`: both → `success`, offsite → `info`, local → `warn`)
    - [x] Actions column: **Restore** (`pi pi-undo`, outlined warn) + **Delete** (`pi pi-trash`, outlined danger) icon buttons per row
  - [x] **Restore confirmation** — `useConfirm().require`, danger severity, strong message (creates safety snapshot first / all users logged out / maintenance during restore), `acceptLabel: 'Restore'`; on accept → `router.post(route('admin.backups.restore'), { file })`
  - [x] **Delete confirmation** — `confirm.destroy(...)` pattern (see `Admin/Users/Index.vue:100-116`); on accept → `router.delete(route('admin.backups.destroy', file))`
  - [x] Per-row loading via `pendingFile` ref (only the clicked row's buttons show loading)
  - [x] Server-side `Paginator` (20/page) identical to AuditLogs.vue:157-165 (`onPage` → `router.get` with filters + `page: event.page + 1`)
  - [x] Success/error toasts come free from global flash watcher (`AppLayout.vue:29-32`) — no extra toast code needed
  - **Done (2026-09-29)**: uses project `useConfirm` composable (`require`/`destroy`), `pendingFile` guards other rows' buttons while one request in flight.

- [~] **D3. Build + visual check**
  - [x] `npm run build` clean (✓ built in 39.04s; only pre-existing >500kB chunk warning)
  - [ ] `npm run dev` → sidebar entry visible for admin only, active-state highlight works
  - [x] Page renders full-width matching other admin pages (`grid grid-cols-12 gap-8` + `col-span-12` + `.card`) — markup identical to AuditLogs.vue; server-side component render asserted by E1
  - **Partially done (2026-09-29)**: build clean + render covered by test; **⚠️ browser visual check left for user** (needs `npm run dev` + manual look).

---

## Part E — Tests

- [x] **E1. New `tests/Feature/BackupPageTest.php`** — **always `Storage::fake('supabase-backups')` in tests that reach the repository** (index + destroy) — otherwise `paginate()` hits the live Supabase API (fails offline/CI)
  - [x] Non-admin staff roles → all 4 routes = 403
  - [x] Admin `index` → 200 with `files` + `filters` props (`Storage::fake` active)
  - [x] `run` with mysql connection pointed at a dead port → flash error + `backup_failed` audit row (never touches real dev DB — tests use sqlite)
  - [x] `restore` with invalid filename → 403/422, no audit row, no command run
  - [x] `destroy` → `Storage::fake('supabase-backups')` + temp local file → both removed + `backup_deleted` audit row
  - [x] `markTestSkipped` guard if `resolveBinary('mysqldump')` unusable (CI safety)
  - **Done (2026-09-29)**: 5 tests / 44 assertions pass. Deferred `files` prop fetched via partial reload (`X-Inertia-Version` = xxh128 of `build/manifest.json`). **Bug found & fixed**: `BackupController::destroy` validated `file` from request input but the route passes it as `{file}` route param → merged `$request->route('file')` into input before validate (E1 test caught it — `destroy` previously always 422'd).

- [x] **E2. Full suite green** — `php artisan test`
  - **Done (2026-09-29)**: **80 passed (417 assertions), 0 failed**, 7.09s (75 prior + 5 new).

---

## Part F — Production readiness (deploy: user pushes + merges to dev themselves; deployment instructions TBD)

**Pre-push scan (2026-10-02) — all clean:**
- Static: no secrets in incoming files (Supabase/Resend/DB keys/emails), no debug leftovers (`dd`/`console.log`/`TODO`), `php -l` clean on all changed/new PHP, `en.json`/`fil.json` valid + en↔fil key parity 0 mismatch, no live refs to deleted `scripts/backup.sh`, gitignore covers `public/build`/`.env`/`node_modules`/`vendor`, modal emit contracts match consumers, full diff reviewed — `routes/web.php` diff is purely backup routes (no hunk-splitting).
- Dynamic gate: **`php artisan test` → 84 passed (458 assertions)**; **`npm run build` clean (11.29s)**.
- Browser UI pass (F0a): **user running it themselves pre-push** (2026-10-02).
- Commit grouping proposed (3 commits: privacy / backup & restore / maintenance confirm) — **user pushes + merges to dev manually** (F3 re-scoped: no agent commits/pushes).

- [x] **F1. `railpack.json` created** at repo root (2026-10-02) — JSON validated; Railpack's PHP image ships neither binary, without it prod backups fail nightly:
  ```json
  { "$schema": "https://schema.railpack.com",
    "deploy": { "aptPackages": ["default-mysql-client", "openssl"] } }
  ```
  `railway.json` already sets `"builder": "RAILPACK"` → picked up automatically, **no Railway dashboard change needed**. Risk: if the base distro rejects `default-mysql-client`, retry with `mysql-client` during the first prod build.
- [x] **F2. Prod env vars confirmed on web + cron + worker** — verified via `railway variables` (2026-10-02): `BACKUP_ENCRYPT_PASS`, `SUPABASE_KEY/SECRET`, `SUPABASE_STORAGE_ENDPOINT` (= `https://urouwnyhopfbhfrmklgv.storage.supabase.co/storage/v1/s3`), `SUPABASE_BACKUP_BUCKET`, `SUPABASE_STORAGE_REGION=ap-northeast-2`, `BACKUP_RETENTION_DAYS=30`, `BACKUP_TEST_DATABASE` all present on **all three services**; `QUEUE_CONNECTION=database` on worker. Start commands (`cron.sh`/`worker.sh`) + `preDeployCommand` already configured.
- [~] **F3. Commit + push consent** — **re-scoped (2026-10-02): user pushes + merges to dev themselves.** Agent does NOT commit/push. Deployment instructions to come from user.
- [ ] **F4. Deploy web + cron + worker** — pending user's deployment instructions (`railway up --service <name> --detach --yes` per service).
- [ ] **F5. Post-deploy smoke checks** — `mysqldump` exists in container, config cache picked up `BACKUP_ENCRYPT_PASS`, 02:00 scheduled run succeeds (check logs), page loads, Backup Now works in prod.
  - [ ] **Prod verify drill**: run `php artisan backup:verify` **inside the prod (cron) container** — exercises offsite download + restore-to-test-DB without touching the live DB (safe substitute for a prod restore rehearsal; A7 cleans the test DB after). Railway has no SSH → temporarily override the service Start Command, read logs, revert (or rely on nightly logs; same image serves all services).
  - [x] ~~Confirm Supabase bucket lifecycle rule is active (from C7)~~ — **superseded (C7): no bucket lifecycle rule possible; verify `backup:prune` schedule fires instead** (Sundays 04:00).
- [x] **F6. Production `system_settings` cleanup SQL** — **done by user directly in production DB (2026-10-02)**: dead keys deleted manually; no SQL handoff needed anymore.

**Railway dashboard answer (2026-10-02): no configuration required.** Env vars, builder (RAILPACK), start commands, and preDeployCommand are all already set. Only user-side dashboard actions during F: read logs (first deploy + nightly 02:00), run the F6 SQL in the MySQL console, and (optionally) a temporary Start Command override to execute one-off container commands since Railway has no shell. FYI: `railway.json` Config-as-Code is deprecated → migrate to `.railway/railway.ts` before **2026-12-01** (separate task).

---

## Global verification (final pass before requesting commit)

- [x] `php artisan config:clear && php artisan backup:run` from plain shell → OK (no PATH prefix) — **verified 2026-09-29**, 54,112 bytes local + offsite upload OK, exit 0
- [x] `php artisan backup:verify` → 22 tables; delete local files → verify again from Supabase — **verified 2026-09-29**: both passes exit 0, second pass ran with 0 local files (offsite download path)
- [x] Prune test (A6) passed both local + offsite
- [x] Verify drops test DB afterward (A7) — `SHOW DATABASES` confirms no leftover `alalay_backup_test` — **re-confirmed 2026-09-29** (only `alalay` + `alalay_system` remain)
- [x] `ps` during dump shows no DB password (C7 `MYSQL_PWD`) — zero `-p` password flags in `app/`; env-based flow functionally proven with temp passworded user (Windows has no `ps`, verified by code + functional test)
- [x] Restore E2E (C5/C6) passed incl. failure path
- [x] `npm run build` clean — **2026-09-29**, 39.04s
- [x] `php artisan test` green — **80 passed (417 assertions), 0 failed**
- [ ] UI pass: sidebar entry → filters (search/from/to) → Backup Now (toast + row + audit) → Restore confirm → full cycle → Delete confirm → gone from both locations → all audit entries visible pre-filtered on Audit Logs — **⚠️ needs manual browser check (`npm run dev`)**
- [ ] `git status` reviewed — only intended files changed; **privacy and backup work split into separate commits**; **ask user before committing**

## Decisions locked in (do not re-litigate silently)

- **Restore safety**: auto pre-restore snapshot + maintenance mode during restore; failure deliberately STAYS in maintenance with snapshot as rollback. Abort only on local snapshot failure; snapshot offsite-upload failure warns but does not block. Restore is per-table drop/recreate (dump's `DROP TABLE IF EXISTS`), not a `DROP DATABASE` wipe — accepted limitation.
- **Locations**: table merges local + Supabase; restore downloads if offsite-only; delete removes from both; rows tagged Local/Offsite/Both.
- **Placement**: standalone top-level sidebar tab, NOT a card/submenu of System Settings (`SystemSettings.vue` untouched).
- **Sync, not queued**: backup/restore run synchronously in the request (DB dump is ~53 KB; revisit only if prod ever times out).
- **Audit**: free-form `module: 'system'` + `backup_*` actions, no migration needed; commands self-log so scheduled and manual runs share one code path.
- **Container-boundary note (expected behavior, NOT a bug)**: scheduled backups run on the **cron** service, so their local files exist only in cron's container. The UI (on **web**) globs *its own* storage → those rows show as `Offsite` only, never `Both`. Restore/verify work regardless (Supabase download path, A4/`locate()`). Same applies to any file created by a different service's container.
- **No failure alerting** (decided out): a failed 02:00 backup surfaces via audit logs + exit codes only; no SMS/email alerting in scope.

---

## Part G — Post-review fixes (2026-09-29, user-reported issues)

- [x] **G1. Dates showed 1970** — `paginate()` now converts Unix-seconds `created_at` → `'Y-m-d H:i:s'` at the output boundary (dayjs read seconds as ms; same shape AuditLogs sends). Sorting/filtering still use int internally. Regression assertion added to E1 (`assertMatchesRegularExpression` + `2026-` prefix). **Done (2026-09-29)**, E1 5/5 pass, live tinker output shows real 2026 dates.
- [x] **G2. Location filter** — `filter()`/`paginate()` gain `?string $location` (`local|offsite|both`, empty = all, invalid values ignored); `BackupController::index` passes `location` query param + into `filters` prop; `Backups.vue` adds `Select` (All Locations/Local/Offsite/Both, `@change=applyFilters`) beside search, included in `filterParams()` (so pagination keeps it). **Done (2026-09-29)**: new E1 test `location_filter_returns_matching_rows_only` (local/offsite isolation + `filters.location` round-trip), 6/6 pass.
- [x] **G3. Action button style** — match `AssistanceCategories/Index.vue:97-99`: `text rounded size="small"` + `flex gap-2` (Restore = `severity="warn"`, Delete = `severity="danger"`). **Done (2026-09-29)**, `npm run build` clean (10.88s).
- [x] **G4. `backup:prune` command** (replaces C7 lifecycle rule, user-approved) — `app/Console/Commands/PruneBackups.php` (`backup:prune {--dry-run}`): local glob + Supabase `db/` listing, cutoff `BACKUP_RETENTION_DAYS`, warns + exit 1 on offsite sweep failure; scheduled `routes/console.php` weekly Sundays 04:00 `withoutOverlapping()` (after verify at 03:00). **Done (2026-09-29)**: dry-run exit 0 (0 pruned, all files fresh), E1 test with retention 0 + faked storage passes (old pruned both locations, new kept, dry-run no-op), `schedule:list` shows all 3 entries, 7/7 pass.
- [x] **G5. Full functional re-check** — **Done (2026-09-29)**, all green:
  | Check | Result |
  |---|---|
  | `config:clear && backup:run` | exit 0, `alalay_2026-09-29_12-37-51` 54,320 B local + offsite |
  | `backup:verify` (local) | exit 0, 22 tables, test DB dropped |
  | `backup:verify` (offsite-only, local deleted first) | exit 0, 22 tables — download path OK |
  | Restore success drill | exit 0: snapshot `12-39-06` (54,432 B) → maintenance down → import → up; `framework/down` absent; 22 tables intact |
  | Audit actions live | `backup_created` 9, `backup_verified` 3, `backup_restored` 5, `backup_failed` 1 (`backup_deleted` = 0 live — no UI delete yet; covered by E1 destroy test) |
  | Leftover test DB | none |
  | Filters via HTTP (E1) | index + `?location=offsite` + `filters` round-trip pass |
  | Full suite | **82 passed (446 assertions), 0 failed** |
  | `npm run build` | clean (15.57s) |
- [x] **G6. Local path documented for user** — `storage/app/backups/` (no `BACKUP_PATH` in `.env`), encrypted `.sql.gz.enc`, 30-day retention. *(answered in chat 2026-09-29)*
- [x] **G7. Explicit per-action toasts** (user-approved plan) — new dedicated `backup_result` flash key `['status' => 'success'|'error', 'message']` instead of generic `success`/`error`, so the global AppLayout watcher (`AppLayout.vue:29-32`) never double-toasts; `BackupController::result()` helper collapses `Artisan::output()` newlines → `' · '` and flashes it; all 4 sites converted (`run`, `restore`, `destroy` not-found + success). `HandleInertiaRequests` shares `flash.backup_result`. `Backups.vue` toasts explicitly in `onSuccess` via `toastBackupResult(okSummary, errorSummary)` + `onError` → `toastValidationErrors()` for 422s (previously silent). **Done (2026-09-29)**: E1 assertions updated (`backup_result.status === 'error'|'success'`, `assertSessionMissing('error'|'success')` proving no double flash, message contains `mysqldump`), 7/7 pass.
- [x] **G8. Backup Now confirm modal** — `runBackupNow()` wrapped in `confirm.require` (header "Confirm Backup", `p-button-success` accept "Backup Now", message re: dump/compress/encrypt/upload + few-seconds duration), matching the existing Restore/Delete confirms. **Done (2026-09-29)**.
- [x] **G9. Confirm modals max-width** — PrimeVue 4 `.p-dialog` ships **no** width rule (dialogs size to content → long messages stretch full-width). Added global `.p-confirmdialog { max-width: 28rem }` in `resources/js/layout/scss/layout/_utils.scss` (alongside the existing `.p-toast` override); `.p-confirmdialog` lands on the Dialog root via `ConfirmDialog`'s `:class="cx('root')"`. Covers Restore/Delete/Backup confirms app-wide. **Done (2026-09-29)**: verified in bundle `p-confirmdialog{max-width:28rem}` in `public/build/assets/app-*.css`.
- [x] **G10. Full re-verification** — **Done (2026-09-29)**: `BackupPageTest` 7/7 (78 assertions), full suite **82 passed (451 assertions)**, `npm run build` clean (14.91s).
- [x] **G11. Backup destination option (local / offsite / both)** — user-requested; flow = **confirm modal → second modal** (Option B). **Done (2026-10-02)**:
  - Backend: `backup:run {--destination=both}` (`BackupDatabase.php` — invalid value fails fast with exit 1, before lock/audit); `BackupFileRepository::snapshot(string $destination = 'both')` — local write is the unavoidable shell-pipeline intermediate, `local` skips the upload block, `offsite` unlinks the local file **only after a successful upload** (kept on failure, nothing ever lost); success semantics fixed (`$destination === 'local' || $uploaded` — intentional upload skip no longer fails), audit labels `local only` / `offsite only` / `local + Supabase`, warn/prune gated on destination; `BackupController::run()` validates `destination in:local,offsite,both` (`sometimes` → default `both`) and passes `--destination`. Scheduled daily run + pre-restore snapshot keep default `both`.
  - Frontend (`Backups.vue`): `runForm = useForm({ destination: 'both' })`; step-1 Confirm Backup accept → opens step-2 `Dialog` "Backup Destination" (PrimeVue `Select`: Both/local only/offsite only + per-option hint text, `max-w-md`, disables while processing); "Start Backup" posts and toasts via existing `toastBackupResult`/`toastValidationErrors`; resets to `both` on finish. (Note: `confirm.require` message renders via `{{ message }}` string interpolation → cannot host interactive content; hence a plain Dialog for step 2.)
  - Verified: new E1 tests `test_run_rejects_invalid_destination` (422 + command-level exit 1, no audit rows) and `test_run_accepts_valid_destination_and_passes_it_through` → `BackupPageTest` 9/9 (85 assertions); full suite **84 passed (458 assertions)**; `npm run build` clean (35.02s); **live e2e all 3 destinations**: `--destination=local` exit 0 (file kept, audit "local only"), `--destination=offsite` exit 0 (uploaded `alalay_2026-10-02_00-43-46` offsite, local copy removed, audit "offsite only"), default `both` exit 0 (file kept + uploaded, audit "local + Supabase").

# ALALAY — Workflow Policy Change

Client-approved refactor of the end-to-end AICS assistance workflow. Adds two new panels
(Internal Audit, Budget Office), removes the Mayor's Office panel, renames the Accountant
stage, and retires the unused `screening`, `on_hold`, and `voucher_returned` statuses.

## Checklist legend

- `- [ ]` Not started
- `- [x]` Done

> Mark each box with `x` as you complete the step. Keep reference sections 1–3 and 5–6
> unmodified; only tick boxes in section 4.

---

## 1. Confirmed decisions

| # | Decision | Chosen value |
|---|----------|--------------|
| 1 | New role strings (full names, no 8-char truncation) | `internal_audit`, `budget_officer` |
| 2 | Internal Audit is a **separate pending stage** — voucher creation only proceeds after IA approval | new status `internal_audit_review` |
| 3 | Voucher return flow is removed entirely | drop `voucher_returned` status + accountant return action + voucher returned fields |
| 4 | Treasurer hold/re-evaluate removed | drop `on_hold` status entirely |

Also inherited from prior agreements:
- Remove the unused `screening` **status** (never assigned by any controller).
- Rename AICS "Screened" tab key `screening` → `forwarded` (it actually filters `mswdo_review`).
- Keep the `aics_screening` **review stage** (still written by AICS approve/return).
- Leave `.ai/*` documentation otherwise untouched except this file.

---

## 2. Target role model

`users.role` enum becomes:

```
admin, aics_staff, mswdo, accountant, treasurer, internal_audit, budget_officer
```

Removed: `mayors_office`. Added: `internal_audit`, `budget_officer`.

Role → panel mapping:
- `internal_audit` → `/internal-audit/*` (Coding Review)
- `budget_officer` → `/budget-office/*` (Vouchers / budget check)

`RoleMiddleware` (app/Http/Middleware/RoleMiddleware.php) compares the raw role string —
no code change needed.

---

## 3. Target status model

`applications.status` enum (final):

```
submitted
returned_to_applicant
mswdo_review
social_case_study_uploaded        (legacy, never assigned — kept for compat)
assistance_coding
internal_audit_review             (NEW)
returned_assistance_coding        (NEW)
voucher_creation
budget_checking                   (reused as Budget Office under-review)
voucher_on_hold                   (NEW)
voucher_recording                 (renamed from voucher_checking)
with_treasurer
cheque_ready
claimed
```

Removed: `screening`, `on_hold`, `voucher_checking` (renamed), `voucher_returned`.

### Transitions

```
submitted ──AICS approve──▶ mswdo_review
submitted ──AICS return──▶ returned_to_applicant
mswdo_review ──MSWDO approve + upload SCS──▶ assistance_coding
mswdo_review ──MSWDO return──▶ returned_to_applicant

assistance_coding ──AICS codes──▶ internal_audit_review
internal_audit_review ──Internal Audit approve──▶ voucher_creation
internal_audit_review ──Internal Audit return──▶ returned_assistance_coding
returned_assistance_coding ──AICS re-codes──▶ internal_audit_review

voucher_creation ──MSWDO creates voucher──▶ budget_checking
budget_checking ──Budget approve──▶ voucher_recording
budget_checking ──Budget hold──▶ voucher_on_hold
voucher_on_hold ──Budget release hold──▶ voucher_recording

voucher_recording ──Accountant records──▶ with_treasurer
with_treasurer ──Treasurer acknowledge──▶ cheque_ready
cheque_ready ──Treasurer mark complete──▶ claimed
```

> Voucher creation is ONLY possible from `voucher_creation` (i.e., after Internal Audit
> approval). MSWDO cannot create a voucher while an application is in
> `internal_audit_review` or `returned_assistance_coding`.

### Review stages (`reviews.stage`, final)

```
aics_screening, mswdo_review, assistance_coding, internal_audit_review (NEW),
voucher_creation, budget_checking, voucher_recording (renamed from
accountant_review; voucher_checking dropped), treasurer_acknowledgment, treasurer_review
```

### Review decisions

- Internal Audit: `approved` / `returned`
- Budget Office: `approved` / `hold` / `approved` (release)
- Accountant: `approved` (record)
- Treasurer: `approved` (acknowledge), `approved` (claim)
- `mayors_approval` stage label removed from frontend (never existed in DB).

---

## 4. Implementation checklist

Best order: **write schema migration → rewrite backend → rewrite frontend → apply schema →
reseed → build/cache → verify**. This avoids running the migration while old code still
writes removed statuses (`voucher_checking`, `voucher_returned`, `on_hold`, `screening`).

### Phase 0 — Preparation

- [x] Confirm `.env` is `APP_ENV=local`, `APP_DEBUG=true`, `SMS_DRIVER=log`.
- [x] Run `php artisan optimize:clear` to clear config/route/view caches.
- [x] Take a DB dump (dev): PHP backup script → `alalay-backup-2026-08-01-055417.sql` (22 tables, 735 inserts).
- [x] Baseline tests: `php artisan test` → `SmsServiceTest` (3) + `TrackOtpTest` (11) pass;
      `AuthTest` had 2 failures unrelated to the refactor (23 passed / 2 failed) — since fixed (see Phase 9).

### Phase 1 — Schema migration (write only, do NOT run yet)

Create **one** migration `database/migrations/2026_08_01_000001_workflow_policy_changes.php`
following the existing MySQL-only `MODIFY COLUMN` guard pattern
(see `database/migrations/2026_07_08_000001_add_review_stages_to_reviews.php`). In `up()`,
guarded by `DB::getDriverName() !== 'mysql'`:

- [x] **Data backfill (before enum drops):**
      - [x] `UPDATE applications SET status='voucher_on_hold' WHERE status='on_hold';`
      - [x] `UPDATE applications SET status='voucher_recording' WHERE status='voucher_checking';`
      - [x] `UPDATE applications SET status='budget_checking' WHERE status='voucher_returned';` (dev only; reseeded anyway)
      - [x] Same three renames applied to `reviews.from_status` and `reviews.to_status`.
      - [x] `UPDATE reviews SET stage='voucher_recording' WHERE stage IN ('voucher_checking','accountant_review');`
      - [x] `UPDATE users SET role='admin', status='inactive' WHERE role='mayors_office';` — found 1 demo "Mayor Office" account (laureanopen0@gmail.com); disabled it instead of granting active admin.
- [x] **`users.role`:** MODIFY ENUM → `admin,aics_staff,mswdo,accountant,treasurer,internal_audit,budget_officer`.
- [x] **`applications.status`:** MODIFY ENUM → final list in §3 (order matters, keep `NOT NULL DEFAULT 'submitted'`).
- [x] **`reviews.from_status` / `reviews.to_status`:** MODIFY ENUM → same final list.
- [x] **`reviews.stage`:** MODIFY ENUM → final stage list in §3.
- [x] **`vouchers`:** drop columns `returned_at`, `returned_by`, `adjustment_remarks` (FK `vouchers_returned_by_foreign` dropped first).
- [x] **`down()`** restores previous enums/columns (reverse; restore `screening`/`on_hold`/`voucher_returned`/`voucher_checking`/`mayors_office`).

> Do **not** run `migrate` yet — code must be updated first.

### Phase 2 — Roles & auth (backend)

- [x] `app/Http/Requests/Admin/StoreUserRequest.php:36` — role rule → `in:admin,aics_staff,mswdo,accountant,treasurer,internal_audit,budget_officer`.
- [x] `app/Http/Requests/Admin/UpdateUserRequest.php:38` — same role rule update.
- [x] `database/factories/UserFactory.php` — delete `mayorsOffice()` (78-83); add `internalAudit()` and `budgetOfficer()` states.
- [x] `app/Http/Controllers/DashboardController.php:13-21` — role→route map: add `internal_audit`→`internal-audit.dashboard`, `budget_officer`→`budget-office.dashboard`; drop `mayors_office`.
- [x] `app/Http/Controllers/PendingCountController.php`:
      - [x] `aics_staff` (18): `where('status','submitted')`.
      - [x] `mswdo` (21-22): drop `screening`; vouchers count → `where('status','voucher_creation')`.
      - [x] `accountant` (25): `where('status','voucher_recording')`.
      - [x] `treasurer` (28): unchanged (`with_treasurer`).
      - [x] Remove `mayors_office` arm (30-32).
      - [x] Add `internal_audit` arm: count `internal_audit_review`.
      - [x] Add `budget_officer` arm: count `['budget_checking','voucher_on_hold']`.
      - [x] `admin` (34): `where('status','submitted')`.
- [x] `app/Http/Controllers/Admin/AuditLogController.php:43,54` — role-label map: drop `mayors_office`, add `internal_audit`→`Internal Audit`, `budget_officer`→`Budget Office`.
- [x] `app/Http/Controllers/Public/ApplicationController.php:175-182` — same role-label map update (drives "returned by" label on public Track).

### Phase 3 — Policies

- [x] `app/Policies/ApplicationPolicy.php`:
      - [x] `viewAny`/`view` (12, 17) → `['aics_staff','mswdo','accountant','treasurer','internal_audit','budget_officer']`.
      - [x] Add `reviewCoding(User, Application)` → `role === 'internal_audit' && status === 'internal_audit_review'`.
- [x] `app/Policies/VoucherPolicy.php`:
      - [x] `viewAny`/`view` (12, 17) → `['mswdo','accountant','treasurer','budget_officer']`.
      - [x] Remove `returnVoucher` (30-33).
      - [x] Keep `create` (mswdo), `approve` (accountant — now "record"), `acknowledge` (treasurer).
      - [x] Remove treasurer-only `markReady`/`hold`/`reEvaluate` (40-53).
      - [x] Add `budgetApprove`, `budgetHold`, `budgetRelease` (all `budget_officer`).
- [x] `app/Policies/AssistanceCodePolicy.php` — `viewAny`/`view` (12, 17) → `['aics_staff','mswdo','accountant','treasurer','internal_audit','budget_officer']`.
- [x] `app/Policies/SocialCaseStudyPolicy.php` — update role lists (no `mayors_office` reference present → no change).

### Phase 4 — Controllers

**New controllers**

- [x] Create `app/Http/Controllers/InternalAudit/CodingReviewController.php`:
      - [x] `index/poll/export`: pending tab = `where('status','internal_audit_review')`; audited tab = `voucher_creation`.
      - [x] `show`: application + assistanceCode + reviews + socialCaseStudy + signed URLs.
      - [x] `approve`: guard `status === 'internal_audit_review'`; set `status='voucher_creation'`; Review `stage='internal_audit_review'`, `decision='approved'`, `from='internal_audit_review'`, `to='voucher_creation'`.
      - [x] `return`: set `status='returned_assistance_coding'`; Review `decision='returned'`, `to='returned_assistance_coding'`.
- [x] Create `app/Http/Controllers/InternalAudit/DashboardController.php` + `AnalyticsController.php`.
- [x] Create `app/Http/Controllers/BudgetOffice/VoucherController.php` — migrated orphaned `Treasurer/BudgetController.php` shape:
      - [x] Tabs: default `budget_checking`, `voucher_on_hold`.
      - [x] `approve`: `budget_checking → voucher_recording`; Review `stage='budget_checking'`, `decision='approved'`.
      - [x] `hold`: `budget_checking → voucher_on_hold`; Review `decision='hold'`, `to='voucher_on_hold'`.
      - [x] `releaseHold`: `voucher_on_hold → voucher_recording`; Review `decision='approved'`, `from='voucher_on_hold'`.
      - [x] No SMS on hold; keep `SendSmsJob` `cheque_ready` only on treasurer acknowledge.
- [x] Create `app/Http/Controllers/BudgetOffice/DashboardController.php` + `AnalyticsController.php`.

**Existing controllers**

- [x] `Aics/ApplicationController.php`:
      - [x] Tab keys `'screening'` → `'forwarded'` (44, 93, 153).
      - [x] Pending filter (46, 95, 155): `whereIn(['submitted','screening'])` → `where('status','submitted')`.
      - [x] Approve/return guards (268, 303): → `$application->status !== 'submitted'`.
- [x] `Aics/AssistanceCodeController.php`:
      - [x] Pending tab (44-47, 93-96, 155-158): `whereIn(['assistance_coding','returned_assistance_coding'])`.
      - [x] `coded` tab → `where('status','internal_audit_review')`.
      - [x] `store` (292): guard `in_array($status, ['assistance_coding','returned_assistance_coding'])`; set `status='internal_audit_review'` (307-311); Review `to_status='internal_audit_review'`, dynamic `from_status`. Also upserts existing `AssistanceCode` on re-code (update instead of duplicate).
- [x] `Mswdo/VoucherController.php`:
      - [x] `completed` tab (48, 96, 155): `voucher_checking` → `budget_checking`.
      - [x] `to_create` (49, 97, 156): → `['voucher_creation']`.
      - [x] `show` `canEdit` (237): → `['voucher_creation']`; removed `returned_at`/`returned_by`/`adjustment_remarks` passthrough.
      - [x] `store` (283): guard `status === 'voucher_creation'`; set `status='budget_checking'` (312); Review `to_status='budget_checking'` (323); dropped `adjustment_remarks` write (308) and review remarks.
      - [x] Remove voucher-url route behavior if unused (kept `voucherUrl` — harmless, authorized via `VoucherPolicy::view`).
- [x] `Accountant/VoucherController.php`:
      - [x] Replace `voucher_checking` → `voucher_recording` (50, 111, 162, 277, 292, 311, 332).
      - [x] Remove `returned` tab (49, 110, 161) and `return()` action (305-343).
      - [x] `approve` (272-303): `voucher_recording → with_treasurer` (keep), stage writes `voucher_recording`.
- [x] `Treasurer/ChequeController.php`:
      - [x] Remove `hold` tab (49, 110, 161); removed `hold()` (305-337) and `reEvaluate()` (339-369).
      - [x] Keep `acknowledge`; added guard `status === 'cheque_ready'` to `claim`.
- [x] Dashboards/analytics status lists:
      - [x] `Mswdo/DashboardController.php` (26-27): `pending_voucher_creation`→`['voucher_creation']`; `vouchers_created_today`→`budget_checking`.
      - [x] `Aics/DashboardController.php` (22-23): `pending_coding`→`['assistance_coding','returned_assistance_coding']`; `coded_today`→`internal_audit_review`.
      - [x] `Accountant/DashboardController.php` (19, 22, 32): `pending_vouchers`→`voucher_recording`; dropped `returned_today`, `voucher_returned`, and `returned_at` passthrough; `voucher_statuses` renamed.
      - [x] `Mswdo/AnalyticsController.php` (30-32), `Treasurer/AnalyticsController.php:24`, `Treasurer/DashboardController.php:16,24,27`: dropped `on_hold`/`voucher_returned`; renamed `voucher_checking`.
- [x] Delete `app/Http/Controllers/MayorsOffice/*` (DashboardController, AnalyticsController).
- [x] Delete `app/Http/Controllers/Treasurer/BudgetController.php` (after migration to BudgetOffice).
- [x] Delete `app/Http/Requests/Accountant/ReturnVoucherRequest.php` and `app/Http/Requests/Treasurer/HoldChequeRequest.php`.
- [x] `Mswdo/CreateVoucherRequest.php`: removed `adjustment_remarks` rule (26) + `prepareForValidation` (14-19).

### Phase 5 — Routes (`routes/web.php`)

- [x] Remove `MayorsOffice` imports (36-37) + group (189-192).
- [x] Add `internal-audit` group (`role:internal_audit`, prefix `internal-audit`, name `internal-audit.`): dashboard, analytics, applications index/export/poll/show, `applications/{application}/approve`, `/return`.
- [x] Add `budget-office` group (`role:budget_officer`, prefix `budget-office`, name `budget-office.`): dashboard, analytics, vouchers index/export/poll/show, `vouchers/{voucher}/approve`, `/hold`, `/release-hold`.
- [x] Treasurer group: remove `hold` (183) + `re-evaluate` (184) routes.
- [x] Accountant group: remove `return` route (171).
- [x] Verified via `php artisan route:list`: new groups register; `php -l` clean on all changed files; `rg` audit clean for removed statuses/classes in `app/ routes/ database/`.

### Phase 6 — Frontend

**Shared utils/composables/layout**

- [x] `resources/js/Utils/constants.js` — `APPLICATION_STATUSES` → final §3 list; `ROLES` → drop `mayors_office`, add `internal_audit`/`budget_officer`.
- [x] `resources/js/Utils/statusLabels.js` — drop `screening`, `voucher_returned`, `on_hold`; rename `voucher_checking`→`voucher_recording`; add `internal_audit_review`, `returned_assistance_coding`, `voucher_on_hold`.
- [x] `resources/js/Utils/roleLabel.js` + `severityMappings.js` — new roles, drop `mayors_office`.
- [x] `resources/js/Composables/useAuth.js` — drop `isMayorsOffice` (16); add `isInternalAudit`, `isBudgetOfficer`.
- [x] `resources/js/Layouts/AppMenu.vue` — drop `mayors_office` block (65-67) + label chain (75); add `internal_audit` (Analytics, Coding Review) + `budget_officer` (Analytics, Vouchers) blocks.

**Admin**

- [x] `resources/js/Pages/Admin/Users/Create.vue:46` — role options.
- [x] `resources/js/Pages/Admin/Users/Edit.vue:50` — role options.
- [x] `resources/js/Pages/Admin/Users/Index.vue:56,91` — role filter + labels.

**New panels**

- [x] Create `resources/js/Pages/InternalAudit/CodingReview/Index.vue`.
- [x] Create `resources/js/Pages/InternalAudit/CodingReview/Review.vue`.
- [x] Create `resources/js/Pages/BudgetOffice/Vouchers/Index.vue` (reuse Treasurer/Budget page as base).
- [x] Create `resources/js/Pages/BudgetOffice/Vouchers/Check.vue` (reuse Treasurer/Budget page as base).
- [x] Also created `InternalAudit/Dashboard.vue`, `InternalAudit/Analytics.vue`, `BudgetOffice/Dashboard.vue`, `BudgetOffice/Analytics.vue` (required by new dashboard/analytics routes).

**Role pages**

- [x] `Aics/Applications/Index.vue` — tab keys/lists `'screening'`→`'forwarded'` (53, 107); header "Screened"→"Forwarded" (217); empty-state text (245).
- [x] `Aics/Applications/Review.vue:42` — `canReview` → `['submitted']`.
- [x] `Aics/AssistanceCodes/Index.vue` + `Code.vue` — pending includes `returned_assistance_coding`; show returned/audit-pending states; allow re-code.
- [x] `Mswdo/Vouchers/Index.vue` + `Create.vue` — status labels; remove `adjustment_remarks`/returned UI; button/success text.
- [x] `Accountant/Vouchers/Index.vue` + `Review.vue` — `voucher_recording` labels; remove "Return" button.
- [x] `Treasurer/Cheques/Index.vue` + `Review.vue` — remove hold/re-evaluate buttons + hold tab.
- [x] `Accountant/Dashboard.vue` — drop `returned` KPI + `returned_at` column; `voucher_statuses` labels `voucher_recording`/`with_treasurer`.
- [x] `Treasurer/Dashboard.vue` + `Treasurer/Analytics.vue` — drop `on_hold` chart entries/KPI cards (statuses now `with_treasurer`/`cheque_ready`/`claimed`).

**Public**

- [x] `resources/js/Pages/Public/Track.vue` — `stageLabels` (180-188): drop `mayors_approval`, add `internal_audit_review`; `statusConfig` (204-219): drop `screening`, `voucher_returned`, `on_hold`, rename `voucher_checking`, add `internal_audit_review`, `returned_assistance_coding`, `voucher_on_hold`.
- [x] `resources/js/Components/Application/ReviewTrail.vue:25-56` — stage/decision labels matching new stages.
- [x] `resources/js/locales/en.json` + `fil.json` — drop `stage.mayors_approval`; add/update keys for `internal_audit_review`, `returned_assistance_coding`, `voucher_on_hold`, `voucher_recording`, `budget_checking`; drop removed keys.

**Delete**

- [x] Delete `resources/js/Pages/MayorsOffice/*` (Dashboard.vue, Analytics.vue).
- [x] Also deleted dead `resources/js/Pages/Treasurer/Budget/*` (Index.vue, Check.vue).

### Phase 7 — Apply schema, reseed, build

- [x] `php artisan migrate` (runs backfills + enum changes + voucher column drops).
- [x] Update `database/seeders/ApplicationDemoSeeder.php:39-44` statuses list to the final set (drop `voucher_checking`, `voucher_returned`, `on_hold`).
- [x] `php artisan db:seed --class=ApplicationDemoSeeder --force`.
- [x] Optionally extend `database/seeders/AdminSeeder.php` with demo users for `internal_audit` and `budget_officer`.
- [x] `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
- [x] `npm run build` (also regenerates `resources/js/ziggy.js` for new routes).

> **Phase 7 notes:** Config/route/view caches were generated to verify the new route groups, then
> cleared (`php artisan optimize:clear`) because cached config caused 419 CSRF errors in local
> testing — dev should stay cache-free. `resources/js/ziggy.js` is NOT used (routes are served via
> `@routes` at runtime in `resources/views/app.blade.php`); it was deleted rather than regenerated,
> so the checkbox above applies only to `npm run build`.

### Phase 8 — Verification

- [x] `php artisan test` → `SmsServiceTest` + `TrackOtpTest` green; `AuthTest` 2 failures now fixed (see Phase 9).
- [x] Grep audit (expect zero in app/ + resources/js/):
      `grep -rnE "screening|mayors_office|mayors-office|on_hold|voucher_checking|voucher_returned|mayors_approval" app resources/js`
      — allowed leftover: `aics_screening` (stage).
- [x] Manual smoke test per role (admin, aics_staff, mswdo, internal_audit, budget_officer, accountant, treasurer): walk an application through every transition in §3.
- [x] Confirm MSWDO cannot create a voucher from `internal_audit_review` / `returned_assistance_coding`.

> **Phase 8 note (automated smoke test):** Smoke tests were run against a scratch MySQL DB
> (`alalay_test`, `migrate:fresh`), then removed. Verified: all new panel pages render 200 for
> `internal_audit` / `budget_officer`; MSWDO gets 403 on those routes; IA approve/return
> (`internal_audit_review` → `voucher_creation` / `returned_assistance_coding`); Budget
> approve/hold/release-hold (`budget_checking` → `voucher_recording` / `voucher_on_hold`);
> MSWDO voucher creation blocked before IA approval.
>
> **Bug found & fixed:** `BudgetOffice\VoucherController::hold()` writes review decision
> `'hold'`, which was missing from the `reviews.decision` enum. Added migration
> `2026_08_01_000002_add_hold_decision_to_reviews.php` (adds `'hold'`, keeps legacy `'on_hold'`).
> Note: the enum migrations are MySQL-only (`if (DB::getDriverName() !== 'mysql') return;`),
> so the SQLite test DB can't represent the new roles/statuses — new-workflow feature tests
> must run against a MySQL scratch DB.
>
> Also deleted stale `resources/js/ziggy.js` (routes are served via `@routes` at runtime).

### Phase 9 — Post-refactor fixes (QA round)

Fix items discovered while verifying the refactor live, plus the pending auth test work.

**Dashboard / analytics data**

- [x] **Root cause of empty charts:** `database/seeders/ApplicationDemoSeeder.php:120` seeded all 30
      demo apps 8–60 days old, outside the 7-day/current-month default analytics windows. Changed
      `created_at` to `now()->subDays($i % 4 === 0 ? rand(8, 14) : rand(0, 7))->startOfDay()->addMinutes(rand(0, 1439))`
      so each chart window has data. Verified via HTTP partial-data: IA + Budget Office Dashboards
      and Analytics all return populated series.
- [x] **`Accountant/AnalyticsController.php` SQLSTATE 42S22 `assistance_codes.amount`:**
      `amount_trend` was built from `$approvedVouchers` before the `assistance_codes` join. Added
      `->join('assistance_codes', 'vouchers.assistance_code_id', '=', 'assistance_codes.id')` to the
      amount_trend clone. `category_amount` and the other role analytics already joined correctly.

**Auth**

- [x] **Login error handling** (`app/Http/Controllers/Auth/LoginController.php`): replaced the
      non-standard `Inertia::render(..., ['errors' => ...])` 200 response (which never wrote session
      errors) with the standard Laravel/Inertia `back()->withErrors([...])->withInput(...)`. Inertia's
      `HandleInertiaRequests` shares session errors to `props.errors`, which `Auth/Login.vue` already
      reads — UI behavior unchanged. This fixed the last 2 `AuthTest` failures
      (`login with invalid credentials fails`, `deactivated user cannot login`).
- [x] **OTP resend 500** (`app/Http/Controllers/Auth/OtpChallengeController.php`): `ReflectionException
      "Class App\Http\Controllers\Auth\Request does not exist"` — added missing
      `use Illuminate\Http\Request;`.

**Accountant review page**

- [x] `Accountant/VoucherController.php::show()` eager-loads `documents.requiredDocument` +
      `socialCaseStudy`, builds `documents` with `signed_url` (SignedUrlService), passes `documents`
      + `socialCaseStudy` props, enriches `assistanceCode` (description, default_amount, assigned_at).
- [x] `resources/js/Pages/Accountant/Vouchers/Review.vue` rewritten: Documents (thumbnail grid +
      viewDocument/viewVoucher/viewScs + DocumentViewer prev/next), Assistance Code, Social Case
      Study, Voucher sections — same h3 style as BO/IA.

**Voucher policy 403s**

- [x] `app/Policies/VoucherPolicy.php`: `approve()` and `acknowledge()` made `?Voucher $voucher`
      (Gate couldn't resolve the policy when the route param was null, causing 403).
- [x] Call sites use `$voucher ?? new \App\Models\Voucher`: `Accountant/VoucherController.php:296`
      and `Treasurer/ChequeController.php:269`. Verified live: Accountant approve
      `voucher_recording → with_treasurer` now 302 (was 403).
- [x] Policies registration verified: all 10 policies registered in `app/Providers/AppServiceProvider.php`.

**Frontend reactivity bug (pagination)**

- [x] `const total = props.applications?.total ?? 0` was a non-reactive snapshot. With `Deferred`
      props (resolved in-place, no component remount), `total` stayed 0 so the `Paginator` never
      rendered even with >10 rows. Fixed → `computed(() => props.applications?.total ?? 0)` in:
      `InternalAudit/CodingReview/Index.vue:48`, `BudgetOffice/Vouchers/Index.vue:35`,
      `Aics/Applications/Index.vue:48`, `Aics/AssistanceCodes/Index.vue:48`,
      `Mswdo/Applications/Index.vue:48`, `Mswdo/Vouchers/Index.vue:48`.
- [x] Audited all other `props.` reads across pages — remaining ones are inside `computed()` or
      event handlers (safe). `tabIndex` non-reactivity is harmless (tab changes remount).

**Final verification**

- [x] `php artisan test` → **25 passed (108 assertions)**, zero failures.
- [x] `npm run build` clean (only pre-existing chunk-size warning).

**Deployment prep (hosting + perf), client-approved scope: Parts 1, 2, 3, 5 (Redis deferred)**

- [x] **Hosting: Railway** (not Render). App hard-requires MySQL: 5 migrations guard on
      `DB::getDriverName() !== 'mysql'` and use `ALTER TABLE ... MODIFY COLUMN ... ENUM(...)`
      (e.g. `2026_08_01_000001_workflow_policy_changes.php:26`). Render has no managed MySQL;
      Railway provides a MySQL plugin + Railpack auto-detects Laravel (FrankenPHP, docroot `public/`).
- [x] **Railway deploy files**: `railway/init.sh` (build: `composer install --no-dev --optimize-autoloader`,
      `npm ci`, `npm run build`), `railway/worker.sh` (`queue:work database --sleep=3 --tries=3
      --max-time=3600`), `railway/cron.sh` (60s `schedule:run` loop), `railway.json` (builder RAILPACK;
      buildCommand chmod+sh init.sh; preDeployCommand `config:cache`, `event:cache`, `route:cache`,
      `view:cache`, `migrate --force`; healthcheck `/up`). Artisan caches run in **preDeploy (runtime)**
      not build, because a custom buildCommand bypasses Railpack's auto-run and build-time env is not
      the production env.
- [x] **`php.ini`** (repo root, picked up by FrankenPHP): opcache enabled (validate_timestamps=0,
      20000 files), display_errors=Off, expose_php=Off, memory_limit 256M, upload limits 20M/24M,
      date.timezone Asia/Manila. No persistent volume needed — all file storage is on the `supabase`
      disk (signed URLs), no app writes to `public`/`storage`.
- [x] **Bundle splitting VERDICT: revert.** Verified `primevue/chart` already lazy-loads
      `chart.js/auto` via dynamic `import()` (`node_modules/primevue/chart/Chart.vue:45`), and pdfjs
      is already lazy via `usePdfThumbnail.js`'s `await import('pdfjs-dist')`. Vite already code-splits
      per-route (AppLayout + every page chunk are lazy). Forcing `manualChunks` made it **worse**:
      eager closure 542.7 kB (app+inertia+utils) vs 496.4 kB original single `app` chunk — it only
      added chunk-boundary glue and lost shared-module dedup. Same lesson as the earlier primevue
      group (915 kB forced eager). `vite.config.js` stays at HEAD; no manualChunks.
- [x] Noted but deferred: composer advisories (guzzle 7.13.1 high; phpspreadsheet via
      maatwebsite/excel 3.1.69) and npm advisories (pdfjs-dist, nanoid, dompurify) — fix in a
      dedicated dependency pass.

### Phase 10 — Production / demo flip

- [ ] `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `SMS_DRIVER=philsms`.
- [ ] `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
- [ ] `npm run build` (Vite dev on 5173 is blocked by CSP in production).
- [ ] Confirm PhilSMS sender ID stays `PhilSMS` (account not authorized for `ALALAY`).

---

## 5. Status ↔ frontend label table (final)

| Status | Label (en) |
|--------|-----------|
| submitted | Submitted |
| returned_to_applicant | Returned |
| mswdo_review | MSWDO Review |
| social_case_study_uploaded | Case Study Uploaded |
| assistance_coding | Assistance Coding |
| internal_audit_review | Internal Audit Review |
| returned_assistance_coding | Returned for Coding |
| voucher_creation | Voucher Creation |
| budget_checking | Budget Checking |
| voucher_on_hold | Voucher On Hold |
| voucher_recording | Voucher Recording |
| with_treasurer | With Treasurer |
| cheque_ready | Cheque Ready |
| claimed | Claimed |

---

## 6. Rollback notes

- The migration is destructive for `vouchers.returned_*` / `adjustment_remarks` columns —
  keep a pre-change DB dump (Phase 0) before running.
- `down()` restores enums and columns but NOT lost row data for dropped statuses; reseed
  `ApplicationDemoSeeder` to regenerate demo data if needed.

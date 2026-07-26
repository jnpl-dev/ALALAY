# ALALAY: Dashboard & Analytics — Delta Build Process

Reference: `DASHBOARD_ANALYTICS.md` (spec)

---

## Current State (After)

| Aspect | Status |
|--------|--------|
| **10 controllers** | All rewritten per spec — no `Cache::remember`, `applications.created_at` qualified in joins |
| **Admin/Dashboard.vue** | Custom — 3 KPIs, users_by_role doughnut, 2 activity tables |
| **Admin/Analytics.vue** | Custom — date-filtered, 4 KPIs + 5 charts |
| **Aics/Dashboard.vue** | Rewritten — 4 KPIs, 4 charts, recent DataTable |
| **Aics/Analytics.vue** | Rewritten — date-filtered, 5 KPIs, 4 charts |
| **Mswdo/Dashboard.vue** | Created — 4 KPIs, 4 charts, recent DataTable |
| **Mswdo/Analytics.vue** | Rewritten — date-filtered, 4 KPIs, 4 charts |
| **Accountant/Dashboard.vue** | Created — 3 KPIs, 3 charts (amount-based), recent vouchers DataTable |
| **Accountant/Analytics.vue** | Rewritten — date-filtered, 5 KPIs, 3 charts (amount-based) |
| **Treasurer/Dashboard.vue** | Created — 3 KPIs, 3 charts (multi-line weekly status), recent DataTable |
| **Treasurer/Analytics.vue** | Rewritten — date-filtered, 5 KPIs, 4 charts |
| **MayorsOffice/Dashboard.vue** | Created — 8 KPIs (2 rows), 5 charts, 8-row recent DataTable |
| **MayorsOffice/Analytics.vue** | Rewritten — date-filtered, 8 KPIs (4-per-row grid), 6 charts incl. pipeline bottleneck |
| **AppKpiCard.vue** | Reused across all 12 pages |
| **Chart wrappers** | Not used; `Chart` registered globally in `app.js` via `chart.js` directly |
| **AppDateRangeFilter.vue** | Created, used on all 6 analytics pages with `preserveState` |
| **chartColors.js** | Created — emerald-based palette (`#059669`) with gold accent |
| **chartDates.js** | Created — `fillMissingDates()`, `generateWeekLabels()` |
| **Cache** | Removed from all dashboard/analytics controllers; `HasPollCache`, `ApplicationController` cache invalidation stripped |
| **beneficiary_barangay** | Column added via migration, backfilled, saved on submission, used in barangay charts |
| **Account Settings** | Change photo works; avatar clickable; View/Change Photo toggle with edit mode |
| **Chart tooltips** | Horizontal bar charts use `interaction: { intersect: false, mode: 'y' }` for row-level hover |

---

## Phase A — Shared Infrastructure

- [x] **A.1** Create `resources/js/Utils/chartColors.js` — emerald-based `CHART_COLORS` with tints, `paletteColors`, `categoryColors`
- [x] **A.2** Create `resources/js/Utils/chartDates.js` — `generateWeekLabels()`, `fillMissingDates(data, dateFrom, dateTo)`
- [x] **A.3** Create `resources/js/Components/Common/AppDateRangeFilter.vue` — PrimeVue DatePicker from/to, emits `apply` / `clear`

---

## Phase B — Controller Rewrites

All analytics controllers: no caching, `applications.created_at` qualified in JOIN queries.

- [x] **B.1** Rewrite `Admin/DashboardController@index` — KPIs: total_users, active_users, inactive_users. Chart: users_by_role doughnut. Tables: recent_activity (5), unusual_activity (5)
- [x] **B.2** Rewrite `Admin/AnalyticsController@index` — date-filtered, 4 KPIs, 5 charts (trend line, status doughnut, disbursement bar, user reg line, role doughnut)
- [x] **B.3** Rewrite `Aics/DashboardController@index` — 4 KPIs (pending_applications, screened_today, pending_coding, coded_today), 4 charts (weekly trend line, categories doughnut, submission type doughnut, barangay horizontal bar top 10), recent DataTable
- [x] **B.4** Rewrite `Aics/AnalyticsController@index` — date-filtered, 5 KPIs, 4 charts (trend with fillMissingDates, categories doughnut, submission type doughnut, barangay horizontal bar)
- [x] **B.5** Rewrite `Mswdo/DashboardController@index` — 4 KPIs, 4 charts, recent DataTable
- [x] **B.6** Rewrite `Mswdo/AnalyticsController@index` — date-filtered, 4 KPIs ("For Review", "Processed", "Total Applications", "Average per Day"), 4 charts
- [x] **B.7** Rewrite `Accountant/DashboardController@index` — 3 KPIs (pending_vouchers, approved_today, returned_today), 3 charts (voucher trend line, status doughnut via Application statuses, amount by category vertical bar), recent vouchers DataTable
- [x] **B.8** Rewrite `Accountant/AnalyticsController@index` — date-filtered, 5 KPIs, 3 charts (voucher volume line, approved amount over time vertical bar, approved amount by category vertical bar)
- [x] **B.9** Rewrite `Treasurer/DashboardController@index` — 3 KPIs (pending_cheques, ready_today, on_hold_today), 3 charts (multi-line weekly status trend, status doughnut, amount by category vertical bar), recent DataTable
- [x] **B.10** Rewrite `Treasurer/AnalyticsController@index` — date-filtered, 5 KPIs, 4 charts (multi-line status trend, status doughnut, amount by category vertical bar, disbursement over time line)
- [x] **B.11** Rewrite `MayorsOffice/DashboardController@index` — 8 KPIs (2 rows: 4 today + 4 monthly), 5 charts (daily volume bar, pipeline horizontal bar with 9 color-coded stage groups, categories doughnut, barangay horizontal bar top 10, amount by category vertical bar), 8-row recent DataTable
- [x] **B.12** Rewrite `MayorsOffice/AnalyticsController@index` — date-filtered, 8 KPIs (incl. approval_rate %, avg_days_to_cheque_ready via Review JOIN), 6 charts (trend line, categories doughnut, submission type doughnut, barangay horizontal bar, monthly disbursement bar, pipeline bottleneck horizontal bar)

---

## Phase C — Vue Page Builds

### Admin Panel

- [x] **C.1** Create `Admin/Dashboard.vue` — 3 KPI cards, users_by_role doughnut, recent + unusual activity tables, breadcrumb
- [x] **C.2** Rewrite `Admin/Analytics.vue` — AppDateRangeFilter, 4 KPI cards, 5 charts

### AICS Panel

- [x] **C.3** Rewrite `Aics/Dashboard.vue` — 4 KPI cards, 4 charts (weekly trend line, categories doughnut, submission type doughnut, barangay horizontal bar), recent DataTable
- [x] **C.4** Rewrite `Aics/Analytics.vue` — date filter, 5 KPI flex row, 4 charts

### MSWDO Panel

- [x] **C.5** Create `Mswdo/Dashboard.vue` — 4 KPI cards, 4 charts, recent DataTable
- [x] **C.6** Rewrite `Mswdo/Analytics.vue` — 4 KPI cards, date filter, 4 charts

### Accountant Panel

- [x] **C.7** Create `Accountant/Dashboard.vue` — 3 KPI cards, 3 charts (amount by category), recent vouchers DataTable
- [x] **C.8** Rewrite `Accountant/Analytics.vue` — 5 KPI flex row, date filter, 3 amount-focused charts

### Treasurer Panel

- [x] **C.9** Create `Treasurer/Dashboard.vue` — 3 KPI cards, 3 charts (multi-line weekly status), recent DataTable
- [x] **C.10** Rewrite `Treasurer/Analytics.vue` — 5 KPI flex row, date filter, 4 charts

### Mayor's Office Panel

- [x] **C.11** Create `MayorsOffice/Dashboard.vue` — 8 KPI cards (2 rows), 5 charts, 8-row recent DataTable
- [x] **C.12** Rewrite `MayorsOffice/Analytics.vue` — 8 KPI cards (4-per-row grid), date filter, 6 charts incl. pipeline bottleneck

---

## Phase D — Verification

- [x] **D.1** All Vue page paths match `Inertia::render()` calls in controllers
- [x] **D.2** `npm run build` passes
- [x] **D.3** All KPIs render with correct values
- [x] **D.4** All charts render with emerald-based CHART_COLORS (no hardcoded hex)
- [x] **D.5** All amounts use `formatCurrency()` — ₱X,XXX.XX format
- [x] **D.6** All charts are responsive (`maintainAspectRatio: false` + `height`)
- [x] **D.7** Analytics default date range is current month on all panels (first-of-month to today)
- [x] **D.8** Date filter `preserveState: true` on all analytics pages

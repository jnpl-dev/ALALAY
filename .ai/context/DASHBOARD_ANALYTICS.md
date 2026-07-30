# ALALAY: Dashboard & Analytics Specification
**All Staff Panels — Data, Charts, and KPI Definitions**
**Municipality of General Mamerto Natividad, Nueva Ecija**

---

## Overview

This document defines every KPI, chart, table, and data query for the
Dashboard and Analytics pages across all five staff panels. All data
derives primarily from the `applications.status` field, supplemented
by `vouchers`, `assistance_codes`, `reviews`, and `sms_notifications`.

**Key design decisions:**
- Dashboard shows today's snapshot + this week's trends
- Analytics shows date-filtered general view (default: this month)
- All charts use PrimeVue Chart (Chart.js wrapper)
- All KPI counts are real-time (no heavy caching on dashboard)
- Analytics queries are cached for 15 minutes (Redis)
- Desktop-first but mobile-friendly layout

---

## Database Notes — No Migration Needed

All data needed for dashboards and analytics already exists in the
current schema. The `applications.status` field is the primary indicator.
Here is how each status maps to each role's data needs:

```
submitted                → AICS: pending applications
mswdo_review             → MSWDO: pending applications
                           AICS: screened (forwarded)
social_case_study_uploaded → MSWDO: approved (SCS uploaded)
assistance_coding        → AICS: pending assistance codes
voucher_creation         → MSWDO: pending voucher creation
voucher_returned         → MSWDO: pending voucher creation (re-create)
voucher_checking         → ACCOUNTANT: pending vouchers
with_treasurer           → TREASURER: pending cheques
                           ACCOUNTANT: approved vouchers
budget_checking          → ACCOUNTANT: budget pending
on_hold                  → TREASURER + ACCOUNTANT: on hold
cheque_ready             → TREASURER: cheque created
claimed                  → Terminal — all panels
```

For "today" KPIs: filter by `DATE(updated_at) = CURDATE()` and
the relevant status transition. For week trends: filter by
`created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)`.

For Accountant voucher data: use `vouchers.returned_at IS NULL`
for approved and `vouchers.returned_at IS NOT NULL` for returned.

---

## SECTION 1 — AICS Staff Panel

### 1.1 Dashboard Page
`resources/js/Pages/Aics/Dashboard.vue`
`app/Http/Controllers/Aics/DashboardController.php`

#### KPI Cards (4 cards, top row)

| KPI | Label | Query | Color |
|---|---|---|---|
| 1 | Pending Applications | `applications` where `status = 'submitted'` COUNT | Primary blue |
| 2 | Screened Today | `applications` where `status = 'mswdo_review'` AND `DATE(updated_at) = today` COUNT | Success green |
| 3 | Pending Assistance Coding | `applications` where `status = 'assistance_coding'` COUNT | Warning amber |
| 4 | Coded Today | `applications` where `status = 'voucher_creation'` AND `DATE(updated_at) = today` COUNT | Purple |

#### Charts (this week — last 7 days including today)

**Chart 1 — Weekly Application Trend (Line)**
```
X-axis: Day labels (Mon, Tue, Wed, Thu, Fri, Sat, Sun — current week)
Y-axis: Number of applications submitted
Query: applications grouped by DATE(created_at) for last 7 days
Dataset: single line — "Applications Submitted"
Color: --color-primary (#1B4F72)
```

**Chart 2 — Category Distribution (Doughnut)**
```
Segments: Medical Assistance, Hospital Assistance, Burial Assistance
Query: applications COUNT grouped by category_id for last 7 days
      joined with assistance_categories for name
Colors:
  Medical:  --color-primary (#1B4F72)
  Hospital: --color-primary-light (#2E86AB)
  Burial:   --color-accent (#E8A838)
```

**Chart 3 — Online vs Walk-in (Doughnut)**
```
Segments: Online, Walk-in
Query: applications COUNT grouped by submission_type for last 7 days
Colors:
  Online:   --color-primary (#1B4F72)
  Walk-in:  --color-text-muted (#9CA3AF)
```

**Chart 4 — Applications by Barangay (Horizontal Bar)**
```
Y-axis: Top 10 barangays by beneficiary_barangay (extracted from beneficiary address)
X-axis: Number of applications
Query: applications COUNT grouped by beneficiary_barangay for last 7 days
       ORDER BY count DESC LIMIT 10
Color: --color-primary-light (#2E86AB)
Note: beneficiary_barangay is a dedicated column in the applications table
      If stored as part of full address, extract via the barangay field
      stored separately (per schema: beneficiary_barangay column)
```

#### Recent Applications Table (5 rows)
```
Columns: Reference Code | Beneficiary Name | Category | Submission Type | Status | Date Submitted
Query: applications ORDER BY created_at DESC LIMIT 5
       with category eager loaded
Status column: AppStatusBadge component
Reference code: JetBrains Mono font, --color-primary
```

#### Controller Method
```php
public function index(): Response
{
    $today = today();
    $weekStart = now()->subDays(6)->startOfDay();

    return Inertia::render('Aics/Dashboard', [

        // KPI Cards
        'kpis' => [
            'pending_applications'    => Application::where('status', 'submitted')->count(),
            'screened_today'          => Application::where('status', 'mswdo_review')
                                            ->whereDate('updated_at', $today)->count(),
            'pending_coding'          => Application::where('status', 'assistance_coding')->count(),
            'coded_today'             => Application::where('status', 'voucher_creation')
                                            ->whereDate('updated_at', $today)->count(),
        ],

        // Weekly trend — last 7 days
        'weekly_trend' => Application::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', $weekStart)
            ->groupBy('date')
            ->orderBy('date')
            ->get(),

        // Category distribution — last 7 days
        'category_distribution' => Application::selectRaw(
                'assistance_categories.category_name, COUNT(*) as count'
            )
            ->join('assistance_categories', 'applications.category_id', '=', 'assistance_categories.id')
            ->where('applications.created_at', '>=', $weekStart)
            ->groupBy('assistance_categories.category_name')
            ->get(),

        // Submission type — last 7 days
        'submission_type_distribution' => Application::selectRaw(
                'submission_type, COUNT(*) as count'
            )
            ->where('created_at', '>=', $weekStart)
            ->groupBy('submission_type')
            ->get(),

        // Barangay distribution — top 10, last 7 days
        'barangay_distribution' => Application::selectRaw(
                'beneficiary_barangay, COUNT(*) as count'
            )
            ->where('created_at', '>=', $weekStart)
            ->whereNotNull('beneficiary_barangay')
            ->groupBy('beneficiary_barangay')
            ->orderByDesc('count')
            ->limit(10)
            ->get(),

        // Recent applications
        'recent_applications' => Application::with('category')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'reference_code', 'beneficiary_first_name',
                   'beneficiary_last_name', 'category_id',
                   'submission_type', 'status', 'created_at']),
    ]);
}
```

---

### 1.2 Analytics Page
`resources/js/Pages/Aics/Analytics.vue`
`app/Http/Controllers/Aics/AnalyticsController.php`

#### Date Filter
```
From date: DatePicker — default: first day of current month
To date:   DatePicker — default: today
Apply button triggers router.get() with preserveState: true
```

#### KPI Summary Cards (top row — filtered by date range)

| KPI | Label | Query |
|---|---|---|
| 1 | Total Applications | All applications within date range COUNT |
| 2 | Average per Day | Total ÷ number of days in range |
| 3 | Total Coded | assistance_codes COUNT joined to applications within date range |
| 4 | Total Assistance Amount | SUM of assistance_codes.amount within date range |
| 5 | Average Assistance Amount | AVG of assistance_codes.amount within date range |

#### Charts (all filtered by date range)

**Chart 1 — Application Trend (Line)**
```
X-axis: Dates within the range (daily if ≤ 31 days, weekly if > 31 days)
Y-axis: Number of applications submitted
Query: applications grouped by DATE(created_at)
```

**Chart 2 — Category Distribution (Doughnut)**
```
Same as dashboard but filtered by date range
```

**Chart 3 — Online vs Walk-in (Doughnut)**
```
Same as dashboard but filtered by date range
```

**Chart 4 — Applications by Barangay (Horizontal Bar)**
```
Top 10 barangays filtered by date range
```

#### Controller Method
```php
public function index(Request $request): Response
{
    $dateFrom = $request->date_from
        ? Carbon::parse($request->date_from)->startOfDay()
        : now()->startOfMonth();

    $dateTo = $request->date_to
        ? Carbon::parse($request->date_to)->endOfDay()
        : now()->endOfDay();

    $cacheKey = "analytics.aics.{$dateFrom->toDateString()}.{$dateTo->toDateString()}";

    $data = Cache::remember($cacheKey, 900, function () use ($dateFrom, $dateTo) {

        $base = Application::whereBetween('created_at', [$dateFrom, $dateTo]);
        $days = max(1, $dateFrom->diffInDays($dateTo) + 1);

        return [
            'total_applications' => (clone $base)->count(),
            'average_per_day'    => round((clone $base)->count() / $days, 1),
            'total_coded'        => AssistanceCode::whereHas('application',
                fn($q) => $q->whereBetween('created_at', [$dateFrom, $dateTo])
            )->count(),
            'total_amount'       => AssistanceCode::whereHas('application',
                fn($q) => $q->whereBetween('created_at', [$dateFrom, $dateTo])
            )->sum('amount'),
            'average_amount'     => AssistanceCode::whereHas('application',
                fn($q) => $q->whereBetween('created_at', [$dateFrom, $dateTo])
            )->avg('amount'),

            'trend' => (clone $base)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')->orderBy('date')->get(),

            'category_distribution' => (clone $base)
                ->selectRaw('assistance_categories.category_name, COUNT(*) as count')
                ->join('assistance_categories', 'applications.category_id', '=', 'assistance_categories.id')
                ->groupBy('assistance_categories.category_name')->get(),

            'submission_type' => (clone $base)
                ->selectRaw('submission_type, COUNT(*) as count')
                ->groupBy('submission_type')->get(),

            'barangay_distribution' => (clone $base)
                ->selectRaw('beneficiary_barangay, COUNT(*) as count')
                ->whereNotNull('beneficiary_barangay')
                ->groupBy('beneficiary_barangay')
                ->orderByDesc('count')->limit(10)->get(),
        ];
    });

    return Inertia::render('Aics/Analytics', array_merge($data, [
        'filters' => [
            'date_from' => $dateFrom->toDateString(),
            'date_to'   => $dateTo->toDateString(),
        ],
    ]));
}
```

---

## SECTION 2 — MSWDO Panel

### 2.1 Dashboard Page

#### KPI Cards (4 cards)

| KPI | Label | Query | Color |
|---|---|---|---|
| 1 | Pending Applications | `status = 'mswdo_review'` COUNT | Primary blue |
| 2 | Approved Today | `status = 'social_case_study_uploaded'` AND `DATE(updated_at) = today` COUNT | Success green |
| 3 | Pending Voucher Creation | `status IN ('voucher_creation', 'voucher_returned')` COUNT | Warning amber |
| 4 | Vouchers Created Today | `status = 'voucher_checking'` AND `DATE(updated_at) = today` COUNT | Purple |

#### Charts (this week — last 7 days)

Same four charts as AICS dashboard:
- Weekly application trend (line)
- Category distribution (doughnut)
- Online vs walk-in (doughnut)
- Barangay distribution (horizontal bar)

All filtered to applications that have passed through MSWDO
(`status NOT IN ('submitted')`) for the last 7 days.

#### Recent Applications Table (5 rows)
```
Columns: Reference Code | Beneficiary Name | Category | Status | Last Updated
Query: applications WHERE status IN ('mswdo_review', 'social_case_study_uploaded',
       'assistance_coding', 'voucher_creation', 'voucher_returned', 'voucher_checking')
       ORDER BY updated_at DESC LIMIT 5
```

---

### 2.2 Analytics Page

#### KPI Summary (filtered by date range)

| KPI | Label | Query |
|---|---|---|
| 1 | Total Applications | Applications within date range COUNT |
| 2 | Total Pending | Applications where status is before social_case_study_uploaded within range |
| 3 | Total Approved | Applications where status is at or after social_case_study_uploaded within range |
| 4 | Average per Day | Total ÷ days in range |

**Pending = statuses before approval:**
`submitted`, `mswdo_review`

**Approved = statuses after MSWDO action:**
`social_case_study_uploaded`, `assistance_coding`, `voucher_creation`,
`voucher_checking`, `voucher_returned`, `with_treasurer`,
`budget_checking`, `on_hold`, `cheque_ready`, `claimed`

#### Charts (filtered by date range)
- Application trend (line)
- Category distribution (doughnut)
- Online vs walk-in (doughnut)
- Barangay distribution (horizontal bar)

Same structure as AICS analytics, same date filter behavior.

---

## SECTION 3 — Accountant Panel

### 3.1 Dashboard Page

#### KPI Cards (3 cards)

| KPI | Label | Query | Color |
|---|---|---|---|
| 1 | Pending Vouchers | `status = 'voucher_checking'` COUNT | Primary blue |
| 2 | Approved Today | `status = 'with_treasurer'` AND `DATE(updated_at) = today` COUNT | Success green |
| 3 | Returned Today | Vouchers where `DATE(returned_at) = today` COUNT (vouchers table) | Danger red |

#### Charts (this week — last 7 days)

**Chart 1 — Weekly Voucher Trend (Line)**
```
X-axis: Day labels for last 7 days
Y-axis: Number of vouchers created
Query: vouchers grouped by DATE(created_at) for last 7 days
Dataset label: "Vouchers Created"
Color: --color-primary
```

**Chart 2 — Voucher Status Distribution (Doughnut)**
```
Segments: Pending (voucher_checking), Approved (with_treasurer and beyond),
          Returned (voucher_returned)
Query:
  Pending:  applications WHERE status = 'voucher_checking' COUNT
  Approved: vouchers WHERE returned_at IS NULL
            AND application status NOT IN ('voucher_checking', 'voucher_returned') COUNT
  Returned: vouchers WHERE returned_at IS NOT NULL COUNT
Colors:
  Pending:  --color-warning (#D97706)
  Approved: --color-success (#16A34A)
  Returned: --color-danger  (#DC2626)
```

**Chart 3 — Category Distribution of Vouchers (Doughnut)**
```
Segments: Medical, Hospital, Burial
Query: vouchers joined to applications joined to assistance_categories
       for last 7 days, grouped by category
Colors: same as other category charts
```

#### Recent Vouchers Table (5 rows)
```
Columns: Reference Code | Beneficiary Name | Category | Amount | Status | Date
Query: vouchers JOIN applications JOIN assistance_codes
       ORDER BY vouchers.created_at DESC LIMIT 5
Amount: JetBrains Mono, formatted as ₱X,XXX.XX
```

---

### 3.2 Analytics Page

#### KPI Summary (filtered by date range)

| KPI | Label | Query |
|---|---|---|
| 1 | Total Vouchers | vouchers COUNT within date range (created_at) |
| 2 | Total Approved | vouchers WHERE returned_at IS NULL COUNT within range |
| 3 | Total Returned | vouchers WHERE returned_at IS NOT NULL COUNT within range |
| 4 | Total Amount Approved | SUM assistance_codes.amount for approved vouchers within range |
| 5 | Average Amount | AVG assistance_codes.amount within range |

#### Charts (filtered by date range)
**Chart 1 — Voucher Trend (Line)**
```
X-axis: dates within range
Y-axis: vouchers created per day
Query: vouchers grouped by DATE(created_at)
```

**Chart 2 — Approved vs Returned Over Time (Bar — stacked)**
```
X-axis: dates within range
Y-axis: count
Dataset 1 (green): approved vouchers per day (returned_at IS NULL)
Dataset 2 (red):   returned vouchers per day (returned_at IS NOT NULL)
```

**Chart 3 — Category Distribution (Doughnut)**
```
Same as dashboard but filtered by date range
```

---

## SECTION 4 — Treasurer Panel

### 4.1 Dashboard Page

#### KPI Cards (3 cards)

| KPI | Label | Query | Color |
|---|---|---|---|
| 1 | Pending Cheques | `status = 'with_treasurer'` COUNT | Primary blue |
| 2 | Cheques Ready Today | `status = 'cheque_ready'` AND `DATE(updated_at) = today` COUNT | Success green |
| 3 | On Hold Today | `status = 'on_hold'` AND `DATE(updated_at) = today` COUNT | Warning amber |

#### Charts (this week — last 7 days)

**Chart 1 — Weekly Cheque Status Trend (Line — multi-dataset)**
```
X-axis: Day labels for last 7 days
Y-axis: Count
Dataset 1 (green):  cheque_ready per day (DATE(updated_at) grouping)
Dataset 2 (amber):  on_hold per day
Dataset 3 (blue):   with_treasurer per day (pending acknowledgment)
Query: applications WHERE status IN ('with_treasurer', 'cheque_ready', 'on_hold')
       grouped by DATE(updated_at) and status for last 7 days
```

**Chart 2 — Cheque Status Distribution (Doughnut)**
```
Segments:
  Pending acknowledgment: status = 'with_treasurer' COUNT
  Budget checking:        status = 'budget_checking' COUNT
  Cheque ready:           status = 'cheque_ready' COUNT
  On hold:                status = 'on_hold' COUNT
  Claimed:                status = 'claimed' — last 7 days
Colors:
  Pending:  --color-warning
  Budget:   --color-info (#2563EB)
  Ready:    --color-success
  On hold:  --color-text-muted
  Claimed:  --color-primary
```

**Chart 3 — Total Assistance Amount by Category (Bar)**
```
X-axis: Medical, Hospital, Burial
Y-axis: Total ₱ amount (SUM of assistance_codes.amount)
Query: assistance_codes JOIN applications JOIN assistance_categories
       WHERE applications.status IN ('cheque_ready', 'claimed')
       AND assistance_codes.created_at >= weekStart
       GROUP BY category
Color: --color-primary-light
```

#### Recent Cheques Table (5 rows)
```
Columns: Reference Code | Beneficiary Name | Category | Amount | Status | Date
Query: applications WHERE status IN ('with_treasurer', 'cheque_ready', 'on_hold', 'claimed')
       JOIN assistance_codes
       ORDER BY updated_at DESC LIMIT 5
```

---

### 4.2 Analytics Page

#### KPI Summary (filtered by date range)

| KPI | Label | Query |
|---|---|---|
| 1 | Total Processed | applications WHERE status reached with_treasurer or beyond within range |
| 2 | Total Cheque Ready | status = 'cheque_ready' within range |
| 3 | Total On Hold | status = 'on_hold' within range |
| 4 | Total Claimed | status = 'claimed' within range |
| 5 | Total Amount Disbursed | SUM assistance_codes.amount where status = 'claimed' within range |

#### Charts (filtered by date range)
- Cheque status trend over time (line — multi-dataset)
- Status distribution (doughnut)
- Amount disbursed by category (bar)
- Amount disbursed over time (line — financial view)

---

## SECTION 5 — Mayor's Office Panel

### 5.1 Dashboard Page

This is an executive summary. Shows today + this week.
No workflow actions. Read-only with maximum clarity.

#### KPI Cards (two rows)

**Row 1 — Volume (today)**

| KPI | Label | Query | Color |
|---|---|---|---|
| 1 | Applications Today | applications WHERE DATE(created_at) = today COUNT | Primary blue |
| 2 | Approved Today | status transitioned to social_case_study_uploaded today COUNT | Success green |
| 3 | Cheques Ready Today | status = cheque_ready AND DATE(updated_at) = today COUNT | Accent amber |
| 4 | Claimed Today | status = claimed AND DATE(updated_at) = today COUNT | Purple |

**Row 2 — Totals (all time / this month)**

| KPI | Label | Query | Color |
|---|---|---|---|
| 5 | Total Applications This Month | applications WHERE MONTH(created_at) = current month COUNT | Primary |
| 6 | Total Approved This Month | reached cheque_ready or claimed this month COUNT | Success |
| 7 | Total Disbursed This Month | SUM assistance_codes.amount where claimed this month | Accent |
| 8 | Total On Hold | status = on_hold COUNT (all time — active concern) | Warning |

#### Charts (this week)

**Chart 1 — Daily Application Volume (Bar)**
```
X-axis: Last 7 days
Y-axis: Applications submitted per day
Shows the week's activity at a glance
Color: --color-primary
```

**Chart 2 — Pipeline Status Distribution (Doughnut)**
```
All active applications grouped by status stage:
  Intake (submitted): COUNT
  Screening (mswdo_review, social_case_study_uploaded): COUNT
  Processing (assistance_coding, voucher_creation, voucher_returned): COUNT
  Review (voucher_checking, with_treasurer, budget_checking): COUNT
  Ready (cheque_ready): COUNT
  On Hold (on_hold): COUNT
  Claimed (claimed — this month): COUNT
This shows the Mayor exactly where applications are piling up
```

**Chart 3 — Category Breakdown (Doughnut)**
```
Medical vs Hospital vs Burial — this month
Shows what type of crisis the community is experiencing
Colors: primary, primary-light, accent
```

**Chart 4 — Applications by Barangay (Horizontal Bar)**
```
Top 10 barangays by beneficiary this month
This is the most GMN-specific insight — shows which barangays
need the most assistance
Color: --color-primary-light
```

**Chart 5 — Assistance Amount by Category (Bar)**
```
Total ₱ disbursed per category this month
Medical, Hospital, Burial
Color: --color-accent for bars — this is a financial highlight
```

#### Summary Table — Recent Activity (8 rows)
```
Columns: Reference Code | Beneficiary | Category | Status | Amount | Date
Query: applications ORDER BY updated_at DESC LIMIT 8
       WITH assistance_codes for amount, category for name
Status: AppStatusBadge component
Amount: JetBrains Mono, ₱ formatted
```

---

### 5.2 Analytics Page

This is the Mayor's office report view — equivalent to the monthly
report they would have received manually.

#### Date Filter
```
From / To DatePicker — default: first day of current month to today
```

#### KPI Summary Cards

| KPI | Label |
|---|---|
| 1 | Total Applications Received |
| 2 | Total Approved (reached cheque_ready or claimed) |
| 3 | Total Claimed |
| 4 | Total On Hold |
| 5 | Total Assistance Amount Disbursed (claimed only) |
| 6 | Average Assistance Amount per Application |
| 7 | Approval Rate (approved ÷ total × 100%) |
| 8 | Average Days from Submission to Cheque Ready |

#### Charts (all filtered by date range)

**Chart 1 — Application Trend (Line)**
Daily application submissions over the date range.

**Chart 2 — Category Distribution (Doughnut)**
Medical vs Hospital vs Burial for the period.

**Chart 3 — Online vs Walk-in (Doughnut)**
Submission channel breakdown for the period.

**Chart 4 — Applications by Barangay (Horizontal Bar)**
Top 10 barangays for the period — the most policy-relevant chart
for the Mayor's office. Shows where resources are being consumed.

**Chart 5 — Monthly Disbursement Trend (Bar)**
Total ₱ assistance per week or per month within the range.
If range ≤ 31 days: weekly bars.
If range > 31 days: monthly bars.

**Chart 6 — Pipeline Bottleneck Analysis (Horizontal Bar)**
Average days an application spends at each stage:
  AICS Screening | MSWDO Review | Assistance Coding |
  Voucher Creation | Voucher Checking | Budget Checking
Query: average time between stage-entry and stage-exit using
       reviews.created_at differences per stage
This tells the Mayor where the process is slowest.

---

## SECTION 6 — Shared Implementation Details

### Chart Color Palette (consistent across all panels)

```javascript
// resources/js/Utils/chartColors.js
export const CHART_COLORS = {
  primary:       '#1B4F72',
  primaryLight:  '#2E86AB',
  accent:        '#E8A838',
  success:       '#16A34A',
  warning:       '#D97706',
  danger:        '#DC2626',
  purple:        '#7C3AED',
  muted:         '#9CA3AF',

  // Category colors (always consistent)
  medical:       '#1B4F72',
  hospital:      '#2E86AB',
  burial:        '#E8A838',

  // Status colors
  pending:       '#D97706',
  approved:      '#16A34A',
  returned:      '#DC2626',
  onHold:        '#9CA3AF',
  processing:    '#7C3AED',

  // Chart backgrounds (with opacity)
  primaryBg:     'rgba(27, 79, 114, 0.15)',
  accentBg:      'rgba(232, 168, 56, 0.15)',
  successBg:     'rgba(22, 163, 74, 0.15)',
  dangerBg:      'rgba(220, 38, 38, 0.15)',
}
```

### KPI Card Component (shared)

```vue
<!-- resources/js/Components/Common/AppKpiCard.vue -->
<script setup>
defineProps({
  label:     { type: String,  required: true  },
  value:     { type: [Number, String], required: true },
  icon:      { type: String,  default: null   },
  color:     { type: String,  default: 'primary' },
  prefix:    { type: String,  default: null   }, // e.g. '₱'
  suffix:    { type: String,  default: null   }, // e.g. '%'
  isCurrency:{ type: Boolean, default: false  },
  trend:     { type: Object,  default: null   }, // { direction: 'up'|'down', value: '+12%' }
  loading:   { type: Boolean, default: false  },
})
</script>
```

Color variants: `primary`, `success`, `warning`, `danger`, `purple`, `accent`

### Currency Formatting

All monetary values formatted as Philippine Peso:
```javascript
// resources/js/Utils/formatCurrency.js
export function formatCurrency(amount) {
  return new Intl.NumberFormat('en-PH', {
    style:    'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
  }).format(amount ?? 0)
}
// Output: ₱5,000.00
```

### Date Label Generation for Charts

```javascript
// resources/js/Utils/chartDates.js
export function generateWeekLabels() {
  const labels = []
  for (let i = 6; i >= 0; i--) {
    const d = new Date()
    d.setDate(d.getDate() - i)
    labels.push(d.toLocaleDateString('en-PH', { weekday: 'short', month: 'short', day: 'numeric' }))
  }
  return labels  // ['Mon, Jun 3', 'Tue, Jun 4', ...]
}

export function fillMissingDates(data, dateFrom, dateTo) {
  // Fills gaps in trend data with 0 for days with no applications
  // So line charts don't skip dates
}
```

### Analytics Date Filter Component (shared)

```vue
<!-- resources/js/Components/Common/AppDateRangeFilter.vue -->
<script setup>
defineProps({
  dateFrom: String,
  dateTo:   String,
})
defineEmits(['apply', 'clear'])
</script>
<!-- Uses PrimeVue DatePicker for both from and to -->
<!-- Apply button triggers emit('apply', { date_from, date_to }) -->
<!-- Clear resets to current month default -->
```

### PrimeVue Chart Usage Pattern

```vue
<script setup>
import { Chart } from 'primevue/chart'
import { CHART_COLORS } from '@/Utils/chartColors'

// Line chart data shape
const lineData = computed(() => ({
  labels: props.trend.map(d => d.date),
  datasets: [{
    label:           'Applications',
    data:            props.trend.map(d => d.count),
    borderColor:     CHART_COLORS.primary,
    backgroundColor: CHART_COLORS.primaryBg,
    tension:         0.4,  // smooth curve
    fill:            true,
  }]
}))

// Doughnut chart data shape
const doughnutData = computed(() => ({
  labels: props.distribution.map(d => d.label),
  datasets: [{
    data:            props.distribution.map(d => d.count),
    backgroundColor: [CHART_COLORS.medical, CHART_COLORS.hospital, CHART_COLORS.burial],
    borderWidth:     2,
    borderColor:     '#FFFFFF',
  }]
}))

const chartOptions = {
  responsive:          true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: { font: { family: 'Inter', size: 12 } }
    }
  }
}
</script>

<template>
  <Chart type="line" :data="lineData" :options="chartOptions"
         style="height: 280px" />
  <Chart type="doughnut" :data="doughnutData" :options="chartOptions"
         style="height: 280px" />
</template>
```

---

## SECTION 7 — Layout Structure Per Panel

### Dashboard Layout
```
┌─────────────────────────────────────────────────────────┐
│  Page Title + "Last updated: X mins ago" timestamp      │
├────────────┬────────────┬────────────┬──────────────────┤
│  KPI Card  │  KPI Card  │  KPI Card  │   KPI Card       │
├────────────┴────────────┼────────────┴──────────────────┤
│  Chart 1 (Line — full)  │  Chart 2 (Doughnut)          │
├─────────────────────────┼──────────────────────────────┤
│  Chart 3 (Doughnut)     │  Chart 4 (Horizontal Bar)    │
├─────────────────────────┴──────────────────────────────┤
│  Recent [Applications/Vouchers/Cheques] Table (5 rows)  │
└─────────────────────────────────────────────────────────┘
```

Mobile: All cards and charts stack to single column.
Charts minimum height 250px on mobile.

### Analytics Layout
```
┌─────────────────────────────────────────────────────────┐
│  Date Range Filter: [From] → [To]  [Apply] [Clear]      │
├────────────────────────────────────────────────────────┤
│  KPI Summary Cards (2 per row on mobile, 4 on desktop)  │
├────────────────────────────────────────────────────────┤
│  Chart 1 (Trend — full width)                           │
├────────────────────┬───────────────────────────────────┤
│  Chart 2           │  Chart 3                           │
├────────────────────┴───────────────────────────────────┤
│  Chart 4 (if applicable — full width)                   │
└────────────────────────────────────────────────────────┘
```

---

## SECTION 8 — PROCESS.md Additions

Add to Phase 3 (Controllers) and Phase 4 (Frontend):

```
### Phase 3 — Dashboard and Analytics Controllers

- [ ] Create Aics/DashboardController@index with all KPIs and chart queries
- [ ] Create Aics/AnalyticsController@index with date filter and cached queries
- [ ] Create Mswdo/DashboardController@index
- [ ] Create Mswdo/AnalyticsController@index
- [ ] Create Accountant/DashboardController@index
- [ ] Create Accountant/AnalyticsController@index
- [ ] Create Treasurer/DashboardController@index
- [ ] Create Treasurer/AnalyticsController@index
- [ ] Create MayorsOffice/DashboardController@index
- [ ] Create MayorsOffice/AnalyticsController@index
- [ ] Add Redis caching to all analytics controller methods (15 min TTL)
- [ ] Add date validation to all analytics controllers
      (date_from, date_to: date format Y-m-d, date_from before date_to)
- [ ] Add fillMissingDates() logic to all trend queries
      so charts show 0 for days with no data instead of skipping dates

### Phase 4 — Dashboard and Analytics Frontend

- [ ] Create resources/js/Utils/chartColors.js
- [ ] Create resources/js/Utils/formatCurrency.js
- [ ] Create resources/js/Utils/chartDates.js (generateWeekLabels, fillMissingDates)
- [ ] Create resources/js/Components/Common/AppKpiCard.vue
- [ ] Create resources/js/Components/Common/AppDateRangeFilter.vue
- [ ] Build Aics/Dashboard.vue (4 KPIs, 4 charts, recent table)
- [ ] Build Aics/Analytics.vue (5 KPIs, 4 charts, date filter)
- [ ] Build Mswdo/Dashboard.vue (4 KPIs, 4 charts, recent table)
- [ ] Build Mswdo/Analytics.vue (4 KPIs, 4 charts, date filter)
- [ ] Build Accountant/Dashboard.vue (3 KPIs, 3 charts, recent table)
- [ ] Build Accountant/Analytics.vue (5 KPIs, 3 charts, date filter)
- [ ] Build Treasurer/Dashboard.vue (3 KPIs, 3 charts, recent table)
- [ ] Build Treasurer/Analytics.vue (5 KPIs, 4 charts, date filter)
- [ ] Build MayorsOffice/Dashboard.vue (8 KPIs, 5 charts, summary table)
- [ ] Build MayorsOffice/Analytics.vue (8 KPIs, 6 charts including bottleneck)
- [ ] Verify all charts use CHART_COLORS constants — no hardcoded hex
- [ ] Verify all amounts use formatCurrency() — ₱X,XXX.XX format
- [ ] Verify all charts are responsive (maintainAspectRatio: false + height set)
- [ ] Verify analytics default date range is current month on all panels
- [ ] Verify date filter preserveState: true on all analytics pages
```

---

*Document prepared for AI consumption and development reference — ALALAY System,
Municipality of General Mamerto Natividad, Nueva Ecija.*

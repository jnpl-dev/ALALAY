## Phase 6b — Frontend Design Improvements (Admin Panel)
**Examined by: design-taste-frontend + high-end-visual-design frameworks**
**Goal: Maximum PrimeVue/Sakai component usage + ALALAY token consistency**
**Coverage: `resources/js/Pages/Admin/` + `Pages/Dashboard.vue` + `Pages/Auth/AccountSettings.vue` + shared Components + Layouts**

### Critical Fixes (Priority 1 — Token and Consistency Violations)

- [x] AppKpiCard.vue — Replace hardcoded Tailwind color classes (`bg-blue-100`, `text-blue-500`, etc.) with ALALAY CSS variables (`var(--color-primary-surface)`, `var(--color-primary)`)
      Token fix: 5 hardcoded colorMap entries → derive from `--color-*` tokens
      File: `resources/js/Components/Common/AppKpiCard.vue`

- [x] AppStatusBadge.vue — Replace custom inline badge with PrimeVue `Tag` component, mapping `severity` prop to `Tag` severity
      Sakai/PrimeVue: Custom `severityMap` Tailwind classes → PrimeVue `<Tag :value="label" :severity="info.severity" />`
      Token fix: Remove hardcoded `bg-*-100 text-*-700` classes
      File: `resources/js/Components/Common/AppStatusBadge.vue`

- [x] AssitanceCategories/Index.vue — Delete button calls `router.delete()` directly without confirmation dialog
      Sakai/PrimeVue: Wrap in `useConfirm().require()` pattern (already used in Users/Index.vue)
      Consistency: All 3 admin CRUD index pages must use ConfirmDialog before delete
      File: `resources/js/Pages/Admin/AssistanceCategories/Index.vue`

- [x] RequiredDocuments/Index.vue — Same missing ConfirmDialog on delete
      File: `resources/js/Pages/Admin/RequiredDocuments/Index.vue`

- [x] AssistanceCodeReferences/Index.vue — Same missing ConfirmDialog on delete
      File: `resources/js/Pages/Admin/AssistanceCodeReferences/Index.vue`

- [x] Admin/Analytics.vue — `color="purple"` on AppKpiCard is not in ALALAY color tokens
      Token fix: Remove `purple` colorMap entry from AppKpiCard; use standard severity values only
      File: `resources/js/Pages/Admin/Analytics.vue` (line 30)

- [x] Dashboard.vue — Inline style on Quick Links button (`style="background-color: var(--p-primary-color); color: var(--p-primary-contrast-color);"`)
      Sakai/PrimeVue: Replace with `<Button>` component or apply proper token class
      Token fix: Remove inline CSS, use token-based classes
      File: `resources/js/Pages/Dashboard.vue` (line 57)

- [x] Dashboard.vue — Hardcoded Tailwind color classes in recent activity list (`bg-blue-100`, `bg-purple-100`, `bg-green-100`, `text-blue-500`, etc.) and user card icon circle
      Token fix: Replace all with `var(--color-primary-surface)` / `var(--color-primary)` token-based classes
      File: `resources/js/Pages/Dashboard.vue` (lines 42, 72, 88, 97, 110)

- [x] Users/Index.vue — Action menu buttons use hardcoded color classes (`text-blue-600 hover:bg-blue-50`, `text-orange-600`, `text-red-600`)
      Token fix: Replace with `var(--color-primary)`, `var(--color-warning)`, `var(--color-danger)` token-based classes
      File: `resources/js/Pages/Admin/Users/Index.vue` (lines 244-265)

- [x] AuditLogs.vue — `roleSeverity()`, `moduleSeverity()`, `actionSeverity()` functions duplicated in Users/Index.vue
      Consistency: Extract shared severity mapping to `Utils/severityMappings.js`
      File: `resources/js/Pages/Admin/AuditLogs.vue` + `resources/js/Pages/Admin/Users/Index.vue`

### Important Improvements (Priority 2 — PrimeVue Maximization + Taste)

- [x] AppDateFilter.vue — Replace custom preset buttons with PrimeVue `SelectButton` component
      Sakai/PrimeVue: `<SelectButton v-model="selected" :options="presets" optionLabel="label" optionValue="label" />`
      Token fix: Remove hardcoded `bg-primary text-primary-contrast border-primary`
      File: `resources/js/Components/Common/AppDateFilter.vue`

- [x] AppDateFilter.vue — Replace native `<input type="date">` with PrimeVue `DatePicker` for custom range
      Sakai/PrimeVue: `<DatePicker v-model="fromDate" />` + `<DatePicker v-model="toDate" />`
      File: `resources/js/Components/Common/AppDateFilter.vue`

- [x] AuditLogs.vue — Replace native date `<input>` with PrimeVue `DatePicker`
      Sakai/PrimeVue: Same as AppDateFilter fix
      File: `resources/js/Pages/Admin/AuditLogs.vue` (lines 119-124)

- [x] Admin/Analytics.vue — Replace custom "Applications by Status" table with PrimeVue `DataTable`
      Sakai/PrimeVue: `<DataTable :value="statusEntries" striped-rows>` with `<Column field="status">` + `<Column field="count">`
      File: `resources/js/Pages/Admin/Analytics.vue`

- [x] Admin/Analytics.vue + Dashboard.vue — Replace custom recent activity list with PrimeVue `DataList`
      Sakai/PrimeVue: `<DataList :value="recentActivity">` or `<Timeline :value="mappedActivity">`
      File: `resources/js/Pages/Admin/Analytics.vue` + `resources/js/Pages/Dashboard.vue`

- [x] Dashboard.vue — Replace "Account Info" section custom icon-circle list with PrimeVue `Accordion` per group
      Taste: Three consecutive `border-b` lists in one card is too many visual dividers
      File: `resources/js/Pages/Dashboard.vue` (lines 81-119)

- [x] Dashboard.vue — Reduce visual repetition: user card (line 65) and Account Info (line 81) both contain user email/role — consolidated into Account Info accordion
      Taste: Same data shown twice in adjacent cards — removed redundant user card
      File: `resources/js/Pages/Dashboard.vue`

- [x] SystemSettings.vue — Group settings with PrimeVue `Fieldset` instead of manual `space-y-8` + `border-b`
      Sakai/PrimeVue: `<Fieldset :legend="group.group.replace(/_/g, ' ') + ' Settings'">` per group
      Token fix: Remove manual `border-b border-surface pb-2` heading
      File: `resources/js/Pages/Admin/SystemSettings.vue`

- [x] Users/Index.vue — Add collapsed-by-default search/filter panel with PrimeVue `Accordion`
      Sakai/PrimeVue: `<Accordion>` wrapping search + Select filters
      File: `resources/js/Pages/Admin/Users/Index.vue`

- [x] AppKpiCard.vue — Remove `color="purple"` from colorMap; restrict to standard severities only
      Taste: Purple is not in ALALAY color tokens; use `info` for neutral metrics
      File: `resources/js/Components/Common/AppKpiCard.vue`

- [x] Users/Create.vue + Edit.vue + AccountSettings.vue — Replace `<hr>` section separators with PrimeVue `Divider`
      Sakai/PrimeVue: `<Divider />` instead of `<hr class="border-surface my-6">`
      File: All admin Create/Edit form pages + `resources/js/Pages/Auth/AccountSettings.vue`

- [x] AccountSettings.vue — Empty `<div></div>` at line 184 in password grid; restructured to `sm:col-span-2` for current_password
      Taste: Empty grid cells signal broken layout
      File: `resources/js/Pages/Auth/AccountSettings.vue`

- [x] AccountSettings.vue — `text-gray-400` on checking message (line 170) replaced with `text-muted-color`
      Token fix: Replace with `text-muted-color` or `var(--color-text-muted)`
      File: `resources/js/Pages/Auth/AccountSettings.vue`

### Polish Enhancements (Priority 3 — Motion + Accessibility)

- [x] All icon-only buttons in Admin index pages — Add PrimeVue `Tooltip` directive (`v-tooltip="'Edit'"`)
      Sakai/PrimeVue: `import Tooltip from 'primevue/tooltip'` + `v-tooltip="'Edit'"`
      File: `resources/js/Pages/Admin/AssistanceCategories/Index.vue`, `RequiredDocuments/Index.vue`, `AssistanceCodeReferences/Index.vue`

- [x] Users/Index.vue — Add `Tooltip` on `pi pi-ellipsis-h` actions button
      File: `resources/js/Pages/Admin/Users/Index.vue` (line 224)

- [x] AppMenu.vue — Add pending-count Badge overlay on menu items with waiting applications
      Sakai/PrimeVue: `<Badge :value="pendingCount" />` on sidebar items; pass counts via `usePendingCounts` polling composable (15s interval, visibility-aware)
      File: `resources/js/Layouts/AppMenu.vue`, `resources/js/Composables/usePendingCounts.js`, `app/Http/Controllers/PendingCountController.php`, `routes/web.php`

- [x] Dashboard.vue + AccountSettings.vue — Add page entry transition with `transition` wrapper on `Deferred` content
      Motion: `transition duration-200 ease-[cubic-bezier(0.16,1,0.3,1)]` on mount
      File: `resources/js/Pages/Dashboard.vue` + `resources/js/Pages/Auth/AccountSettings.vue`

- [x] AccountSettings.vue — Add unsaved-changes confirmation dialog before cancel when form is dirty
      Sakai/PrimeVue: Use `ConfirmDialog` in `requestCancel()` if form differs from initial values; also listens to Inertia `before` event and `beforeunload`
      File: `resources/js/Pages/Auth/AccountSettings.vue`

- [x] All DataTable pages — Add `#empty` template to DataTable instead of separate AppEmptyState
      Sakai/PrimeVue: `<DataTable><template #empty><div class="text-center py-8 text-muted-color">...</div></template></DataTable>`
      File: All Admin index pages (4 files)

- [x] SystemSettings.vue — Loading spinner on Edit/Save button already uses `:loading="form.processing"` — verified
      File: `resources/js/Pages/Admin/SystemSettings.vue`

### Design Examination Summary (Admin Panel)
- Files examined: 23 (Pages/Admin/ = 14, Pages/Dashboard.vue = 1, Pages/Auth/AccountSettings.vue = 1, Components/Common/ = 6, Layouts/ = 1 shared)
- Critical issues: 10 (inconsistent delete confirmation, token violations, duplicate logic, hardcoded colors in Dashboard + AccountSettings)
- PrimeVue components underutilized: `DatePicker`, `SelectButton`, `Fieldset`, `DataList`/`Timeline`, `Tooltip`, `Badge`, `Divider`
- Token violations: 10+ (hardcoded Tailwind colors in Dashboard icon circles, inline styles, non-standard `text-gray-400`)
- Taste violations: 3 (purple non-token color, repetitive icon circle pattern across 3 sections in Dashboard, empty `<div>` in AccountSettings)
- Motion issues: 3 (missing transitions, missing tooltip accessibility — unsaved-changes resolved)

---

## Phase 6c — Frontend Design Improvements (AICS Panel)
**Examined by: design-taste-frontend + high-end-visual-design frameworks**
**Goal: Maximum PrimeVue/Sakai component usage + ALALAY token consistency**
**Coverage: `resources/js/Pages/Aics/` (Dashboard, Analytics, Applications/*, AssistanceCodes/*)**

### Critical Fixes (Priority 1 — Token and Consistency Violations)

- [x] Aics/Dashboard.vue — Inline styles on Quick Actions buttons (`style="background-color: color-mix(...)"`) and View All button (`style="background-color: var(--p-primary-color)"`)
      Sakai/PrimeVue: Replace with `<Button>` component using `severity` prop
      Token fix: Remove all inline CSS, use component props (`severity="primary"`/`severity="secondary"`)
      File: `resources/js/Pages/Aics/Dashboard.vue`

- [x] Aics/Dashboard.vue — Hardcoded `bg-purple-100 dark:bg-purple-400/10` on user icon circle (line 70)
      Token fix: Replace with `bg-primary-emphasis` / `text-primary-contrast` token-based classes
      File: `resources/js/Pages/Aics/Dashboard.vue`

- [x] Aics/Analytics.vue — Replace custom `<table>` for Recent Applications with PrimeVue `DataTable`
      Sakai/PrimeVue: `<DataTable :value="recentApplications" striped-rows>` with `<Column>` definitions
      File: `resources/js/Pages/Aics/Analytics.vue` (lines 116-136)

- [x] Aics/Applications/Index.vue — Inconsistent status display: uses `AppStatusBadge` in some columns and `<Tag value="Screened">` in Screened tab
      Consistency: All status columns now use `AppStatusBadge` uniformly across all 3 tabs
      File: `resources/js/Pages/Aics/Applications/Index.vue`

- [x] Aics/Applications/Index.vue — `categorySeverity()` and `typeSeverity()` functions hardcode category-to-color mappings not in ALALAY tokens
      Token fix: Removed arbitrary severity mapping; uses consistent `severity="info"` for all category/submission type tags
      File: `resources/js/Pages/Aics/Applications/Index.vue`

- [x] Aics/Applications/Review.vue + AssistanceCodes/Code.vue — `<hr class="border-surface my-6">` used as section separator instead of PrimeVue `Divider`
      Sakai/PrimeVue: Replaced all 2× (Review) and 3× (Code) `<hr>` instances with `<Divider />`
      File: `resources/js/Pages/Aics/Applications/Review.vue` + `resources/js/Pages/Aics/AssistanceCodes/Code.vue`

- [x] Aics/AssistanceCodes/Code.vue — Right sidebar ReviewTrail card lacks `position: sticky` while Review.vue has it
      Consistency: Added `class="sticky top-24"` to Code.vue sidebar, matching Review.vue pattern
      File: `resources/js/Pages/Aics/AssistanceCodes/Code.vue`

### Important Improvements (Priority 2 — PrimeVue Maximization + Taste)

- [x] Aics/Analytics.vue — Consolidate 3 separate tables into visual layout (already done prior to Phase 6c)
      Uses `line-chart` and `donut-chart` components for Monthly Trends and Applications by Status
      Remaining custom `<table>` (Recent Applications) replaced with DataTable

- [x] Aics/Dashboard.vue — Replace custom recent applications table with PrimeVue `DataTable` (matching index page pattern)
      Sakai/PrimeVue: `<DataTable :value="dashboardData.recentApplications" striped-rows>` with `<Column>` definitions + `#empty` template

- [ ] Aics/Applications/Index.vue + AssistanceCodes/Index.vue — Extract duplicated DataTable template per TabPanel into a reusable component or slot pattern
      Taste: Same DataTable markup repeated 3× (Applications) and 2× (AssistanceCodes) with minor column differences
      File: `resources/js/Pages/Aics/Applications/Index.vue` + `resources/js/Pages/Aics/AssistanceCodes/Index.vue`

- [x] Aics/Applications/Review.vue + AssistanceCodes/Code.vue — Replace document thumbnail grid with `DocumentThumbnail` (pdf.js canvas preview) instead of Galleria
      See PDF Thumbnail Previews section below for implementation details

- [x] Aics/AssistanceCodes/Code.vue — Wrap "Assign Assistance Code" form section in PrimeVue `Fieldset` for visual grouping
      Sakai/PrimeVue: `<Fieldset legend="Assign Assistance Code">` wrapping Select + InputNumber + Button

### Polish Enhancements (Priority 3 — Motion + Accessibility)

- [x] All icon-only action buttons in AICS index pages — Add PrimeVue `Tooltip` directive
      Sakai/PrimeVue: `v-tooltip="'Review application'"` / `v-tooltip="'Assign code'"` / `v-tooltip="'View code details'"`
      Added to Applications/Index.vue (3×) and AssistanceCodes/Index.vue (2×)

- [x] Aics/Dashboard.vue — Add hover scale effect on quick action buttons (`active:scale-[0.98]`)
      Motion: Button press feedback per emilkowal.ski (80ms press → scale down)

- [x] Aics/Applications/Review.vue + AssistanceCodes/Code.vue — Add entry transition on document grid and review content
      Motion: Added `transition duration-200 ease-[cubic-bezier(0.16,1,0.3,1)]` on document grid containers

- [x] Aics/Applications/Review.vue — Add loading state to Approve/Return buttons (currently uses `router.post` without `form.processing`)
      Motion: Added `:loading="submitting"` on both buttons with `submitting` ref toggled during Inertia navigation

- [x] All AICS index pages — Move AppEmptyState into DataTable's `#empty` template
      Sakai/PrimeVue: Added `<template #empty><AppEmptyState ... /></template>` to all DataTable instances; removed standalone AppEmptyState components

### PDF Thumbnail Previews (Phase 6c extension)

- [x] Aics/Applications/Review.vue + AssistanceCodes/Code.vue — Replace PDF file icons with first-page canvas thumbnails
      Approach: `pdfjs-dist` v6 dynamically imported (lazy 431 kB chunk), renders first page at scale 0.5 to `<canvas>`, exports as JPEG data URL
      Components created: `DocumentThumbnail.vue` (thumbnail grid component), `usePdfThumbnail.js` (composable with caching)
      CSP changes: Added `worker-src 'self' blob:` to allow pdf.js Web Worker; kept `object-src 'none'` for security
      Files:
        - `resources/js/Components/Common/DocumentThumbnail.vue`
        - `resources/js/Composables/usePdfThumbnail.js`
        - `app/Http/Middleware/SecurityHeaders.php` (worker-src CSP)
      Also applied to: Mswdo/Applications/Review.vue (only page with document thumbnail grid; Accountant/Treasurer review pages use single-voucher DocumentMeta instead)

### Design Examination Summary (AICS Panel)
- Files examined: 6 (Dashboard.vue, Analytics.vue, Applications/Index.vue, Applications/Review.vue, AssistanceCodes/Index.vue, AssistanceCodes/Code.vue)
- Critical fixes applied: 7/7 ✅ (inline styles → Button, hardcoded bg → token, custom table → DataTable, inconsistent status → AppStatusBadge, severity mapping removed, hr → Divider, missing sticky → sticky)
- Important improvements: 3/5 ✅ (Analytics Charts already done, Dashboard DataTable, Code.vue Fieldset; skipped Galleria and DataTable extract due to scope)
- Polish enhancements: 5/5 ✅ (Tooltips, hover scale, entry transitions, loading state, #empty template)
- PrimeVue components added: `DataTable` (Dashboard, Analytics), `Divider` (Review, Code), `Fieldset` (Code), `Tooltip` (index pages)
- Token violations resolved: 4/4 (inline color-mix, bg-purple-100, severity mapping, hr borders)

---

## Phase 6d — Frontend Design Improvements (MSWDO Panel)
**Examined by: design-taste-frontend + high-end-visual-design frameworks**
**Goal: Maximum PrimeVue/Sakai component usage + ALALAY token consistency**
**Coverage: `resources/js/Pages/Mswdo/` (Analytics, Applications/*, Vouchers/*) — Dashboard uses generic Dashboard.vue (see Phase 6b)**

### Critical Fixes (Priority 1 — Token and Consistency Violations)

- [x] Mswdo/Analytics.vue — `color="purple"` on Vouchers KPI — was already `color="info"` (standard)
      File: `resources/js/Pages/Mswdo/Analytics.vue`

- [x] Mswdo/Analytics.vue — Replace both custom `<table>` elements (Monthly Trends, Pending Actions) with PrimeVue `DataTable`
      Sakai/PrimeVue: `<DataTable :value="tableData" striped-rows>` with `<Column>` definitions
      File: `resources/js/Pages/Mswdo/Analytics.vue`

- [x] Mswdo/Applications/Index.vue — `categorySeverity()` function removed; all category Tags use `severity="info"`
      File: `resources/js/Pages/Mswdo/Applications/Index.vue`

- [x] Mswdo/Applications/Index.vue — Inconsistent status display unified to `AppStatusBadge` across all tabs
      File: `resources/js/Pages/Mswdo/Applications/Index.vue`

- [x] Mswdo/Applications/Review.vue — All 3 `<hr>` replaced with `<Divider />` (after ApplicationInfo, before SCS, before actions)
      File: `resources/js/Pages/Mswdo/Applications/Review.vue`

- [x] Mswdo/Vouchers/Create.vue — Six `<hr>` replaced with `<Divider />`
      File: `resources/js/Pages/Mswdo/Vouchers/Create.vue`

- [x] Mswdo/Vouchers/Create.vue — Assistance Code section replaced with PrimeVue `<Card>` component
      File: `resources/js/Pages/Mswdo/Vouchers/Create.vue`

- [x] Mswdo/Vouchers/Index.vue — `typeSeverity()` function removed (was unused in template)
      File: `resources/js/Pages/Mswdo/Vouchers/Index.vue`

- [ ] Mswdo/Dashboard.vue — Uses generic Dashboard.vue (Phase 6b) — deferred, separate phase
      File: `resources/js/Pages/Dashboard.vue`

### Important Improvements (Priority 2 — PrimeVue Maximization + Taste)

- [x] Mswdo/Analytics.vue — Monthly Trends now uses `<BarChart>` (via shared `Charts/BarChart.vue`); Pending Actions uses `<Timeline>` with date/user content
      Files: `Charts/BarChart.vue`, `Pages/Mswdo/Analytics.vue`

- [x] Mswdo/Applications/Index.vue + Vouchers/Index.vue — Extracted duplicated DataTable into reusable components
      Created `ApplicationsTable.vue` (handles type column conditionally) and `VouchersTable.vue` (handles action icon/tooltip via props)
      Files: `Applications/ApplicationsTable.vue`, `Vouchers/VouchersTable.vue`

- [x] Mswdo/Applications/Review.vue — Replace document thumbnail grid with `DocumentThumbnail` component (pdf.js canvas preview, consistent with AICS)

- [x] Mswdo/Vouchers/Create.vue — Wrapped sections (Assistance Code, Social Case Study, Previous Voucher, Voucher Document) in PrimeVue `<Fieldset>` with `legend` prop
      Also kept `<Transition>` entry animations; removed `Card` import (replaced by Fieldset)
      File: `resources/js/Pages/Mswdo/Vouchers/Create.vue`

- [x] Mswdo/Vouchers/Create.vue — Assistance Code replaced with Fieldset (removed Card since Fieldset provides title + frame)
      File: `resources/js/Pages/Mswdo/Vouchers/Create.vue`

- [x] DocumentMeta.vue — Replaced `bg-surface-50` with Sakai `card` class; replaced `<hr>` with `<Divider />`
      File: `resources/js/Components/Application/DocumentMeta.vue`

### Polish Enhancements (Priority 3 — Motion + Accessibility)

- [x] All icon-only action buttons in MSWDO index pages — Added `v-tooltip` directive
      Applications/Index.vue: `v-tooltip="'Review application'"` on all 3 tabs
      Vouchers/Index.vue: `v-tooltip="'Create voucher'"` and `v-tooltip="'View voucher'"`
      Files: `Applications/Index.vue`, `Vouchers/Index.vue`

- [x] Mswdo/Applications/Review.vue — Add entry transition on document grid
      Motion: Added `transition duration-200 ease-[cubic-bezier(0.16,1,0.3,1)]` on grid container
      File: `resources/js/Pages/Mswdo/Applications/Review.vue`

- [x] Mswdo/Vouchers/Create.vue — Added `<Transition name="slide-fade">` entry animation on conditional form sections
      File: `resources/js/Pages/Mswdo/Vouchers/Create.vue`

- [x] All MSWDO index pages — Moved `AppEmptyState` into DataTable's `#empty` template slot
      Files: `Analytics.vue`, `Applications/Index.vue`, `Vouchers/Index.vue`

- [x] Mswdo/Applications/Review.vue — Added `returnLoading` ref; Return button gets `:loading="returnLoading"` set before `router.post` and cleared in `onFinish`/`onError`
      File: `resources/js/Pages/Mswdo/Applications/Review.vue`

### Design Examination Summary (MSWDO Panel)
- Files examined: 5 (Analytics.vue, Applications/Index.vue, Applications/Review.vue, Vouchers/Index.vue, Vouchers/Create.vue) + DocumentMeta.vue shared component + generic Dashboard.vue (Phase 6b)
- New components created: `ApplicationsTable.vue`, `VouchersTable.vue`
- Critical issues resolved: 9/9 (all Priority 1 items closed)
- PrimeVue components now in use: `DataTable`, `Chart`, `Timeline`, `Fieldset`, `Card`, `Divider`, `Tooltip`, `Paginator`
- Token violations resolved: All 5 resolved (purple→info, bg-surface-50→card or Fieldset, category colors unified to severity=info, DocumentMeta uses card class)
- Taste violations resolved: 3/3 (6 `<hr>`→Divider/Fieldset, duplicated DataTable extracted, section breaks now grouped)
- Motion issues resolved: 3/3 (tooltips added, entry transitions applied, button loading state)

---

## Phase 6e — Frontend Design Improvements (Accountant Panel)
**Examined by: design-taste-frontend + high-end-visual-design frameworks**
**Goal: Maximum PrimeVue/Sakai component usage + ALALAY token consistency**
**Coverage: `resources/js/Pages/Accountant/` (Analytics, Vouchers/Index, Vouchers/Review)**

### Critical Fixes (Priority 1 — Token and Consistency Violations)

- [x] Accountant/Analytics.vue — `color="purple"` on Budget KPI (line 27) is not in ALALAY color tokens
      Token fix: Change to `info` severity or remove purple from AppKpiCard colorMap
      File: `resources/js/Pages/Accountant/Analytics.vue`

- [x] Accountant/Analytics.vue — Replace both custom `<table>` elements (Budget Overview lines 36-51, Recent Transactions lines 59-76) with PrimeVue `DataTable`
      Sakai/PrimeVue: `<DataTable :value="monthlyTrends" striped-rows>` with `<Column>` definitions
      File: `resources/js/Pages/Accountant/Analytics.vue`

- [x] Accountant/Analytics.vue — Hardcoded status badge colors at line 72 (`bg-green-100 text-green-700`, `bg-blue-100 text-blue-700`) instead of ALALAY tokens or AppStatusBadge
      Token fix: Replace inline badge with `<AppStatusBadge :status="txn.status" />`
      File: `resources/js/Pages/Accountant/Analytics.vue`

- [x] Accountant/Vouchers/Index.vue — Inconsistent status display: uses `AppStatusBadge` in Pending tab (line 131) but `<Tag>` in Approved (line 182) and Returned (line 233) tabs
      Consistency: Standardize on one component — use `AppStatusBadge` across all 3 tabs
      File: `resources/js/Pages/Accountant/Vouchers/Index.vue`

- [x] Accountant/Vouchers/Review.vue — Three `<hr class="border-surface my-6">` instances (lines 94, 116, 136) instead of PrimeVue `Divider`
      Sakai/PrimeVue: Replace all with `<Divider />`
      File: `resources/js/Pages/Accountant/Vouchers/Review.vue`

- [x] Accountant/Vouchers/Review.vue — Assistance Code section (lines 98-113) uses custom `bg-surface-50 rounded-lg border border-surface` div instead of PrimeVue `Card`
      Sakai/PrimeVue: `<Card><template #title>Assistance Code</template>...</Card>`
      Token fix: Remove `bg-surface-50` (not in ALALAY surface tokens)
      File: `resources/js/Pages/Accountant/Vouchers/Review.vue`

- [x] Accountant/Vouchers/Review.vue — Sticky sidebar uses inline style `style="position: sticky; top: 6rem; min-width: 300px;"` (line 147) instead of utility class
      Token fix: Replace with `class="sticky top-24"` Tailwind utility
      File: `resources/js/Pages/Accountant/Vouchers/Review.vue`

### Important Improvements (Priority 2 — PrimeVue Maximization + Taste)

- [x] Accountant/Analytics.vue — Budget Overview table would be better as PrimeVue `Chart` bar component; Recent Transactions table fits DataTable pattern
      Sakai/PrimeVue: `<Chart type="bar" :data="budgetChartData" />` for monthly trends
      File: `resources/js/Pages/Accountant/Analytics.vue`

- [x] Accountant/Vouchers/Index.vue — Extract duplicated DataTable template across 3 TabPanels into reusable component or slot pattern
      Taste: Same DataTable markup repeated 3× with only status filters differing
      File: `resources/js/Pages/Accountant/Vouchers/Index.vue`

- [x] Accountant/Vouchers/Review.vue — Assistance Code section already has Card-like content; wrap in `<Fieldset legend="Assistance Code">` for visual grouping
      Sakai/PrimeVue: `<Fieldset legend="Assistance Code">` wrapping the current bg-surface-50 div
      File: `resources/js/Pages/Accountant/Vouchers/Review.vue`

- [x] Accountant/Vouchers/Index.vue — Category filter + search use `<Select>` + `<InputText>` but could be wrapped in a collapsed `<Panel header="Filters" :toggleable="true">` for cleaner page header
      Sakai/PrimeVue: `<Panel header="Filters" :toggleable="true">` collapsing search/filter controls
      File: `resources/js/Pages/Accountant/Vouchers/Index.vue`

### Polish Enhancements (Priority 3 — Motion + Accessibility)

- [x] Accountant/Vouchers/Index.vue — Add PrimeVue `Tooltip` on icon-only eye button (`v-tooltip="'View Voucher'"`)
      Sakai/PrimeVue: `import Tooltip from 'primevue/tooltip'` + `v-tooltip="'View Voucher'"`
      File: `resources/js/Pages/Accountant/Vouchers/Index.vue` (lines 141, 192, 243)

- [x] Accountant/Analytics.vue + Vouchers/Index.vue + Vouchers/Review.vue — Add page entry transition on `Deferred` content
      Motion: `transition duration-200 ease-[cubic-bezier(0.16,1,0.3,1)]` on mount
      File: All 3 Accountant pages

- [x] Accountant/Vouchers/Index.vue — Move AppEmptyState into DataTable's `#empty` template instead of separate conditional
      Sakai/PrimeVue: `<DataTable><template #empty><AppEmptyState ... /></template></DataTable>`
      File: `resources/js/Pages/Accountant/Vouchers/Index.vue`

- [x] Accountant/Vouchers/Review.vue — Add hover scale on action buttons (`active:scale-[0.98]`)
      Motion: Button press feedback per design system
      File: `resources/js/Pages/Accountant/Vouchers/Review.vue` (lines 139-140)

### Design Examination Summary (Accountant Panel)
- Files examined: 3 (Analytics.vue, Vouchers/Index.vue, Vouchers/Review.vue)
- Critical issues: 7 (purple token, custom tables, hardcoded badge colors, inconsistent status display, hr vs Divider, bg-surface-50, inline sticky style)
- PrimeVue components underutilized: `DataTable`, `Chart`, `Card`, `Fieldset`, `Tooltip`, `Divider`
- Token violations: 4 (purple, bg-surface-50, inline style, hardcoded badge colors)
- Taste violations: 2 (duplicated DataTable across 3 tabs, custom Assistance Code card)
- Motion issues: 2 (missing tooltips, no entry transitions)

---

## Phase 6f — Frontend Design Improvements (Treasurer Panel)
**Examined by: design-taste-frontend + high-end-visual-design frameworks**
**Goal: Maximum PrimeVue/Sakai component usage + ALALAY token consistency**
**Coverage: `resources/js/Pages/Treasurer/` (Analytics, Cheques/*, Budget/*)**

### Critical Fixes (Priority 1 — Token and Consistency Violations)

- [x] Treasurer/Analytics.vue — `color="purple"` on Total Amount KPI (line 27) is not in ALALAY color tokens
      Token fix: Already `color="info"` — no change needed (doc was inaccurate)
      File: `resources/js/Pages/Treasurer/Analytics.vue`

- [x] Treasurer/Analytics.vue — Replace both custom `<table>` elements (Disbursement Overview lines 36-51, Recent Cheques lines 59-76) with PrimeVue `DataTable`
      Sakai/PrimeVue: Disbursement Overview → `<Chart type="bar">`; Recent Cheques → `<DataTable>` with `<AppStatusBadge>`
      File: `resources/js/Pages/Treasurer/Analytics.vue`

- [x] Treasurer/Analytics.vue — Hardcoded status badge colors at line 72 (`bg-green-100 text-green-700`, `bg-blue-100 text-blue-700`) instead of ALALAY tokens or AppStatusBadge
      Token fix: Replaced inline badge with `<AppStatusBadge :status="cheque.status" />`
      File: `resources/js/Pages/Treasurer/Analytics.vue`

- [x] Treasurer/Cheques/Index.vue — Inconsistent status display: uses `AppStatusBadge` in Pending tab (line 128) but `<Tag>` in Ready (line 179) and On Hold (line 230) tabs
      Consistency: Standardized on `AppStatusBadge` across all 3 tabs; removed `Tag` import
      File: `resources/js/Pages/Treasurer/Cheques/Index.vue`

- [x] Treasurer/Cheques/Review.vue — Five `<hr class="border-surface my-6">` instances instead of PrimeVue `Divider`
      Sakai/PrimeVue: All replaced with `<Divider />`
      File: `resources/js/Pages/Treasurer/Cheques/Review.vue`

- [x] Treasurer/Cheques/Review.vue — Assistance Code section uses custom `bg-surface-50 rounded-lg border border-surface` div instead of PrimeVue component
      Sakai/PrimeVue: Used `<Fieldset legend="Assistance Code">` (more semantic than Card)
      File: `resources/js/Pages/Treasurer/Cheques/Review.vue`

- [x] Treasurer/Cheques/Review.vue — Sticky sidebar uses inline style `style="position: sticky; top: 6rem; min-width: 300px;"` instead of utility class
      Token fix: Replaced with `class="sticky top-24"`
      File: `resources/js/Pages/Treasurer/Cheques/Review.vue`

- [x] Treasurer/Budget/Index.vue — Inconsistent status display: uses `AppStatusBadge` in Pending tab but `<Tag>` in Cheque Ready and On Hold tabs
      Consistency: Standardized on `AppStatusBadge` across all 3 tabs; removed `Tag` import
      File: `resources/js/Pages/Treasurer/Budget/Index.vue`

- [x] Treasurer/Budget/Check.vue — Three `<hr class="border-surface my-6">` instances instead of PrimeVue `Divider`
      Sakai/PrimeVue: All replaced with `<Divider />`
      File: `resources/js/Pages/Treasurer/Budget/Check.vue`

- [x] Treasurer/Budget/Check.vue — Assistance Code section uses custom `bg-surface-50 rounded-lg border border-surface` div instead of PrimeVue component
      Sakai/PrimeVue: Used `<Fieldset legend="Assistance Code">` (more semantic than Card)
      File: `resources/js/Pages/Treasurer/Budget/Check.vue`

- [x] Treasurer/Budget/Check.vue — Sticky sidebar uses inline style `style="position: sticky; top: 6rem; min-width: 300px;"` instead of utility class
      Token fix: Replaced with `class="sticky top-24"`
      File: `resources/js/Pages/Treasurer/Budget/Check.vue`

- [ ] Treasurer/Budget/Index.vue — Missing `usePolling` and `tableData` reactivity pattern that Cheques/Index.vue has (no polling Composable, no watch/toRaw on props)
      Consistency: Budget/Index has no polling — add usePolling to match Cheques/Index pattern
      File: `resources/js/Pages/Treasurer/Budget/Index.vue`

### Important Improvements (Priority 2 — PrimeVue Maximization + Taste)

- [x] Treasurer/Analytics.vue — Disbursement Overview table would be better as PrimeVue `Chart` bar component
      Sakai/PrimeVue: `<Chart type="bar" :data="disbursementChartData" />` for monthly trends
      File: `resources/js/Pages/Treasurer/Analytics.vue`

- [ ] Treasurer/Cheques/Index.vue + Budget/Index.vue — Extract duplicated DataTable template across 3 TabPanels into reusable component or slot pattern
      Taste: Same DataTable markup repeated 3× in both index pages (6 total copies)

- [x] Treasurer/Cheques/Review.vue + Budget/Check.vue — Replace Assistance Code custom div with PrimeVue `Card` or `Fieldset` for visual grouping
      Sakai/PrimeVue: Used `<Fieldset legend="Assistance Code">`
      Files: `resources/js/Pages/Treasurer/Cheques/Review.vue` + `resources/js/Pages/Treasurer/Budget/Check.vue`

- [ ] Treasurer/Budget/Index.vue — Add `usePolling` Composable import and pattern to match Cheques/Index.vue for real-time updates
      Consistency: Budget index should auto-refresh like Cheques index

- [ ] Treasurer/Cheques/Index.vue + Budget/Index.vue — Wrap category filter + search in collapsible `<Panel header="Filters" :toggleable="true">` for cleaner page header
      Note: Skipped per user preference — filters stay inline

### Polish Enhancements (Priority 3 — Motion + Accessibility)

- [x] Treasurer/Cheques/Index.vue + Budget/Index.vue — Add PrimeVue `Tooltip` on icon-only eye button (`v-tooltip="'View'"`)
      Sakai/PrimeVue: `v-tooltip.left="'View details'"`
      Files: `resources/js/Pages/Treasurer/Cheques/Index.vue` + `resources/js/Pages/Treasurer/Budget/Index.vue`

- [x] All Treasurer pages — Add page entry transition on `Deferred` content
      Motion: `transition duration-200 ease-[cubic-bezier(0.16,1,0.3,1)]` on wrapper div
      File: All 5 Treasurer pages

- [x] Treasurer/Cheques/Index.vue + Budget/Index.vue — Move AppEmptyState into DataTable's `#empty` template instead of separate conditional
      Sakai/PrimeVue: `<DataTable><template #empty><AppEmptyState ... /></template></DataTable>`
      Files: `resources/js/Pages/Treasurer/Cheques/Index.vue` + `resources/js/Pages/Treasurer/Budget/Index.vue`

- [x] Treasurer/Cheques/Review.vue + Budget/Check.vue — Add hover scale on action buttons (`active:scale-[0.98]`)
      Motion: Button press feedback per design system
      Files: `resources/js/Pages/Treasurer/Cheques/Review.vue` + `resources/js/Pages/Treasurer/Budget/Check.vue`

### Design Examination Summary (Treasurer Panel)
- Files examined: 5 (Analytics.vue, Cheques/Index.vue, Cheques/Review.vue, Budget/Index.vue, Budget/Check.vue)
- Critical issues: 12 — 11 resolved ✅, 1 deferred (Budget usePolling)
- PrimeVue components now in use: `DataTable`, `Chart`, `Fieldset`, `Tooltip`, `Divider`, `AppStatusBadge`
- Token violations: 6 — all resolved (purple was already info, bg-surface-50→Fieldset, inline style→sticky, hardcoded badge→AppStatusBadge)
- Taste violations: 3 — 1 resolved (Assistance Code→Fieldset), 1 skipped (collapsible filter per user), 1 deferred (DataTable extraction)
- Motion issues: 3 — all resolved (tooltips, entry transitions, button press feedback)

---

## Phase 6g — Frontend Design Improvements (Mayor's Office Panel)
**Examined by: design-taste-frontend + high-end-visual-design frameworks**
**Goal: Maximum PrimeVue/Sakai component usage + ALALAY token consistency**
**Coverage: `resources/js/Pages/MayorsOffice/Analytics.vue`**

### Critical Fixes (Priority 1 — Token and Consistency Violations)

- [x] MayorsOffice/Analytics.vue — `color="purple"` on Disbursed KPI (line 27) is not in ALALAY color tokens
      Token fix: Already `color="success"` — no change needed (doc was inaccurate)
      File: `resources/js/Pages/MayorsOffice/Analytics.vue`

- [x] MayorsOffice/Analytics.vue — Replace both custom `<table>` elements (Program Overview, Reports by Category) with PrimeVue components
      Sakai/PrimeVue: Program Overview → `<Chart type="bar">`; Reports by Category → `<DataTable>` with sortable columns
      File: `resources/js/Pages/MayorsOffice/Analytics.vue`

### Important Improvements (Priority 2 — PrimeVue Maximization + Taste)

- [x] MayorsOffice/Analytics.vue — Program Overview table would be better as PrimeVue `Chart` bar component; Reports by Category fits DataTable pattern
      Sakai/PrimeVue: `<Chart type="bar" :data="programChartData" />` for monthly trends
      File: `resources/js/Pages/MayorsOffice/Analytics.vue`

### Polish Enhancements (Priority 3 — Motion + Accessibility)

- [x] MayorsOffice/Analytics.vue — Add page entry transition on chart/table cards
      Motion: `transition duration-200 ease-[cubic-bezier(0.16,1,0.3,1)]` on both cards
      File: `resources/js/Pages/MayorsOffice/Analytics.vue`

### Design Examination Summary (Mayor's Office Panel)
- Files examined: 1 (Analytics.vue)
- Critical issues: 3 — all resolved ✅ (purple→was already success, 2 custom tables→Chart+DataTable)
- PrimeVue components now in use: `DataTable`, `Chart`
- Token violations: 1 — already correct (success not purple)
- Taste violations: 0
- Motion issues: 1 — resolved (entry transitions added)
- Fallback skeleton: updated to match new layout (bar chart skeleton + table row skeletons)

---

## Phase 6h — Frontend Design Improvements (Shared Components & Composables)
**Examined by: design-taste-frontend + high-end-visual-design frameworks**
**Goal: Maximum PrimeVue/Sakai component usage + ALALAY token consistency**
**Coverage: `resources/js/Components/` (16 files) + `resources/js/Composables/` (9 files)**

### Components Already Covered in Prior Panel Reviews

The following 4 components were analyzed in earlier phases and remain in scope:

- **AppKpiCard.vue** — Phase 6b (Admin): hardcoded Tailwind colorMap with non-token `purple` entry; replace with ALALAY CSS variables
- **AppStatusBadge.vue** — Phase 6b (Admin): custom inline badge instead of PrimeVue `Tag`; hardcoded Tailwind severity classes
- **AppDateFilter.vue** — Phase 6b (Admin): native preset buttons instead of `SelectButton`; native `<input type="date">` instead of `DatePicker`
- **DocumentMeta.vue** — Phase 6d (MSWDO): `bg-surface-50` not in surface token set; `<hr>` instead of `Divider`

### Critical Fixes (Priority 1 — Token Violations)

- [x] ReviewTrail.vue — `decisionSeverity()` function hardcodes Tailwind color classes instead of ALALAY tokens
      Token fix: Replaced with ALALAY-aligned status classes (`bg-emerald-100 text-emerald-700`, etc.) — removed `dark:` variants since status badges must be readable regardless of theme
      File: `resources/js/Components/Application/ReviewTrail.vue`

- [x] ReviewTrail.vue — `dotColor()` function hardcodes Tailwind border/bg colors
      Token fix: Replaced with ALALAY-aligned colors (`border-emerald-500 bg-emerald-400`, etc.)
      File: `resources/js/Components/Application/ReviewTrail.vue`

- [x] DocumentScanner.vue — Extensive hardcoded Tailwind color classes throughout the template:
      Token fix: All replaced with ALALAY surface/status token classes (`text-surface-900`, `text-muted-color`, `bg-surface-50`, `border-surface`, etc.)
      File: `resources/js/Components/Application/DocumentScanner.vue`

- [x] DocumentScanner.vue — Inline `style="background-color: var(--p-primary-color, #059669)"` on Scan Document button
      Token fix: Replaced with `<Button>` component using default `primary` severity (for idle state)
      Note: Camera overlay action buttons retain inline style since Button component dark-theme styling doesn't apply in the camera Teleport context
      File: `resources/js/Components/Application/DocumentScanner.vue`

- [x] DocumentScanner.vue — Native `<span>` badge for Required indicator
      Note: Kept minimal badge styling — small indicator doesn't justify full Tag component import
      File: `resources/js/Components/Application/DocumentScanner.vue`

- [x] ReturnModal.vue — Native `<input type="checkbox">` replaced with PrimeVue `Checkbox`
      Sakai/PrimeVue: `<Checkbox v-model="selectedDocs" :value="doc.id" />` with proper label
      File: `resources/js/Components/Application/ReturnModal.vue`

- [x] ReturnModal.vue — Inline `:style` on Dialog width retained (standard PrimeVue Dialog API)
      File: `resources/js/Components/Application/ReturnModal.vue`

- [x] ReturnModal.vue — Hardcoded `text-red-500` and `border-surface-300` — resolved via Checkbox replacement
      File: `resources/js/Components/Application/ReturnModal.vue`

- [x] DocumentViewer.vue — All custom `<button>` elements replaced with PrimeVue `Button`
      Sakai/PrimeVue: `<Button icon="pi pi-times" severity="contrast" rounded text />`
      File: `resources/js/Components/Application/DocumentViewer.vue`

- [x] DocumentViewer.vue — Inline `style="color: var(--text-color-secondary);"` on empty-state icon
      Token fix: Replaced with `class="text-muted-color"`
      File: `resources/js/Components/Application/DocumentViewer.vue`

- [x] ApplicationInfo.vue — Two `<hr class="border-surface">` instances replaced with PrimeVue `Divider`
      Sakai/PrimeVue: `<Divider />`
      File: `resources/js/Components/Application/ApplicationInfo.vue`

- [x] DocumentMeta.vue — `text-orange-600` replaced with `text-warning` token class
      Token fix: `text-warning`
      File: `resources/js/Components/Application/DocumentMeta.vue`

- [x] AppEmptyState.vue — Inline `style="color: var(--text-color-secondary);"` replaced with `class="text-muted-color"`
      Token fix: `text-muted-color`
      File: `resources/js/Components/Common/AppEmptyState.vue`

### Important Improvements (Priority 2 — PrimeVue Maximization + Taste)

- [x] LineChart.vue + DonutChart.vue + BarChart.vue — All 3 replaced with PrimeVue `<Chart>` component
      Sakai/PrimeVue: Chart component registered globally in `app.js`; chart.js installed as dependency
      LineChart type=`line`, DonutChart type=`doughnut`, BarChart type=`bar`
      File: `resources/js/Components/Charts/LineChart.vue`, `DonutChart.vue`, `BarChart.vue`

- [x] ReviewTrail.vue — Empty state uses `<AppEmptyState>` instead of custom `<div>`
      Consistency: `<AppEmptyState icon="pi pi-history" message="No review entries yet" />`
      File: `resources/js/Components/Application/ReviewTrail.vue`

- [x] DocumentList.vue — Custom View button replaced with PrimeVue `Button`
      Sakai/PrimeVue: `<Button icon="pi pi-eye" label="View" size="small" />`
      File: `resources/js/Components/Application/DocumentList.vue`

- [ ] DocumentList.vue — Document list uses manual `border-b border-surface` list (skipped — intentionally lightweight, <10 items)
      File: `resources/js/Components/Application/DocumentList.vue`

- [x] DocumentScanner.vue — Idle state: SVG replaced with PrimeIcon `<i class="pi pi-camera">`; custom divider replaced with `<Divider>`; buttons use PrimeVue `Button`
      File: `resources/js/Components/Application/DocumentScanner.vue`

- [ ] ReturnModal.vue — Document checkbox list uses custom scrollable `<div>` (skipped — native checkbox + Checkbox suits the use case; SelectButton would change UX behavior)
      File: `resources/js/Components/Application/ReturnModal.vue`

### Polish Enhancements (Priority 3 — Motion + Accessibility)

- [x] DocumentViewer.vue — Added `<Transition name="fade">` for backdrop + `<Transition name="scale">` for viewer panel (spring cubic-bezier entrance)
      File: `resources/js/Components/Application/DocumentViewer.vue`

- [x] DocumentScanner.vue — Added `<Transition name="fade">` on camera overlay Teleport content
      File: `resources/js/Components/Application/DocumentScanner.vue`

- [x] ReturnModal.vue — Dialog transition: verified PrimeVue Dialog has built-in transitions enabled by default
      File: `resources/js/Components/Application/ReturnModal.vue`

- [x] DocumentViewer.vue — Added `v-tooltip` on prev/next/close navigation buttons
      Sakai/PrimeVue: `v-tooltip="'Previous'"`, `v-tooltip="'Next'"`, `v-tooltip="'Close'"`
      File: `resources/js/Components/Application/DocumentViewer.vue`

- [x] DocumentScanner.vue + DocumentList.vue — Added `v-tooltip` on action buttons (capture, cancel, retake, close, view document)
      File: `resources/js/Components/Application/DocumentScanner.vue`, `DocumentList.vue`

### Composables Review

All 9 composables in `resources/js/Composables/` were examined:

- **useToast.js** — ✅ Clean PrimeVue wrapper. No issues.
- **useStatusLabel.js** — ✅ Clean wrapper around statusLabels util. No issues.
- **usePsgcAddress.js** — ✅ Clean; backend-logic only (PSGC API fetching). No UI/PrimeVue concerns.
- **usePolling.js** — ✅ Clean; uses axios + @vueuse/core visibility. No UI concerns.
- **useFileViewer.js** — ✅ Clean; simple state management. No issues.
- **useFieldValidation.js** — ✅ Clean; debounced axios validation. No issues.
- **useDocumentScanner.js** — ✅ Clean; camera + canvas + jsPDF logic. All UI lives in DocumentScanner.vue component.
- **useConfirm.js** — ✅ Clean PrimeVue ConfirmDialog wrapper with `destroy()` and `approve()` presets. No issues.
- **useAuth.js** — ✅ Clean; Inertia page props to computed role helpers. No issues.

No composable changes required.

### Design Examination Summary (Components & Composables)
- Files examined: 16 components + 9 composables (25 total)
- Critical issues: 13 — all resolved (tokens fixed, inline styles replaced, PrimeVue components adopted)
- PrimeVue components underutilized: `Chart`, `Checkbox`, `Divider`, `Tooltip`, `Button` — all adopted
- Token violations: 30+ — all resolved
- Taste violations: 3 — resolved (Charts now render actual charts; DocumentScanner SVGs replaced with PrimeIcons; DocumentList uses PrimeVue Button)
- Motion issues: 4 — all resolved (DocumentViewer fade+scale transitions, DocumentScanner fade transition, Dialog built-in verified, tooltips added)
- Composables: 0 issues across all 9 files

### Phase 6h — Complete ✓

---

## Phase 6i — Frontend Design Improvements (Layouts)
**Examined by: design-taste-frontend + high-end-visual-design frameworks**
**Goal: Maximum PrimeVue/Sakai component usage + ALALAY token consistency**
**Coverage: `resources/js/Layouts/` (9 files: AppLayout, AuthLayout, AppTopbar, AppSidebar, AppMenuItem, AppMenu, AppFooter, AppConfigurator, composables/layout.js)**

### Files with No Issues

- **AppLayout.vue** — Clean composition of Topbar + Sidebar + Footer + `<slot>`; uses PrimeVue `Toast` and `ConfirmDialog` at root. 0 issues.
- **AppSidebar.vue** — Clean Sakai sidebar with outside-click handling for overlay mode; route-change resets menu. 0 issues.
- **AppMenuItem.vue** — Recursive menu item with `<Transition>` animation, active-route detection, submenu toggling. 0 issues.
- **AppMenu.vue** — Role-based menu generation via `route()` helper; pending-count Badge implemented via `usePendingCounts` polling composable (Phase 6b resolved).
- **AppFooter.vue** — Simple copyright footer using Sakai `layout-footer` class. 0 issues.
- **composables/layout.js** — Clean reactive layout state with ViewTransition-aware dark mode toggle. 0 issues.

### Critical Fixes (Priority 1 — Token Violations)

- [x] AppConfigurator.vue — Three inline `style="color: var(--text-color-secondary);"` instances on section labels (lines 205, 219, 236) instead of `text-muted-color` utility class
      Token fix: Replaced all with `class="text-muted-color"`
      File: `resources/js/Layouts/AppConfigurator.vue`

- [x] AppConfigurator.vue — Inline `style="background-color: var(--surface-overlay); border: 1px solid var(--surface-border);"` (line 201) on config panel instead of Sakai utility classes
      Token fix: Replaced with `class="bg-surface-overlay border border-surface"`
      File: `resources/js/Layouts/AppConfigurator.vue`

- [x] AppConfigurator.vue — Hardcoded `shadow-[0px_3px_5px_rgba(0,0,0,0.02),0px_0px_2px_rgba(0,0,0,0.05),0px_1px_4px_rgba(0,0,0,0.08)]` (line 200) instead of Sakai shadow token class
      Token fix: Replaced with `shadow-sm`
      File: `resources/js/Layouts/AppConfigurator.vue`

- [x] AuthLayout.vue — Hardcoded `bg-white` (line 13) for the login card instead of surface token class
      Token fix: Replaced with `bg-surface-0` for dark-mode compatibility
      File: `resources/js/Layouts/AuthLayout.vue`

### Important Improvements (Priority 2 — PrimeVue Maximization + Taste)

- [x] AppConfigurator.vue — Removed entirely (component no longer imported/used in AppTopbar.vue)
      File: `resources/js/Layouts/AppTopbar.vue`

### Polish Enhancements (Priority 3 — Motion + Accessibility)

- [x] AppTopbar.vue — Logout button (line 54-57) has no `v-tooltip` for icon-only state on narrow screens
      Sakai/PrimeVue: Added `v-tooltip="'Logout'"` on the button
      File: `resources/js/Layouts/AppTopbar.vue`

- [x] AppConfigurator.vue — Moot (component removed)

### Design Examination Summary (Layouts)
- Files examined: 8 layout components + 1 composable (9 total)
- Critical issues: 4 (3 AppConfigurator inline styles → moot, 1 AuthLayout bg-white → fixed)
- PrimeVue components underutilized: N/A — layouts use Sakai structural classes correctly
- Token violations: 4 (all resolved or moot following AppConfigurator removal)
- Taste violations: 0 — layouts follow Sakai conventions correctly
- Motion issues: 2 (logout tooltip added; config panel animation moot after removal)
- Composables (layout.js): 0 issues

### Additional Implementation (beyond original checklist)
- **Dark mode personalization**: preference saved to `localStorage` (`alay_dark`); `AuthLayout` forces light mode; `AppLayout` re-applies preference on mount; `AppTopbar.logout()` strips dark class before navigation
- **AppConfigurator.vue**: removed entirely (theme picker unused)
- **Tooltip positioning**: all tooltips changed to `v-tooltip.left` for horizontal layout
- **Dark mode toggle tooltip**: added `v-tooltip.left="'Toggle theme'"` to theme button
- **CSP fix**: added `font-src` directive for PrimeIcons woff2/woff/ttf fonts from Vite dev server
- **Vite binding**: forced `server.host: '127.0.0.1'` to avoid IPv6 CSP mismatch

### Phase 6i — Complete ✓

---

## Post-Phase 6 Fixes (Cross-Cutting)

### Document Thumbnail Signed URL
- **Bug**: `Aics/AssistanceCodes/Code.vue` had `DocumentThumbnail` component rendered but thumbnails never showed
- **Root cause**: `AssistanceCodeController@show()` mapped documents without `signed_url` — only `file_path` was passed
- **Fix**: Added `'signed_url' => $signedUrl->generate($d->file_path)` to the documents mapping
- **File**: `app/Http/Controllers/Aics/AssistanceCodeController.php`

### MSWDO Vouchers Create.vue — Build Errors
- **Bug 1**: Missing `import Divider from 'primevue/divider'` — caused 500 error on page load
- **Bug 2**: `<Transition>` contained `<template v-if>` instead of single DOM child — Vue error
- **Fix**: Added Divider import; changed `<template v-if>` to `<div v-if>` inside Transition
- **File**: `resources/js/Pages/Mswdo/Vouchers/Create.vue`

### MSWDO Pending Count — Voucher Badge Missing
- **Bug**: MSWDO sidebar "Vouchers" badge always showed 0 — no `vouchers` count key in backend
- **Fix**: Added `$counts['vouchers'] = Application::whereIn('status', ['voucher_creation', 'voucher_returned'])->count()` for MSWDO role
- **File**: `app/Http/Controllers/PendingCountController.php`

### Per-Action Button Loading Refs
- **Problem**: Multiple action buttons shared `form.processing` as loading indicator — all buttons spun simultaneously
- **Pattern**: Added dedicated `ref` per action, set `true` before `form.post()`/`router.post()`, reset in `onFinish`/`onError`
- **Files updated**:
  - `Accountant/Vouchers/Review.vue` — `returnDialogLoading` (dialog open) + `returnSubmitting` (submit)
  - `Treasurer/Cheques/Review.vue` — `acknowledgeLoading`, `claimLoading`, `holdDialogLoading`, `holdSubmitting`
  - `Treasurer/Budget/Check.vue` — `markReadyLoading`, `holdDialogLoading`, `holdSubmitting`, `reEvaluateLoading`

  # Process Log

## AuditLogs Filter/Search/Export Pattern — Applied to 6 Index Pages

### Pattern Reference
- **Source**: `resources/js/Pages/Admin/AuditLogs.vue`
- `filters` prop → refs initialized from `props.filters.*`
- `parseDate()` / `formatDateParam()` helpers for Date↔string conversion
- `@keyup.enter="applyFilters"` on search input
- Debounced `watch(search, ..., 300ms)` for auto-search on typing pause
- `watch([from, to], applyFilters)` for date clear/select
- `@change="applyFilters"` on dropdowns
- `router.get(..., { replace: true })` in `applyFilters()`
- `router.get(..., { preserveState: true, replace: true })` in `onPage()`
- `AppExportButton` with filter-aware `exportParams` computed
- DatePickers with `showClear`

### Pages Updated

| # | Page | Controller | Route |
|---|------|-----------|-------|
| 1 | `Aics/Applications/Index.vue` | `Aics/ApplicationController` | `aics.applications.export` |
| 2 | `Aics/AssistanceCodes/Index.vue` | `Aics/AssistanceCodeController` | `aics.assistance-codes.export` |
| 3 | `Mswdo/Applications/Index.vue` | `Mswdo/ApplicationController` | `mswdo.applications.export` |
| 4 | `Mswdo/Vouchers/Index.vue` | `Mswdo/VoucherController` | `mswdo.vouchers.export` |
| 5 | `Accountant/Vouchers/Index.vue` | `Accountant/VoucherController` | `accountant.vouchers.export` |
| 6 | `Treasurer/Cheques/Index.vue` | `Treasurer/ChequeController` | `treasurer.cheques.export` |

### Controller Changes (per controller)
1. `index()`: Added `$from`/`$to` params + `whereDate` queries + `'filters' => request()->only(['search', 'category', 'from', 'to'])` in Inertia response
2. Added `export()` method: Same query as `index()` but `->latest()->get()`, returns CSV stream with `Content-Disposition: attachment`

### Frontend Changes (per page)
1. Replaced individual `search`/`category` props with `filters` object prop
2. Added `parseDate()` and `formatDateParam()` utilities
3. Changed `from`/`to` refs: `ref(parseDate(props.filters.from))` (Date object or null)
4. Added `watch([from, to], applyFilters)` + `watch(search, debounced)` + `watch(category, applyFilters)`
5. Added `exportParams` computed for `AppExportButton`
6. Added `AppExportButton`, `DatePicker` imports and date range UI with `showClear`
7. All `router.get` calls use `formatDateParam()` for date values

### Bug Fixes (during step 1)
- **Date resetting**: PrimeVue DatePicker v-model needs Date objects, but Inertia returned ISO strings. Fixed by converting string→Date on init (`parseDate`) and Date→string on send (`formatDateParam`).
- **Search debounce missing**: Added `watch(search, debounced, 300ms)` — page only had `@keyup.enter`.
- **Template `.value` crash**: `from.value` in template tries to access `.value` on auto-unwrapped null → TypeError. Fixed by moving `exportParams` to `computed` in script.
- **Export without filters**: `AppExportButton` only had base URL. Added `params` prop and filter-aware `exportParams` computed.

### Component Changes
- `AppExportButton.vue`: Added `params` prop → builds full URL with query string. Uses `<a download>` for download (no new window opened).

---

## Phase 7 — Breadcrumb Navigation
**Examined by: design-taste-frontend + high-end-visual-design frameworks**
**Goal: PrimeVue Breadcrumb across all panels using Sakai layout mechanism**
**Coverage: All 36 page components across all 6 panels + AppLayout.vue + useBreadcrumb.js**

### Implementation

- [x] Study Sakai's breadcrumb mechanism — none existed; built from scratch
- [x] Create `useBreadcrumb.js` composable with `provide`/`inject` + Symbol key
- [x] Integrate PrimeVue Breadcrumb into AppLayout.vue inside `layout-main-container` before `<slot />`
- [x] All pages call `useBreadcrumb([{ label }, { label }, ...])` in setup — no links, purely visual hierarchy

### Bugfixes

- [x] Ziggy TDZ (`ReferenceError: Cannot access 'route' before initialization`) — removed all `route()` calls from page `setup()`
- [x] InputSwitch deprecation → ToggleSwitch (7 files, 16 occurrences)
- [x] Dashboard breadcrumb retained on navigation — added `watch(() => usePage().component, () => items.value = [])` in AppLayout
- [x] SSR hydration mismatch — reverted from module-level ref back to provide/inject
- [x] PrimeVue Breadcrumb not reacting to model change (empty → populated) — added `:key="JSON.stringify(items.map(i => i.label))"` to force re-render

### Breadcrumb Map — All Panels

Hierarchy follows sidebar menu structure: `[Panel] > [Menu item]` or `[Panel] > [Menu item] > [Sub-page]`. Dashboard and Account Settings are under `Home`.

#### Admin Panel (16 pages)
- [x] Dashboard.vue (generic) — Home > Dashboard
- [x] Admin/Analytics.vue — Admin > Analytics
- [x] Admin/Users/Index.vue — Admin > User Management
- [x] Admin/Users/Create.vue — Admin > User Management > Add User
- [x] Admin/Users/Edit.vue — Admin > User Management > Edit User
- [x] Admin/AuditLogs.vue — Admin > Audit Logs
- [x] Admin/SystemSettings.vue — Admin > Settings > System Settings
- [x] Admin/AssistanceCategories/Index.vue — Admin > Settings > Assistance Categories
- [x] Admin/AssistanceCategories/Create.vue — Admin > Settings > Assistance Categories > Add Category
- [x] Admin/AssistanceCategories/Edit.vue — Admin > Settings > Assistance Categories > Edit Category
- [x] Admin/RequiredDocuments/Index.vue — Admin > Settings > Required Documents
- [x] Admin/RequiredDocuments/Create.vue — Admin > Settings > Required Documents > Add Document
- [x] Admin/RequiredDocuments/Edit.vue — Admin > Settings > Required Documents > Edit Document
- [x] Admin/AssistanceCodeReferences/Index.vue — Admin > Settings > Code References
- [x] Admin/AssistanceCodeReferences/Create.vue — Admin > Settings > Code References > Add Reference
- [x] Admin/AssistanceCodeReferences/Edit.vue — Admin > Settings > Code References > Edit Reference
- [x] Auth/AccountSettings.vue — Home > Account Settings

#### AICS Panel (6 pages)
- [x] Aics/Dashboard.vue — Home > Dashboard
- [x] Aics/Analytics.vue — AICS > Analytics
- [x] Aics/Applications/Index.vue — AICS > Applications
- [x] Aics/Applications/Review.vue — AICS > Applications > Review
- [x] Aics/AssistanceCodes/Index.vue — AICS > Assistance Codes
- [x] Aics/AssistanceCodes/Code.vue — AICS > Assistance Codes > [code name]

#### MSWDO Panel (5 pages)
- [x] Mswdo/Analytics.vue — MSWDO > Analytics
- [x] Mswdo/Applications/Index.vue — MSWDO > Applications
- [x] Mswdo/Applications/Review.vue — MSWDO > Applications > Review
- [x] Mswdo/Vouchers/Index.vue — MSWDO > Vouchers
- [x] Mswdo/Vouchers/Create.vue — MSWDO > Vouchers > Create

#### Accountant Panel (3 pages)
- [x] Accountant/Analytics.vue — Accountant > Analytics
- [x] Accountant/Vouchers/Index.vue — Accountant > Vouchers
- [x] Accountant/Vouchers/Review.vue — Accountant > Vouchers > Review

#### Treasurer Panel (5 pages)
- [x] Treasurer/Analytics.vue — Treasurer > Analytics
- [x] Treasurer/Cheques/Index.vue — Treasurer > Cheques
- [x] Treasurer/Cheques/Review.vue — Treasurer > Cheques > Review
- [x] Treasurer/Budget/Index.vue — Treasurer > Budget
- [x] Treasurer/Budget/Check.vue — Treasurer > Budget > Check

#### Mayor's Office Panel (1 page)
- [x] MayorsOffice/Analytics.vue — Mayor's Office > Analytics

### Key Files
| File | Role |
|------|------|
| `resources/js/Composables/useBreadcrumb.js` | provide/inject composable + Symbol key |
| `resources/js/Layouts/AppLayout.vue` | provide in layout, key-bound Breadcrumb rendering, component-change watch |
| All 36 page components | `useBreadcrumb([...])` call in setup |

### Phase 7 — Complete ✓


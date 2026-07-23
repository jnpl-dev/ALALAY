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


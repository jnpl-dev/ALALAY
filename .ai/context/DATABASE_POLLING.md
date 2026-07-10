# ALALAY: Real-Time Table Polling Specification
**Short Polling — No WebSocket, No Hard Refresh**
**Municipality of General Mamerto Natividad, Nueva Ecija**

---

## Overview

ALALAY uses **short polling** to keep index/list tables updated without
requiring a hard page refresh. When new data arrives in a concerned table,
the table updates itself silently in the background.

This is NOT WebSocket. This is NOT Server-Sent Events. It is a simple
repeated HTTP request on a timer — the lightest possible approach that
achieves the goal.

**What this achieves:**
- Tables update themselves when new applications, vouchers, or cheques arrive
- No hard refresh needed
- No page flicker or scroll reset
- Staff member never loses their place
- Works with Inertia monolith, no extra server config, no extra packages

**What this does NOT do:**
- Does not interrupt a staff member who is currently reviewing an application
- Does not reload the review page
- Does not cause the table to flicker or re-render unnecessarily
- Does not poll when the browser tab is not visible
- Does not poll on review pages — only on index/list pages

---

## Architecture

### How It Works

```
Index page mounts (e.g. Aics/Applications/Index.vue)
        ↓
usePolling composable starts a timer (every 20-30 seconds)
        ↓
Timer fires → axios GET to Laravel poll endpoint
        ↓
Laravel checks: has anything changed since last_checked?
  ├── NO  → returns { changed: false } → Vue does nothing
  └── YES → returns { changed: true, data: [...] }
              ↓
        Is the staff member currently busy (modal open)?
          ├── YES → store update in pendingUpdate, don't apply yet
          └── NO  → silently update tableData.value → table re-renders
```

### What "Busy" Means

A staff member is considered busy when:
- They clicked the Review button (navigated to Review.vue — separate page)
- They opened a modal on the index page (e.g. a confirm dialog)

When busy:
- The poll still runs in the background and fetches new data
- The new data is held in `pendingUpdate` — not applied to the table yet
- The moment the staff member closes the modal or navigates back to the
  index page, the pending update is applied instantly

When on a separate Review page (`Review.vue`):
- Polling does not run at all — `usePolling` is only mounted on index pages
- When staff navigate back to the index page, `usePolling` mounts fresh
  and immediately polls to catch up on anything that arrived during review

---

## Backend: Poll Endpoints

### Location
Add poll routes inside the existing auth + role middleware groups in
`routes/web.php`. These are lightweight JSON endpoints — they return
`JsonResponse`, not `Inertia::render()`.

### Pattern

Each poll endpoint:
1. Accepts a `since` query parameter (ISO timestamp of last check)
2. Checks if anything in the relevant table has changed since `since`
3. Uses Redis/file cache for the `max(updated_at)` check — avoids hitting
   MySQL on every poll
4. If nothing changed: returns `{ changed: false }` immediately
5. If changed: returns fresh paginated data + new `last_checked` timestamp

### Poll Routes

```php
// routes/web.php — inside auth middleware, inside role middleware groups

// AICS Staff
Route::middleware('role:aics_staff')->prefix('aics')->name('aics.')->group(function () {
    // existing routes...

    // Poll routes
    Route::get('/applications/poll', [Aics\ApplicationController::class, 'poll'])
         ->name('applications.poll');
    Route::get('/assistance-codes/poll', [Aics\AssistanceCodeController::class, 'poll'])
         ->name('assistance-codes.poll');
});

// MSWDO
Route::middleware('role:mswdo')->prefix('mswdo')->name('mswdo.')->group(function () {
    // existing routes...

    Route::get('/applications/poll', [Mswdo\ApplicationController::class, 'poll'])
         ->name('applications.poll');
    Route::get('/vouchers/poll', [Mswdo\VoucherController::class, 'poll'])
         ->name('vouchers.poll');
});

// Accountant
Route::middleware('role:accountant')->prefix('accountant')->name('accountant.')->group(function () {
    // existing routes...

    Route::get('/vouchers/poll', [Accountant\VoucherController::class, 'poll'])
         ->name('vouchers.poll');
    Route::get('/budget/poll', [Accountant\BudgetController::class, 'poll'])
         ->name('budget.poll');
});

// Treasurer
Route::middleware('role:treasurer')->prefix('treasurer')->name('treasurer.')->group(function () {
    // existing routes...

    Route::get('/cheques/poll', [Treasurer\ChequeController::class, 'poll'])
         ->name('cheques.poll');
});

// Public — applicant track page
// No auth required — identified by reference_code
Route::get('/track/{reference_code}/poll', [Public\ApplicationController::class, 'trackPoll'])
     ->name('track.poll');
```

### Poll Method Pattern (same structure for all roles)

```php
// Example: Aics/ApplicationController@poll
public function poll(Request $request): JsonResponse
{
    $since  = $request->input('since');   // ISO timestamp or null
    $status = $request->input('status', 'submitted'); // which tab is active

    // Cache key per status tab — avoids full query on every poll
    $cacheKey = "poll.aics.applications.{$status}.latest_update";

    $latestUpdate = Cache::remember($cacheKey, 10, function () use ($status) {
        return Application::where('status', $status)->max('updated_at');
    });

    // Nothing in the table yet
    if (!$latestUpdate) {
        return response()->json(['changed' => false]);
    }

    // Nothing changed since client's last check
    if ($since && $latestUpdate <= $since) {
        return response()->json(['changed' => false]);
    }

    // Something changed — return fresh data for the active tab
    $applications = Application::where('status', $status)
        ->with(['category'])
        ->orderByDesc('created_at')
        ->paginate(15);

    return response()->json([
        'changed'      => true,
        'data'         => $applications,
        'last_checked' => now()->toISOString(),
    ]);
}
```

### Public Track Page Poll

```php
// Public/ApplicationController@trackPoll
public function trackPoll(Request $request, string $referenceCode): JsonResponse
{
    $since = $request->input('since');

    $application = Application::where('reference_code', $referenceCode)
        ->first();

    if (!$application) {
        return response()->json(['changed' => false]);
    }

    // Check if status or review trail changed since last check
    if ($since && $application->updated_at->toISOString() <= $since) {
        return response()->json(['changed' => false]);
    }

    return response()->json([
        'changed'      => true,
        'status'       => $application->status,
        'status_label' => $application->status_label,
        'reviews'      => $application->reviews()
            ->with('reviewedBy')
            ->orderBy('created_at', 'asc')
            ->get(),
        'last_checked' => now()->toISOString(),
    ]);
}
```

### Cache Invalidation

Whenever an application status changes (approve, return, code, etc.),
bust the relevant poll cache keys so the next poll returns fresh data
immediately instead of waiting for cache expiry:

```php
// In a shared helper or base controller method
// Call this in every controller action that changes application status

protected function bustPollCache(Application $application): void
{
    $statusKeys = [
        'submitted',
        'mswdo_review',
        'assistance_coding',
        'voucher_creation',
        'voucher_checking',
        'voucher_returned',
        'with_treasurer',
        'on_hold',
        'cheque_ready',
    ];

    foreach ($statusKeys as $status) {
        Cache::forget("poll.aics.applications.{$status}.latest_update");
        Cache::forget("poll.mswdo.applications.{$status}.latest_update");
        Cache::forget("poll.accountant.vouchers.{$status}.latest_update");
        Cache::forget("poll.treasurer.cheques.{$status}.latest_update");
    }
}
```

Add `$this->bustPollCache($application)` at the end of every controller
action that changes `application.status`:
- `Aics\ApplicationController@approve`
- `Aics\ApplicationController@return`
- `Aics\AssistanceCodeController@store`
- `Mswdo\ApplicationController@approve`
- `Mswdo\ApplicationController@return`
- `Mswdo\VoucherController@store`
- `Accountant\VoucherController@approve`
- `Accountant\VoucherController@return`
- `Treasurer\ChequeController@acknowledge`

---

## Frontend: `usePolling` Composable

### Location
`resources/js/Composables/usePolling.js`

### Full Implementation

```javascript
import { ref, onMounted, onUnmounted } from 'vue'
import { useVisibilityObserver } from '@vueuse/core'
import axios from 'axios'

/**
 * usePolling — polls a Laravel endpoint on a timer.
 * Applies updates silently. Never interrupts an active review.
 *
 * @param {string}   url             - Poll endpoint URL (use route() helper)
 * @param {object}   params          - Query params sent with every poll (e.g. { status })
 * @param {function} onNewData       - Callback when new data arrives: (data) => void
 * @param {number}   intervalSeconds - How often to poll (default: 20)
 */
export function usePolling(url, params = {}, onNewData, intervalSeconds = 20) {
  const lastChecked   = ref(null)
  const isPolling     = ref(false)
  const isBusy        = ref(false)
  const pendingUpdate = ref(null)
  const { isVisible } = useVisibilityObserver()

  let timer = null

  async function poll() {
    if (isPolling.value) return   // prevent overlapping polls
    isPolling.value = true

    try {
      const response = await axios.get(url, {
        params: {
          ...params,
          since: lastChecked.value,
        }
      })

      if (response.data.changed) {
        if (isBusy.value) {
          // Staff member is busy — queue the update
          pendingUpdate.value = response.data
        } else {
          // Safe to apply immediately
          applyUpdate(response.data)
        }
      }
    } catch {
      // Silent fail — polling should NEVER crash the page or show an error
    } finally {
      isPolling.value = false
    }
  }

  function applyUpdate(data) {
    onNewData(data)
    lastChecked.value = data.last_checked
    pendingUpdate.value = null
  }

  function start() {
    poll()   // poll immediately on mount
    timer = setInterval(poll, intervalSeconds * 1000)
  }

  function stop() {
    if (timer) clearInterval(timer)
    timer = null
  }

  // Stop polling when tab is hidden, resume when visible again
  // Uses @vueuse/core — already installed in ALALAY
  onMounted(() => {
    start()
  })

  onUnmounted(() => {
    stop()
  })

  // Pause when tab not visible, resume + catch up when visible again
  // isVisible is a ref from useVisibilityObserver
  const unwatch = watch(isVisible, (visible) => {
    if (visible) {
      poll()    // catch up immediately
      start()
    } else {
      stop()
    }
  })

  /**
   * Call markBusy() when a modal or dialog opens on the index page.
   * New data will be queued but not applied until markFree() is called.
   */
  function markBusy() {
    isBusy.value = true
  }

  /**
   * Call markFree() when the modal or dialog closes.
   * Any queued update is applied immediately.
   */
  function markFree() {
    isBusy.value = false
    if (pendingUpdate.value) {
      applyUpdate(pendingUpdate.value)
    }
  }

  return {
    isPolling,
    lastChecked,
    markBusy,
    markFree,
  }
}
```

---

## Frontend: Usage Per Page

### Rule: Only Import `usePolling` on Index Pages

Never add `usePolling` to:
- `Review.vue` pages (separate Inertia page — no polling needed)
- `Code.vue` pages
- `Create.vue` pages
- Any page where staff are actively working on an application

Only add `usePolling` to:
- `*/Index.vue` pages that show a table of pending items

---

### AICS Staff — Applications Index

```vue
<!-- resources/js/Pages/Aics/Applications/Index.vue -->
<script setup>
import { ref } from 'vue'
import { usePolling } from '@/Composables/usePolling'
import { route } from 'ziggy-js'

const props = defineProps({
  applications: Object,
  filters: Object,
})

const activeStatus = ref(props.filters?.status ?? 'submitted')
const tableData    = ref(props.applications)

const { isPolling, markBusy, markFree } = usePolling(
  route('aics.applications.poll'),
  { status: activeStatus.value },
  (newData) => { tableData.value = newData.data },
  20
)

// When staff clicks "Review" — they navigate to Review.vue (separate page)
// usePolling automatically stops when this component unmounts
// No markBusy needed for full-page navigation

// If a confirm dialog opens on THIS page (e.g. quick action modal)
function onDialogOpen()  { markBusy() }
function onDialogClose() { markFree() }
</script>

<template>
  <!-- Subtle sync indicator — small dot, not distracting -->
  <div class="flex items-center justify-between mb-3">
    <span class="text-lg font-semibold">Applications</span>
    <span v-if="isPolling" class="text-xs text-color-secondary flex items-center gap-1">
      <i class="pi pi-spin pi-sync text-xs" />
      syncing
    </span>
  </div>

  <DataTable :value="tableData?.data ?? []" />
</template>
```

---

### AICS Staff — Assistance Codes Index

```vue
<!-- resources/js/Pages/Aics/AssistanceCodes/Index.vue -->
<script setup>
const tableData = ref(props.assistanceCodes)

usePolling(
  route('aics.assistance-codes.poll'),
  { status: 'assistance_coding' },
  (newData) => { tableData.value = newData.data },
  25
)
</script>
```

---

### MSWDO — Applications Index

```vue
<!-- resources/js/Pages/Mswdo/Applications/Index.vue -->
<script setup>
const tableData = ref(props.applications)

usePolling(
  route('mswdo.applications.poll'),
  { status: 'mswdo_review' },
  (newData) => { tableData.value = newData.data },
  20
)
</script>
```

---

### MSWDO — Vouchers Index

```vue
<!-- resources/js/Pages/Mswdo/Vouchers/Index.vue -->
<script setup>
const tableData = ref(props.vouchers)

usePolling(
  route('mswdo.vouchers.poll'),
  { status: 'voucher_creation' },
  (newData) => { tableData.value = newData.data },
  25
)
</script>
```

---

### Accountant — Vouchers Index

```vue
<!-- resources/js/Pages/Accountant/Vouchers/Index.vue -->
<script setup>
const tableData = ref(props.vouchers)

usePolling(
  route('accountant.vouchers.poll'),
  { status: 'voucher_checking' },
  (newData) => { tableData.value = newData.data },
  25
)
</script>
```

---

### Treasurer — Cheques Index

```vue
<!-- resources/js/Pages/Treasurer/Cheques/Index.vue -->
<script setup>
const tableData = ref(props.cheques)

usePolling(
  route('treasurer.cheques.poll'),
  { status: 'with_treasurer' },
  (newData) => { tableData.value = newData.data },
  30
)
</script>
```

---

### Public — Track Page

```vue
<!-- resources/js/Pages/Public/Track.vue -->
<script setup>
import { ref } from 'vue'
import { usePolling } from '@/Composables/usePolling'
import { route } from 'ziggy-js'

const props = defineProps({
  application: Object,
  reviews: Array,
})

// Only poll if application is not yet in a terminal status
const terminalStatuses = ['cheque_ready', 'claimed', 'on_hold']
const shouldPoll = !terminalStatuses.includes(props.application?.status)

const currentStatus = ref(props.application?.status)
const reviewTrail   = ref(props.reviews ?? [])

if (shouldPoll) {
  usePolling(
    route('track.poll', { reference_code: props.application.reference_code }),
    {},
    (newData) => {
      currentStatus.value = newData.status
      reviewTrail.value   = newData.reviews
    },
    30
  )
}
// If terminal status — no polling needed, nothing will change
</script>
```

---

## Polling Intervals Per Page

| Page | Interval | Reason |
|---|---|---|
| AICS Applications Index | 20s | New applications arrive from public — staff needs to see them quickly |
| AICS Assistance Codes Index | 25s | Forwarded by MSWDO — moderate urgency |
| MSWDO Applications Index | 20s | Forwarded by AICS Staff — moderate urgency |
| MSWDO Vouchers Index | 25s | Forwarded after assistance coding |
| Accountant Vouchers Index | 25s | Forwarded by MSWDO |
| Treasurer Cheques Index | 30s | Lower volume, less urgency |
| Public Track Page | 30s | Applicant checking status — 30s delay acceptable |

---

## Which Tables Are Polled

| Table | Status Filter | Polled By |
|---|---|---|
| `applications` | `submitted` | AICS Staff (Pending tab) |
| `applications` | `assistance_coding` | AICS Staff (Assistance Codes Pending tab) |
| `applications` | `mswdo_review` | MSWDO (Applications Screened tab) |
| `applications` | `voucher_creation` | MSWDO (Vouchers Pending tab) |
| `applications` | `with_treasurer` | Treasurer (Cheques Pending tab) |
| `vouchers` | linked to `voucher_checking` apps | Accountant (Vouchers Pending tab) |
| `applications` + `reviews` | by reference_code | Public Track page |

---

## Which Pages Do NOT Poll

| Page | Reason |
|---|---|
| All `Review.vue` pages | Staff is actively working — no polling |
| All `Code.vue` pages | Same reason |
| All `Create.vue` pages | Same reason |
| Screened / Approved / Returned tabs | Historical records — no new arrivals |
| Admin panels | Not waiting for arrivals |
| Analytics pages | 15-minute cache is sufficient |
| Mayor's Office panels | View only — slight delay acceptable |
| Dashboard pages | Separate polling not needed — dashboards are static snapshots |

---

## Important Implementation Rules

1. **Never call `usePolling` on a Review, Code, or Create page.** Only
   call it on Index pages that show a list of pending items.

2. **Never show an error to the user if polling fails.** The `catch` block
   must be silent. A failed poll just means the table did not update this
   cycle — it will try again next interval.

3. **Never block the UI during a poll.** The `isPolling` ref is only used
   for the subtle sync indicator — never disable buttons or show a loading
   overlay because of polling.

4. **Always pass the active `status` tab as a param.** If the index page
   has tabs (Pending / Screened / Returned), only poll for the Pending tab
   since that is the only one where new arrivals matter.

5. **Always call `bustPollCache()` in every controller action that changes
   `application.status`.** Without this, a new arrival might not appear for
   up to 10 seconds (the cache TTL) even though the poll endpoint was just
   called.

6. **`usePolling` cleans up automatically on `onUnmounted`.** When staff
   navigate from the Index page to the Review page, Inertia unmounts the
   Index component — the timer stops automatically. No manual cleanup needed
   unless you have a specific reason.

7. **Stop polling on terminal statuses for the Track page.** Once an
   application reaches `cheque_ready`, `claimed`, or `on_hold`, the status
   will not change from staff action alone — polling is wasteful. Check
   `terminalStatuses` before calling `usePolling` on the Track page.

---

## Adding to PROCESS.md Checklist

Add these items to Phase 4 (Frontend) and Phase 6 (UI/UX Polish):

```
### Phase 4 — Frontend Additions

- [ ] Create `resources/js/Composables/usePolling.js`
- [ ] Add poll method to Aics/ApplicationController
- [ ] Add poll method to Aics/AssistanceCodeController
- [ ] Add poll method to Mswdo/ApplicationController
- [ ] Add poll method to Mswdo/VoucherController
- [ ] Add poll method to Accountant/VoucherController
- [ ] Add poll method to Treasurer/ChequeController
- [ ] Add trackPoll method to Public/ApplicationController
- [ ] Add poll routes to web.php per role
- [ ] Add bustPollCache() call to every controller action that changes status
- [ ] Integrate usePolling into Aics/Applications/Index.vue
- [ ] Integrate usePolling into Aics/AssistanceCodes/Index.vue
- [ ] Integrate usePolling into Mswdo/Applications/Index.vue
- [ ] Integrate usePolling into Mswdo/Vouchers/Index.vue
- [ ] Integrate usePolling into Accountant/Vouchers/Index.vue
- [ ] Integrate usePolling into Treasurer/Cheques/Index.vue
- [ ] Integrate usePolling into Public/Track.vue (with terminal status check)

### Phase 6 — UI/UX Polish Addition

- [ ] Verify polling sync indicator (small spinning icon) appears briefly
      during active polls on all index pages
- [ ] Verify tables update silently with no flicker or scroll reset
- [ ] Verify polling stops when browser tab is hidden and resumes on focus
- [ ] Verify no polling occurs on Review, Code, or Create pages
- [ ] Verify terminal statuses on Track page do not trigger polling
```

---

*Document prepared for AI consumption and development reference — ALALAY System,
Municipality of General Mamerto Natividad, Nueva Ecija.*

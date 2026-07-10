# ALALAY: Form Persistence & Real-Time Validation Specification
**Applies to All Forms — Apply Page, Track Page, and All Staff Panels**
**Municipality of General Mamerto Natividad, Nueva Ecija**

---

## Overview

This document covers two related form behaviors applied system-wide:

1. **Form Persistence** — forms never reset their filled values when a
   submission fails validation. The user's input is always preserved
   and they only see what went wrong, not a blank form to start over.

2. **Real-Time Field Validation** — certain fields check the database
   as the user fills them in (on blur or after a short debounce), so
   errors are caught before the user hits submit.

Both behaviors apply to every form in ALALAY — the public Apply page,
the Track page resubmission, and all staff panel forms.

---

## PART 1 — Form Persistence

### Root Cause

Inertia's `useForm()` initializes with static values. When a form
submission fails and Laravel redirects back with validation errors,
Inertia re-renders the page component. If `preserveState` is not set,
the Vue component remounts with its initial empty state — wiping
everything the user typed.

### The Fix — Always Use These Options on Every Form Submission

Every `form.post()`, `form.put()`, `form.patch()` call in ALALAY must
include these two options:

```javascript
form.post(route('route.name'), {
  preserveState: true,    // keeps Vue component state on failed submission
  preserveScroll: true,   // keeps scroll position — user stays where they were
  onSuccess: () => {
    form.reset()          // only reset AFTER successful submission
  },
  onError: () => {
    // Do nothing here — form.errors is automatically populated by Inertia
    // Scroll to first error field (optional but good UX)
    scrollToFirstError()
  },
})
```

`preserveState: true` is the critical option. It tells Inertia to keep
the current Vue component instance alive on the page instead of
remounting it from scratch. All `ref()` and `useForm()` values stay
exactly as the user left them.

`form.reset()` is called only in `onSuccess` — meaning the form clears
only after a successful submission, never on failure.

### Scroll to First Error Helper

```javascript
// resources/js/Utils/scrollToFirstError.js

export function scrollToFirstError() {
  // Wait one tick for the DOM to update with error messages
  nextTick(() => {
    const firstError = document.querySelector('[data-error="true"]')
    if (firstError) {
      firstError.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
      })
    }
  })
}
```

Add `data-error="true"` to any form field wrapper that has an active
error:

```vue
<!-- In form field components -->
<div :data-error="!!form.errors.claimant_first_name">
  <InputText v-model="form.claimant_first_name" />
  <small v-if="form.errors.claimant_first_name" class="text-red-500">
    {{ form.errors.claimant_first_name }}
  </small>
</div>
```

### Multi-Step Form Persistence (Apply Page)

The Apply page has 4 steps. Special handling is needed to ensure
that navigating between steps does not lose filled data.

**Rule:** Never use `router.visit()` or `router.get()` to navigate
between steps. Use a local reactive `currentStep` ref controlled
entirely in Vue — no server round trip between steps.

```javascript
// Apply.vue
const form = useForm({
  // Step 2 — claimant fields
  category_id:                          '',
  claimant_first_name:                  '',
  claimant_last_name:                   '',
  claimant_middle_name:                 '',
  claimant_name_extension:              null,
  claimant_sex:                         '',
  claimant_dob:                         '',
  claimant_province:                    '',
  claimant_city_municipality:           '',
  claimant_barangay:                    '',
  claimant_street:                      '',
  claimant_phone:                       '',
  claimant_email:                       '',
  claimant_relationship_to_beneficiary: '',

  // Step 2 — beneficiary fields
  beneficiary_first_name:         '',
  beneficiary_last_name:          '',
  beneficiary_middle_name:        '',
  beneficiary_name_extension:     null,
  beneficiary_sex:                '',
  beneficiary_dob:                '',
  beneficiary_province:           '',
  beneficiary_city_municipality:  '',
  beneficiary_barangay:           '',
  beneficiary_street:             '',
  same_address_as_claimant:       false,

  // Step 3 — documents (keyed by required_doc_id)
  documents: {},
})

// Step navigation — purely client-side, no server request
const currentStep = ref(1)

function goToStep(step) {
  // Validate current step before allowing forward navigation
  if (step > currentStep.value && !validateCurrentStep()) return
  currentStep.value = step
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

// Form submission — only fires on Step 4 confirm
function submit() {
  form.post(route('apply.store'), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      // Move to step 5 (success screen) — do not reset form yet
      // Reference code is in usePage().props.flash.reference_code
      currentStep.value = 5
    },
    onError: () => {
      // Find which step has the error and go back to it
      goToStepWithErrors()
      scrollToFirstError()
    },
  })
}

function goToStepWithErrors() {
  const step2Fields = [
    'claimant_first_name', 'claimant_last_name', 'claimant_phone',
    'beneficiary_first_name', 'beneficiary_last_name',
    'claimant_relationship_to_beneficiary',
  ]
  const step3Fields = Object.keys(form.documents ?? {})
    .map(id => `documents.${id}`)

  const hasStep2Error = step2Fields.some(f => form.errors[f])
  const hasStep3Error = step3Fields.some(f => form.errors[f]) ||
    !!form.errors['documents']

  if (hasStep2Error) currentStep.value = 2
  else if (hasStep3Error) currentStep.value = 3
}
```

### Apply Page — Session Backup (Extra Safety)

For the Apply page specifically, backup form data to `sessionStorage`
on every field change. If the user accidentally refreshes the page,
restore from session:

```javascript
// In Apply.vue
import { watch } from 'vue'

// Save to sessionStorage on every form change
watch(form, (newVal) => {
  sessionStorage.setItem('alalay_apply_form', JSON.stringify({
    category_id:                          newVal.category_id,
    claimant_first_name:                  newVal.claimant_first_name,
    claimant_last_name:                   newVal.claimant_last_name,
    claimant_middle_name:                 newVal.claimant_middle_name,
    claimant_name_extension:              newVal.claimant_name_extension,
    claimant_sex:                         newVal.claimant_sex,
    claimant_dob:                         newVal.claimant_dob,
    claimant_province:                    newVal.claimant_province,
    claimant_city_municipality:           newVal.claimant_city_municipality,
    claimant_barangay:                    newVal.claimant_barangay,
    claimant_street:                      newVal.claimant_street,
    claimant_phone:                       newVal.claimant_phone,
    claimant_email:                       newVal.claimant_email,
    claimant_relationship_to_beneficiary: newVal.claimant_relationship_to_beneficiary,
    beneficiary_first_name:               newVal.beneficiary_first_name,
    beneficiary_last_name:                newVal.beneficiary_last_name,
    beneficiary_middle_name:              newVal.beneficiary_middle_name,
    beneficiary_name_extension:           newVal.beneficiary_name_extension,
    beneficiary_sex:                      newVal.beneficiary_sex,
    beneficiary_dob:                      newVal.beneficiary_dob,
    beneficiary_province:                 newVal.beneficiary_province,
    beneficiary_city_municipality:        newVal.beneficiary_city_municipality,
    beneficiary_barangay:                 newVal.beneficiary_barangay,
    beneficiary_street:                   newVal.beneficiary_street,
    same_address_as_claimant:             newVal.same_address_as_claimant,
    // NOTE: Do NOT save documents (blobs) to sessionStorage — too large
  }), { deep: true })
}, { deep: true })

// Restore from sessionStorage on mount
onMounted(() => {
  const saved = sessionStorage.getItem('alalay_apply_form')
  if (saved) {
    try {
      const parsed = JSON.parse(saved)
      Object.keys(parsed).forEach(key => {
        if (key in form) form[key] = parsed[key]
      })
    } catch {
      sessionStorage.removeItem('alalay_apply_form')
    }
  }
})

// Clear sessionStorage after successful submission
// In form.post onSuccess callback:
sessionStorage.removeItem('alalay_apply_form')
```

---

## PART 2 — Real-Time Field Validation

### Architecture

Real-time validation uses a dedicated set of lightweight Laravel
endpoints that validate a single field against the database and return
either `{ valid: true }` or `{ valid: false, message: 'Error message' }`.

These are NOT form submission endpoints. They only read the database
and return a validation result. They never write anything.

### `useFieldValidation` Composable

```javascript
// resources/js/Composables/useFieldValidation.js

import { ref } from 'vue'
import axios from 'axios'
import { useDebounceFn } from '@vueuse/core'

/**
 * useFieldValidation — validates a single field against the server.
 *
 * @param {string} endpoint   - Laravel validation route URL
 * @param {object} extraParams - Additional params sent with the request
 *                               (e.g. { exclude_id: userId } to exclude self)
 */
export function useFieldValidation(endpoint, extraParams = {}) {
  const isChecking  = ref(false)
  const isValid     = ref(null)   // null = not yet checked
  const errorMessage = ref(null)

  const check = useDebounceFn(async (value) => {
    if (!value || String(value).trim() === '') {
      isValid.value = null
      errorMessage.value = null
      return
    }

    isChecking.value = true
    isValid.value = null
    errorMessage.value = null

    try {
      const response = await axios.post(endpoint, {
        value,
        ...extraParams,
      })

      isValid.value    = response.data.valid
      errorMessage.value = response.data.valid ? null : response.data.message
    } catch {
      // Silent fail — real-time validation should never block the form
      isValid.value    = null
      errorMessage.value = null
    } finally {
      isChecking.value = false
    }
  }, 600)  // 600ms debounce — fires 600ms after user stops typing

  function reset() {
    isValid.value      = null
    errorMessage.value = null
    isChecking.value   = false
  }

  return {
    isChecking,
    isValid,
    errorMessage,
    check,   // call this on @blur or @update:modelValue
    reset,
  }
}
```

### Reusable Field Wrapper Component

Create a wrapper component that shows checking/valid/error states
consistently across all forms:

```vue
<!-- resources/js/Components/Common/AppFormField.vue -->
<script setup>
defineProps({
  label:        { type: String,  required: true  },
  error:        { type: String,  default:  null  },  // from form.errors
  liveError:    { type: String,  default:  null  },  // from useFieldValidation
  isChecking:   { type: Boolean, default:  false },
  isValid:      { type: Boolean, default:  null  },  // null = not yet checked
  required:     { type: Boolean, default:  false },
  hint:         { type: String,  default:  null  },
})
</script>

<template>
  <div class="field" :data-error="!!(error || liveError)">
    <label>
      {{ label }}
      <span v-if="required" class="text-red-500 ml-1">*</span>
    </label>

    <!-- Slot for the actual input -->
    <slot />

    <!-- Status indicators -->
    <div class="flex items-center gap-1 mt-1">
      <!-- Checking spinner -->
      <span v-if="isChecking" class="text-xs text-color-secondary flex items-center gap-1">
        <i class="pi pi-spin pi-spinner text-xs" />
        Checking...
      </span>

      <!-- Valid indicator -->
      <span v-else-if="isValid === true" class="text-xs text-green-600 flex items-center gap-1">
        <i class="pi pi-check text-xs" />
        Available
      </span>

      <!-- Live error (from real-time check) -->
      <small v-else-if="liveError" class="text-red-500">
        {{ liveError }}
      </small>

      <!-- Submit error (from form.errors after submit) -->
      <small v-else-if="error" class="text-red-500">
        {{ error }}
      </small>

      <!-- Hint text -->
      <small v-else-if="hint" class="text-color-secondary">
        {{ hint }}
      </small>
    </div>
  </div>
</template>
```

---

## PART 3 — Validation Endpoints

### Location in Routes

Add all validation routes inside `routes/web.php` under a dedicated
`/validate` prefix. No auth required for public endpoints, auth
required for staff endpoints:

```php
// routes/web.php

// Public validation endpoints (no auth)
Route::prefix('validate')->name('validate.')->group(function () {
    Route::post('/reference-code',  [ValidationController::class, 'referenceCode'])
         ->name('reference-code');
    Route::post('/phone',           [ValidationController::class, 'phone'])
         ->name('phone');
});

// Staff validation endpoints (auth required)
Route::middleware('auth')->prefix('validate')->name('validate.')->group(function () {
    Route::post('/email',           [ValidationController::class, 'email'])
         ->name('email');
    Route::post('/assistance-code', [ValidationController::class, 'assistanceCode'])
         ->name('assistance-code');
});
```

### `ValidationController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\User;
use App\Models\AssistanceCode;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ValidationController extends Controller
{
    /**
     * Check if a reference code exists (Track page).
     */
    public function referenceCode(Request $request): JsonResponse
    {
        $exists = Application::where('reference_code', $request->input('value'))
            ->exists();

        return response()->json([
            'valid'   => $exists,
            'message' => $exists ? null : 'No application found with this reference code.',
        ]);
    }

    /**
     * Check if a phone number already has an active application (Apply page).
     * Warn the user — not a hard block, just a notice.
     */
    public function phone(Request $request): JsonResponse
    {
        $hasActive = Application::where('claimant_phone', $request->input('value'))
            ->whereNotIn('status', ['claimed', 'rejected'])
            ->exists();

        return response()->json([
            'valid'   => true,   // never block — phone can have multiple apps
            'warning' => $hasActive
                ? 'This phone number already has an active application in the system.'
                : null,
        ]);
    }

    /**
     * Check if an email is already taken (Admin User Management, Account Settings).
     */
    public function email(Request $request): JsonResponse
    {
        $excludeId = $request->input('exclude_id');  // for Account Settings — exclude self

        $taken = User::where('email', $request->input('value'))
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists();

        return response()->json([
            'valid'   => !$taken,
            'message' => $taken ? 'This email address is already registered.' : null,
        ]);
    }

    /**
     * Check if an assistance code reference is valid and active (AICS Staff coding).
     */
    public function assistanceCode(Request $request): JsonResponse
    {
        $exists = \App\Models\AssistanceCodeReference::where('id', $request->input('value'))
            ->where('is_active', true)
            ->exists();

        return response()->json([
            'valid'   => $exists,
            'message' => $exists ? null : 'This assistance code type is not active.',
        ]);
    }
}
```

---

## PART 4 — Application Per Form

### 4.1 Apply Page — Public

Fields with real-time validation:

| Field | Endpoint | Trigger | Type |
|---|---|---|---|
| `claimant_phone` | `validate.phone` | `@blur` | Warning (not blocking) |
| `claimant_email` | Basic format only | `@blur` | Client-side only |

```vue
<!-- In Apply.vue Step 2 — phone field -->
<script setup>
import { useFieldValidation } from '@/Composables/useFieldValidation'
import { route } from 'ziggy-js'

const phoneValidation = useFieldValidation(route('validate.phone'))
</script>

<template>
  <AppFormField
    label="Phone Number"
    :required="true"
    :error="form.errors.claimant_phone"
    :live-error="phoneValidation.errorMessage.value"
    :is-checking="phoneValidation.isChecking.value"
    :hint="phoneValidation.isValid.value === null
      ? 'Enter your 11-digit mobile number'
      : null"
  >
    <InputText
      v-model="form.claimant_phone"
      @blur="phoneValidation.check(form.claimant_phone)"
      @update:model-value="phoneValidation.reset()"
      placeholder="09XXXXXXXXX"
      maxlength="11"
    />

    <!-- Warning (not blocking) — different from error styling -->
    <small v-if="phoneValidation.warning?.value" class="text-amber-500">
      ⚠ {{ phoneValidation.warning.value }}
    </small>
  </AppFormField>
</template>
```

**preserveState on Apply page submission:**

```javascript
function submit() {
  form.post(route('apply.store'), {
    preserveState:  true,
    preserveScroll: true,
    onSuccess: () => {
      sessionStorage.removeItem('alalay_apply_form')
      currentStep.value = 5  // success screen
    },
    onError: () => {
      goToStepWithErrors()
      scrollToFirstError()
    },
  })
}
```

---

### 4.2 Track Page — Reference Code Input

```vue
<script setup>
const refValidation = useFieldValidation(route('validate.reference-code'))
</script>

<template>
  <AppFormField
    label="Reference Code"
    :required="true"
    :error="form.errors.reference_code"
    :live-error="refValidation.errorMessage.value"
    :is-checking="refValidation.isChecking.value"
  >
    <InputText
      v-model="form.reference_code"
      @blur="refValidation.check(form.reference_code)"
      @update:model-value="refValidation.reset()"
      placeholder="e.g. GMN-2024-000001"
    />
  </AppFormField>
</template>
```

---

### 4.3 AICS Staff — Approve / Return Forms

These are small decision forms (remarks + document checklist). The
only persistence concern is the remarks textarea.

```javascript
// In ApplicationController approve/return forms
form.post(route('aics.applications.approve', application.id), {
  preserveState:  true,
  preserveScroll: true,
  onSuccess: () => {
    form.reset()
    emit('close')   // close dialog/modal
  },
  onError: () => {
    scrollToFirstError()
  },
})
```

---

### 4.4 AICS Staff — Assistance Coding Form

Real-time validation on the assistance code reference dropdown (checks
if selected code is still active):

```vue
<script setup>
const codeValidation = useFieldValidation(route('validate.assistance-code'))
</script>

<template>
  <AppFormField
    label="Assistance Code Type"
    :required="true"
    :error="form.errors.assistance_code_reference_id"
    :live-error="codeValidation.errorMessage.value"
    :is-checking="codeValidation.isChecking.value"
  >
    <Select
      v-model="form.assistance_code_reference_id"
      :options="codeReferences"
      option-label="code_type"
      option-value="id"
      @change="codeValidation.check(form.assistance_code_reference_id)"
    />
  </AppFormField>
</template>
```

**preserveState on assistance coding submission:**

```javascript
form.post(route('aics.assistance-codes.store', application.id), {
  preserveState:  true,
  preserveScroll: true,
  onSuccess: () => {
    form.reset()
    router.visit(route('aics.assistance-codes.index'))
  },
  onError: () => scrollToFirstError(),
})
```

---

### 4.5 MSWDO — Application Approve / Return Forms

Same pattern as AICS forms. Small decision forms with remarks.

```javascript
form.post(route('mswdo.applications.approve', application.id), {
  preserveState:  true,
  preserveScroll: true,
  onSuccess: () => {
    form.reset()
    router.visit(route('mswdo.applications.index'))
  },
  onError: () => scrollToFirstError(),
})
```

---

### 4.6 MSWDO — Voucher Creation Form

Two-step form. Preserve state between steps and on failed submission.

```javascript
// Step navigation — client-side only, no server round trip
const voucherStep = ref(1)

// Step 2 submission
form.post(route('mswdo.vouchers.store', application.id), {
  preserveState:  true,
  preserveScroll: true,
  onSuccess: () => {
    form.reset()
    router.visit(route('mswdo.vouchers.index'))
  },
  onError: () => {
    // If error is on voucher_file, go back to step 2
    if (form.errors.voucher_file) voucherStep.value = 2
    scrollToFirstError()
  },
})
```

---

### 4.7 Accountant — Voucher Review (Approve / Return)

```javascript
form.post(route('accountant.vouchers.approve', application.id), {
  preserveState:  true,
  preserveScroll: true,
  onSuccess: () => {
    form.reset()
    router.visit(route('accountant.vouchers.index'))
  },
  onError: () => scrollToFirstError(),
})
```

---

### 4.8 Admin — User Management (Create / Edit User)

Real-time email validation on the email field:

```vue
<script setup>
import { usePage } from '@inertiajs/vue3'

// For Edit User — exclude current user from duplicate check
const currentUserId = props.user?.id ?? null

const emailValidation = useFieldValidation(
  route('validate.email'),
  { exclude_id: currentUserId }  // null for Create, user ID for Edit
)
</script>

<template>
  <AppFormField
    label="Email Address"
    :required="true"
    :error="form.errors.email"
    :live-error="emailValidation.errorMessage.value"
    :is-checking="emailValidation.isChecking.value"
    :is-valid="emailValidation.isValid.value"
  >
    <InputText
      v-model="form.email"
      @blur="emailValidation.check(form.email)"
      @update:model-value="emailValidation.reset()"
      type="email"
    />
  </AppFormField>
</template>
```

**preserveState on user create/edit:**

```javascript
form.post(route('admin.users.store'), {
  preserveState:  true,
  preserveScroll: true,
  onSuccess: () => {
    form.reset()
    router.visit(route('admin.users.index'))
  },
  onError: () => scrollToFirstError(),
})
```

---

### 4.9 Account Settings (All Roles)

Email field with real-time duplicate check (excluding self):

```javascript
const emailValidation = useFieldValidation(
  route('validate.email'),
  { exclude_id: usePage().props.auth.user.id }
)

form.put(route('account.update'), {
  preserveState:  true,
  preserveScroll: true,
  onSuccess: () => {
    // Do NOT reset — user stays on the page and sees their updated info
    // Flash message confirms success
  },
  onError: () => scrollToFirstError(),
})
```

---

## PART 5 — Global Rules

### Rules for ALL Forms in ALALAY

Apply these without exception to every `useForm()` submission:

1. **Always pass `preserveState: true`** — no form ever resets on
   failed submission.

2. **Always pass `preserveScroll: true`** — user never gets sent back
   to the top of the page on error.

3. **Only call `form.reset()` inside `onSuccess`** — never in `onError`,
   never unconditionally.

4. **Always call `scrollToFirstError()` in `onError`** — brings the
   user's attention to what needs fixing.

5. **Never disable the submit button based on `form.processing`** —
   disable it (show loading state) but ensure it re-enables if the
   submission fails. Inertia handles this automatically via
   `form.processing` — it is `true` during submission and `false`
   after response, whether success or error.

6. **Real-time validation never blocks form submission** — it is
   informational only. If the real-time check shows an error but the
   user submits anyway, Laravel's server-side validation is the final
   authority. Never add `if (liveError) return` before `form.post()`.

7. **sessionStorage backup only on the Apply page** — not needed on
   staff panels since staff are on a managed office network and
   accidental refreshes are less common.

---

## Files to Create or Modify

| File | Action | Change |
|---|---|---|
| `resources/js/Composables/useFieldValidation.js` | Create | Real-time field validation composable |
| `resources/js/Utils/scrollToFirstError.js` | Create | Scroll utility helper |
| `resources/js/Components/Common/AppFormField.vue` | Create | Reusable field wrapper with status indicators |
| `app/Http/Controllers/ValidationController.php` | Create | Lightweight DB validation endpoints |
| `routes/web.php` | Modify | Add `/validate/*` routes |
| `resources/js/Pages/Public/Apply.vue` | Modify | preserveState + sessionStorage backup + step error routing |
| `resources/js/Pages/Public/Track.vue` | Modify | preserveState + reference code live validation |
| `resources/js/Pages/Aics/Applications/Review.vue` | Modify | preserveState on approve/return forms |
| `resources/js/Pages/Aics/AssistanceCodes/Code.vue` | Modify | preserveState + live code validation |
| `resources/js/Pages/Mswdo/Applications/Review.vue` | Modify | preserveState on approve/return forms |
| `resources/js/Pages/Mswdo/Vouchers/Create.vue` | Modify | preserveState + step error routing |
| `resources/js/Pages/Accountant/Vouchers/Review.vue` | Modify | preserveState on approve/return forms |
| `resources/js/Pages/Accountant/Budget/Check.vue` | Modify | preserveState on decision forms |
| `resources/js/Pages/Treasurer/Cheques/Review.vue` | Modify | preserveState on acknowledge form |
| `resources/js/Pages/Admin/Users/Create.vue` | Modify | preserveState + live email validation |
| `resources/js/Pages/Admin/Users/Edit.vue` | Modify | preserveState + live email validation (exclude self) |
| `resources/js/Pages/*/AccountSettings.vue` (all roles) | Modify | preserveState + live email validation (exclude self) |

---

## PROCESS.md Checklist Additions

Add to Phase 4 (Frontend) and Phase 6 (UI/UX Polish):

```
### Phase 4 — Form Persistence and Real-Time Validation

- [ ] Create resources/js/Composables/useFieldValidation.js
- [ ] Create resources/js/Utils/scrollToFirstError.js
- [ ] Create resources/js/Components/Common/AppFormField.vue
- [ ] Create app/Http/Controllers/ValidationController.php
- [ ] Add /validate/* routes to web.php
- [ ] Apply preserveState + preserveScroll to every form.post/put/patch
      across all pages (Apply, Track, all staff panels, Account Settings)
- [ ] Add sessionStorage backup/restore to Apply.vue
- [ ] Add goToStepWithErrors() to Apply.vue multi-step navigation
- [ ] Add phone live validation to Apply.vue claimant phone field
- [ ] Add reference code live validation to Track.vue
- [ ] Add email live validation to Admin Users Create and Edit pages
- [ ] Add email live validation to all Account Settings pages
- [ ] Add assistance code live validation to AICS Assistance Coding page

### Phase 6 — UI/UX Polish Verification

- [ ] Verify: fail a form submission — fields retain their filled values
- [ ] Verify: page scrolls to first error field after failed submission
- [ ] Verify: Apply page multi-step — failed submission routes back to
      the step that contains the error field
- [ ] Verify: Apply page sessionStorage — fill form, hard refresh,
      confirm values are restored
- [ ] Verify: phone validation shows warning (amber) not hard error
- [ ] Verify: email validation shows "Available" (green check) for
      a new email and error for a taken email
- [ ] Verify: reference code validation shows error immediately on blur
      if code does not exist
- [ ] Verify: live validation errors disappear when user edits the field
- [ ] Verify: live validation never prevents form submission
```

---

*Document prepared for AI consumption and development reference — ALALAY System,
Municipality of General Mamerto Natividad, Nueva Ecija.*

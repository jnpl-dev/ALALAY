# ALALAY: Frontend Design Foundation
**Visual Identity, Motion, and Interaction Standards**
**Municipality of General Mamerto Natividad, Nueva Ecija**

---

## How to Use This Document

Read this document in full before touching any frontend file in ALALAY.
Every visual decision — color, spacing, typography, animation, component
structure — must derive from what is defined here. Do not invent new
colors, do not add animations not specified here, do not choose a font
not listed here.

This document synthesizes three design skill frameworks:
- **Taste** (tasteskill.dev) — macro visual decisions, restraint,
  knowing when to stop
- **Frontend Design** (emilkowal.ski/skill) — motion-first thinking,
  physical feel, micro-interactions that make interfaces feel alive
- **Frontend Design Skill** (built-in) — design process discipline,
  signature element, planning before building, avoiding AI defaults

They operate at different layers and do not conflict:
- Taste sets the visual foundation
- Frontend Design gives it a signature identity
- Motion makes it feel real and physical

---

## SECTION 1 — Design Intent

### What ALALAY Is

ALALAY is a government workflow system for the Municipality of General
Mamerto Natividad, Nueva Ecija. It handles Assistance to Individuals
in Crisis Situation (AICS) applications — people in financial distress
submitting for medical, hospital, or burial assistance.

### What the Design Must Communicate

- **Trustworthy** — applicants are sharing sensitive personal information
  in a vulnerable moment. The interface must feel safe, official, and
  competent — not cold or intimidating.
- **Clear** — most applicants are not tech-savvy. Every action must be
  immediately understandable without training.
- **Filipino-institutional** — it should feel like it belongs to a
  Philippine LGU office: warm, approachable, but unmistakably official.
- **Efficient for staff** — internal panels are used by the same 6–7
  people every working day. Efficiency and legibility matter more than
  visual novelty.

### The Signature Element

Per the frontend-design skill: every system needs one element it is
remembered by. ALALAY's signature is the **application status system** —
the visual language used to represent where an application is in the
pipeline. This appears in:
- Status badges on all data tables
- The review trail timeline on review pages
- The delivery-tracker timeline on the public Track page
- The pulsing active-state node on the timeline

This is the most designed element in ALALAY. Everything else supports it.

### What to Avoid (AI Design Tells)

Per the frontend-design skill, these patterns are AI defaults and must
be actively avoided:
- Warm cream (`#F4F1EA`) background with terracotta accent (`#D97757`)
- Near-black background with acid-green accent
- Broadsheet hairline-rule layouts
- Numbered section markers (01, 02, 03) unless content is truly sequential
- Scattered animations on every element
- Generic blue-on-white admin panel with no identity

---

## SECTION 2 — Design Tokens

All visual decisions derive from these tokens. No magic numbers anywhere
in the codebase. Every color, spacing, radius, and timing value must
reference a token defined here.

### Color Palette

```css
/* resources/css/alalay-tokens.css */
:root {

  /* ── Primary ── */
  --color-primary:          #1B4F72;  /* deep government blue — authoritative */
  --color-primary-hover:    #154060;  /* 15% darker — hover state */
  --color-primary-light:    #2E86AB;  /* accessible lighter blue — links, icons */
  --color-primary-surface:  #EEF2F7;  /* very light blue tint — card backgrounds */

  /* ── Accent ── */
  --color-accent:           #E8A838;  /* warm amber — Filipino warmth, harvest */
  --color-accent-hover:     #D49520;  /* darker amber — hover */
  --color-accent-surface:   #FEF9EC;  /* very light amber — accent backgrounds */
  /* Accent is used SPARINGLY: active nav items, primary CTA buttons,
     the pulsing active node on timelines, important badges only */

  /* ── Neutral Surface ── */
  --color-surface:          #F8F9FA;  /* page background — near white, not pure */
  --color-surface-alt:      #EEF2F7;  /* card, panel background */
  --color-surface-raised:   #FFFFFF;  /* elevated cards, modals */
  --color-border:           #DDE3ED;  /* card borders, dividers */
  --color-border-strong:    #B8C4D4;  /* strong dividers, input borders on focus */

  /* ── Text ── */
  --color-text:             #1A1D23;  /* primary text — near black, not pure */
  --color-text-secondary:   #4B5563;  /* secondary labels, descriptions */
  --color-text-muted:       #9CA3AF;  /* placeholders, disabled, timestamps */
  --color-text-inverse:     #FFFFFF;  /* text on dark backgrounds */

  /* ── Semantic ── */
  --color-success:          #16A34A;
  --color-success-surface:  #F0FDF4;
  --color-warning:          #D97706;
  --color-warning-surface:  #FFFBEB;
  --color-danger:           #DC2626;
  --color-danger-surface:   #FEF2F2;
  --color-info:             #2563EB;
  --color-info-surface:     #EFF6FF;

  /* ── Status Colors (Application Pipeline) ── */
  /* These are the most important colors in ALALAY — used in badges,
     timeline nodes, and status indicators everywhere */
  --status-submitted:       #2563EB;   /* blue */
  --status-screening:       #D97706;   /* amber */
  --status-returned:        #DC2626;   /* red */
  --status-review:          #D97706;   /* amber */
  --status-processing:      #7C3AED;   /* purple */
  --status-hold:            #6B7280;   /* gray */
  --status-ready:           #16A34A;   /* green */
  --status-claimed:         #16A34A;   /* green */
}
```

### Typography

```css
:root {
  /* ── Typefaces ── */
  --font-display: 'Plus Jakarta Sans', sans-serif;
  /* Used for: panel headings, page titles, card titles, KPI numbers */
  /* NOT used for: body copy, table cells, form labels */

  --font-body:    'Inter', sans-serif;
  /* Used for: all body copy, form labels, table cells, descriptions */
  /* This is PrimeVue Sakai's default — keep it */

  --font-mono:    'JetBrains Mono', monospace;
  /* Used for: reference codes (GMN-2024-000001), file sizes,
     amounts (₱5,000.00), IDs, technical values */
  /* Deliberately makes these values feel like data, not prose */

  /* ── Type Scale ── */
  --text-xs:   0.75rem;   /* 12px — timestamps, secondary metadata */
  --text-sm:   0.875rem;  /* 14px — table cells, form hints, badges */
  --text-base: 1rem;      /* 16px — body text, form labels */
  --text-lg:   1.125rem;  /* 18px — card titles, section headings */
  --text-xl:   1.25rem;   /* 20px — panel headings */
  --text-2xl:  1.5rem;    /* 24px — page titles */
  --text-3xl:  1.875rem;  /* 30px — KPI numbers on dashboard */
  --text-4xl:  2.25rem;   /* 36px — reference code on Apply success screen */

  /* ── Font Weights ── */
  --weight-normal:   400;
  --weight-medium:   500;
  --weight-semibold: 600;
  --weight-bold:     700;

  /* ── Line Heights ── */
  --leading-tight:  1.25;  /* headings */
  --leading-normal: 1.5;   /* body */
  --leading-relaxed: 1.75; /* long-form descriptions */
}
```

### Spacing

```css
:root {
  /* ── Spacing Scale (4px base) ── */
  --space-1:  0.25rem;   /* 4px */
  --space-2:  0.5rem;    /* 8px */
  --space-3:  0.75rem;   /* 12px */
  --space-4:  1rem;      /* 16px */
  --space-5:  1.25rem;   /* 20px */
  --space-6:  1.5rem;    /* 24px */
  --space-8:  2rem;      /* 32px */
  --space-10: 2.5rem;    /* 40px */
  --space-12: 3rem;      /* 48px */
  --space-16: 4rem;      /* 64px */

  /* ── Border Radius ── */
  /* Committed to these values — never vary arbitrarily */
  --radius-sm:   4px;    /* badges, chips, small tags */
  --radius-md:   6px;    /* inputs, buttons */
  --radius-lg:   8px;    /* cards, panels, dropdowns */
  --radius-xl:   12px;   /* modals, dialogs */
  --radius-full: 9999px; /* pills, avatars, round buttons */

  /* ── Shadows ── */
  --shadow-sm:  0 1px 2px 0 rgb(0 0 0 / 0.05);
  --shadow-md:  0 4px 6px -1px rgb(0 0 0 / 0.07),
                0 2px 4px -1px rgb(0 0 0 / 0.05);
  --shadow-lg:  0 10px 15px -3px rgb(0 0 0 / 0.08),
                0 4px 6px -2px rgb(0 0 0 / 0.04);
  --shadow-xl:  0 20px 25px -5px rgb(0 0 0 / 0.08),
                0 10px 10px -5px rgb(0 0 0 / 0.03);
}
```

### Motion Tokens

```css
:root {
  /* ── Durations ── */
  --duration-instant: 80ms;   /* camera flash, button press feedback */
  --duration-fast:    150ms;  /* hover states, badge transitions */
  --duration-normal:  200ms;  /* modal enter/exit, panel transitions */
  --duration-slow:    300ms;  /* checkmark draw, status transitions */
  --duration-crawl:   500ms;  /* background color flashes (polling updates) */

  /* ── Easing Curves ── */
  --ease-spring:   cubic-bezier(0.34, 1.56, 0.64, 1); /* physical, bouncy */
  --ease-out:      cubic-bezier(0.16, 1, 0.3, 1);      /* snappy deceleration */
  --ease-in-out:   cubic-bezier(0.65, 0, 0.35, 1);     /* smooth both ways */
  --ease-linear:   linear;                              /* spinners, progress */

  /* ── Motion Rule ── */
  /* Per Emil's skill: spend motion budget deliberately.
     Fast interactions (hover, press) = --duration-fast + --ease-out
     Entrances (modal, page) = --duration-normal + --ease-spring
     State changes (status, color) = --duration-slow + --ease-in-out
     Never animate more than 2 properties simultaneously */
}

/* Always respect reduced-motion preference */
@media (prefers-reduced-motion: reduce) {
  * {
    animation-duration: 0.01ms !important;
    transition-duration: 0.01ms !important;
  }
}
```

---

## SECTION 3 — Application Status Visual System

This is ALALAY's signature element. Every status appearance across the
entire system derives from this single definition. Never invent a new
status color or label — always reference this map.

```javascript
// resources/js/Utils/statusConfig.js

export const STATUS_CONFIG = {
  submitted: {
    label:   'Submitted',
    color:   'var(--status-submitted)',
    surface: 'var(--color-info-surface)',
    icon:    'pi pi-send',
    dot:     true,
  },
  screening: {
    label:   'Under Screening',
    color:   'var(--status-screening)',
    surface: 'var(--color-warning-surface)',
    icon:    'pi pi-search',
    dot:     true,
  },
  returned_to_applicant: {
    label:   'Action Required',
    color:   'var(--status-returned)',
    surface: 'var(--color-danger-surface)',
    icon:    'pi pi-exclamation-triangle',
    dot:     true,
    pulse:   true,  // pulsing dot — attention needed from applicant
  },
  mswdo_review: {
    label:   'Under Review',
    color:   'var(--status-review)',
    surface: 'var(--color-warning-surface)',
    icon:    'pi pi-eye',
    dot:     true,
  },
  social_case_study_uploaded: {
    label:   'Case Study Uploaded',
    color:   'var(--status-processing)',
    surface: '#F5F3FF',
    icon:    'pi pi-file',
    dot:     true,
  },
  assistance_coding: {
    label:   'Being Processed',
    color:   'var(--status-processing)',
    surface: '#F5F3FF',
    icon:    'pi pi-cog',
    dot:     true,
  },
  voucher_creation: {
    label:   'Being Processed',
    color:   'var(--status-processing)',
    surface: '#F5F3FF',
    icon:    'pi pi-file-edit',
    dot:     true,
  },
  voucher_checking: {
    label:   'Voucher Under Review',
    color:   'var(--status-review)',
    surface: 'var(--color-warning-surface)',
    icon:    'pi pi-eye',
    dot:     true,
  },
  voucher_returned: {
    label:   'Voucher Returned',
    color:   'var(--status-returned)',
    surface: 'var(--color-danger-surface)',
    icon:    'pi pi-replay',
    dot:     true,
  },
  with_treasurer: {
    label:   'With Treasurer',
    color:   'var(--status-review)',
    surface: 'var(--color-warning-surface)',
    icon:    'pi pi-building',
    dot:     true,
  },
  budget_checking: {
    label:   'Budget Check',
    color:   'var(--status-review)',
    surface: 'var(--color-warning-surface)',
    icon:    'pi pi-calculator',
    dot:     true,
  },
  on_hold: {
    label:   'On Hold',
    color:   'var(--status-hold)',
    surface: '#F9FAFB',
    icon:    'pi pi-pause-circle',
    dot:     true,
  },
  cheque_ready: {
    label:   'Ready to Claim',
    color:   'var(--status-ready)',
    surface: 'var(--color-success-surface)',
    icon:    'pi pi-check-circle',
    dot:     true,
    pulse:   true,  // pulsing — positive attention, action needed
  },
  claimed: {
    label:   'Claimed',
    color:   'var(--status-claimed)',
    surface: 'var(--color-success-surface)',
    icon:    'pi pi-verified',
    dot:     false,  // no dot — terminal state, no further action
  },
}
```

### Status Badge Component

```vue
<!-- resources/js/Components/Common/AppStatusBadge.vue -->
<!-- Used in every data table and anywhere a status needs to be displayed -->
<script setup>
import { computed } from 'vue'
import { STATUS_CONFIG } from '@/Utils/statusConfig'

const props = defineProps({
  status: { type: String, required: true },
  size:   { type: String, default: 'md' },  // 'sm' | 'md'
})

const config = computed(() => STATUS_CONFIG[props.status] ?? {
  label:   props.status,
  color:   'var(--color-text-muted)',
  surface: 'var(--color-surface)',
  icon:    'pi pi-circle',
  dot:     true,
  pulse:   false,
})
</script>

<template>
  <span
    class="app-status-badge"
    :class="[`app-status-badge--${size}`, { 'app-status-badge--pulse': config.pulse }]"
    :style="{
      color:           config.color,
      backgroundColor: config.surface,
      borderColor:     config.color + '33',
    }"
  >
    <!-- Dot indicator -->
    <span
      v-if="config.dot"
      class="app-status-badge__dot"
      :style="{ backgroundColor: config.color }"
    />
    {{ config.label }}
  </span>
</template>

<style scoped>
.app-status-badge {
  display:         inline-flex;
  align-items:     center;
  gap:             var(--space-2);
  padding:         var(--space-1) var(--space-3);
  border-radius:   var(--radius-full);
  border:          1px solid;
  font-size:       var(--text-xs);
  font-weight:     var(--weight-medium);
  font-family:     var(--font-body);
  white-space:     nowrap;
  transition:      all var(--duration-fast) var(--ease-out);
}

.app-status-badge--sm { font-size: 0.7rem; padding: 2px var(--space-2); }

.app-status-badge__dot {
  width:         6px;
  height:        6px;
  border-radius: var(--radius-full);
  flex-shrink:   0;
}

/* Pulsing dot — returned_to_applicant and cheque_ready */
.app-status-badge--pulse .app-status-badge__dot {
  animation: status-pulse 2s ease-in-out infinite;
}

@keyframes status-pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50%       { opacity: 0.5; transform: scale(0.75); }
}

/* Smooth color transition when status changes (from polling) */
.app-status-badge {
  transition: color             var(--duration-slow) var(--ease-in-out),
              background-color  var(--duration-slow) var(--ease-in-out),
              border-color      var(--duration-slow) var(--ease-in-out);
}
</style>
```

---

## SECTION 4 — Motion Playbook

Per Emil's skill: every animation must feel physical and purposeful.
Spend motion budget deliberately. This section defines exactly which
animation to use for each interaction — do not invent new animations.

### 4.1 Entrances and Exits

**Modals and Dialogs:**
```css
/* Enter: scale from 95% + fade in */
.modal-enter-active {
  transition: opacity var(--duration-normal) var(--ease-out),
              transform var(--duration-normal) var(--ease-spring);
}
.modal-enter-from {
  opacity: 0;
  transform: scale(0.95);
}
/* Exit: reverse, faster */
.modal-leave-active {
  transition: opacity var(--duration-fast) var(--ease-in-out),
              transform var(--duration-fast) var(--ease-in-out);
}
.modal-leave-to {
  opacity: 0;
  transform: scale(0.97);
}

/* Backdrop */
.backdrop-enter-active { transition: opacity var(--duration-fast) var(--ease-out); }
.backdrop-enter-from   { opacity: 0; }
.backdrop-leave-active { transition: opacity var(--duration-fast) var(--ease-in-out); }
.backdrop-leave-to     { opacity: 0; }
```

**Toast Notifications:**
```css
/* Slide in from top-right, spring bounce */
.toast-enter-active {
  transition: opacity  var(--duration-normal) var(--ease-out),
              transform var(--duration-normal) var(--ease-spring);
}
.toast-enter-from {
  opacity: 0;
  transform: translateX(100%) scale(0.9);
}
.toast-leave-active {
  transition: opacity  var(--duration-fast) var(--ease-in-out),
              transform var(--duration-fast) var(--ease-in-out);
}
.toast-leave-to {
  opacity: 0;
  transform: translateX(20px);
}
```

**Page Content Transition (Inertia navigation):**
```css
/* Subtle fade — not dramatic, just acknowledges the change */
.page-enter-active { transition: opacity var(--duration-normal) var(--ease-out); }
.page-enter-from   { opacity: 0; }
.page-leave-active { transition: opacity var(--duration-fast) var(--ease-in-out); }
.page-leave-to     { opacity: 0; }
```

**Error Messages and Validation Feedback:**
```css
/* Slide down from above — feels like the message is being attached */
.error-enter-active {
  transition: opacity   var(--duration-fast) var(--ease-out),
              transform var(--duration-fast) var(--ease-spring),
              max-height var(--duration-fast) var(--ease-out);
}
.error-enter-from {
  opacity:    0;
  transform:  translateY(-4px);
  max-height: 0;
}
.error-leave-active {
  transition: opacity   var(--duration-fast) var(--ease-in-out),
              max-height var(--duration-fast) var(--ease-in-out);
}
.error-leave-to {
  opacity:    0;
  max-height: 0;
}
```

**New Table Rows (from polling):**
```css
/* Subtle slide + fade — not distracting */
.table-row-enter-active {
  transition: opacity   var(--duration-normal) var(--ease-out),
              transform var(--duration-normal) var(--ease-out);
}
.table-row-enter-from {
  opacity:   0;
  transform: translateY(-8px);
}
```

### 4.2 Micro-Interactions

**Button Press:**
```css
button:active,
.p-button:active {
  transform:  scale(0.97);
  transition: transform var(--duration-instant) var(--ease-out);
}
```

**Card Hover:**
```css
.app-card {
  transition: box-shadow var(--duration-fast) var(--ease-out),
              transform  var(--duration-fast) var(--ease-out);
}
.app-card:hover {
  box-shadow: var(--shadow-md);
  transform:  translateY(-1px);
}
```

**Input Focus:**
```css
.p-inputtext:focus,
.p-select:focus {
  transition: border-color var(--duration-fast) var(--ease-out),
              box-shadow   var(--duration-fast) var(--ease-out);
}
```

**"Use This" Button (DocumentScanner confirmation):**
```css
/* The satisfying confirm state — physical press feel */
.scanner-confirm-btn {
  transition: transform        var(--duration-fast) var(--ease-spring),
              background-color var(--duration-normal) var(--ease-out),
              color            var(--duration-normal) var(--ease-out);
}
.scanner-confirm-btn:active {
  transform: scale(0.97);
}
/* Confirmed state */
.scanner-confirm-btn--confirmed {
  background-color: var(--color-success);
  color:            var(--color-text-inverse);
  transform:        scale(1);
}
```

### 4.3 DocumentScanner-Specific Animations

These are the most motion-intensive part of ALALAY. Get them right.

```css
/* Camera feed fade in */
.scanner-video {
  animation: scanner-appear var(--duration-normal) var(--ease-out);
}
@keyframes scanner-appear {
  from { opacity: 0; }
  to   { opacity: 1; }
}

/* Guide overlay border pulse — signals "align here" */
.scanner-guide-border {
  animation: guide-pulse 2.5s ease-in-out infinite;
}
@keyframes guide-pulse {
  0%, 100% { opacity: 1; }
  50%       { opacity: 0.5; }
}
/* Stop pulsing after capture button is tapped */
.scanner-guide-border--capturing {
  animation: none;
  opacity: 1;
}

/* Capture flash — brief white overlay, like a real camera */
.scanner-capture-flash {
  background: white;
  animation: capture-flash var(--duration-instant) var(--ease-out) forwards;
}
@keyframes capture-flash {
  0%   { opacity: 0.8; }
  100% { opacity: 0; }
}

/* Preview slides up — document is being handed to you */
.scanner-preview {
  animation: preview-appear var(--duration-normal) var(--ease-spring);
}
@keyframes preview-appear {
  from {
    opacity:   0;
    transform: translateY(12px) scale(0.98);
  }
  to {
    opacity:   1;
    transform: translateY(0) scale(1);
  }
}

/* Checkmark draws itself in on document complete */
.scanner-checkmark-path {
  stroke-dasharray:  50;
  stroke-dashoffset: 50;
  animation: checkmark-draw var(--duration-slow) var(--ease-out) forwards;
}
@keyframes checkmark-draw {
  to { stroke-dashoffset: 0; }
}

/* Phone rotation hint — animated phone icon */
.scanner-rotate-hint {
  animation: hint-rotate 1.5s ease-in-out infinite alternate;
}
@keyframes hint-rotate {
  from { transform: rotate(0deg); }
  to   { transform: rotate(90deg); }
}
```

### 4.4 Timeline / Review Trail Animations

```css
/* Active node pulse ring */
.timeline-node--active::after {
  content:       '';
  position:      absolute;
  inset:         -4px;
  border-radius: var(--radius-full);
  border:        2px solid currentColor;
  animation:     node-pulse 2s ease-in-out infinite;
}
@keyframes node-pulse {
  0%, 100% { opacity: 0.6; transform: scale(1); }
  50%       { opacity: 0;   transform: scale(1.4); }
}

/* Node transitions from active to completed */
.timeline-node {
  transition: background-color var(--duration-slow) var(--ease-in-out),
              color            var(--duration-slow) var(--ease-in-out);
}

/* Connecting line fills as stages complete */
.timeline-connector--completed {
  background:  var(--color-success);
  transition:  background-color var(--duration-slow) var(--ease-in-out);
}
.timeline-connector--pending {
  background:  var(--color-border);
}
```

### 4.5 Polling Table Updates

```css
/* New row highlight — brief amber flash, then fade to normal */
.table-row--new {
  animation: row-highlight var(--duration-crawl) var(--ease-out) forwards;
}
@keyframes row-highlight {
  0%   { background-color: var(--color-accent-surface); }
  100% { background-color: transparent; }
}
```

---

## SECTION 5 — Component Conventions

### 5.1 Cards

Every panel card follows this structure:

```vue
<!-- Standard card pattern -->
<div class="app-card">
  <div class="app-card__header">
    <h3 class="app-card__title">Title</h3>
    <div class="app-card__actions"><!-- optional slot --></div>
  </div>
  <div class="app-card__body">
    <!-- content -->
  </div>
</div>

<style>
.app-card {
  background:    var(--color-surface-raised);
  border:        1px solid var(--color-border);
  border-radius: var(--radius-lg);
  box-shadow:    var(--shadow-sm);
}
.app-card__header {
  display:         flex;
  align-items:     center;
  justify-content: space-between;
  padding:         var(--space-4) var(--space-6);
  border-bottom:   1px solid var(--color-border);
}
.app-card__title {
  font-family: var(--font-display);
  font-size:   var(--text-lg);
  font-weight: var(--weight-semibold);
  color:       var(--color-text);
}
.app-card__body {
  padding: var(--space-6);
}
</style>
```

### 5.2 KPI Cards (Dashboard)

```vue
<div class="app-kpi">
  <span class="app-kpi__label">New Applications Today</span>
  <span class="app-kpi__value">12</span>
  <span class="app-kpi__delta app-kpi__delta--up">+3 from yesterday</span>
</div>

<style>
.app-kpi { /* inherits app-card styles + */ }
.app-kpi__label {
  font-size:   var(--text-sm);
  font-weight: var(--weight-medium);
  color:       var(--color-text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
.app-kpi__value {
  font-family: var(--font-display);
  font-size:   var(--text-3xl);
  font-weight: var(--weight-bold);
  color:       var(--color-text);
  line-height: var(--leading-tight);
}
.app-kpi__delta--up   { color: var(--color-success); }
.app-kpi__delta--down { color: var(--color-danger);  }
</style>
```

### 5.3 Reference Code Display

The reference code is one of the most important pieces of data in ALALAY.
It is shown prominently on the Apply success screen and in tables.

```vue
<span class="app-ref-code">GMN-2024-000001</span>

<style>
.app-ref-code {
  font-family:     var(--font-mono);
  font-size:       var(--text-sm);
  font-weight:     var(--weight-medium);
  color:           var(--color-primary);
  background:      var(--color-primary-surface);
  padding:         var(--space-1) var(--space-2);
  border-radius:   var(--radius-sm);
  letter-spacing:  0.03em;
  white-space:     nowrap;
}
/* Large version on Apply success screen */
.app-ref-code--lg {
  font-size:     var(--text-4xl);
  font-weight:   var(--weight-bold);
  padding:       var(--space-4) var(--space-8);
  border-radius: var(--radius-lg);
  letter-spacing: 0.05em;
}
</style>
```

### 5.4 Amount / Currency Display

```vue
<span class="app-amount">₱5,000.00</span>

<style>
.app-amount {
  font-family: var(--font-mono);
  font-weight: var(--weight-semibold);
  color:       var(--color-text);
}
.app-amount--large {
  font-size:   var(--text-2xl);
  color:       var(--color-primary);
}
</style>
```

---

## SECTION 6 — Restraint Rules (Taste Skill)

Per the taste skill: spend boldness in one place (the status system).
Keep everything else quiet and disciplined.

### What Gets Animation
- DocumentScanner interactions (full playbook above)
- Modal and dialog enter/exit
- Toast notifications
- Timeline node state transitions
- New table rows from polling
- Status badge color transitions

### What Does NOT Get Animation
- Basic navigation between pages (Inertia progress bar is enough)
- Form field labels
- Table header sorting
- Dropdown menus opening (PrimeVue default is sufficient)
- Loading skeletons (use PrimeVue Skeleton, no custom animation)
- Dashboard KPI numbers (no counting-up number animation —
  this is an AI design tell and adds no real value)

### Typography Rules
- Only **Plus Jakarta Sans** for headings and display
- Only **Inter** for body, labels, table cells
- Only **JetBrains Mono** for codes, amounts, IDs, file sizes
- Never mix more than two font families on one page
- Never use font-weight below 400 or above 700

### Color Rules
- Accent color (`--color-accent`) used in maximum 2 places per page
  (primary CTA button + one other element)
- Status colors used ONLY through the `STATUS_CONFIG` system —
  never apply status colors ad-hoc
- Never use pure `#000000` or pure `#FFFFFF` — always use the token values
- Never add a new color not in the token system without updating this document

### Spacing Rules
- All spacing from the `--space-*` scale — no arbitrary pixel values
- Section gaps: `--space-8` or `--space-12`
- Card padding: `--space-6`
- Form field gap: `--space-4`
- Table cell padding: `--space-3` vertical, `--space-4` horizontal

### Radius Rules
- Committed to the four values: sm, md, lg, xl
- Small interactive elements (badges, tags): `--radius-full`
- Inputs and buttons: `--radius-md`
- Cards and panels: `--radius-lg`
- Modals: `--radius-xl`
- Never mix radii on the same component

---

## SECTION 7 — Google Fonts Import

Add to `resources/css/app.css` before Tailwind imports:

```css
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap');
@import './alalay-tokens.css';

@tailwind base;
@tailwind components;
@tailwind utilities;
```

---

## SECTION 8 — PrimeVue Sakai Override Strategy

PrimeVue Sakai has its own theme variables. Override only what is needed
to match ALALAY's token system. Do not rewrite Sakai's entire theme —
override at the token level:

```css
/* In alalay-tokens.css — after the :root block */
/* PrimeVue token overrides — mapped to ALALAY tokens */
:root {
  --p-primary-color:        var(--color-primary);
  --p-primary-color-text:   var(--color-text-inverse);
  --p-surface-0:            var(--color-surface-raised);
  --p-surface-50:           var(--color-surface);
  --p-surface-100:          var(--color-surface-alt);
  --p-surface-border:       var(--color-border);
  --p-text-color:           var(--color-text);
  --p-text-muted-color:     var(--color-text-muted);
  --p-border-radius:        var(--radius-md);
  --p-content-border-radius: var(--radius-lg);
  --p-transition-duration:  var(--duration-fast);
  --p-focus-ring-color:     var(--color-primary-light);
}
```

---

## SECTION 9 — Before Touching Any Frontend File

**Checklist — run through this before writing any component:**

1. Is this color from `--color-*` or `--status-*` tokens? If not, add
   it to the token file first.

2. Is this spacing value from `--space-*`? If not, find the closest
   token and use that.

3. Is this animation in the Motion Playbook (Section 4)? If not, do
   not add it. The motion budget is already spent.

4. Does this component use the status system? If so, import from
   `STATUS_CONFIG` — never hardcode a status color or label.

5. Is the font choice one of the three defined typefaces? If not,
   do not add a new one.

6. Is this a new color or design pattern not covered here? Add it to
   this document first, then implement it. This document is the
   single source of truth.

7. Does this animation respect `prefers-reduced-motion`? The global
   CSS rule handles this, but do not override it in component styles.

---

*Document prepared for AI consumption and frontend development reference
— ALALAY System, Municipality of General Mamerto Natividad, Nueva Ecija.*

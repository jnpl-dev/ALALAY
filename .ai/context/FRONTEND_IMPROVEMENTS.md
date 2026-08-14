# ALALAY: Frontend Design Examination & Improvement Task
**Sakai + PrimeVue Component Maximization**
**Powered by tasteskill.dev + emilkowal.ski/skill**
**Municipality of General Mamerto Natividad, Nueva Ecija**

---

## Purpose

This document instructs an AI agent to:
1. Examine every staff panel page and shared component currently built
2. Identify what can be improved or replaced with native Sakai/PrimeVue components
3. Identify design inconsistencies against the ALALAY token system
4. Produce a prioritized list of improvements formatted as PROCESS.md checklist items

The goal is **maximum use of Sakai and PrimeVue's built-in capabilities**
before writing any custom code. If PrimeVue already has a component that
does the job, use it. Custom code is only written for things PrimeVue
genuinely does not cover.

---

## Design Frameworks to Apply

Apply both installed skills throughout examination and prescription.
These are not optional — invoke them explicitly for every decision.

### tasteskill.dev — Restraint and Visual Judgment
- One bold element per page. Everything else quiet.
- When in doubt, remove — do not add more detail.
- White space is a design element — resist filling gaps.
- Consistency beats creativity for a government system used daily.
- Good taste means knowing when Sakai's default is already correct
  and leaving it alone.

### emilkowal.ski/skill — Physical Motion and Micro-Interaction
- Motion must feel physical, not decorative.
- Spring curve for entrances: `cubic-bezier(0.34, 1.56, 0.64, 1)`
- Fast ease-out for hover states: `cubic-bezier(0.16, 1, 0.3, 1)`
- Duration hierarchy: 80ms press → 150ms hover → 200ms enter → 300ms state
- Motion budget per page: maximum 3 animated elements
- Never animate more than 2 CSS properties at once

---

## ALALAY Design Token Reference

Every visual value in ALALAY derives from these tokens.
Any value not in this list is a violation.

```
COLOR TOKENS
  --color-primary:          #1B4F72
  --color-primary-hover:    #154060
  --color-primary-light:    #2E86AB
  --color-primary-surface:  #EEF2F7
  --color-accent:           #E8A838   (max 2 uses per page)
  --color-accent-surface:   #FEF9EC
  --color-surface:          #F8F9FA
  --color-surface-alt:      #EEF2F7
  --color-surface-raised:   #FFFFFF
  --color-border:           #DDE3ED
  --color-text:             #1A1D23
  --color-text-secondary:   #4B5563
  --color-text-muted:       #9CA3AF
  --color-success:          #16A34A
  --color-success-surface:  #F0FDF4
  --color-warning:          #D97706
  --color-warning-surface:  #FFFBEB
  --color-danger:           #DC2626
  --color-danger-surface:   #FEF2F2

STATUS COLORS (these only — never invent)
  submitted              → #2563EB  blue
  screening/review       → #D97706  amber
  returned_to_applicant  → #DC2626  red   + pulsing dot
  processing             → #7C3AED  purple
  on_hold                → #6B7280  gray
  cheque_ready           → #16A34A  green + pulsing dot
  claimed                → #16A34A  green  no pulse

TYPOGRAPHY
  Headings/display:   Plus Jakarta Sans (weights 600, 700)
  Body/UI/labels:     Inter           (weights 400, 500, 600)
  Codes/amounts/IDs:  JetBrains Mono  (weights 400, 500)

SPACING SCALE (only these values — no arbitrary px)
  4px  8px  12px  16px  20px  24px  32px  40px  48px  64px

RADIUS SCALE (committed — never vary)
  Badges/chips/tags:  9999px  (rounded-full)
  Inputs/buttons:     6px     (rounded-md  in Tailwind ≈ 6px)
  Cards/panels:       8px     (rounded-lg  in Tailwind ≈ 8px)
  Modals/dialogs:     12px    (rounded-xl  in Tailwind ≈ 12px)

MOTION TOKENS
  --duration-instant: 80ms    (button press, capture flash)
  --duration-fast:    150ms   (hover states, badge color)
  --duration-normal:  200ms   (modal enter, page)
  --duration-slow:    300ms   (status change, checkmark draw)
  --ease-spring:   cubic-bezier(0.34, 1.56, 0.64, 1)
  --ease-out:      cubic-bezier(0.16, 1, 0.3, 1)
  --ease-in-out:   cubic-bezier(0.65, 0, 0.35, 1)
```

---

## Sakai + PrimeVue Component Reference

Before prescribing any custom solution, check this list.
If PrimeVue has a component that covers the need, use it.

### Layout Components (Sakai)
- `AppLayout.vue` — persistent sidebar + topbar shell
- `AppTopbar.vue` — top navigation bar
- `AppSidebar.vue` — collapsible sidebar
- `AppMenu.vue` — sidebar menu with nested items and active state
- `AppBreadcrumb.vue` — page breadcrumb (if present in Sakai version)
- `AppFooter.vue` — bottom bar

### Data Display (PrimeVue)
- `DataTable` — paginated sortable filterable table with column templates
- `DataView` — card or list layout for collections
- `TreeTable` — hierarchical table
- `Timeline` — vertical or horizontal event timeline (USE THIS for ReviewTrail)
- `Card` — content card with header, content, footer slots
- `Panel` — collapsible content panel with header
- `Fieldset` — grouped form fields with legend
- `ScrollPanel` — custom scrollable container
- `Divider` — horizontal or vertical separator
- `Chip` — small label (USE THIS for document tags, category labels)
- `Tag` — colored label (USE THIS for status badges as fallback)
- `Badge` — notification count overlay (USE THIS for sidebar menu counts)
- `Avatar` — user avatar with initials or image
- `AvatarGroup` — stacked avatars
- `Skeleton` — loading placeholder (USE THIS instead of custom spinners)
- `ProgressBar` — progress indicator
- `ProgressSpinner` — circular spinner
- `Meter` / `MeterGroup` — metric visualization
- `Knob` — circular value display

### Input Components (PrimeVue)
- `InputText` — standard text input
- `InputNumber` — numeric input with formatting (USE THIS for amounts)
- `InputMask` — masked input (USE THIS for phone numbers: 09#########)
- `InputOtp` — OTP digit input (already used in auth)
- `Textarea` — multiline text
- `Select` (Dropdown) — single select with search
- `MultiSelect` — multiple selection dropdown
- `AutoComplete` — searchable input with suggestions
- `CascadeSelect` — nested dropdown (USE THIS for PSGC province→city→barangay)
- `DatePicker` (Calendar) — date selection (USE THIS for date of birth fields)
- `Checkbox` — single or group checkboxes
- `RadioButton` — radio selection (USE THIS for sex: Male/Female)
- `ToggleSwitch` — boolean toggle
- `Slider` — range slider
- `Rating` — star rating
- `ColorPicker` — color input (USE THIS in System Settings for color tokens)
- `FileUpload` — file picker (for profile picture — NOT for documents)
- `TreeSelect` — tree-structured dropdown

### Button Components (PrimeVue)
- `Button` — primary action button with icon support
- `SplitButton` — button with dropdown actions
- `SpeedDial` — floating action button group
- `ButtonGroup` — grouped buttons

### Overlay Components (PrimeVue)
- `Dialog` — modal dialog (USE THIS for all modals)
- `Drawer` (Sidebar) — slide-in panel (USE THIS for review detail panels)
- `Popover` (OverlayPanel) — popover/flyout
- `Tooltip` — hover tooltip (USE THIS for icon-only buttons)
- `ConfirmDialog` — confirmation modal (already used)
- `ConfirmPopup` — inline confirmation popover

### Message Components (PrimeVue)
- `Toast` — notification toasts (already used)
- `Message` — inline status message
- `InlineMessage` — field-level inline message (USE THIS for form errors)

### Navigation Components (PrimeVue)
- `TabView` — tabbed content (USE THIS for Pending/Screened/Returned tabs)
- `Tabs` + `TabList` + `Tab` + `TabPanels` (v4 API) — same as above
- `Steps` — step indicator (USE THIS for Apply page multi-step)
- `Breadcrumb` — breadcrumb navigation
- `Menubar` — horizontal menu
- `Menu` — simple dropdown menu
- `ContextMenu` — right-click menu
- `TieredMenu` — nested dropdown menu
- `MegaMenu` — large dropdown with columns
- `PanelMenu` — accordion sidebar menu
- `Accordion` — collapsible content sections
- `Stepper` — multi-step form indicator (USE THIS for voucher creation steps)

### Media Components (PrimeVue)
- `Image` — image with preview capability
- `Galleria` — image gallery with lightbox
- `Carousel` — scrollable card carousel

### Chart Components (PrimeVue)
- `Chart` — wrapper for Chart.js (line, bar, doughnut, pie, radar, polar)

---

## Examination Instructions

### What Files to Examine

Examine every file in these directories:
```
resources/js/Pages/Admin/
resources/js/Pages/Aics/
resources/js/Pages/Mswdo/
resources/js/Pages/Accountant/
resources/js/Pages/Treasurer/
resources/js/Pages/MayorsOffice/
resources/js/Pages/Auth/
resources/js/Components/Common/
resources/js/Components/Application/
resources/js/Components/Charts/
resources/js/Layouts/
```

### For Each File, Examine These Six Areas

**AREA 1 — Sakai/PrimeVue Underutilization**
Look for custom code that duplicates what PrimeVue already provides.
Ask for each element: "Is there a PrimeVue component that does this?"

Common patterns to look for:
- Custom tab switching logic → should be PrimeVue `TabView` or `Tabs`
- Custom modal/dialog → should be PrimeVue `Dialog`
- Custom dropdown → should be PrimeVue `Select`
- Custom date input → should be PrimeVue `DatePicker`
- Custom phone input → should be PrimeVue `InputMask`
- Custom number/amount input → should be PrimeVue `InputNumber`
- Custom sex/radio selection → should be PrimeVue `RadioButton` group
- Custom loading spinner → should be PrimeVue `ProgressSpinner` or `Skeleton`
- Custom badge/tag → should be PrimeVue `Tag` or `Chip`
- Custom timeline → should be PrimeVue `Timeline`
- Custom step indicator → should be PrimeVue `Steps` or `Stepper`
- Custom color picker (system settings) → should be PrimeVue `ColorPicker`
- Custom tooltip → should be PrimeVue `Tooltip` directive
- Custom notification count → should be PrimeVue `Badge` overlay

**AREA 2 — Token Violations**
Check every color, spacing, radius, and font value.
Flag anything not from the ALALAY token list above.

Common violations to look for:
- Hardcoded hex colors (`#3B82F6`, `text-blue-500`, etc.)
- Tailwind color classes that don't map to ALALAY tokens
- Arbitrary spacing values (`gap-3.5`, `p-5`, `mt-7`, etc.)
- Mixed border-radius values on the same component type
- Wrong font family on headings (Inter instead of Plus Jakarta Sans)
- Plain numbers/amounts not using JetBrains Mono

**AREA 3 — Motion Issues**
Check all transitions and animations.
Flag anything not matching the motion playbook.

Common issues to look for:
- `transition-all` — too broad, never use this
- `duration-300` on hover states — should be 150ms
- `ease-in` curves — never use ease-in
- Missing transitions on state-changing elements
- Status badge color changes without transition
- Modal appearing without spring entrance animation
- Button press without scale feedback

**AREA 4 — Taste Violations**
Apply tasteskill.dev judgment.
Flag visual clutter, overuse of accent color, too many bold elements.

Common issues to look for:
- More than 2 accent color usages on one page
- Every card having a colored left border (accent overuse)
- Icon + label + badge + color all on the same element (too much)
- Excessive shadows when one level would do
- Dense information with no breathing room
- Every heading in bold when only the page title needs bold
- Decorative dividers that add no meaning

**AREA 5 — Consistency Issues**
Look for the same element implemented differently across pages.
Flag when the same UI pattern was built differently in two places.

Common issues:
- Approve button styled differently on AICS vs MSWDO review pages
- Status badges using different color logic on different pages
- Date formatting inconsistent (some PST, some UTC)
- KPI card layout different on Admin vs AICS dashboard
- ReviewTrail built differently from page to page

**AREA 6 — Missing Standard Patterns**
Look for things that should be there but aren't.

Common missing patterns:
- Empty states missing (`AppEmptyState.vue` not used)
- No `Skeleton` loading state while data loads
- No `Tooltip` on icon-only buttons (accessibility)
- No `Badge` on sidebar menu items showing pending count
- Tables missing column sort indicators
- Forms missing `InlineMessage` for field errors
- Multi-step forms missing `Steps` component progress indicator

---

## Prescription Instructions

After examining all files, produce improvements in this format.

### Priority 1 — Critical (breaks consistency, must fix)
Violations of the token system or framework rules that make the
interface look inconsistent or unprofessional.

Format:
```
- [ ] [Component/Page] — [specific change]
      Sakai/PrimeVue: Replace [current] with [PrimeVue component]
      Token fix: [current value] → [correct token]
      File: resources/js/[path]
```

### Priority 2 — Important (weakens design, should fix)
Missing PrimeVue components where custom code was written instead.
Taste violations. Motion issues.

Same format as Priority 1.

### Priority 3 — Enhancement (adds polish, nice to have)
Motion additions. Accessibility improvements.
Additional PrimeVue features that would improve the experience.

Same format as Priority 1.

---

## Output Format — PROCESS.md Ready

After completing examination and prescription, format ALL improvements
as PROCESS.md checklist items ready to paste directly into PROCESS.md
under a new section called `## Phase 6b — Frontend Design Improvements`.

The output must be in this exact format so it can be pasted directly:

```markdown
## Phase 6b — Frontend Design Improvements
**Examined by: tasteskill.dev + emilkowal.ski/skill frameworks**
**Goal: Maximum PrimeVue/Sakai component usage + ALALAY token consistency**

### Critical Fixes (Priority 1 — Token and Consistency Violations)

- [ ] [Specific actionable item]
- [ ] [Specific actionable item]

### Important Improvements (Priority 2 — PrimeVue Maximization + Taste)

- [ ] [Specific actionable item]
- [ ] [Specific actionable item]

### Polish Enhancements (Priority 3 — Motion + Accessibility)

- [ ] [Specific actionable item]
- [ ] [Specific actionable item]

### Design Examination Summary
- Files examined: X
- Critical issues: X
- PrimeVue components underutilized: X (list them)
- Token violations: X
- Taste violations: X
- Motion issues: X
```

---

## Rules for the AI Agent During This Task

1. **Always prefer PrimeVue over custom code.** Before prescribing
   any custom component or CSS, check the PrimeVue component list
   above. If a component exists, use it.

2. **Never prescribe removing a working pattern just to replace it
   with something visually identical.** A change is only worth making
   if it reduces custom code, fixes a token violation, or meaningfully
   improves the design.

3. **Cite the specific rule for every prescription.** Every item in
   the improvement list must reference either a token, a framework
   rule, or a PrimeVue component. No vague prescriptions like
   "improve the styling" — be specific.

4. **Do not prescribe changes to the public Apply and Track pages.**
   Those pages are intentionally custom Tailwind with no PrimeVue —
   this is by design. Only examine staff panel pages and shared
   components used in staff panels.

5. **Do not prescribe changes to DocumentScanner.vue or
   useDocumentScanner.js.** These are intentionally custom — PrimeVue
   has no camera capture component.

6. **Preserve all working business logic.** Only change visual
   presentation — never change data flow, API calls, event handlers,
   or Inertia form submissions during this task.

7. **When prescribing PrimeVue TabView for tab switching**, confirm
   the existing tab state logic can be preserved. The prescription
   must include how to migrate the existing `activeTab` or `status`
   ref to PrimeVue's TabView binding.

8. **When prescribing PrimeVue Timeline for ReviewTrail**, note that
   PrimeVue Timeline accepts a `value` array with `{ status, date,
   icon, color, content }` shape — the prescription must include
   the data transformation from ALALAY's `reviews` array to this shape.

9. **Format all PROCESS.md items as actionable single tasks** — one
   checkbox per file per change. Do not combine multiple file changes
   into one checkbox item.

10. **Group PROCESS.md items by panel** — Admin section, AICS section,
    MSWDO section, Accountant section, Treasurer section, Mayor's Office
    section, Shared Components section.

---

## Session Start Instructions

When an AI agent reads this document, it must:

1. Confirm it has read and understood all three frameworks, the token
   list, and the PrimeVue component list.

2. Ask for the first batch of files to examine. Suggest starting with:
   - `resources/js/Components/Common/` — all files
   - `resources/js/Components/Application/ReviewTrail.vue`
   - `resources/js/Pages/Aics/Dashboard.vue`
   - `resources/js/Layouts/AppLayout.vue` or `AppTopbar.vue`

3. After receiving files, begin examination by area (1–6 above) for
   each file. Do not skip any area.

4. After examining all pasted files, produce the prescription and
   PROCESS.md output.

5. Ask if there are more files to examine before finalizing the
   PROCESS.md output.

---

## What Good Output Looks Like

A good PROCESS.md item is specific, actionable, and cites the reason:

```
GOOD:
- [ ] ReviewTrail.vue — Replace custom vertical list with PrimeVue Timeline component
      Data shape: map reviews array to { status, date, icon, color } per PrimeVue Timeline docs
      Token fix: connector line color hardcoded #E5E7EB → var(--color-border)
      File: resources/js/Components/Application/ReviewTrail.vue

BAD:
- [ ] Fix ReviewTrail styling
- [ ] Make ReviewTrail look better
- [ ] Update ReviewTrail colors
```

A good prescription always answers:
- What PrimeVue component replaces the current code (if applicable)?
- What specific token violation is being fixed?
- Which file needs the change?
- What is the expected visual result?

---

*Document prepared for AI consumption — ALALAY Frontend Design
Examination Task, Municipality of General Mamerto Natividad, Nueva Ecija.*

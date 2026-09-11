# ALALAY: IT Expert Presentation & Discussion Guide

**Purpose:** Efficient presentation strategy for evaluating the ALALAY system against the ISO/IEC 25010:2023 questionnaire (35 items, 8 characteristics).

---

## Presentation Format

| Aspect | Detail |
|---|---|
| Duration | 30–45 minutes total |
| Structure | 10-min overview → 20-min live demo → 5-min architecture → 5-min Q&A → questionnaire |
| Setup | Laptop/projector; two browser windows (staff panel + applicant tracking) |

---

## Part 1: Opening Overview (10 min)

### What to Cover
- What ALALAY is: digital AICS workflow system for GMN
- The problem it solves: manual paper-based process
- Who uses it: 7 roles (Applicant, AICS Staff, MSWDO, Accountant, Treasurer, Mayor's Office, Admin)
- Architecture summary: Laravel 12 + Vue 3 + Inertia.js monolith, MySQL, Supabase Storage, SMS API

### Items Addressed
| # | Item | How |
|---|---|---|
| 33 | Scalability | Single-municipality scope, queue-based background jobs |
| 34 | Installability | Standard LAMP stack, documented deploy script |

---

## Part 2: Live Demo — Full Workflow Walkthrough (20 min)

Walk through the entire application lifecycle as a continuous story, showing each actor's panel in sequence.

### Step 1: Applicant Submits (3 min)

**What to show:**
- Open the public Apply page
- Fill in claimant/beneficiary info
- Select assistance category
- Capture documents via DocumentScanner (camera-based)
- Submit and show the reference code success screen

**Items addressed:**
| # | Item | What they see |
|---|---|---|
| 1 | Functional Completeness | Full submission flow with category, PII, documents |
| 3 | Functional Correctness | Validation prevents invalid data, required fields enforced |
| 10 | Learnability | Intuitive multi-step form, no training needed |
| 12 | User Error Protection | Validation errors shown inline, confirmation before submit |
| 13 | User Engagement | Success screen with reference code and copy button |
| 16 | Self-Descriptiveness | Clear labels, instructions, category descriptions |

### Step 2: Applicant Tracks (1 min)

**What to show:**
- Open Track page
- Enter reference code
- Show status timeline with current step highlighted

**Items addressed:**
| # | Item | What they see |
|---|---|---|
| 9 | Appropriateness Recognizability | Status visible at a glance |
| 15 | User Assistance | Status cues show where application is in the pipeline |

### Step 3: AICS Staff Screens (2 min)

**What to show:**
- Log in as AICS Staff (show OTP challenge first)
- Show dashboard with KPIs and recent applications
- Open pending applications list
- Review one application, approve it

**Items addressed:**
| # | Item | What they see |
|---|---|---|
| 2 | Functional Appropriateness | Logical sequence: pending → review → approve |
| 4 | Time Behavior | Responsive page loads, instant navigation |
| 9 | Appropriateness Recognizability | Only screening actions visible to AICS Staff |
| 11 | Operability | Efficient controls: approve/return buttons, document viewer |
| 21 | Confidentiality | Role-based access — only sees own stage applications |
| 25 | Authenticity | OTP challenge required before access |

### Step 4: MSWDO Reviews + SCS Capture (3 min)

**What to show:**
- Log in as MSWDO
- Show application now in their queue (status changed)
- Review uploaded documents via inline viewer
- Capture social case study via DocumentScanner

**Items addressed:**
| # | Item | What they see |
|---|---|---|
| 1 | Functional Completeness | SCS capture step exists |
| 2 | Functional Appropriateness | Follows AICS screening in sequence |
| 3 | Functional Correctness | Status validation prevents wrong-step actions |
| 12 | User Error Protection | Confirmation dialog before approving |

### Step 5: AICS Staff Creates Assistance Code (2 min)

**What to show:**
- Log in as AICS Staff
- Open assistance coding page
- Show code type dropdown with default amounts
- Create the assistance code

**Items addressed:**
| # | Item | What they see |
|---|---|---|
| 1 | Functional Completeness | Coding step in the workflow |
| 2 | Functional Appropriateness | Follows SCS capture |
| 11 | Operability | Dropdown autofill, efficient data entry |

### Step 6: MSWDO Creates Voucher (2 min)

**What to show:**
- Log in as MSWDO
- Show voucher creation page with application context
- Capture voucher via DocumentScanner
- Submit

**Items addressed:**
| # | Item | What they see |
|---|---|---|
| 1 | Functional Completeness | Voucher creation step |
| 3 | Functional Correctness | Validation on file and page count |

### Step 7: Accountant Reviews Voucher (2 min)

**What to show:**
- Log in as Accountant
- Show pending vouchers
- Review voucher document and assistance code details
- Approve (or return to demonstrate return flow)

**Items addressed:**
| # | Item | What they see |
|---|---|---|
| 2 | Functional Appropriateness | Logical flow: created → checking → approved |
| 12 | User Error Protection | ConfirmDialog before approval |
| 13 | User Engagement | Toast notification on success |

### Step 8: Treasurer Acknowledges (2 min)

**What to show:**
- Log in as Treasurer
- Show pending cheques
- Acknowledge and mark as ready (or hold)
- Mention SMS notification sent to applicant

**Items addressed:**
| # | Item | What they see |
|---|---|---|
| 1 | Functional Completeness | Final workflow step |
| 13 | User Engagement | SMS triggered on cheque ready |

### Step 9: Admin Panel (3 min)

**What to show:**
- Log in as Admin
- Show user management (create/edit/toggle/deactivate users)
- Show audit logs with filters and CSV export
- Show system settings (SMS templates, file limits, branding)
- Point out the audit trail entry for the workflow just demonstrated

**Items addressed:**
| # | Item | What they see |
|---|---|---|
| 21 | Confidentiality | User management with role assignments |
| 23 | Non-Repudiation | Audit logs capturing every action |
| 24 | Accountability | Each action traced to a specific user |
| 29 | Analysability | Audit trail for debugging and review |
| 30 | Modifiability | System settings changed at runtime, no code deploy |

### Step 10: SMS Notification (1 min)

**What to show:**
- Show the sms_notifications log or mention that SMS was sent at submission and cheque ready
- Briefly explain the PhilSMS integration

**Items addressed:**
| # | Item | What they see |
|---|---|---|
| 8 | Interoperability | External SMS API integration |
| 13 | User Engagement | Applicant receives real-time notifications |

---

## Part 3: Architecture & Technical Walkthrough (5 min)

Show the codebase structure and key technical decisions. This targets the maintainability and flexibility items.

### What to Show

1. **Folder structure** — `app/Http/Controllers/`, `app/Services/`, `resources/js/Pages/`
2. **Service layer** — AuditLogger, SmsService, FileUploadService, SignedUrlService, ReferenceCodeService
3. **Policies** — ApplicationPolicy, VoucherPolicy, AssistanceCodePolicy, etc.
4. **Config files** — `config/sms.php`, `config/backup.php`, `config/filesystems.php` (Supabase disk)
5. **Queue system** — SendSmsJob dispatched asynchronously
6. **Database** — migrations, ENUM constraints, foreign keys
7. **Tests** — PHPUnit tests (`php artisan test` output)

### Items Addressed
| # | Item | What they see |
|---|---|---|
| 5 | Capacity | Queue-based background processing, deferred page loading |
| 6 | Resource Utilization | File cache driver, efficient DB queries with indexes |
| 7 | Co-existence | Standard LAMP stack (XAMPP/Ubuntu), no conflicts |
| 14 | Inclusivity | Hybrid submission (online + walk-in), camera fallback |
| 17 | Faultlessness | ENUMs, FKs, validation at every layer |
| 18 | Fault Tolerance | Queue retries, encrypted PII fields |
| 19 | Availability | Database session/queue drivers, no Redis dependency |
| 20 | Recoverability | Automated backup + encrypt + offsite upload + weekly restore test |
| 22 | Integrity | Laravel Policies prevent unauthorized record changes |
| 26 | Resistance | Rate limiting, CSRF middleware, security headers, XSS auto-escaping |
| 27 | Modularity | Service layer, separate controllers per role, Policies per model |
| 28 | Reusability | Shared Vue components (DocumentScanner, Dashboard, ReviewTrail), shared composables |
| 31 | Testability | PHPUnit tests exist and pass |
| 32 | Adaptability | System settings configurable at runtime, locale-ready |
| 35 | Replaceability | Supabase Storage via S3 driver (swappable), PhilSMS swappable |

---

## Part 4: Q&A (5 min)

Let experts ask questions. Common topics and how to address them:

| Question | Answer |
|---|---|
| How is data protected? | Encrypted PII fields, role-based access, MFA via OTP, audit logging |
| What if the server goes down? | Daily encrypted backups, weekly restore test, offsite copy in Supabase |
| Can it handle more users? | Queue-based processing, database indexes, deferred loading; built for single-municipality but extensible |
| How is authentication handled? | Laravel Fortify + Email OTP, session-based, AUP acknowledgment required |
| What about mobile? | Responsive PrimeVue Sakai layout, DocumentScanner works on phone cameras |

---

## Part 5: Hand Out Questionnaire

After the demo, distribute the IT Expert Questionnaire. Experts have now seen the system firsthand and can evaluate each item based on direct observation.

---

## Complete Item Mapping

| # | Characteristic | Sub-characteristic | Primary Demo Step | Secondary |
|---|---|---|---|---|
| 1 | Functional Suitability | Functional Completeness | Steps 1–9 (full workflow) | — |
| 2 | Functional Suitability | Functional Appropriateness | Steps 3–8 (logical sequence) | — |
| 3 | Functional Suitability | Functional Correctness | Steps 1, 4, 6 (validation) | — |
| 4 | Performance Efficiency | Time Behavior | Steps 3–8 (responsive UI) | — |
| 5 | Performance Efficiency | Capacity | Architecture (queue, deferred) | — |
| 6 | Performance Efficiency | Resource Utilization | Architecture (file cache, indexes) | — |
| 7 | Compatibility | Co-existence | Architecture (LAMP stack) | — |
| 8 | Compatibility | Interoperability | Step 10 (SMS API) | — |
| 9 | Interaction Capability | Appropriateness Recognizability | Steps 3, 5 (role-based actions) | Step 2 |
| 10 | Interaction Capability | Learnability | Step 1 (intuitive form) | — |
| 11 | Interaction Capability | Operability | Steps 3, 5 (efficient controls) | — |
| 12 | Interaction Capability | User Error Protection | Steps 1, 4, 7 (validation + confirm) | — |
| 13 | Interaction Capability | User Engagement | Steps 1, 8, 10 (feedback, toasts, SMS) | — |
| 14 | Interaction Capability | Inclusivity | Step 1 (hybrid submission, camera fallback) | — |
| 15 | Interaction Capability | User Assistance | Step 2 (status cues) | — |
| 16 | Interaction Capability | Self-Descriptiveness | Step 1 (clear labels) | — |
| 17 | Reliability | Faultlessness | Architecture (ENUMs, FKs, validation) | — |
| 18 | Reliability | Fault Tolerance | Architecture (queue retries, encrypted PII) | — |
| 19 | Reliability | Availability | Architecture (session/queue drivers) | — |
| 20 | Reliability | Recoverability | Architecture (backup strategy) | — |
| 21 | Security | Confidentiality | Steps 3, 9 (role-based access, MFA) | — |
| 22 | Security | Integrity | Architecture (Policies prevent changes) | Step 9 |
| 23 | Security | Non-Repudiation | Step 9 (audit logs) | — |
| 24 | Security | Accountability | Step 9 (audit trail per user) | — |
| 25 | Security | Authenticity | Step 3 (OTP challenge) | — |
| 26 | Security | Resistance | Architecture (rate limiting, CSRF, headers) | — |
| 27 | Maintainability | Modularity | Architecture (service layer, controllers) | — |
| 28 | Maintainability | Reusability | Architecture (shared components, composables) | — |
| 29 | Maintainability | Analysability | Step 9 (audit logs, logs) | — |
| 30 | Maintainability | Modifiability | Step 9 (system settings at runtime) | — |
| 31 | Flexibility | Testability | Architecture (PHPUnit tests) | — |
| 32 | Flexibility | Adaptability | Architecture (system settings, locale) | — |
| 33 | Flexibility | Scalability | Overview (queue, deferred loading) | — |
| 34 | Flexibility | Installability | Architecture (documented deploy) | — |
| 35 | Flexibility | Replaceability | Architecture (S3 driver, SMS swappable) | — |

---

## Key Principles

1. **Show, don't tell** — every item demonstrated visually, not just described
2. **Continuous story** — one application from submission to claiming, so experts see the full lifecycle
3. **Technical depth when needed** — architecture walkthrough gives the backend view for maintainability/flexibility items
4. **No training mode** — this is an evaluation demo, not user training; keep it fast and focused

---

*Document prepared for AI consumption and system development reference — ALALAY System, Municipality of General Mamerto Natividad, Nueva Ecija.*

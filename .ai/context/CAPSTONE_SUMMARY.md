# ALALAY — Capstone Summary
**A Digital AICS Management and Notification System with Hybrid Submission**
**Municipality of General Mamerto Natividad, Nueva Ecija**

> Audience: IT Expert / technical evaluator. This document provides background on the capstone system — what it is, why it exists, what it is built with, and its target device/platform — before hands-on technical evaluation.

---

## 1. Summary of Capstone

**ALALAY** is a web-based workflow and notification system that manages Assistance to Individuals in Crisis Situation (AICS) applications from submission to cheque claiming. It replaces the manual, paper-based process with a single digital workflow that supports both **online** and **in-person (hybrid) submission**.

Every application is issued a unique **reference code** that the applicant uses to track status and resubmit returned documents. Applicants receive **SMS notifications** at critical stages (submission complete, under review, resubmission needed, cheque ready).

**Core workflow:** Submission → AICS Staff screening → MSWDO review (social case study + voucher) → Accountant validation → Treasurer acknowledgement (cheque ready) → cheque claiming.

**Staff panels (role-based access):**

| Panel | Role |
|---|---|
| Admin | User/role management, audit logs, system settings |
| AICS Staff | Application screening, assistance codes |
| MSWDO | Review, social case study (document scanner), voucher creation |
| Accountant | Voucher validation (approve/return) |
| Treasurer | Cheque readiness / on-hold |
| Internal Audit | Audit trail review |
| Budget Office | Budget-related review |

**Key features:**
- Hybrid submission portal (online + encoded in-person applications)
- Application tracking with OTP verification (SMS/email) per reference code
- Document scanner (capture, crop, auto-attach to applications)
- Dashboards and reports per role
- SMS/email notification system (PhilSMS + Resend)
- Full audit logging of user actions
- Role-based access control with Acceptable Use Policy acceptance
- Daily encrypted database backup with offsite replication (Supabase Storage) and scheduled restore verification
- Data-privacy compliance measures per NPC Circular 2023-06 / RA 10173 (Data Privacy Act)

---

## 2. Purpose

**Problem:** AICS assistance processing is manual and fragmented — paper forms, physical routing between departments, no centralized status visibility, and no reliable audit trail. Applicants have no way to check progress except by returning to the municipal office, and staff cannot efficiently coordinate reviews across AICS, MSWDO, accounting, and treasury.

**ALALAY exists to:**
1. **Digitize the end-to-end AICS workflow** — one application record moves through all approving roles without physical routing.
2. **Give applicants transparency** — a reference code + OTP-protected tracking page lets applicants see status and resubmit documents without going to the office.
3. **Notify automatically** — SMS alerts at each critical stage reduce follow-up inquiries.
4. **Establish accountability** — role-based access control and immutable audit logs record who did what and when.
5. **Protect personal data** — the system handles sensitive personal information (names, addresses, crisis status, financial details); privacy-by-design controls satisfy the municipality's obligations as Personal Information Controller under the Data Privacy Act of 2012.
6. **Ensure continuity of records** — automated encrypted backups with offsite replication and verified restores protect against data loss.

**Intended users:** municipal staff (7 roles above) and AICS applicants (public portal, unauthenticated except for OTP-gated tracking).

---

## 3. Technologies Used

| Layer | Technology |
|---|---|
| Backend framework | **Laravel 12** (PHP 8.2) |
| Frontend | **Vue 3** + **Inertia.js** (server-driven SPA — no REST API layer, no separate frontend build/deploy) |
| UI library | **PrimeVue** (Sakai theme), Tailwind CSS, SCSS |
| Build tooling | **Vite** |
| Database | **MySQL** (dev: local XAMPP; production: Railway MySQL 9 service) |
| File/object storage | **Supabase Storage** (S3-compatible, offsite backup bucket) |
| Email | **Resend** (transactional: OTP mails, notifications) |
| SMS | **PhilSMS** API |
| Captcha / bot protection | **Cloudflare Turnstile** |
| Auth & security | Session-based auth, **email OTP** on login (6-digit, 5-min expiry, 5 attempts), role middleware, Acceptable Use Policy gate, bcrypt/hashing, rate-limited OTP endpoints |
| Background jobs | Laravel queues (**database** driver) — worker process on Railway |
| Scheduling | Laravel scheduler (cron service) — nightly encrypted backups, weekly restore verification, retention pruning |
| Reporting/exports | Chart.js (dashboards), Maatwebsite Excel, jsPDF (document generation) |
| Document handling | pdfjs-dist, Intervention Image (document scanner pipeline) |
| Localization | vue-i18n (English + Filipino) |
| Hosting / deployment | **Railway** (separate web, cron, worker services + MySQL; RAILPACK builder), GitHub for source control |
| Testing | PHPUnit feature tests (84 tests / 458 assertions at last full run) |

Architecture note: ALALAY is an **Inertia monolith** — Laravel serves routes, auth, and data to Vue components as props. There is no separate API, no CORS configuration, and no client-side router; URL routing is handled entirely by Laravel.

---

## 4. Device Focused: Web-Based

**ALALAY is a web-based system. It runs in a web browser — there is no native mobile or desktop application.**

| Aspect | Detail |
|---|---|
| Target device | Desktop / laptop computers (primary); tablets via responsive layouts |
| Access method | Modern web browser — Google Chrome or Microsoft Edge recommended |
| Installation required | **None** — users visit the hosted URL and sign in |
| Deployment model | Fully hosted web application (Railway cloud); users never install updates — the server is updated centrally |
| Staff usage | Municipal office workstations (desktop browsers) |
| Applicant usage | Any device with a browser — desktop, laptop, or mobile browser (public portal is responsive, but the system is designed and evaluated primarily as a **desktop web application**) |
| Connectivity | Requires internet access (hosted in the cloud) |

**Not in scope:** native iOS/Android apps, desktop installers, offline mode, browser extensions.

---

## Appendix — Evaluation Notes

- **Login flow to expect:** credentials → email OTP challenge (6 digits) → Acceptable Use Policy (first sign-in) → role-specific dashboard.
- **Public tracking flow:** reference code → OTP sent via SMS (PhilSMS, email fallback) → application status view.
- **OTP delivery in evaluation environments:** outbound mail is redirected to a configured test recipient (`MAIL_TEST_RECIPIENT`), and SMS may run in log/sandbox mode — a bypass code mechanism is provided for evaluation so the IT expert can access staff panels without live OTP delivery. Ask the development team for the active bypass code if needed.
- **Backups:** `backup:run` daily (02:00 UTC), `backup:verify` weekly (Sunday 03:00 UTC), `backup:prune` weekly (Sunday 04:00 UTC); artifacts are gzip + AES-256 encrypted.

**Companion documents:** `TECH_STACK.md` (detailed stack spec), `ALALAY_DOCUMENTATION.md` (full system documentation), `NPC_SECURITY_GUIDELINE.md` / `NPC_COMPLIANCE_CHECKLIST.md` (data-privacy compliance), `USER_STORY.md` (actors + workflow).

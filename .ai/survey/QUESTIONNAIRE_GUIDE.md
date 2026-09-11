# ALALAY Survey Questionnaire Guide

## Purpose

This guide defines the structure for two validated survey instruments to evaluate ALALAY (Digital AICS Management and Notification System, GMN MSWDO). Use this guide together with the ALALAY architecture context to write the actual item wording. Every item must reference a real ALALAY feature, screen, or workflow, not a generic statement about "the system."

## System context to draw from when writing items

- ALALAY digitizes AICS (Assistance to Individuals in Crisis Situation) application workflows for the Municipality of General Mamerto Natividad (GMN) MSWDO.
- Stack: Laravel 12 (Inertia.js monolith), Vue 3, PrimeVue Sakai, MySQL, Supabase Storage, Laravel Fortify with email OTP, file-based caching, database-backed queues, short polling for real-time updates.
- Key features: camera-based document scanner with presets (A4 portrait, card landscape, half-sheet landscape for the Cedula) and enhancement pipeline (downscale, grayscale, contrast stretch, adaptive threshold, PDF output); UUID-based records; reference codes in the format GMN-YYYY-XXXXXX; multi-step public application form with PSGC address selectors; application status tracking with OTP-verified lookup; SMS notifications at key workflow stages (submission, review, resubmission, cheque ready); walk-in assisted application intake by AICS Staff; beneficiary duplicate detection with 3-month cooldown; real-time table polling via short polling; CSV export on all index tables; i18n support (Filipino/English).
- Security features: role-based access control via Laravel middleware and policies (7 roles); encrypted PII fields (claimant/beneficiary address, phone, email); signed URLs for all file access; append-only audit logs and review trails; email OTP multi-factor authentication; Acceptable Use Policy acknowledgment gate; rate limiting on login, OTP, submission, and tracking endpoints; Cloudflare Turnstile bot protection; security headers (CSP, HSTS, X-Frame-Options, X-Content-Type-Options, Permissions-Policy); daily encrypted database backup with weekly verification; emergency maintenance mode; session revocation by Admin.
- Known security scope: an OWASP Top 10 audit already covered Broken Access Control, Injection, Insecure Design, Security Logging Failures, and Authentication Failures. A separate audit already covered Input Validation, Data Sanitization, Hashing, Security Configuration, Session Management, and OTP.
- Respondents: end-users are internal staff (AICS Staff, MSWDO, Accountant, Treasurer, Internal Audit, Budget Officer) who process AICS applications, and applicants/beneficiaries who submit applications or track their status. IT experts are technical evaluators assessing the system against ISO/IEC 25010:2023.

When drafting items, ground each one in a specific ALALAY action (e.g., scanning a Cedula, tracking an application's status, receiving a notification, an admin reviewing a voucher) rather than writing an abstract statement.

---

## Instrument 1: End-User Questionnaire (based on SUS)

**Format:** 10 items, all positively worded (adapted from the all-positive SUS variant, Sauro & Lewis 2011, to avoid reverse-scoring errors). 5-point Likert scale, Strongly Disagree to Strongly Agree.

**Scoring:** For each item, contribution = (response − 1). Sum all 10 contributions (range 0–40), multiply by 2.5 for a 0–100 overall score. For per-criterion reporting, average the raw (1–5) scores of the items within each criterion group.

| Criteria | Sub-Characteristics (what the item measures) | Item Count |
|---|---|---|
| Ease of Use | Simplicity, Perceived ease of use, Intuitiveness | 3 |
| Efficiency | Function integration, Design consistency | 2 |
| Learnability | Independence from technical support, Speed of learning, Minimal prior learning needed | 3 |
| User Satisfaction | Intention to use frequently, Confidence while using the system | 2 |
| **Total** | | **10** |

**Instructions for item drafting:**
- Write each item as a positive statement the respondent rates agreement with.
- Anchor each item to a concrete ALALAY task (submitting an application, scanning a document, checking application status, receiving a notification).
- Keep wording plain, one idea per item, no double-barreled statements.
- Do not reverse-word any item.

---

## Instrument 2: IT Expert Questionnaire (based on ISO/IEC 25010:2023)

**Format:** One item per sub-characteristic, 5-point Likert scale, Strongly Disagree to Strongly Agree. Scope is limited to the 8 characteristics below; Safety is intentionally excluded as not applicable to this system.

**Scoring:** Weighted mean per characteristic (average of its sub-characteristic items), then an overall quality index as the average of the 8 characteristic means.

| Criteria | Sub-Characteristics | Item Count |
|---|---|---|
| Functional Suitability | Functional Completeness, Functional Appropriateness, Functional Correctness | 3 |
| Performance Efficiency | Time Behavior, Capacity, Resource Utilization | 3 |
| Compatibility | Co-existence, Interoperability | 2 |
| Interaction Capability | Appropriateness Recognizability, Learnability, Operability, User Error Protection, User Engagement, Inclusivity, User Assistance, Self-Descriptiveness | 8 |
| Reliability | Faultlessness, Fault Tolerance, Availability, Recoverability | 4 |
| Security | Confidentiality, Integrity, Non-Repudiation, Accountability, Authenticity, Resistance | 6 |
| Maintainability | Modularity, Reusability, Analysability, Modifiability | 4 |
| Flexibility | Testability, Adaptability, Scalability, Installability, Replaceability | 5 |
| **Total** | | **35** |

**Instructions for item drafting:**
- Write one Likert statement per sub-characteristic, evaluating that specific quality attribute of ALALAY.
- Ground each item in a real architectural element. Use the hints below as a starting point, not a limitation:
  - **Confidentiality** → OTP-gated access to applicant records; encrypted PII fields (claimant/beneficiary address, phone, email) stored as ciphertext in MySQL; signed URLs for file access (Supabase Storage); role-based panel isolation.
  - **Integrity** → append-only `reviews` and `audit_logs` tables (no UPDATE or DELETE allowed); UUID primary keys preventing ID enumeration; Form Request validation on every mutating endpoint.
  - **Non-Repudiation** → immutable audit trail recording user_id, role, IP address, user agent, and timestamp for every action; review decisions tied to specific user and application state transitions.
  - **Accountability** → per-user audit logging across all panels; session tracking with IP and device info; Admin "Revoke Sessions" action; AUP acceptance recorded with timestamp.
  - **Authenticity** → email OTP multi-factor authentication on every login; Acceptable Use Policy acknowledgment gate before panel access; password policy enforcement (12+ chars, mixed case, numbers, symbols, breached-list check).
  - **Resistance** → rate limiting on login (5/min), OTP verification, application submission, and tracking endpoints; Cloudflare Turnstile bot protection on public forms; security headers (CSP, HSTS, X-Frame-Options, X-Content-Type-Options, Permissions-Policy); emergency maintenance mode toggle.
  - **Recoverability** → behavior after a failed document upload (application rolls back, partial uploads cleaned up); daily encrypted database backup with weekly restore verification; queue retry for failed SMS jobs.
  - **Time Behavior** → short polling via `usePolling` composable on all index tables; `Inertia::defer()` for deferred data loading on dashboards and analytics; eager loading (`with()`) on all Eloquent queries.
  - **Interoperability** → Supabase Storage integration via S3-compatible API (league/flysystem-aws-s3-v3); PhilSMS API integration for SMS delivery; PSGC API integration for address selectors.
  - **Adaptability** → i18n support (Filipino/English) via vue-i18n; admin-configurable SMS templates and system settings; dynamic assistance categories and required documents managed through Admin panel.
- Keep each item scoped to one sub-characteristic only, do not combine two sub-characteristics into one statement.
- Word items so a technical evaluator can answer them from direct inspection or testing of the system, not from end-user experience alone.

---

## Summary

- End-user questionnaire: 10 items, SUS-based, all positively worded. Includes a demographic section (role checklist) before the scored items.
- IT expert questionnaire: 35 items, ISO/IEC 25010:2023-based, one item per sub-characteristic. Includes a demographic profile and screening question before the scored items.
- Grand total: 45 scored items across both instruments.

---

## Sample Item Wording

The examples below demonstrate the expected grounding, specificity, and tone for each instrument. Item writers should use these as templates, not copy them verbatim.

### Instrument 1: End-User — Sample Items

| # | Criterion | Sample Item |
|---|---|---|
| 1 | Ease of Use | I found it easy to submit an AICS application through the ALALAY online portal. |
| 2 | Ease of Use | The document scanner on the Apply page was straightforward to use when capturing my supporting documents. |
| 3 | Efficiency | The application form collected all the information I needed to provide in a logical order. |
| 4 | Learnability | I was able to track my application status using my reference code without needing help from anyone. |
| 5 | User Satisfaction | I felt confident that my application was being processed correctly after receiving the SMS notification. |

### Instrument 2: IT Expert — Sample Items

| # | Sub-Characteristic | Sample Item |
|---|---|---|
| 1 | Functional Completeness | ALALAY covers the full AICS workflow from application submission through cheque claiming, including document capture, assistance coding, and voucher creation. |
| 2 | Confidentiality | Sensitive applicant data (phone numbers, addresses, emails) is stored as encrypted ciphertext in the database and is never exposed in plaintext through the application interface. |
| 3 | Non-Repudiation | Every workflow action (approve, return, code, create voucher) is recorded in an append-only review trail with the acting user's ID, role, IP address, and timestamp. |
| 4 | Time Behavior | The application index tables refresh automatically through short polling, allowing staff to see new submissions without manually reloading the page. |
| 5 | Recoverability | When a document upload fails mid-submission, the system rolls back the partial application and cleans up any orphaned files from storage. |
| 6 | Resistance | The public application form is protected by rate limiting and Cloudflare Turnstile, preventing automated bulk submissions. |

---

## Instrument 1: End-User Demographic Profile

This section precedes the 10-item SUS questionnaire. It is not scored; it describes the sample in the results chapter.

### Demographic Fields

| # | Field | Response Format |
|---|---|---|
| 1 | Role | Checklist: AICS Staff / MSWDO Staff / Accountant / Treasurer / Internal Audit / Budget Officer / Applicant (online) / Applicant (walk-in) |

**Notes:**
- Respondents check all roles that apply (e.g., a staff member who also submitted a personal application checks both).
- This field appears first in the questionnaire, before the 10 Likert items.
- Responses are descriptive only (e.g., reporting the distribution of respondents by role), they are not part of the SUS scoring.

---

## Instrument 2: IT Expert Demographic Profile

### Purpose

This section precedes the 35-item ISO/IEC 25010:2023 quality assessment. It establishes respondent eligibility and profile. It is not scored, it is used to describe the sample in the results chapter.

### Screening Question

Before proceeding, respondents must confirm they meet all four qualification criteria.

**Do you currently meet all of the following qualifications?**
- Hold a bachelor's degree or higher in Information Technology, Computer Science, Computer Engineering, or a closely related computing field
- Have at least 2 years of active professional experience in software development, software quality assurance, systems analysis, or a related IT discipline
- Have direct, demonstrable experience developing, testing, or evaluating web-based information systems (front-end, back-end, database integration, system deployment)
- Are currently employed or actively practicing in the IT industry, an IT-related academic institution, or a government technology office

Response: Yes / No
(If No, respondent is not qualified to proceed.)

### Demographic Fields

| # | Field | Response Format |
|---|---|---|
| 1 | Highest Educational Attainment | Bachelor's Degree / Master's Degree / Doctorate Degree |
| 2 | Field of Degree | Information Technology / Computer Science / Computer Engineering / Other (please specify) |
| 3 | Years of Professional IT Experience | 2–5 years / 6–10 years / 11+ years |
| 4 | Primary IT Discipline | Software Development / Software Quality Assurance / Systems Analysis / Other (please specify) |
| 5 | Areas of Direct Experience with Web-Based Systems (select all that apply) | Front-end Development / Back-end Development / Database Integration / System Deployment / Testing or Evaluation of Web-Based Systems |
| 6 | Current Employment Sector | IT Industry / IT-related Academic Institution / Government Technology Office |
| 7 | Current Position/Role | Open text |

### Notes

- This profile section comes first in the questionnaire, before the 35 ISO/IEC 25010:2023 items.
- Responses here are descriptive only (e.g., reporting the percentage of respondents by years of experience), they are not part of the Likert scoring.
- The screening question documents that the sample was purposively qualified rather than self-selected.
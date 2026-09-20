# ALALAY Application Flow

## Overview

The ALALAY system manages AICS (Assistance to Individuals in Crisis Situations) applications from submission to claiming. Applications flow through a multi-stage approval pipeline involving 7 roles across 10 workflow statuses.

---

## Status Flow

```
 submitted ──► mswdo_review ──► assistance_coding ──► internal_audit_review
                                                              │
                                                              ▼
                                                        voucher_creation
                                                              │
                                                              ▼
                                                        budget_checking
                                                              │
                                                              ▼
                                                       voucher_recording
                                                              │
                                                              ▼
                                                        with_treasurer
                                                              │
                                                              ▼
                                                         cheque_ready
                                                              │
                                                              ▼
                                                          claimed
```

### Return/Rejection Branches

Applications can be returned at multiple stages for document resubmission or re-coding:

```
submitted ◄──► returned_to_applicant ◄──► mswdo_review

internal_audit_review ◄──► returned_assistance_coding

budget_checking ◄──► voucher_on_hold
```

---

## Step-by-Step Walkthrough

### Step 1 — Application Submission

**Actor:** Applicant (public)
**Status:** `submitted`

The applicant fills out a multi-step form:

1. Select assistance category (medical, burial, food, etc.)
2. Enter claimant and beneficiary information
3. Scan/upload required documents (camera-based document scanner)
4. Review and confirm submission

On submission:
- An `Application` record is created with a unique reference code
- Document files are uploaded and `ApplicationDocument` records are created
- An SMS notification is sent: *"Your AICS application {ref} has been submitted. Track it at {url}"*

---

### Step 2 — AICS Staff Screening

**Actor:** AICS Staff
**Status:** `submitted` → `mswdo_review`

The AICS staff reviews newly submitted applications and decides:

- **Approve:** Forward to MSWDO for social case study review
  - Creates a `Review` record (stage: `aics_screening`, decision: `approved`)
  - SMS sent: *"Your application {ref} is now under review."*

- **Return:** Send back to applicant for document resubmission
  - Requires remarks explaining what is needed
  - Optionally specifies which documents must be re-uploaded
  - Creates a `Review` record (stage: `aics_screening`, decision: `returned`)
  - SMS sent: *"Your application {ref} requires resubmission. Reason: {remarks}"*

**AICS staff can also:**
- Create assisted walk-in applications on behalf of applicants
- Assign assistance codes (financial classification + amount) to approved applications

---

### Step 3 — MSWDO Social Case Study Review

**Actor:** MSWDO (Municipal Social Welfare and Development Officer)
**Status:** `mswdo_review` → `assistance_coding`

The MSWDO reviews applications forwarded by AICS staff:

- **Approve:** Upload social case study PDF and forward to assistance coding
  - Creates a `SocialCaseStudy` record with the uploaded PDF
  - Creates a `Review` record (stage: `mswdo_review`, decision: `approved`)

- **Return:** Send back to applicant for document resubmission
  - Same return mechanism as AICS screening
  - Creates a `Review` record (stage: `mswdo_review`, decision: `returned`)
  - SMS sent with resubmission instructions

---

### Step 4 — Assistance Coding

**Actor:** AICS Staff
**Status:** `assistance_coding` → `internal_audit_review`

The AICS staff assigns a financial code to the application:

- Selects an assistance code type (e.g., food assistance, medical, burial)
- Enters the peso amount
- Creates an `AssistanceCode` record
- Creates a `Review` record (stage: `assistance_coding`, decision: `coded`)

---

### Step 5 — Internal Audit Review

**Actor:** Internal Audit
**Status:** `internal_audit_review` → `voucher_creation`

The Internal Audit team reviews the assistance code assignment:

- **Approve:** Forward to MSWDO for voucher creation
  - Creates a `Review` record (stage: `internal_audit_review`, decision: `approved`)

- **Return:** Send back to AICS for re-coding
  - Requires remarks explaining the issue
  - Status goes to `returned_assistance_coding`
  - Creates a `Review` record (stage: `internal_audit_review`, decision: `returned`)

---

### Step 6 — Voucher Creation

**Actor:** MSWDO
**Status:** `voucher_creation` → `budget_checking`

The MSWDO uploads the disbursement voucher document:

- Uploads a PDF voucher file
- Creates a `Voucher` record (versioned, with file metadata)
- Creates a `Review` record (stage: `voucher_creation`, decision: `voucher_created`)

---

### Step 7 — Budget Checking

**Actor:** Budget Office
**Status:** `budget_checking` → `voucher_recording`

The Budget Office reviews the voucher against available funds:

- **Approve:** Forward to Accountant for recording
  - Creates a `Review` record (stage: `budget_checking`, decision: `approved`)

- **Hold:** Place voucher on hold
  - Status goes to `voucher_on_hold`
  - Creates a `Review` record (stage: `budget_checking`, decision: `hold`)

- **Release Hold:** Resume processing a held voucher
  - Status returns to `voucher_recording`
  - Creates a `Review` record (stage: `budget_checking`, decision: `approved`)

---

### Step 8 — Voucher Recording

**Actor:** Accountant
**Status:** `voucher_recording` → `with_treasurer`

The Accountant performs final accounting verification:

- **Approve:** Forward to Treasurer for cheque preparation
  - Creates a `Review` record (stage: `voucher_recording`, decision: `approved`)

---

### Step 9 — Treasurer Acknowledgment

**Actor:** Treasurer
**Status:** `with_treasurer` → `cheque_ready`

The Treasurer reviews the voucher and marks the cheque as ready:

- **Acknowledge:** Mark cheque as ready for claiming
  - Creates a `Review` record (stage: `treasurer_review`, decision: `approved`)
  - SMS sent: *"Your AICS cheque is ready for claiming. Please visit the MSWDO office."*

---

### Step 10 — Claiming

**Actor:** Treasurer + Applicant (physical)
**Status:** `cheque_ready` → `claimed`

The final step involves the applicant physically picking up the cheque:

1. **Admin triggers claiming notification:** Sends mass SMS to all applicants with ready cheques, specifying a claiming date
2. **Applicant arrives at MSWDO office** with their documents
3. **Treasurer marks as complete:** Updates status to `claimed` and sets `claimed_at` timestamp
4. **Application lifecycle is complete** (terminal status)

---

## Role Summary

| Role | Key Responsibilities |
|------|---------------------|
| **Applicant** | Submit application, resubmit documents when returned, physically claim cheque |
| **AICS Staff** | Screen applications, assign assistance codes, create walk-in applications |
| **MSWDO** | Social case study review, create voucher documents |
| **Internal Audit** | Review assistance coding for accuracy |
| **Budget Office** | Verify budget availability, approve/hold vouchers |
| **Accountant** | Final accounting verification, forward to Treasurer |
| **Treasurer** | Acknowledge cheque ready, mark as claimed |
| **Admin** | System management, trigger mass claiming SMS notifications |

---

## SMS Notifications

| Event | Trigger | Message |
|-------|---------|---------|
| `submission_complete` | Application submitted | Confirmation with reference code and tracking URL |
| `application_under_review` | AICS staff approves | Application is now under review |
| `resubmission_needed` | AICS/MSWDO returns application | Resubmission required with reason |
| `cheque_ready` | Treasurer acknowledges | Cheque ready for claiming at MSWDO office |
| `cheque_claiming` | Admin triggers mass notification | Cheque ready on specific date |

---

## Applicant Tracking

Applicants can track their application status at `/track`:

1. Enter reference code
2. Verify identity via OTP (6-digit code sent by SMS)
3. View color-coded status badge and full review timeline
4. If application is returned, resubmit required documents directly from the tracking page
5. Page polls every 20 seconds for real-time status updates

---

## Data Entities

| Entity | Created At | Purpose |
|--------|-----------|---------|
| `Application` | Submission | Core record with claimant info, status, reference code |
| `ApplicationDocument` | Submission | Uploaded/scanned documents (supports resubmissions) |
| `Review` | Every status change | Immutable audit trail of all decisions |
| `SocialCaseStudy` | MSWDO approval | Social case study PDF document |
| `AssistanceCode` | Assistance coding | Financial code type and peso amount |
| `Voucher` | Voucher creation | Disbursement voucher PDF (versioned) |

---

## Status Reference

| Status | Description |
|--------|-------------|
| `submitted` | Newly submitted, awaiting AICS screening |
| `returned_to_applicant` | Returned for document resubmission |
| `mswdo_review` | Forwarded to MSWDO for social case study |
| `assistance_coding` | MSWDO approved, awaiting assistance code assignment |
| `returned_assistance_coding` | Internal Audit returned coding for re-do |
| `internal_audit_review` | Assistance code assigned, awaiting IA review |
| `voucher_creation` | IA approved, MSWDO creating voucher |
| `budget_checking` | Voucher created, awaiting Budget Office review |
| `voucher_on_hold` | Budget Office placed voucher on hold |
| `voucher_recording` | Budget approved, Accountant recording |
| `with_treasurer` | Accountant approved, with Treasurer |
| `cheque_ready` | Cheque ready for physical claiming |
| `claimed` | Applicant picked up cheque (terminal) |

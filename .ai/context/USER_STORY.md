# ALALAY: A Digital AICS Management and Notification System with Hybrid Submission
**Municipality of General Mamerto Natividad, Nueva Ecija**

---

## System Overview

**ALALAY** is a digital workflow and notification system that manages Assistance to Individuals in Crisis Situation (AICS) applications from submission to cheque claiming. It supports **hybrid submission** (online and in-person), issues a unique reference code per application for tracking and document resubmission, and sends **SMS notifications** to applicants at critical stages of the process.

---

## Actors

| Actor | Role |
|---|---|
| **Applicant** | Submits application (online or in-person), receives reference code, tracks application, resubmits documents when returned |
| **AICS Staff** | Screens applications; creates assistance codes |
| **MSWDO** | Reviews applications; conducts and captures social case study (DocumentScanner); creates vouchers |
| **Accountant** | Checks voucher validity; approves or returns vouchers |
| **Treasurer** | Acknowledges approved vouchers; marks as cheque ready or places on hold |
| **Mayor's Office Staff** | View-only access to application data and reports; no direct manipulation of the workflow |
| **Admin** | Manages system users and role assignments; views and maintains audit logs; configures system settings; no direct manipulation of the application workflow |

---

## Notification System

**Channel:** SMS API
**Recipient:** Applicant (via registered mobile number)

| Trigger Event | SMS Notification Sent |
|---|---|
| Application successfully submitted | ✅ Submission Complete |
| Application is under active review | ✅ Application Under Review |
| Application returned for document resubmission | ✅ Resubmission Needed |
| Application is cheque ready | ✅ Cheque Claiming Notice |

---

## Hybrid Submission

Applicants may submit their application through either of the following channels:

- **Online** — via the ALALAY web portal using their device
- **In-Person** — submitted at the municipal office and encoded by AICS Staff into the system

Both methods produce the same workflow and issue a reference code to the applicant.

---

## Reference Code

- Issued immediately upon successful application submission
- Unique per application
- Used by the applicant to:
  - Track current application status
  - Resubmit supporting documents when the application is returned

---

## Application Lifecycle

### Stage 0 — Application Submission
- **Actor:** Applicant (or AICS Staff on behalf of walk-in applicant)
- **Action:** Submits application with required supporting documents via online portal or in-person.
- **Outcome:** System generates a unique **reference code** and sends an SMS notification.
- **SMS Trigger:** `Submission Complete`

---

### Stage 1 — AICS Staff Screening
- **Actor:** AICS Staff
- **Input:** Newly submitted application

| Decision | Next Step | SMS Trigger |
|---|---|---|
| ✅ Approved | Application moves to **Stage 2 — MSWDO Review** | `Application Under Review` |
| 🔄 Returned | Application returned to applicant for document resubmission | `Resubmission Needed` |

**Resubmission:** Applicant uses reference code to access and resubmit corrected documents. Application re-enters Stage 1.

---

### Stage 2 — MSWDO Review
- **Actor:** MSWDO
- **Input:** AICS Staff-approved application

| Decision | Next Step | SMS Trigger |
|---|---|---|
| ✅ Valid | MSWDO conducts **Social Case Study** and captures the document/image (via DocumentScanner camera) to the system. Application moves to **Stage 3** | `Application Under Review` |
| 🔄 Returned | Application returned to applicant for document resubmission | `Resubmission Needed` |

**Resubmission:** Applicant uses reference code to access and resubmit corrected documents. Application re-enters Stage 2.

---

### Stage 3 — Assistance Coding
- **Actor:** AICS Staff
- **Input:** Application with captured social case study
- **Action:** AICS Staff reviews the social case study and generates an **Assistance Code**.
- **Outcome:** Application with assistance code moves to MSWDO for voucher creation.

---

### Stage 4 — Voucher Creation
- **Actor:** MSWDO
- **Input:** Application with assistance code
- **Action:** MSWDO creates a **voucher** derived from the assistance code.
- **Outcome:** Voucher is forwarded to the Accountant for checking.

---

### Stage 5 — Accountant: Voucher Checking
- **Actor:** Accountant
- **Input:** Created voucher

| Decision | Next Step |
|---|---|
| ✅ Approved | Voucher forwarded to **Treasurer** |
| 🔄 Returned | Voucher returned for **re-creation** (back to Stage 4) |

---

### Stage 6 — Treasurer: Voucher Acknowledgment
- **Actor:** Treasurer
- **Input:** Accountant-approved voucher

| Decision | Next Step |
|---|---|
| ✅ Acknowledge & Ready | Application marked as **Cheque Ready**; SMS sent to applicant |
| ⏸ Acknowledge & Hold | Application placed **On Hold** (pending budget) |

---

### Stage 7 — Cheque Claiming Notification
- **Actor:** System → Applicant
- **Trigger:** Application status becomes `CHEQUE_READY`
- **Action:** System sends SMS notification instructing the applicant to claim their cheque.
- **SMS Trigger:** `Cheque Claiming Notice`

---

## Application Status Reference

| Status Code | Description |
|---|---|
| `SUBMITTED` | Application submitted; reference code issued |
| `SCREENING` | Under review by AICS Staff |
| `RETURNED_TO_APPLICANT` | Returned for document resubmission |
| `MSWDO_REVIEW` | Under review by MSWDO |
| `SOCIAL_CASE_STUDY_UPLOADED` | Social case study conducted and captured |
| `ASSISTANCE_CODING` | AICS Staff creating assistance code |
| `VOUCHER_CREATION` | MSWDO creating voucher |
| `VOUCHER_CHECKING` | Accountant reviewing voucher |
| `WITH_TREASURER` | Voucher forwarded to Treasurer |
| `ON_HOLD` | Placed on hold by Treasurer |
| `CHEQUE_READY` | Approved and ready for claiming |
| `CLAIMED` | Applicant has claimed the cheque |

---

## Key Business Rules

1. A reference code is **unique per application** and issued immediately upon submission.
2. Hybrid submission supports both **online** and **in-person (walk-in)** channels; both follow the same workflow.
3. Applications can only be returned to the applicant from **Stage 1 (AICS Staff)** or **Stage 2 (MSWDO)**.
4. The **Social Case Study image/document** must be captured (via DocumentScanner) before an application can proceed past Stage 2.
5. An **Assistance Code** must exist before a voucher can be created.
6. A voucher must be **Accountant-approved** before it is forwarded to the Treasurer.
7. The **Treasurer** makes the final determination — applications are marked **Cheque Ready** or placed **On Hold** directly after voucher acknowledgment; no separate budget checking step exists.
8. **SMS notifications** are sent only at defined critical stages; non-critical internal transitions do not trigger applicant notifications.
9. **Mayor's Office Staff** has read-only access to all application data and reports and cannot modify or act on any application at any stage.
10. **Admin** has no role in the application workflow and cannot approve, return, or act on any application at any stage.
11. **Admin** is responsible for creating, updating, deactivating, and assigning roles to all system users (AICS Staff, MSWDO, Accountant, Treasurer, Mayor's Office Staff).
12. **Audit logs** are system-generated and capture all user actions across all stages; only the Admin can access the full audit log.
13. **System settings** (e.g., SMS API configuration, application form fields, notification templates) are managed exclusively by the Admin.

---

## Workflow Diagram (Text)

```
Applicant Submits (Online or In-Person)
            ↓
  [Reference Code Issued] → SMS: Submission Complete
            ↓
  Stage 1: AICS Staff Screening
    ├── Returned → SMS: Resubmission Needed → Applicant Resubmits → Stage 1
    └── Approved → SMS: Application Under Review ↓
  Stage 2: MSWDO Review
    ├── Returned → SMS: Resubmission Needed → Applicant Resubmits → Stage 2
    └── Valid → Upload Social Case Study ↓
  Stage 3: AICS Staff — Assistance Coding
    └── Assistance Code Created ↓
  Stage 4: MSWDO — Voucher Creation
    └── Voucher Created ↓
  Stage 5: Accountant — Voucher Checking
    ├── Returned → Stage 4 (Re-create Voucher)
    └── Approved → Treasurer ↓
  Stage 6: Treasurer — Voucher Acknowledgment
    ├── On Hold → ON HOLD (pending budget)
    └── Ready → CHEQUE READY ↓
  Stage 7: Notify Applicant → SMS: Cheque Claiming Notice
```

---

*Document prepared for AI consumption and system development reference — ALALAY System, Municipality of General Mamerto Natividad, Nueva Ecija.*

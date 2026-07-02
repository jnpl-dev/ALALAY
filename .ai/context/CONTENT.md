# ALALAY: Panels, Pages, and Content Specification
**MySQL/MariaDB — Laravel — XAMPP**
**Municipality of General Mamerto Natividad, Nueva Ecija**

---

## Overview

This document defines every panel, page, and content element for each actor role in the ALALAY system. All table columns, form fields, and data references are aligned with the finalized schema dictionary. Each role has a dedicated panel with access strictly restricted to their scope of responsibility.

---

## Role-to-Panel Map

| Role | Panel | Workflow Access |
|---|---|---|
| Applicant | Public Website | Submit, track, resubmit |
| AICS Staff | AICS Staff Panel | Screen applications, create assistance codes |
| MSWDO | MSWDO Panel | Review applications, capture social case study (DocumentScanner), create vouchers |
| Accountant | Accountant Panel | Check vouchers, check budget |
| Treasurer | Treasurer Panel | Acknowledge vouchers, manage cheque status |
| Mayor's Office Staff | Mayor's Office Panel | View only — no workflow actions |
| Admin | Admin Panel | User management, audit logs, system settings — no workflow actions |

---

## Global UI/UX Standards

- **Review Trail** is present on every review/action page across all panels. Displayed on the right side panel, ordered chronologically (newest on top). Columns: Stage | Decision | Remarks | From Status | To Status | Date & Time | Reviewed By.
- **Modal confirmations** are required for all irreversible actions (approve, return, submit, deactivate).
- **Document viewer** (image/PDF inline viewer) is embedded on all review pages — no download required to view.
- **SMS notifications** fire automatically on system status transitions — no manual trigger needed by staff.
- **Pagination, search, and column filters** are present on all data tables.
- **Export to CSV** is available on all data tables across all panels.
- All timestamps displayed in the UI are in **Philippine Standard Time (PST, UTC+8)**.
- **Toast notifications** confirm successful actions (approve, return, save, etc.).

---

## 1. Public-Facing Website (Applicants)

No login required. Accessible by any member of the public.

---

### 1.1 Apply Page

**Purpose:** Allow applicants to browse assistance categories and submit an application.

**Flow:**

**Step 1 — Select Assistance Category**
- Displays all active assistance categories (`assistance_categories` where `is_active = 1`).
- Each category card shows: `category_name`, `category_description`, and a list of required documents (`required_documents` where `category_id` matches and `is_active = 1`).
- Mandatory documents are visually marked (`is_mandatory = 1`).
- Selecting a category proceeds to Step 2.

**Step 2 — Fill Application Form**

*Claimant Information:*
| Field | Schema Column | Notes |
|---|---|---|
| Last Name | `claimant_last_name` | Required |
| First Name | `claimant_first_name` | Required |
| Middle Name | `claimant_middle_name` | Optional |
| Name Extension | `claimant_name_extension` | Optional — Jr., Sr., III |
| Sex | `claimant_sex` | Dropdown: Male / Female |
| Date of Birth | `claimant_dob` | Date picker |
| Address | `claimant_address` | Textarea |
| Phone Number | `claimant_phone` | Required |
| Email Address | `claimant_email` | Optional |
| Relationship to Beneficiary | `claimant_relationship_to_beneficiary` | Required |

*Beneficiary Information:*
| Field | Schema Column | Notes |
|---|---|---|
| Last Name | `beneficiary_last_name` | Required |
| First Name | `beneficiary_first_name` | Required |
| Middle Name | `beneficiary_middle_name` | Optional |
| Name Extension | `beneficiary_name_extension` | Optional |
| Sex | `beneficiary_sex` | Dropdown: Male / Female |
| Date of Birth | `beneficiary_dob` | Date picker |
| Address | `beneficiary_address` | Textarea |

**Step 3 — Capture Supporting Documents**
- Lists all required documents for the selected category.
- Each document has a DocumentScanner component (camera capture with guide overlay; enhancement pipeline runs automatically on capture).
- Mandatory documents (`is_mandatory = 1`) must be captured before proceeding.
- Accepted file types: image (JPG, PNG) only — camera captures as JPEG; fallback file input accepts JPG, PNG only.
- File size limit enforced (configurable via `system_settings`).
- If camera is unavailable, a fallback file input (JPG, PNG) is shown as a secondary option.

**Step 4 — Summary & Confirmation**
- Displays a read-only summary of all filled fields and captured documents.
- Applicant confirms accuracy before final submission.
- Submit button triggers application creation.

**Step 5 — Submission Complete**
- System generates a unique `reference_code` and sets `status = 'submitted'`, `submission_type = 'online'`.
- Reference code is displayed prominently on screen.
- Instruction to save the reference code for tracking.
- **SMS triggered:** `submission_complete` → sent to `claimant_phone`.

---

### 1.2 Track Page

**Purpose:** Allow applicants to track their application status and resubmit documents if returned.

**Flow:**

**Step 1 — Enter Reference Code**
- Single input field for `reference_code`.
- Submit button fetches the application.

**Step 2 — Application Status View**

*Application Summary Card:*
| Field | Schema Column |
|---|---|
| Reference Code | `applications.reference_code` |
| Assistance Category | `assistance_categories.category_name` |
| Beneficiary Name | `beneficiary_first_name` + `beneficiary_last_name` |
| Date Submitted | `applications.created_at` |
| Current Status | `applications.status` |

*Review Trail:*
- Full chronological log pulled from `reviews` table for that `application_id`.
- Columns displayed: Stage | Decision | Remarks | From Status | To Status | Date & Time.

**Step 3 — Resubmission (Conditional)**
- Visible only when `applications.status = 'returned_to_applicant'`.
- Displays `applications.resubmission_remarks` as a notice to the applicant.
- Shows only the documents flagged in the latest `reviews.resubmission_docs_required` (JSON array of `required_documents.id`).
- Each flagged document has a DocumentScanner component for re-capture (camera primary; fallback file input secondary).
- Submit resubmission button:
  - Saves new documents to `application_documents` with `is_resubmission = 1` and incremented `resubmission_number`.
  - Resets `applications.status` to the appropriate review stage.
  - **SMS triggered:** `application_under_review`.

---

## 2. Admin Panel

**Access:** Admin role only.
**Route prefix:** `/admin`

---

### 2.1 Dashboard

**Purpose:** High-level snapshot of system health and user activity for today.

**KPI Cards (today's data):**
| KPI | Source |
|---|---|
| Total Registered Users | `users` count |
| Active Accounts | `users` where `status = 'active'` |
| Inactive Accounts | `users` where `status = 'inactive'` |
| Currently Online Users | `users` where `is_online = 1` |
| New Users Registered Today | `users` where `created_at` = today |
| Total Audit Log Entries Today | `audit_logs` where `created_at` = today |

**Tables:**

*Recent Activities (latest 10):*
| Column | Source |
|---|---|
| User | `users.first_name` + `users.last_name` |
| Role | `audit_logs.role` |
| Module | `audit_logs.module` |
| Action | `audit_logs.action` |
| Description | `audit_logs.description` |
| IP Address | `audit_logs.ip_address` |
| Timestamp | `audit_logs.created_at` |

*Unusual Activities (flagged entries, latest 10):*
- Flagged by: multiple failed logins, rapid successive actions, actions outside business hours.
| Column | Source |
|---|---|
| User | `users.first_name` + `users.last_name` |
| Role | `audit_logs.role` |
| Action | `audit_logs.action` |
| Description | `audit_logs.description` |
| IP Address | `audit_logs.ip_address` |
| Timestamp | `audit_logs.created_at` |

**System Status Strip:**
- SMS API status (live ping to provider endpoint — configurable via `system_settings.setting_key = 'sms_api_endpoint'`).
- Last SMS sent timestamp from `sms_notifications` where `status = 'sent'` ordered by `created_at` DESC.

---

### 2.2 Analytics

**Date Filter:** All Time | Last Year | Last 3 Months | Last Month | This Month | Custom Date Range

**Charts:**
| Chart | Type | Source |
|---|---|---|
| User Registrations Over Time | Line | `users.created_at` grouped by date |
| Active vs. Inactive Users Over Time | Line | `users.status` + `users.updated_at` |
| Users by Role | Donut | `users.role` group count |
| Login Frequency by Role | Bar | `audit_logs` where `action = 'login'` grouped by `role` |
| Audit Log Volume Over Time | Bar | `audit_logs.created_at` grouped by date |
| Failed Login Attempts Over Time | Line | `audit_logs` where `action = 'login_failed'` grouped by date |

---

### 2.3 User Management

**Purpose:** Full CRUD management of all system users.

**Main Table:**
| Column | Source |
|---|---|
| Name | `users.last_name`, `users.first_name`, `users.middle_name`, `users.name_extension` |
| Email | `users.email` |
| Role | `users.role` |
| Status | `users.status` |
| Online | `users.is_online` |
| Date Created | `users.created_at` |
| Actions | View \| Edit \| Activate / Deactivate |

**Add User Page** (top-right button → separate page):
| Field | Schema Column | Notes |
|---|---|---|
| First Name | `users.first_name` | Required |
| Last Name | `users.last_name` | Required |
| Middle Name | `users.middle_name` | Optional |
| Name Extension | `users.name_extension` | Optional |
| Email | `users.email` | Required, unique |
| Role | `users.role` | Dropdown of all roles except `applicant` |
| Initial Password | `users.password` | Required; hashed on save |
| Confirm Password | — | Validation only |

**Edit User Page:**
- Same fields as Add User.
- Password field is optional on edit (leave blank to keep current password).
- Role can be changed.

**Activate / Deactivate:**
- Toggles `users.status` between `'active'` and `'inactive'`.
- Deactivating does not delete the user (no hard delete).
- Confirmation modal required before action.
- Writes to `audit_logs`.

---

### 2.4 Audit Logs

**Purpose:** Full read-only log of all user actions across the system.

**Main Table:**
| Column | Source |
|---|---|
| Log ID | `audit_logs.id` |
| User | `users.first_name` + `users.last_name` (via `audit_logs.user_id`) |
| Role | `audit_logs.role` |
| Module | `audit_logs.module` |
| Action | `audit_logs.action` |
| Description | `audit_logs.description` |
| Affected Record | `audit_logs.entity_type` + `audit_logs.entity_id` |
| IP Address | `audit_logs.ip_address` |
| Timestamp | `audit_logs.created_at` |

**Filters:** User | Role | Module | Action | Date Range

**Export:** CSV

---

### 2.5 System Settings

**Purpose:** Admin configuration of system-wide settings stored in `system_settings`.

**Setting Groups and Keys:**

*Branding (`setting_group = 'branding'`):*
| Setting Key | Description |
|---|---|
| `system_name` | Display name of the system |
| `system_logo` | Uploaded logo file path |
| `primary_color` | Hex color code |
| `secondary_color` | Hex color code |

*SMS (`setting_group = 'sms'`):*
| Setting Key | Description |
|---|---|
| `sms_api_key` | API key for SMS provider |
| `sms_sender_name` | Sender name/number shown to recipient |
| `sms_api_endpoint` | Provider API endpoint URL |

*Notifications (`setting_group = 'notifications'`):*
| Setting Key | Description |
|---|---|
| `sms_template_submission_complete` | Editable SMS body for submission complete |
| `sms_template_under_review` | Editable SMS body for under review |
| `sms_template_resubmission_needed` | Editable SMS body for resubmission needed |
| `sms_template_cheque_claiming` | Editable SMS body for cheque claiming |

*Application (`setting_group = 'application'`):*
| Setting Key | Description |
|---|---|
| `max_file_size_kb` | Maximum upload file size in KB |
| `allowed_mime_types` | Comma-separated allowed MIME types |

**Assistance Categories Management** (sub-section):
- Table of all `assistance_categories` with Add / Edit / Activate / Deactivate actions.
- Editing a category also manages its `required_documents` (add, edit, toggle `is_active`, toggle `is_mandatory`).

**Assistance Code References Management** (sub-section):
- Table of all `assistance_code_references` with Add / Edit / Activate / Deactivate actions.

---

### 2.6 Account Settings

**Purpose:** Allow the Admin to update their own profile.

| Field | Schema Column | Notes |
|---|---|---|
| First Name | `users.first_name` | Required |
| Last Name | `users.last_name` | Required |
| Middle Name | `users.middle_name` | Optional |
| Name Extension | `users.name_extension` | Optional |
| Email | `users.email` | Required |
| Profile Photo | `users.profile_picture_*` | Upload; stores name, path, size, mime type |
| Current Password | — | Required to change password |
| New Password | `users.password` | Optional |
| Confirm New Password | — | Validation only |

---

## 3. AICS Staff Panel

**Access:** `aics_staff` role only.
**Route prefix:** `/aics`

---

### 3.1 Dashboard

**Purpose:** Daily snapshot of application screening workload.

**KPI Cards (today's data):**
| KPI | Source |
|---|---|
| New Applications Today | `applications` where `status = 'submitted'` and `created_at` = today |
| Total Pending Applications | `applications` where `status = 'submitted'` |
| Screened Applications | `applications` where `status = 'screening'` or forwarded to MSWDO |
| Returned Applications | `applications` where `status = 'returned_to_applicant'` |
| Pending Assistance Coding | `applications` where `status = 'assistance_coding'` |
| Resubmissions Received Today | `application_documents` where `is_resubmission = 1` and `created_at` = today |

**Table — Applications Requiring Action Today:**
| Column | Source |
|---|---|
| Reference Code | `applications.reference_code` |
| Beneficiary Name | `applications.beneficiary_first_name` + `beneficiary_last_name` |
| Category | `assistance_categories.category_name` |
| Submission Type | `applications.submission_type` |
| Date Submitted | `applications.created_at` |
| Status | `applications.status` |

---

### 3.2 Analytics

**Date Filter:** All Time | Last Year | Last 3 Months | Last Month | This Month | Custom Date Range

**Charts:**
| Chart | Type | Source |
|---|---|---|
| Applications Received Over Time | Line | `applications.created_at` grouped by date |
| Approved vs. Returned Applications | Bar | `reviews` where `stage = 'aics_screening'` grouped by `decision` |
| Applications by Assistance Category | Donut | `applications.category_id` group count |
| Resubmissions Over Time | Bar | `application_documents` where `is_resubmission = 1` grouped by date |
| Average Screening Turnaround Time | Metric | Avg diff between `applications.created_at` and `reviews.created_at` where `stage = 'aics_screening'` |

---

### 3.3 Applications

**Tabs:** `Pending` | `Screened` | `Returned`

---

**Pending Tab** — applications with `status = 'submitted'`

*Table:*
| Column | Source |
|---|---|
| Reference Code | `applications.reference_code` |
| Beneficiary Name | `beneficiary_first_name` + `beneficiary_last_name` |
| Category | `assistance_categories.category_name` |
| Submission Type | `applications.submission_type` |
| Date Submitted | `applications.created_at` |
| Action | Review button |

*Review Page (on Review button click):*

Left/Main Panel:
- **Application Information** — all claimant and beneficiary fields from `applications`.
- **Supporting Documents** — list of captured documents from `application_documents` joined with `required_documents.doc_name`; each document has an inline viewer (image).

Right Panel — Review Trail:
- All entries from `reviews` where `application_id` matches, ordered by `created_at` DESC.
- Columns: Stage | Decision | Remarks | From Status | To Status | Date & Time | Reviewed By.

Decision Buttons:
- **Approve:**
  - Sets `applications.status = 'mswdo_review'`.
  - Sets `applications.reviewed_by = auth()->user()->id` and `reviewed_at = now()`.
  - Inserts row into `reviews` (stage: `aics_screening`, decision: `approved`, from: `submitted`, to: `mswdo_review`).
  - Writes to `audit_logs`.
  - **SMS triggered:** `application_under_review` → sent to `applications.claimant_phone`.

- **Return:**
  - Opens a modal with:
    - Checklist of required documents (`required_documents` for that `category_id`) — select which need resubmission.
    - Remarks textarea (saved to `applications.resubmission_remarks`).
  - On confirm:
    - Sets `applications.status = 'returned_to_applicant'`.
    - Inserts row into `reviews` (stage: `aics_screening`, decision: `returned`, from: `submitted`, to: `returned_to_applicant`, `resubmission_docs_required` = selected doc IDs as JSON).
    - Writes to `audit_logs`.
    - **SMS triggered:** `resubmission_needed` → sent to `applications.claimant_phone`.

---

**Screened Tab** — applications with `status = 'mswdo_review'` or beyond, originating from AICS screening

*Table:*
| Column | Source |
|---|---|
| Reference Code | `applications.reference_code` |
| Beneficiary Name | `beneficiary_first_name` + `beneficiary_last_name` |
| Category | `assistance_categories.category_name` |
| Date Screened | `reviews.created_at` (where `stage = 'aics_screening'` and `decision = 'approved'`) |
| Action | View button |

*View Page:* Read-only version of the Review Page. No decision buttons.

---

**Returned Tab** — applications with `status = 'returned_to_applicant'` returned from AICS screening

*Table:*
| Column | Source |
|---|---|
| Reference Code | `applications.reference_code` |
| Beneficiary Name | `beneficiary_first_name` + `beneficiary_last_name` |
| Category | `assistance_categories.category_name` |
| Date Returned | `reviews.created_at` (latest where `stage = 'aics_screening'` and `decision = 'returned'`) |
| Action | View button |

*View Page:* Read-only. No decision buttons.

---

### 3.4 Assistance Coding

**Tabs:** `Pending` | `Coded`

---

**Pending Tab** — applications with `status = 'assistance_coding'`

*Table:*
| Column | Source |
|---|---|
| Reference Code | `applications.reference_code` |
| Beneficiary Name | `beneficiary_first_name` + `beneficiary_last_name` |
| Category | `assistance_categories.category_name` |
| Date Forwarded | `reviews.created_at` (latest where `stage = 'mswdo_review'` and `decision = 'approved'`) |
| Action | Code button |

*Assistance Coding Page (on Code button click):*

Left/Main Panel:
- **Application Information** — all claimant and beneficiary fields from `applications`.
- **Social Case Study** — inline viewer of `social_case_studies.file_path` for that application.
- **Coding Form:**
  | Field | Schema Column | Notes |
  |---|---|---|
  | Assistance Code Type | `assistance_codes.assistance_code_reference_id` | Dropdown from `assistance_code_references` where `is_active = 1`; selecting auto-fills default amount |
  | Amount to Receive | `assistance_codes.amount` | Pre-filled from `assistance_code_references.default_amount`; editable |

- **Submit Button:**
  - Inserts row into `assistance_codes` (`application_id`, `assistance_code_reference_id`, `amount`, `assigned_by = auth()->user()->id`).
  - Sets `applications.status = 'voucher_creation'`.
  - Inserts row into `reviews` (stage: `assistance_coding`, decision: `coded`, from: `assistance_coding`, to: `voucher_creation`).
  - Writes to `audit_logs`.

Right Panel — Review Trail (same as Applications).

---

**Coded Tab** — applications with `status = 'voucher_creation'` or beyond where assistance code exists

*Table:*
| Column | Source |
|---|---|
| Reference Code | `applications.reference_code` |
| Beneficiary Name | `beneficiary_first_name` + `beneficiary_last_name` |
| Category | `assistance_categories.category_name` |
| Assistance Code Type | `assistance_code_references.code_type` |
| Amount | `assistance_codes.amount` |
| Date Coded | `assistance_codes.created_at` |
| Coded By | `users.first_name` + `users.last_name` (via `assistance_codes.assigned_by`) |
| Action | View button |

*View Page:* Read-only. No decision buttons.

---

### 3.5 Account Settings

Same structure as Admin Account Settings (Section 2.6). Scoped to the logged-in AICS Staff user.

---

## 4. MSWDO Panel

**Access:** `mswdo` role only.
**Route prefix:** `/mswdo`

---

### 4.1 Dashboard

**Purpose:** Daily snapshot of MSWDO review and voucher workload.

**KPI Cards (today's data):**
| KPI | Source |
|---|---|
| New Screened Applications Today | `applications` where `status = 'mswdo_review'` and `updated_at` = today |
| Total Pending Screened Applications | `applications` where `status = 'mswdo_review'` |
| Approved Applications | `applications` where `status = 'social_case_study_uploaded'` or beyond |
| Returned Applications | `applications` where `status = 'returned_to_applicant'` (from MSWDO stage) |
| Pending Voucher Creation | `applications` where `status = 'voucher_creation'` |

**Table — Applications Requiring Action Today:**
| Column | Source |
|---|---|
| Reference Code | `applications.reference_code` |
| Beneficiary Name | `beneficiary_first_name` + `beneficiary_last_name` |
| Category | `assistance_categories.category_name` |
| Date Forwarded from AICS | `reviews.created_at` (where `stage = 'aics_screening'` and `decision = 'approved'`) |
| Status | `applications.status` |

---

### 4.2 Analytics

**Date Filter:** All Time | Last Year | Last 3 Months | Last Month | This Month | Custom Date Range

**Charts:**
| Chart | Type | Source |
|---|---|---|
| Screened Applications Received Over Time | Line | `reviews` where `stage = 'aics_screening'` and `decision = 'approved'` grouped by date |
| Approved vs. Returned by MSWDO | Bar | `reviews` where `stage = 'mswdo_review'` grouped by `decision` |
| Social Case Studies Uploaded Per Month | Bar | `social_case_studies.created_at` grouped by month |
| Vouchers Created Per Month | Bar | `vouchers.created_at` grouped by month |
| Average MSWDO Review Turnaround Time | Metric | Avg diff between `applications.updated_at` at `mswdo_review` and review decision timestamp |

---

### 4.3 Applications

**Tabs:** `Screened` | `Approved` | `Returned`

---

**Screened Tab** — applications with `status = 'mswdo_review'`

*Table:*
| Column | Source |
|---|---|
| Reference Code | `applications.reference_code` |
| Beneficiary Name | `beneficiary_first_name` + `beneficiary_last_name` |
| Category | `assistance_categories.category_name` |
| Date Screened by AICS | `reviews.created_at` (where `stage = 'aics_screening'`, `decision = 'approved'`) |
| Action | Review button |

*Review Page (on Review button click):*

Left/Main Panel:
- **Application Information** — all claimant and beneficiary fields.
- **Supporting Documents** — inline document viewer per captured file.

Right Panel — Review Trail.

Decision Flow:
- **Next Button** → proceeds to Social Case Study Capture step:
  - DocumentScanner component for social case study (MSWDO scans the printed physical SCS; camera primary; fallback file input secondary).
  - Accepted types: JPG, PNG only (PDF not accepted).
  - On Submit:
    - Inserts row into `social_case_studies` (`application_id`, `conducted_by = auth()->user()->id`, file fields).
    - Sets `applications.status = 'assistance_coding'`.
    - Inserts row into `reviews` (stage: `mswdo_review`, decision: `approved`, from: `mswdo_review`, to: `assistance_coding`).
    - Writes to `audit_logs`.
    - **SMS triggered:** `application_under_review`.

- **Return Button** → opens modal:
  - Checklist of required documents to flag for resubmission.
  - Remarks textarea (saved to `applications.resubmission_remarks`).
  - On confirm:
    - Sets `applications.status = 'returned_to_applicant'`.
    - Inserts row into `reviews` (stage: `mswdo_review`, decision: `returned`, from: `mswdo_review`, to: `returned_to_applicant`, `resubmission_docs_required` = JSON).
    - Writes to `audit_logs`.
    - **SMS triggered:** `resubmission_needed`.

---

**Approved Tab** — applications with `status` beyond `mswdo_review`, approved by MSWDO

*Table:*
| Column | Source |
|---|---|
| Reference Code | `applications.reference_code` |
| Beneficiary Name | `beneficiary_first_name` + `beneficiary_last_name` |
| Category | `assistance_categories.category_name` |
| Date Approved | `reviews.created_at` (where `stage = 'mswdo_review'`, `decision = 'approved'`) |
| Action | View button |

*View Page:* Read-only.

---

**Returned Tab** — applications returned by MSWDO

*Table:*
| Column | Source |
|---|---|
| Reference Code | `applications.reference_code` |
| Beneficiary Name | `beneficiary_first_name` + `beneficiary_last_name` |
| Category | `assistance_categories.category_name` |
| Date Returned | `reviews.created_at` (where `stage = 'mswdo_review'`, `decision = 'returned'`) |
| Action | View button |

*View Page:* Read-only.

---

### 4.4 Vouchers

**Tabs:** `Pending` | `Created`

---

**Pending Tab** — applications with `status = 'voucher_creation'`

*Table:*
| Column | Source |
|---|---|
| Reference Code | `applications.reference_code` |
| Beneficiary Name | `beneficiary_first_name` + `beneficiary_last_name` |
| Category | `assistance_categories.category_name` |
| Assistance Code Type | `assistance_code_references.code_type` |
| Amount | `assistance_codes.amount` |
| Date Forwarded | `assistance_codes.created_at` |
| Action | Create button |

*Voucher Creation Page — Step 1 (on Create button click):*

Left/Main Panel:
- **Application Information** — all claimant and beneficiary fields.
- **Social Case Study** — inline viewer of `social_case_studies.file_path`.
- **Assistance Code Details:**
  | Field | Source |
  |---|---|
  | Code Type | `assistance_code_references.code_type` |
  | Amount | `assistance_codes.amount` |
  | Assigned By | `users.first_name` + `users.last_name` (via `assistance_codes.assigned_by`) |
  | Date Assigned | `assistance_codes.created_at` |

- **Next Button** → proceeds to Step 2.

Right Panel — Review Trail.

*Voucher Creation Page — Step 2 (Voucher Capture):*
- DocumentScanner component for the voucher document (MSWDO scans the physical voucher; camera primary; fallback file input secondary).
- Accepted types: JPG, PNG only (PDF not accepted).
- Adjustment remarks textarea (optional, saved to `vouchers.adjustment_remarks`).
- **Submit Button:**
  - Inserts row into `vouchers` (`application_id`, `assistance_code_id`, `prepared_by = auth()->user()->id`, file fields, `version = 1`).
  - Sets `applications.status = 'voucher_checking'`.
  - Inserts row into `reviews` (stage: `voucher_creation`, decision: `voucher_created`, from: `voucher_creation`, to: `voucher_checking`).
  - Writes to `audit_logs`.

---

**Created Tab** — applications with `status = 'voucher_checking'` or beyond where voucher exists

*Table:*
| Column | Source |
|---|---|
| Reference Code | `applications.reference_code` |
| Beneficiary Name | `beneficiary_first_name` + `beneficiary_last_name` |
| Category | `assistance_categories.category_name` |
| Voucher Version | `vouchers.version` |
| Amount | `assistance_codes.amount` |
| Date Created | `vouchers.created_at` |
| Prepared By | `users.first_name` + `users.last_name` (via `vouchers.prepared_by`) |
| Action | View button |

*View Page:* Read-only.

---

### 4.5 Account Settings

Same structure as Admin Account Settings (Section 2.6). Scoped to the logged-in MSWDO user.

---

## 5. Accountant Panel

**Access:** `accountant` role only.
**Route prefix:** `/accountant`

---

### 5.1 Dashboard

**Purpose:** Daily snapshot of voucher checking workload.

**KPI Cards (today's data):**
| KPI | Source |
|---|---|
| New Pending Vouchers Today | `applications` where `status = 'voucher_checking'` and `updated_at` = today |
| Total Pending Vouchers | `applications` where `status = 'voucher_checking'` |
| Approved Vouchers | `reviews` where `stage = 'voucher_checking'` and `decision = 'voucher_approved'` |
| Returned Vouchers | `reviews` where `stage = 'voucher_checking'` and `decision = 'voucher_returned'` |

**Table — Vouchers Requiring Action Today:**
| Column | Source |
|---|---|
| Reference Code | `applications.reference_code` |
| Beneficiary Name | `beneficiary_first_name` + `beneficiary_last_name` |
| Amount | `assistance_codes.amount` |
| Voucher Version | `vouchers.version` |
| Date Created | `vouchers.created_at` |
| Status | `applications.status` |

---

### 5.2 Analytics

**Date Filter:** All Time | Last Year | Last 3 Months | Last Month | This Month | Custom Date Range

**Charts:**
| Chart | Type | Source |
|---|---|---|
| Vouchers Received vs. Approved Over Time | Line | `vouchers.created_at` vs. `reviews` where `decision = 'voucher_approved'` |
| Returned Vouchers Per Month | Bar | `reviews` where `decision = 'voucher_returned'` grouped by month |
| Average Voucher Checking Turnaround Time | Metric | Avg diff between `vouchers.created_at` and voucher checking review |
| Total Assistance Amount Approved Per Month | Bar | `assistance_codes.amount` sum where voucher approved, grouped by month |

---

### 5.3 Vouchers

**Tabs:** `Pending` | `Approved` | `Returned`

---

**Pending Tab** — applications with `status = 'voucher_checking'`

*Table:*
| Column | Source |
|---|---|
| Reference Code | `applications.reference_code` |
| Beneficiary Name | `beneficiary_first_name` + `beneficiary_last_name` |
| Category | `assistance_categories.category_name` |
| Amount | `assistance_codes.amount` |
| Voucher Version | `vouchers.version` |
| Date Submitted to Accountant | `vouchers.created_at` |
| Action | Review button |

*Voucher Review Page (on Review button click):*

Left/Main Panel:
- **Voucher Viewer** — inline viewer of `vouchers.file_path`.
- **Application Summary** — key fields from `applications` and `assistance_codes`.
- **Adjustment Remarks** — `vouchers.adjustment_remarks` if present.

Right Panel — Review Trail.

Decision Buttons:
- **Approve:**
  - Sets `applications.status = 'with_treasurer'`.
  - Inserts row into `reviews` (stage: `voucher_checking`, decision: `voucher_approved`, from: `voucher_checking`, to: `with_treasurer`).
  - Writes to `audit_logs`.

- **Return for Re-creation:**
  - Opens modal with remarks textarea.
  - Sets `applications.status = 'voucher_returned'`.
  - Inserts row into `reviews` (stage: `voucher_checking`, decision: `voucher_returned`, from: `voucher_checking`, to: `voucher_returned`).
  - Writes to `audit_logs`.
  - MSWDO re-creates voucher; `vouchers.version` increments on next submission.

---

**Approved Tab** — applications with `status = 'with_treasurer'` or beyond

*Table:*
| Column | Source |
|---|---|
| Reference Code | `applications.reference_code` |
| Beneficiary Name | `beneficiary_first_name` + `beneficiary_last_name` |
| Amount | `assistance_codes.amount` |
| Date Approved | `reviews.created_at` (where `stage = 'voucher_checking'`, `decision = 'voucher_approved'`) |
| Action | View button |

*View Page:* Read-only.

---

**Returned Tab** — vouchers returned for re-creation

*Table:*
| Column | Source |
|---|---|
| Reference Code | `applications.reference_code` |
| Beneficiary Name | `beneficiary_first_name` + `beneficiary_last_name` |
| Amount | `assistance_codes.amount` |
| Date Returned | `reviews.created_at` (where `stage = 'voucher_checking'`, `decision = 'voucher_returned'`) |
| Action | View button |

*View Page:* Read-only.

---

### 5.4 Account Settings

Same structure as Admin Account Settings (Section 2.6). Scoped to the logged-in Accountant user.

---

## 6. Treasurer Panel

**Access:** `treasurer` role only.
**Route prefix:** `/treasurer`

---

### 6.1 Dashboard

**Purpose:** Daily snapshot of voucher acknowledgment and cheque readiness.

**KPI Cards (today's data):**
| KPI | Source |
|---|---|
| New Approved Vouchers Today | `applications` where `status = 'with_treasurer'` and `updated_at` = today |
| Total Pending Acknowledgments | `applications` where `status = 'with_treasurer'` |
| Acknowledged Today | `reviews` where `stage = 'treasurer_acknowledgment'` and `created_at` = today |
| Cheque Ready Applications | `applications` where `status = 'cheque_ready'` |
| Applications On Hold | `applications` where `status = 'on_hold'` |

**Table — Vouchers Requiring Acknowledgment Today:**
| Column | Source |
|---|---|
| Reference Code | `applications.reference_code` |
| Beneficiary Name | `beneficiary_first_name` + `beneficiary_last_name` |
| Amount | `assistance_codes.amount` |
| Date Voucher Approved | `reviews.created_at` (where `stage = 'voucher_checking'`, `decision = 'voucher_approved'`) |
| Status | `applications.status` |

---

### 6.2 Analytics

**Date Filter:** All Time | Last Year | Last 3 Months | Last Month | This Month | Custom Date Range

**Charts:**
| Chart | Type | Source |
|---|---|---|
| Vouchers Acknowledged Per Month | Bar | `reviews` where `stage = 'treasurer_acknowledgment'` grouped by month |
| Cheque Ready vs. On Hold Over Time | Line | `applications` status grouped by date |
| Total Disbursement Amount Per Month | Bar | `assistance_codes.amount` sum where `status = 'cheque_ready'` or `'claimed'` grouped by month |

---

### 6.3 Cheques (Acknowledgment)

**Tabs:** `Pending` | `Ready` | `On Hold` (read-only views; budget actions in Budget Checking)

---

**Pending Tab** — applications with `status = 'with_treasurer'`

*Table:*
| Column | Source |
|---|---|
| Reference Code | `applications.reference_code` |
| Beneficiary Name | `beneficiary_first_name` + `beneficiary_last_name` |
| Category | `assistance_categories.category_name` |
| Amount | `assistance_codes.amount` |
| Date Voucher Approved | `reviews.created_at` (where `stage = 'voucher_checking'`, `decision = 'voucher_approved'`) |
| Action | Review button |

*Cheque Review Page (on Review button click):*

Left/Main Panel:
- **Voucher Viewer** — inline viewer of `vouchers.file_path`.
- **Application Summary** — key fields from `applications` and `assistance_codes`.

Right Panel — Review Trail.

Decision Buttons:
- **Acknowledge & Forward to Budget Checking:**
  - Sets `applications.status = 'budget_checking'`.
  - Inserts row into `reviews` (stage: `treasurer_acknowledgment`, decision: `approved`, from: `with_treasurer`, to: `budget_checking`).
  - Writes to `audit_logs`.

---

**Ready Tab** — applications with `status = 'cheque_ready'`

*Table:*
| Column | Source |
|---|---|
| Reference Code | `applications.reference_code` |
| Beneficiary Name | `beneficiary_first_name` + `beneficiary_last_name` |
| Amount | `assistance_codes.amount` |
| Date Marked Ready | `reviews.created_at` (where `decision = 'cheque_ready'`) |
| Action | View button |

*View Page:* Read-only.

---

**On Hold Tab** — applications with `status = 'on_hold'`

*Table:*
| Column | Source |
|---|---|
| Reference Code | `applications.reference_code` |
| Beneficiary Name | `beneficiary_first_name` + `beneficiary_last_name` |
| Amount | `assistance_codes.amount` |
| Date Put On Hold | `reviews.created_at` (where `decision = 'on_hold'`) |
| Action | View button |

*View Page:* Read-only.

---

### 6.4 Budget Checking

**Access:** Treasurer only (after acknowledging the voucher).
**Route prefix:** `/treasurer/budget`

**Tabs:** `Pending` | `Cheque Ready` | `On Hold`

---

**Pending Tab** — applications with `status = 'budget_checking'`

*Table:*
| Column | Source |
|---|---|
| Reference Code | `applications.reference_code` |
| Beneficiary Name | `beneficiary_first_name` + `beneficiary_last_name` |
| Category | `assistance_categories.category_name` |
| Amount | `assistance_codes.amount` |
| Date Received | `reviews.created_at` (where `stage = 'treasurer_acknowledgment'`) |
| Action | Review button |

*Budget Check Page (on Review button click):*
- Application summary and voucher details (read-only).
- Inline voucher viewer.
- Right Panel — Review Trail.

Decision Buttons:
- **Mark as Cheque Ready:**
  - Sets `applications.status = 'cheque_ready'`.
  - Inserts row into `reviews` (stage: `budget_checking`, decision: `cheque_ready`, from: `budget_checking`, to: `cheque_ready`).
  - Writes to `audit_logs`.
  - **SMS triggered:** `cheque_claiming` → sent to `applications.claimant_phone`.

- **Put On Hold:**
  - Sets `applications.status = 'on_hold'`.
  - Inserts row into `reviews` (stage: `budget_checking`, decision: `on_hold`, from: `budget_checking`, to: `on_hold`).
  - Writes to `audit_logs`.

---

**Cheque Ready Tab** — applications with `status = 'cheque_ready'` (read-only; managed via Budget Checking)

*Table:*
| Column | Source |
|---|---|
| Reference Code | `applications.reference_code` |
| Beneficiary Name | `beneficiary_first_name` + `beneficiary_last_name` |
| Amount | `assistance_codes.amount` |
| Date Marked Ready | `reviews.created_at` (where `decision = 'cheque_ready'`) |
| Action | View button |

*View Page:* Read-only.

---

**On Hold Tab** — applications with `status = 'on_hold'` (read-only; managed via Budget Checking)

*Table:*
| Column | Source |
|---|---|
| Reference Code | `applications.reference_code` |
| Beneficiary Name | `beneficiary_first_name` + `beneficiary_last_name` |
| Amount | `assistance_codes.amount` |
| Date Put On Hold | `reviews.created_at` (where `decision = 'on_hold'`) |
| Action | View button |

*View Page:* Read-only.

---

### 6.5 Account Settings

Same structure as Admin Account Settings (Section 2.6). Scoped to the logged-in Treasurer user.

---

## 7. Mayor's Office Staff Panel

**Access:** `mayors_office` role only. **View only — no workflow actions of any kind.**
**Route prefix:** `/mayors-office`

---

### 7.1 Dashboard

**Purpose:** Executive-level consolidated snapshot of the entire AICS program.

**KPI Cards (consolidated, all-time unless noted):**
| KPI | Source |
|---|---|
| Total Applications Received | `applications` count |
| Applications Today | `applications` where `created_at` = today |
| Total Approved (Cheque Ready + Claimed) | `applications` where `status IN ('cheque_ready','claimed')` |
| Total Returned to Applicant | `reviews` where `decision = 'returned'` count |
| Total On Hold | `applications` where `status = 'on_hold'` |
| Total Claimed | `applications` where `status = 'claimed'` |
| Total Assistance Amount Disbursed | Sum of `assistance_codes.amount` where `status = 'claimed'` |
| Total Assistance Amount Pending | Sum of `assistance_codes.amount` where `status NOT IN ('claimed','on_hold')` |

**Table — Recent Application Activity (latest 20):**
| Column | Source |
|---|---|
| Reference Code | `applications.reference_code` |
| Beneficiary Name | `beneficiary_first_name` + `beneficiary_last_name` |
| Category | `assistance_categories.category_name` |
| Current Stage | `applications.status` (human-readable label) |
| Last Updated | `applications.updated_at` |

**Table — Applications by Category (summary):**
| Column | Source |
|---|---|
| Category | `assistance_categories.category_name` |
| Total Applications | Count per `category_id` |
| Approved | Count where `status IN ('cheque_ready','claimed')` |
| On Hold | Count where `status = 'on_hold'` |
| Claimed | Count where `status = 'claimed'` |
| Total Amount Disbursed | Sum of `assistance_codes.amount` where `status = 'claimed'` |

---

### 7.2 Analytics

**Date Filter:** All Time | Last Year | Last 3 Months | Last Month | This Month | Custom Date Range

**Charts:**
| Chart | Type | Source |
|---|---|---|
| Applications Received Over Time | Line | `applications.created_at` grouped by date |
| Applications by Assistance Category | Donut | `applications.category_id` group count |
| Approval vs. Return Rate Over Time | Bar | `reviews.decision` grouped by month |
| Total Assistance Disbursed Over Time | Bar | `assistance_codes.amount` sum grouped by month |
| Stage Bottleneck Analysis | Bar | Average time (days) per stage across all applications |
| Online vs. Walk-in Submissions | Donut | `applications.submission_type` group count |

---

### 7.3 Account Settings

Same structure as Admin Account Settings (Section 2.6). Scoped to the logged-in Mayor's Office Staff user.

---

## Appendix A — SMS Notification Trigger Map

| Trigger Event | `trigger_event` value | Stage | Sent To |
|---|---|---|---|
| Application submitted | `submission_complete` | Stage 0 | `applications.claimant_phone` |
| Application approved by AICS Staff | `application_under_review` | Stage 1 | `applications.claimant_phone` |
| Application returned by AICS Staff | `resubmission_needed` | Stage 1 | `applications.claimant_phone` |
| Application approved by MSWDO | `application_under_review` | Stage 2 | `applications.claimant_phone` |
| Application returned by MSWDO | `resubmission_needed` | Stage 2 | `applications.claimant_phone` |
| Application marked Cheque Ready | `cheque_claiming` | Stage 6 | `applications.claimant_phone` |

---

## Appendix B — Application Status to UI Label Map

| `status` value | UI Display Label |
|---|---|
| `submitted` | Submitted |
| `screening` | Under Screening |
| `returned_to_applicant` | Returned — Resubmission Needed |
| `mswdo_review` | Under MSWDO Review |
| `social_case_study_uploaded` | Social Case Study Captured |
| `assistance_coding` | Pending Assistance Coding |
| `voucher_creation` | Pending Voucher Creation |
| `voucher_checking` | Voucher Under Review |
| `voucher_returned` | Voucher Returned for Re-creation |
| `with_treasurer` | With Treasurer |
| `budget_checking` | Pending Budget Check |
| `on_hold` | On Hold |
| `cheque_ready` | Cheque Ready for Claiming |
| `claimed` | Claimed |

---

*Document prepared for AI consumption and system development reference — ALALAY System, Municipality of General Mamerto Natividad, Nueva Ecija.*

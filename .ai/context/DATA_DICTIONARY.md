# ALALAY Data Dictionary

AICS Management and Notification System — Municipality of General Mamerto Natividad, Nueva Ecija.
Stack: **MySQL / MariaDB · Laravel 12 · Inertia + Vue 3**

| | |
| --- | --- |
| Source of truth | `database/migrations/` — 30 files, final state |
| Cross-checked with | `app/Models/` (14), `database/seeders/` (8), requests, controllers, `routes/web.php` |
| Scope | The 14 domain tables. Laravel's own tables (`sessions`, `cache`, `jobs`, ...) are not covered. |
| Size | 161 columns across 14 tables |
| Generated | 2026-10-04 |

This file replaces `.ai/context/SCHEMA.md`, which describes an older design and no longer matches the migrations.

---

## How to read this

Columns appear in migration order.

| Notation | Meaning |
| --- | --- |
| Required / Optional | Whether the column can be null |
| `default: x` | Value used when none is supplied |
| **PK** | Primary key |
| **UQ** | Unique value |
| **FK → table (rule)** | Foreign key. Rule: `cascade` = delete together, `restrict` = block the delete, `set null` = blank it out |
| **encrypted** | Encrypted with AES-256 before storage, so SQL cannot read the plaintext |

**Laravel type → MySQL type**

| Laravel | MySQL | Used for |
| --- | --- | --- |
| `uuid()` / `foreignUuid()` | `char(36)` | Every primary and foreign key |
| `string(n)` | `varchar(n)` | Short text |
| `text()` / `longText()` | `text` / `longtext` | Long text, files paths, encrypted values |
| `boolean()` | `tinyint(1)` | `0` / `1` flags |
| `unsignedInteger()` | `int unsigned` | File sizes in bytes |
| `unsignedTinyInteger()` | `tinyint unsigned` | Counters, page counts |
| `decimal(12,2)` | `decimal(12,2)` | Peso amounts |
| `enum(...)` | `enum(...)` | Fixed choice, enforced by MySQL |
| `date()` / `timestamp()` / `json()` | same | Dates, times, JSON |

### Rules that apply to every table

- Keys are UUID v4 strings (`char(36)`), never auto-increment numbers.
- Timestamps are UTC.
- Only `users` has soft deletes (`deleted_at`). Everything else is deleted for real.
- `file_path` columns hold relative paths on the `public` disk — never URLs.
- `reviews` and `audit_logs` are write-once: they have `created_at` but no `updated_at`.
- Login is **Laravel Fortify** with database sessions and an email one-time code (not Breeze, not Sanctum, not an authenticator app).

---

## Table index

| # | Table | What it holds | Model |
| --- | --- | --- | --- |
| 1 | `users` | Staff accounts and roles | `User` |
| 2 | `email_otps` | One-time codes for login MFA | `EmailOtp` |
| 3 | `assistance_categories` | Lookup: kinds of AICS assistance | `AssistanceCategory` |
| 4 | `required_documents` | Document checklist per category | `RequiredDocument` |
| 5 | `applications` | The core record: one AICS application | `Application` |
| 6 | `application_documents` | Uploaded / scanned document images | `ApplicationDocument` |
| 7 | `reviews` | Write-once trail of workflow decisions | `Review` |
| 8 | `social_case_studies` | Case study PDF, one per application | `SocialCaseStudy` |
| 9 | `assistance_code_references` | Lookup: code catalogue and default amounts | `AssistanceCodeReference` |
| 10 | `assistance_codes` | The code assigned to an application | `AssistanceCode` |
| 11 | `vouchers` | Voucher file plus version history | `Voucher` |
| 12 | `audit_logs` | Write-once log of user actions | `AuditLog` |
| 13 | `sms_notifications` | Outbound SMS log with delivery status | `SmsNotification` |
| 14 | `system_settings` | Admin-managed key/value settings | `SystemSetting` |

Only `users` has `deleted_at`. `reviews` and `audit_logs` have `created_at` only.

---

## How the tables relate

```text
users ──1:n── applications        (encoded_by, reviewed_by)
users ──1:n── reviews             (reviewed_by)
users ──1:n── social_case_studies (conducted_by)
users ──1:n── assistance_codes    (assigned_by)
users ──1:n── vouchers            (prepared_by)
users ──1:n── audit_logs          (user_id, set null)
users ──1:n── system_settings     (updated_by, set null)
users ──1:n── email_otps          (user_id, cascade)

assistance_categories ──1:n── required_documents  (cascade)
assistance_categories ──1:n── applications        (restrict)

applications ──1:n── application_documents  (cascade)
applications ──1:n── reviews                (cascade)
applications ──1:1── social_case_studies    (cascade, unique)
applications ──1:1── assistance_codes       (cascade, unique)
applications ──1:n── vouchers               (cascade)
applications ──1:n── sms_notifications      (cascade)

assistance_code_references ──1:n── assistance_codes (restrict)
assistance_codes            ──1:n── vouchers        (restrict)

audit_logs.entity_type + entity_id → soft pointer to a record (no FK)
```

**In plain terms:** an application has documents, reviews, vouchers and SMS messages. It has exactly one case study and one assistance code. A category has many required documents and many applications.

**Vouchers are versioned:** a returned voucher is re-created with a higher `version`. The current one is the newest (`Application::voucher()` uses `latestOfMany`).

**What a delete does:** deleting an application wipes its documents, reviews, case study, assistance code, vouchers and SMS log. A required document cannot be deleted while documents reference it. Deleting a user blanks out `encoded_by`, `reviewed_by` and `audit_logs.user_id`, but is blocked where the actor column is `restrict`.

---

## Fixed choices (MySQL enums)

These values are enforced by the database. There are no PHP enums in this project — every workflow value is a plain string.

### `users.role` — 7 values

Controls which panel and routes the account can reach (`role` middleware).

| Value | Panel | Can do |
| --- | --- | --- |
| `admin` | `/admin` | Users, audit logs, settings, categories, documents, code references, SMS templates, maintenance |
| `aics_staff` | `/aics` | Screen/return applications, assign assistance codes |
| `mswdo` | `/mswdo` | Upload case study, prepare vouchers, approve/return applications |
| `accountant` | `/accountant` | Approve vouchers |
| `treasurer` | `/treasurer` | Acknowledge and release cheques |
| `internal_audit` | `/internal-audit` | Approve or return coding |
| `budget_officer` | `/budget-office` | Approve, hold, release vouchers |

`mayors_office` existed earlier and was retired — those accounts became `admin` + `inactive`.

### `users.status`

`active` (default) can log in, `inactive` cannot. Toggled from Admin → Users. This is separate from soft deletion.

### `applications.status` — 14 values

The most important column in the system: it drives every dashboard count, queue filter and workflow step. Default `submitted`.

| Status | Meaning | Set by |
| --- | --- | --- |
| `submitted` | Received (portal or walk-in) | Public form |
| `returned_to_applicant` | Sent back for more documents | AICS / MSWDO return |
| `mswdo_review` | With MSWDO for the case study | AICS approve |
| `social_case_study_uploaded` | Case study captured | Seed data only — never set by app code |
| `assistance_coding` | With AICS for coding | MSWDO approve |
| `internal_audit_review` | With Internal Audit | AICS approve (coding step) |
| `returned_assistance_coding` | Coding sent back | Internal Audit return |
| `voucher_creation` | Ready for voucher preparation | Internal Audit approve |
| `budget_checking` | With the Budget Office | Voucher saved / hold released |
| `voucher_on_hold` | Held by the Budget Office | Budget hold |
| `voucher_recording` | Recorded by Accounting | MSWDO / Accountant approve |
| `with_treasurer` | With the Treasurer | Accountant approve |
| `cheque_ready` | Cheque ready to claim | Treasurer |
| `claimed` | Cheque picked up | Treasurer claim |

Old values were renamed in Aug 2026: `screening` → `submitted`, `on_hold` → `voucher_on_hold`, `voucher_checking` → `voucher_recording`, `voucher_returned` → `budget_checking`.

### Other enums

| Column | Values | Notes |
| --- | --- | --- |
| `applications.submission_type` | `online`, `walk_in` | default `online`. App code only ever writes `online`; `walk_in` appears in demo data |
| `applications.claimant_sex`, `applications.beneficiary_sex` | `male`, `female` | Stored lowercase, no default |
| `sms_notifications.status` | `pending`, `sent`, `failed` | default `pending`, then updated by the queued job |
| `reviews.stage` | 9 values | Same names as the workflow statuses above, plus `treasurer_acknowledgment` (legacy, never written) |
| `reviews.decision` | `approved`, `returned`, `coded`, `voucher_created`, `hold` + 5 legacy values | The legacy ones (`voucher_approved`, `voucher_returned`, `cheque_ready`, `on_hold`, `claimed`) exist only for old rows |
| `reviews.from_status`, `reviews.to_status` | the 14 `applications.status` values | Status before and after the action |

---

## Plain-text values (not enums)

| Column | Accepted values |
| --- | --- |
| `applications.reference_code` | `GMN-YYYY-XXXXXX` (14 chars): fixed `GMN`, year, 6 random uppercase letters/digits. Unique, generated by `ReferenceCodeService`, used in tracking links and SMS |
| `required_documents.capture_type` | `single` (1 page), `double` (front + back), `multi` (unlimited). Default `single` |
| `required_documents.scanner_size` | `a4` (default), `card`, `half_sheet`. Seeded but never read by app code |
| `applications.claimant_relationship_to_beneficiary` | Free text. Demo data: `Self`, `Parent`, `Spouse`, `Sibling`, `Child`, `Guardian` |
| `applications.beneficiary_barangay` | Free text, nullable. When null, derived from the decrypted address. Demo data uses the 20 barangays of General Mamerto Natividad |
| `applications.claimant_phone` | 11 digits starting `09`, stored encrypted. The SMS log stores it as `63XXXXXXXXXX` in clear text |
| `email_otps.otp_code` | 6-digit numeric string |
| `reviews.resubmission_docs_required` | JSON array of `required_documents.id` values |
| `audit_logs.entity_id` | UUID of the affected record (no FK) |
| file name columns | The original uploaded filename |

### `assistance_code_references.code_type`

Six catalogue rows, each with a default peso amount. `assistance_codes.amount` is a separate value and may differ.

| Code | Default amount |
| --- | --- |
| `A` | ₱500.00 |
| `B` | ₱1,000.00 |
| `C` | ₱1,500.00 |
| `D` | ₱2,000.00 |
| `E` | ₱2,500.00 |
| `F` | ₱3,000.00 |

### `assistance_categories.category_name`

`Medical Assistance`, `Hospital Assistance`, `Burial Assistance`.

### `system_settings` — 13 keys

| Group | Keys |
| --- | --- |
| `branding` | `system_name`, `system_tagline`, `municipality_name`, `primary_color` |
| `uploads` | `file_max_size_mb`, `allowed_file_types` |
| `sms` | `sms_enabled`, `sms_sender_name` |
| `sms_templates` | `sms_template_submission_complete`, `sms_template_under_review`, `sms_template_resubmission_needed`, `sms_template_cheque_ready`, `sms_template_cheque_claiming` |

SMS credentials are **not** stored here — they come from `.env` (`PHILSMS_*`).

### `sms_notifications.trigger_event`

Six events, all written by `SmsService::send()`:

| Event | Fired when |
| --- | --- |
| `submission_complete` | Application submitted |
| `application_under_review` | Application approved into review |
| `resubmission_needed` | Application returned |
| `cheque_ready` | Cheque ready to claim |
| `cheque_claiming` | Claiming reminder sent |
| `track_otp` | Tracking code requested (inline message, no template) |

Placeholders: `{reference_code}`, `{track_url}`, `{remarks}`, `{claiming_date}`, `{claimant_name}`. Unknown events fall back to the submission template.

### `audit_logs.module`, `action`, `entity_type`

`module` and `action` come from the first two segments of the route name (route `admin.users.update` → `module=admin`, `action=users`), plus a few hard-coded values such as `login`, `logout`, `maintenance_on`.

`entity_type` is one of `User`, `Application`, `RequiredDocument`, `AssistanceCodeReference`.

---

## Table dictionaries

### `users`

Staff accounts. The only table with soft deletes.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `char(36)` | **PK**, UUID |
| `first_name`, `last_name` | `varchar(100)` | Required |
| `middle_name` | `varchar(100)` | Optional |
| `name_extension` | `varchar(10)` | Optional — Jr., Sr., III |
| `email` | `varchar(255)` | Required, **UQ** — login identifier |
| `email_verified_at` | `timestamp` | Optional |
| `password` | `varchar(255)` | Required — bcrypt hash, hidden from output |
| `role` | `enum` (7) | Required — see `users.role` |
| `status` | `enum` | `active` (default) / `inactive` |
| `is_online` | `tinyint(1)` | default `0` — presence flag for the UI |
| `profile_picture_name` | `varchar(255)` | Optional — original filename |
| `profile_picture_path` | `text` | Optional — path on the `public` disk |
| `profile_picture_size` | `int unsigned` | Optional — bytes |
| `profile_picture_mime_type` | `varchar(100)` | Optional |
| `acceptable_use_policy_accepted_at` | `timestamp` | Optional — when the AUP was accepted |
| `remember_token` | `varchar(100)` | Optional, hidden |
| `deleted_at` | `timestamp` | Optional — soft delete |
| `created_at`, `updated_at` | `timestamp` | |

**Model:** `User`. Accessor `full_name` (first + middle + last). Scopes `active()`, `byRole()`. Relations to applications, reviews, vouchers, audit logs, settings and OTPs.

### `email_otps`

One-time codes for login MFA. Invalid after `expires_at`, once `used_at` is set, or after 5 attempts.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `char(36)` | **PK**, UUID |
| `user_id` | `char(36)` | Required, **FK → users** (cascade) |
| `otp_code` | `varchar(255)` | Required — 6 digits |
| `expires_at` | `timestamp` | Required |
| `used_at` | `timestamp` | Optional — set once verified |
| `attempts` | `tinyint unsigned` | default `0` |
| `created_at`, `updated_at` | `timestamp` | |

**Model:** `EmailOtp`. Helper `isValid()`.

### `assistance_categories`

Lookup of the assistance types offered. Drives the public category picker and the document checklist.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `char(36)` | **PK**, UUID |
| `category_name` | `varchar(150)` | Required, **UQ** |
| `category_description` | `text` | Optional — shown to the public |
| `is_active` | `tinyint(1)` | default `1` — soft disable |
| `created_at`, `updated_at` | `timestamp` | |

**Model:** `AssistanceCategory`. Scope `active()`.

### `required_documents`

The document checklist for each category.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `char(36)` | **PK**, UUID |
| `category_id` | `char(36)` | Required, **FK → assistance_categories** (cascade) |
| `doc_name` | `varchar(200)` | Required — unique per category |
| `doc_description` | `text` | Optional — guidance for the applicant |
| `is_mandatory` | `tinyint(1)` | default `1` — blocks submission when missing |
| `is_active` | `tinyint(1)` | default `1` |
| `capture_type` | `varchar(10)` | default `single` |
| `scanner_size` | `varchar(12)` | default `a4` |
| `created_at`, `updated_at` | `timestamp` | |

**Model:** `RequiredDocument`. Scopes `active()`, `mandatory()`.

### `applications`

The core table. One row = one AICS application. Holds both the **claimant** (who applies) and the **beneficiary** (who receives).

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `char(36)` | **PK**, UUID |
| `category_id` | `char(36)` | Required, **FK → assistance_categories** (restrict) |
| `reference_code` | `varchar(20)` | Required, **UQ** — `GMN-YYYY-XXXXXX` |
| `status` | `enum` (14) | Required, default `submitted` — the workflow state |
| `submission_type` | `enum` | default `online` |
| `encoded_by` | `char(36)` | Optional, **FK → users** (set null) — staff encoder |
| `claimant_last_name`, `claimant_first_name` | `varchar(100)` | Required |
| `claimant_middle_name` | `varchar(100)` | Optional |
| `claimant_name_extension` | `varchar(10)` | Optional |
| `claimant_sex` | `enum` | Required — `male` / `female` |
| `claimant_dob` | `date` | Required |
| `claimant_address` | `text` | Required, **encrypted** |
| `claimant_phone` | `text` | Required, **encrypted** — 11 digits starting `09` |
| `claimant_email` | `text` | Optional, **encrypted** |
| `claimant_relationship_to_beneficiary` | `varchar(100)` | Required |
| `beneficiary_last_name`, `beneficiary_first_name` | `varchar(100)` | Required |
| `beneficiary_middle_name` | `varchar(100)` | Optional |
| `beneficiary_name_extension` | `varchar(10)` | Optional |
| `beneficiary_sex` | `enum` | Required |
| `beneficiary_dob` | `date` | Required |
| `beneficiary_address` | `text` | Required, **encrypted** |
| `beneficiary_barangay` | `varchar(100)` | Optional — derived from the address when null |
| `resubmission_remarks` | `text` | Optional — shown to the applicant |
| `reviewed_by` | `char(36)` | Optional, **FK → users** (set null) |
| `reviewed_at` | `timestamp` | Optional — last review action |
| `claimed_at` | `timestamp` | Optional — when the cheque was claimed |
| `claiming_date` | `date` | Optional — scheduled claiming day |
| `created_at`, `updated_at` | `timestamp` | |

**Model:** `Application`. Relations: `category`, `encoder`, `reviewer`, `documents`, `reviews`, `vouchers`, `smsNotifications`, `socialCaseStudy`, `assistanceCode`, `voucher` (current version). Scopes `byStatus()`, `today()`, `online()`, `walkIn()`.

### `application_documents`

Scanned or uploaded supporting documents, including every resubmission round.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `char(36)` | **PK**, UUID |
| `application_id` | `char(36)` | Required, **FK → applications** (cascade) |
| `required_doc_id` | `char(36)` | Required, **FK → required_documents** (restrict) — which checklist item this satisfies |
| `file_name` | `varchar(255)` | Required — original filename |
| `file_path` | `text` | Required — relative storage path |
| `file_size` | `int unsigned` | Required — bytes |
| `mime_type` | `varchar(100)` | Required |
| `is_resubmission` | `tinyint(1)` | default `0` — `1` when re-uploaded after a return |
| `resubmission_number` | `tinyint unsigned` | default `0` — round number, `0` = original |
| `created_at`, `updated_at` | `timestamp` | |

**Model:** `ApplicationDocument`. Scopes `resubmissions()`, `originals()`.

### `reviews`

Write-once trail of every workflow decision. No `updated_at`, no soft deletes.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `char(36)` | **PK**, UUID |
| `application_id` | `char(36)` | Required, **FK → applications** (cascade) |
| `reviewed_by` | `char(36)` | Required, **FK → users** (restrict) |
| `stage` | `enum` (9) | Required — which workflow step |
| `decision` | `enum` (10) | Required — what was decided |
| `from_status` | `enum` (14) | Required — status before the action |
| `to_status` | `enum` (14) | Required — status after the action |
| `remarks` | `text` | Optional |
| `resubmission_docs_required` | `json` | Optional — document IDs to resubmit |
| `created_at` | `timestamp` | Required — no `updated_at` column |

**Model:** `Review`. Scopes `byStage()`, `latest()`.

### `social_case_studies`

The case study document captured by MSWDO. Exactly one per application.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `char(36)` | **PK**, UUID |
| `application_id` | `char(36)` | Required, **UQ**, **FK → applications** (cascade) |
| `conducted_by` | `char(36)` | Required, **FK → users** (restrict) |
| `file_name` | `varchar(255)` | Required |
| `file_path` | `text` | Required |
| `file_size` | `int unsigned` | Required — bytes |
| `mime_type` | `varchar(100)` | Required |
| `page_count` | `tinyint unsigned` | default `1` |
| `conducted_at` | `timestamp` | Required |
| `created_at`, `updated_at` | `timestamp` | |

**Model:** `SocialCaseStudy`. Accessor `file_size_label`.

### `assistance_code_references`

Master catalogue of assistance codes and their standard amounts.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `char(36)` | **PK**, UUID |
| `code_type` | `varchar(100)` | Required, **UQ** — `A` to `F` |
| `default_amount` | `decimal(12,2)` | Required — standard peso amount |
| `description` | `text` | Optional |
| `is_active` | `tinyint(1)` | default `1` |
| `created_at`, `updated_at` | `timestamp` | |

**Model:** `AssistanceCodeReference`. Scope `active()`.

### `assistance_codes`

The code assigned to an application during coding. One row per application.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `char(36)` | **PK**, UUID |
| `application_id` | `char(36)` | Required, **UQ**, **FK → applications** (cascade) |
| `assistance_code_reference_id` | `char(36)` | Required, **FK → assistance_code_references** (restrict) |
| `amount` | `decimal(12,2)` | Required — actual amount, may differ from the default |
| `assigned_by` | `char(36)` | Required, **FK → users** (restrict) |
| `created_at`, `updated_at` | `timestamp` | |

**Model:** `AssistanceCode`.

### `vouchers`

The voucher file for an application, with version history. Stores file details only — amounts live in `assistance_codes`.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `char(36)` | **PK**, UUID |
| `application_id` | `char(36)` | Required, **FK → applications** (cascade) |
| `assistance_code_id` | `char(36)` | Required, **FK → assistance_codes** (restrict) |
| `prepared_by` | `char(36)` | Required, **FK → users** (restrict) |
| `file_name` | `varchar(255)` | Required |
| `file_path` | `text` | Required |
| `file_size` | `int unsigned` | Required — bytes |
| `mime_type` | `varchar(100)` | Required |
| `version` | `tinyint unsigned` | default `1` — goes up each time it is re-created |
| `page_count` | `tinyint unsigned` | default `1` |
| `prepared_at` | `timestamp` | Required |
| `created_at`, `updated_at` | `timestamp` | |

**Model:** `Voucher`. Accessor `file_size_label`.

### `audit_logs`

Write-once log of user actions across the system.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `char(36)` | **PK**, UUID |
| `user_id` | `char(36)` | Optional, **FK → users** (set null) — null for public events |
| `role` | `varchar(50)` | Optional — role at the time of the action |
| `module` | `varchar(100)` | Required — first route-name segment |
| `action` | `varchar(100)` | Required — second route-name segment or a fixed verb |
| `description` | `text` | Optional — phone numbers are redacted before saving |
| `entity_type` | `varchar(100)` | Optional — model name of the record affected |
| `entity_id` | `char(36)` | Optional — its UUID, no FK |
| `ip_address` | `varchar(45)` | Optional |
| `user_agent` | `text` | Optional |
| `created_at` | `timestamp` | Required — no `updated_at` column |

**Model:** `AuditLog`. Scopes `byModule()`, `byAction()`, `today()`, `latest()`. Written by `AuditLogMiddleware` plus a handful of explicit calls.

### `sms_notifications`

Every outbound SMS, for traceability and retries. Created as `pending`, then updated by the queued job.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `char(36)` | **PK**, UUID |
| `application_id` | `char(36)` | Required, **FK → applications** (cascade) |
| `recipient_phone` | `varchar(20)` | Required — stored as `63XXXXXXXXXX` |
| `trigger_event` | `varchar(100)` | Required |
| `message_body` | `text` | Required — final text after placeholders are filled |
| `status` | `enum` | default `pending` |
| `provider_response` | `json` | Optional — raw reply from the provider |
| `created_at`, `updated_at` | `timestamp` | |

**Model:** `SmsNotification`. Scopes `pending()`, `sent()`, `failed()`, `byEvent()`.

### `system_settings`

Admin-managed key/value settings.

| Column | Type | Notes |
| --- | --- | --- |
| `id` | `char(36)` | **PK**, UUID |
| `setting_key` | `varchar(100)` | Required, **UQ** |
| `setting_value` | `text` | Optional — raw string, no model cast |
| `setting_group` | `varchar(100)` | Optional — `branding`, `uploads`, `sms`, `sms_templates` |
| `updated_by` | `char(36)` | Optional, **FK → users** (set null) |
| `created_at`, `updated_at` | `timestamp` | |

**Model:** `SystemSetting`. Scopes `byGroup()`, `byKey()`.

---

## Known issues

Found while reading the code. Nothing here has been fixed yet.

1. **High** — `email_otps.otp_code` is not in `$hidden`, so serializing an OTP record exposes the MFA code.
2. **High** — Sex is validated as `Male`/`Female` but stored as `male`/`female`. MySQL's case-insensitive collation lets it through, so the rule is not really enforced.
3. **Medium** — `capture_type` and `scanner_size` are never validated in admin CRUD, so any value can reach the database.
4. **Medium** — Audit `action` stores the second route segment but the audit page labels come from the third, so most labels show the section name instead of the verb. `entity_type` is also null for category and voucher edits.
5. **Low** — `sms_notifications.recipient_phone` is stored in clear text even though `applications.claimant_phone` is encrypted.
6. **Low** — `system_settings.setting_value` has no cast, so booleans such as `sms_enabled` are compared as strings.
7. **Low** — Some values exist only in old or demo data and are never written by the app: status `social_case_study_uploaded`, `submission_type = walk_in`, `reviews.stage = treasurer_acknowledgment`, and five legacy `reviews.decision` values.

---

*Generated from `database/migrations/`, `app/Models/`, `database/seeders/` and application code — ALALAY System, Municipality of General Mamerto Natividad, Nueva Ecija.*

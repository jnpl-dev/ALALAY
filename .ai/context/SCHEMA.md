# ALALAY: Data Schema Dictionary
**MySQL/MariaDB — Laravel — XAMPP**
**Municipality of General Mamerto Natividad, Nueva Ecija**

---

## Schema Design Notes

- Follows **Laravel naming conventions**: snake_case, plural table names, `id` as primary key, `created_at` / `updated_at` timestamps.
- **UUIDs** are used as primary keys via Laravel's `HasUuids` trait for security and distributed-safe IDs.
- **MySQL ENUMs** are used for constrained string fields to enforce data integrity at the database level.
- **Foreign keys** use the `_id` suffix and are explicitly indexed for query performance.
- `file_path` fields reference Laravel storage paths (`storage/app/public`).
- **Soft deletes** (`deleted_at`) are applied only on `users`; other tables use `is_active` flags or are append-only.
- All timestamps are stored in UTC. Set `APP_TIMEZONE=UTC` in `.env`.
- Authentication is handled by **Laravel Fortify** with session-based auth.
- MFA uses **Email OTP** — a 6-digit code sent via email during login — instead of TOTP.
- Session driver is set to `database` (`SESSION_DRIVER=database` in `.env`).
- Running on **XAMPP** (Apache + MySQL/MariaDB) for local development.

---

## ENUM Reference

```sql
-- User roles
ENUM('admin','aics_staff','mswdo','accountant','treasurer','mayors_office')

-- Account status
ENUM('active','inactive')

-- Sex
ENUM('male','female')

-- Submission type
ENUM('online','walk_in')

-- Application status
ENUM(
  'submitted',
  'screening',
  'returned_to_applicant',
  'mswdo_review',
  'social_case_study_uploaded',
  'assistance_coding',
  'voucher_creation',
  'voucher_checking',
  'voucher_returned',
  'with_treasurer',
  'budget_checking',
  'on_hold',
  'cheque_ready',
  'claimed'
)

-- Review stage
ENUM(
  'aics_screening',
  'mswdo_review',
  'assistance_coding',
  'voucher_creation',
  'voucher_checking',
  'treasurer_acknowledgment',
  'budget_checking'
)

-- Review decision
ENUM(
  'approved',
  'returned',
  'coded',
  'voucher_created',
  'voucher_approved',
  'voucher_returned',
  'cheque_ready',
  'on_hold',
  'claimed'
)

-- SMS notification status
ENUM('pending','sent','failed')
```

---

## Tables

---

### `users`
Stores all internal system users. This is the standard Laravel auth table extended with system-specific fields.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | `char(36)` | PK | UUID via `HasUuids` trait |
| `first_name` | `varchar(100)` | NOT NULL | |
| `last_name` | `varchar(100)` | NOT NULL | |
| `middle_name` | `varchar(100)` | NULLABLE | |
| `name_extension` | `varchar(10)` | NULLABLE | Jr., Sr., III, etc. |
| `email` | `varchar(255)` | UNIQUE, NOT NULL | Used for login |
| `email_verified_at` | `timestamp` | NULLABLE | Laravel default |
| `password` | `varchar(255)` | NOT NULL | Hashed via bcrypt |
| `role` | `ENUM(user_roles)` | NOT NULL | |
| `status` | `ENUM('active','inactive')` | NOT NULL, default `'active'` | |
| `is_online` | `tinyint(1)` | NOT NULL, default `0` | |
| `profile_picture_name` | `varchar(255)` | NULLABLE | Original filename |
| `profile_picture_path` | `text` | NULLABLE | Laravel storage path |
| `profile_picture_size` | `int unsigned` | NULLABLE | Bytes |
| `profile_picture_mime_type` | `varchar(100)` | NULLABLE | |
| `remember_token` | `varchar(100)` | NULLABLE | Laravel default |
| `deleted_at` | `timestamp` | NULLABLE | Soft delete |
| `created_at` | `timestamp` | NULLABLE | |
| `updated_at` | `timestamp` | NULLABLE | |

**Indexes:** `email`, `role`, `status`

**Laravel notes:**
- Use `HasUuids`, `SoftDeletes` traits on the `User` model.
- Set `$keyType = 'string'` and `$incrementing = false`.
- Extend the default `create_users_table` migration.
- MFA uses Email OTP (6-digit code sent via email) — no TOTP columns needed.

---

### `email_otps`
Stores one-time passcodes sent to users for MFA during login.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | `char(36)` | PK | UUID via `HasUuids` trait |
| `user_id` | `char(36)` | FK → `users.id`, NOT NULL | |
| `otp_code` | `varchar(255)` | NOT NULL | Hashed via bcrypt |
| `expires_at` | `timestamp` | NOT NULL | 5-minute expiry |
| `used_at` | `timestamp` | NULLABLE | Null until verified |
| `attempts` | `tinyint unsigned` | NOT NULL, default `0` | Max 5 before invalid |
| `created_at` | `timestamp` | NULLABLE | |
| `updated_at` | `timestamp` | NULLABLE | |

**Indexes:** `user_id`, `user_id` + `expires_at`

**Laravel notes:**
- Model uses `HasUuids`, `$keyType = 'string'`, `$incrementing = false`.
- `otp_code` is stored hashed — never plaintext.
- Use `scopePending()` to find valid (unused, not expired) OTPs.

---

### `sessions`
Laravel's default database session table. Extended with user metadata for audit and security tracking.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | `varchar(255)` | PK | Laravel session ID |
| `user_id` | `char(36)` | NULLABLE, FK → `users.id` | |
| `ip_address` | `varchar(45)` | NULLABLE | Supports IPv6 |
| `user_agent` | `text` | NULLABLE | Browser/device info |
| `payload` | `longtext` | NOT NULL | Encrypted session data |
| `last_activity` | `int` | NOT NULL | Unix timestamp |

**Indexes:** `user_id`, `last_activity`

**Laravel notes:**
- Run `php artisan session:table` to generate the base migration, then extend it.
- Set `SESSION_DRIVER=database` in `.env`.

---

### `assistance_categories`
Defines the types of assistance available under the AICS program.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | `char(36)` | PK | UUID |
| `category_name` | `varchar(150)` | UNIQUE, NOT NULL | |
| `category_description` | `text` | NULLABLE | |
| `is_active` | `tinyint(1)` | NOT NULL, default `1` | Soft disable |
| `created_at` | `timestamp` | NULLABLE | |
| `updated_at` | `timestamp` | NULLABLE | |

**Indexes:** `is_active`

---

### `required_documents`
Defines the documents required per assistance category.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | `char(36)` | PK | UUID |
| `category_id` | `char(36)` | FK → `assistance_categories.id`, NOT NULL | |
| `doc_name` | `varchar(200)` | NOT NULL | |
| `doc_description` | `text` | NULLABLE | |
| `is_mandatory` | `tinyint(1)` | NOT NULL, default `1` | |
| `is_active` | `tinyint(1)` | NOT NULL, default `1` | Soft disable |
| `created_at` | `timestamp` | NULLABLE | |
| `updated_at` | `timestamp` | NULLABLE | |

**Indexes:** `category_id`, `is_active`

---

### `applications`
Core table. One row represents one AICS application.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | `char(36)` | PK | UUID |
| `category_id` | `char(36)` | FK → `assistance_categories.id`, NOT NULL | |
| `reference_code` | `varchar(20)` | UNIQUE, NOT NULL | System-generated, human-readable |
| `status` | `ENUM(application_status)` | NOT NULL, default `'submitted'` | |
| `submission_type` | `ENUM('online','walk_in')` | NOT NULL, default `'online'` | |
| `encoded_by` | `char(36)` | FK → `users.id`, NULLABLE | AICS Staff for walk-in encoding |
| `claimant_last_name` | `varchar(100)` | NOT NULL | |
| `claimant_first_name` | `varchar(100)` | NOT NULL | |
| `claimant_middle_name` | `varchar(100)` | NULLABLE | |
| `claimant_name_extension` | `varchar(10)` | NULLABLE | |
| `claimant_sex` | `ENUM('male','female')` | NOT NULL | |
| `claimant_dob` | `date` | NOT NULL | |
| `claimant_address` | `text` | NOT NULL | |
| `claimant_phone` | `varchar(20)` | NOT NULL | |
| `claimant_email` | `varchar(255)` | NULLABLE | |
| `claimant_relationship_to_beneficiary` | `varchar(100)` | NOT NULL | |
| `beneficiary_last_name` | `varchar(100)` | NOT NULL | |
| `beneficiary_first_name` | `varchar(100)` | NOT NULL | |
| `beneficiary_middle_name` | `varchar(100)` | NULLABLE | |
| `beneficiary_name_extension` | `varchar(10)` | NULLABLE | |
| `beneficiary_sex` | `ENUM('male','female')` | NOT NULL | |
| `beneficiary_dob` | `date` | NOT NULL | |
| `beneficiary_address` | `text` | NOT NULL | |
| `resubmission_remarks` | `text` | NULLABLE | Visible to applicant when returned |
| `reviewed_at` | `timestamp` | NULLABLE | Timestamp of last review action |
| `reviewed_by` | `char(36)` | FK → `users.id`, NULLABLE | Last reviewer |
| `claimed_at` | `timestamp` | NULLABLE | When cheque was physically claimed |
| `created_at` | `timestamp` | NULLABLE | Submission timestamp |
| `updated_at` | `timestamp` | NULLABLE | |

**Indexes:** `category_id`, `status`, `reference_code`, `submission_type`, `encoded_by`, `reviewed_by`, `created_at`

---

### `application_documents`
Stores captured supporting document images per application, including resubmissions.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | `char(36)` | PK | UUID |
| `application_id` | `char(36)` | FK → `applications.id`, NOT NULL | |
| `required_doc_id` | `char(36)` | FK → `required_documents.id`, NOT NULL | |
| `file_name` | `varchar(255)` | NOT NULL | Original filename |
| `file_path` | `text` | NOT NULL | Laravel storage path |
| `file_size` | `int unsigned` | NOT NULL | Bytes |
| `mime_type` | `varchar(100)` | NOT NULL | |
| `is_resubmission` | `tinyint(1)` | NOT NULL, default `0` | Flags resubmitted documents |
| `resubmission_number` | `tinyint unsigned` | NOT NULL, default `0` | Which round of resubmission |
| `created_at` | `timestamp` | NULLABLE | Upload timestamp |
| `updated_at` | `timestamp` | NULLABLE | |

**Indexes:** `application_id`, `required_doc_id`, `is_resubmission`

---

### `reviews`
Immutable audit trail of every review action taken on an application across all stages.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | `char(36)` | PK | UUID |
| `application_id` | `char(36)` | FK → `applications.id`, NOT NULL | |
| `reviewed_by` | `char(36)` | FK → `users.id`, NOT NULL | |
| `stage` | `ENUM(review_stage)` | NOT NULL | |
| `decision` | `ENUM(review_decision)` | NOT NULL | |
| `from_status` | `ENUM(application_status)` | NOT NULL | Status before action |
| `to_status` | `ENUM(application_status)` | NOT NULL | Status after action |
| `remarks` | `text` | NULLABLE | General review remarks |
| `resubmission_docs_required` | `json` | NULLABLE | Array of `required_documents.id` values flagged for resubmission |
| `created_at` | `timestamp` | NULLABLE | When the review was made |
| `updated_at` | `timestamp` | NULLABLE | |

**Indexes:** `application_id`, `reviewed_by`, `stage`, `created_at`

**Laravel note:** This table is append-only. Never update or delete rows. Do not apply `SoftDeletes`.

---

### `social_case_studies`
Stores the social case study document captured by MSWDO via DocumentScanner. One per application.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | `char(36)` | PK | UUID |
| `application_id` | `char(36)` | FK → `applications.id`, UNIQUE, NOT NULL | One per application |
| `conducted_by` | `char(36)` | FK → `users.id`, NOT NULL | MSWDO user |
| `file_name` | `varchar(255)` | NOT NULL | |
| `file_path` | `text` | NOT NULL | Laravel storage path |
| `file_size` | `int unsigned` | NOT NULL | Bytes |
| `mime_type` | `varchar(100)` | NOT NULL | |
| `created_at` | `timestamp` | NULLABLE | Conduct/capture timestamp |
| `updated_at` | `timestamp` | NULLABLE | |

**Indexes:** `application_id`, `conducted_by`

---

### `assistance_code_references`
Master lookup table for standard assistance code types and their default amounts.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | `char(36)` | PK | UUID |
| `code_type` | `varchar(100)` | UNIQUE, NOT NULL | |
| `default_amount` | `decimal(12,2)` | NOT NULL | |
| `description` | `text` | NULLABLE | |
| `is_active` | `tinyint(1)` | NOT NULL, default `1` | Soft disable |
| `created_at` | `timestamp` | NULLABLE | |
| `updated_at` | `timestamp` | NULLABLE | |

**Indexes:** `is_active`

---

### `assistance_codes`
Records the assistance code assigned to a specific application by AICS Staff. One per application.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | `char(36)` | PK | UUID |
| `application_id` | `char(36)` | FK → `applications.id`, UNIQUE, NOT NULL | |
| `assistance_code_reference_id` | `char(36)` | FK → `assistance_code_references.id`, NOT NULL | |
| `amount` | `decimal(12,2)` | NOT NULL | Actual assigned amount; may differ from reference default |
| `assigned_by` | `char(36)` | FK → `users.id`, NOT NULL | AICS Staff user |
| `created_at` | `timestamp` | NULLABLE | |
| `updated_at` | `timestamp` | NULLABLE | |

**Indexes:** `application_id`, `assistance_code_reference_id`, `assigned_by`

---

### `vouchers`
Stores the voucher document created by MSWDO after assistance coding.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | `char(36)` | PK | UUID |
| `application_id` | `char(36)` | FK → `applications.id`, NOT NULL | |
| `assistance_code_id` | `char(36)` | FK → `assistance_codes.id`, NOT NULL | |
| `prepared_by` | `char(36)` | FK → `users.id`, NOT NULL | MSWDO user |
| `file_name` | `varchar(255)` | NOT NULL | |
| `file_path` | `text` | NOT NULL | Laravel storage path |
| `file_size` | `int unsigned` | NOT NULL | Bytes |
| `mime_type` | `varchar(100)` | NOT NULL | |
| `version` | `tinyint unsigned` | NOT NULL, default `1` | Increments on each re-creation after return |
| `adjustment_remarks` | `text` | NULLABLE | Notes on adjustments made |
| `created_at` | `timestamp` | NULLABLE | |
| `updated_at` | `timestamp` | NULLABLE | |

**Indexes:** `application_id`, `assistance_code_id`, `prepared_by`

---

### `audit_logs`
System-wide log of all user actions. Append-only; never update or delete rows.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | `char(36)` | PK | UUID |
| `user_id` | `char(36)` | FK → `users.id`, NULLABLE | Null for unauthenticated events (e.g. failed login) |
| `role` | `ENUM(user_roles)` | NULLABLE | Snapshot of role at time of action |
| `module` | `varchar(100)` | NOT NULL | e.g. `applications`, `vouchers`, `user_management` |
| `action` | `varchar(100)` | NOT NULL | e.g. `approved`, `returned`, `login`, `created` |
| `description` | `text` | NULLABLE | Human-readable detail of the action |
| `entity_type` | `varchar(100)` | NULLABLE | e.g. `application`, `voucher`, `user` |
| `entity_id` | `char(36)` | NULLABLE | ID of the affected record |
| `ip_address` | `varchar(45)` | NULLABLE | Supports IPv6 |
| `user_agent` | `text` | NULLABLE | Browser/device info |
| `created_at` | `timestamp` | NULLABLE | Action timestamp |

**Indexes:** `user_id`, `module`, `action`, `entity_type`, `entity_id`, `created_at`

**Laravel note:** Use a dedicated `AuditLogger` service class or a model Observer to write logs. Do not apply `SoftDeletes` or `updated_at`.

---

### `sms_notifications`
Logs all outbound SMS notifications sent to applicants for traceability and retry handling.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | `char(36)` | PK | UUID |
| `application_id` | `char(36)` | FK → `applications.id`, NOT NULL | |
| `recipient_phone` | `varchar(20)` | NOT NULL | |
| `trigger_event` | `varchar(100)` | NOT NULL | e.g. `submission_complete`, `resubmission_needed`, `cheque_claiming` |
| `message_body` | `text` | NOT NULL | Actual SMS content sent |
| `status` | `ENUM('pending','sent','failed')` | NOT NULL, default `'pending'` | |
| `provider_response` | `json` | NULLABLE | Raw API response from SMS provider |
| `created_at` | `timestamp` | NULLABLE | Send timestamp |
| `updated_at` | `timestamp` | NULLABLE | |

**Indexes:** `application_id`, `trigger_event`, `status`, `created_at`

---

### `system_settings`
Key-value store for Admin-managed system configuration.

| Column | Type | Constraints | Notes |
|---|---|---|---|
| `id` | `char(36)` | PK | UUID |
| `setting_key` | `varchar(100)` | UNIQUE, NOT NULL | e.g. `system_name`, `primary_color`, `sms_api_key` |
| `setting_value` | `text` | NULLABLE | |
| `setting_group` | `varchar(100)` | NULLABLE | e.g. `branding`, `sms`, `notifications` |
| `updated_by` | `char(36)` | FK → `users.id`, NULLABLE | Admin user who last updated |
| `created_at` | `timestamp` | NULLABLE | |
| `updated_at` | `timestamp` | NULLABLE | |

**Indexes:** `setting_key`, `setting_group`

---

## Relationships Summary

```
users
  ├── sessions (user_id)
  ├── applications (encoded_by, reviewed_by)
  ├── reviews (reviewed_by)
  ├── social_case_studies (conducted_by)
  ├── assistance_codes (assigned_by)
  ├── vouchers (prepared_by)
  ├── audit_logs (user_id)
  └── system_settings (updated_by)

assistance_categories
  ├── required_documents (category_id)
  └── applications (category_id)

applications
  ├── application_documents (application_id)
  ├── reviews (application_id)
  ├── social_case_studies (application_id) [1:1]
  ├── assistance_codes (application_id) [1:1]
  ├── vouchers (application_id)
  └── sms_notifications (application_id)

required_documents
  └── application_documents (required_doc_id)

assistance_code_references
  └── assistance_codes (assistance_code_reference_id)

assistance_codes
  └── vouchers (assistance_code_id)
```

---

## Laravel-Specific Implementation Notes

| Concern | Implementation |
|---|---|
| **Authentication** | Laravel Breeze + Sanctum for session-based auth |
| **UUID Primary Keys** | Apply `HasUuids` trait on all models; set `$keyType = 'string'` and `$incrementing = false` |
| **Role Middleware** | Create a `RoleMiddleware` that checks `auth()->user()->role` against allowed roles per route group |
| **File Storage** | Use `Storage::disk('public')` with `php artisan storage:link`; store relative paths only in DB |
| **Audit Logging** | Use a dedicated `AuditLogger` service or Eloquent Observers triggered on model events |
| **SMS Notifications** | Use Laravel Queues with a `SendSmsJob`; set `QUEUE_CONNECTION=database` to avoid blocking requests |
| **Session Driver** | Set `SESSION_DRIVER=database` in `.env`; run `php artisan session:table` |
| **Soft Deletes** | Apply `SoftDeletes` trait only on the `User` model; all other tables use `is_active` or are append-only |
| **Enum Validation** | Validate enum fields in Form Requests using `Rule::in([...])` |
| **Timestamps** | Set `APP_TIMEZONE=UTC` in `.env`; all models use default `created_at` / `updated_at` |
| **XAMPP** | Use `DB_CONNECTION=mysql` in `.env`; set `DB_HOST=127.0.0.1`, `DB_PORT=3306`; ensure MySQL service is running in XAMPP Control Panel |
| **Migration Order** | Run migrations in this order: `users` → `assistance_categories` → `required_documents` → `applications` → `application_documents` → `reviews` → `social_case_studies` → `assistance_code_references` → `assistance_codes` → `vouchers` → `audit_logs` → `sms_notifications` → `system_settings` → `sessions` |

---

*Document prepared for AI consumption and system development reference — ALALAY System, Municipality of General Mamerto Natividad, Nueva Ecija.*

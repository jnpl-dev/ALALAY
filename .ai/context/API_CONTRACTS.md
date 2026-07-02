# ALALAY: API Contracts Specification
**Municipality of General Mamerto Natividad, Nueva Ecija**

---

## Global API Standards

- **Base URL (local):** `http://localhost:8000/api/v1`
- **Base URL (production):** `https://api.alalay.gmn.gov.ph/api/v1`
- **Content-Type:** `application/json` for all requests except file uploads (`multipart/form-data`)
- **Auth:** Laravel Sanctum — CSRF cookie (`X-XSRF-TOKEN` header) required on all mutating requests
- **Timestamps:** All timestamps returned in ISO 8601 UTC; frontend converts to PST (UTC+8)

### Standard Response Envelope

```json
// Success
{
  "success": true,
  "message": "Action completed successfully.",
  "data": {}
}

// Paginated Success
{
  "success": true,
  "message": "Applications retrieved.",
  "data": {
    "items": [],
    "pagination": {
      "current_page": 1,
      "per_page": 15,
      "total": 120,
      "last_page": 8
    }
  }
}

// Validation Error (422)
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "field_name": ["Error message."]
  }
}

// Auth Error (401)
{
  "success": false,
  "message": "Unauthenticated."
}

// Forbidden (403)
{
  "success": false,
  "message": "You do not have permission to perform this action."
}

// Not Found (404)
{
  "success": false,
  "message": "Resource not found."
}

// Server Error (500)
{
  "success": false,
  "message": "An internal server error occurred. Please try again."
}
```

### Standard Query Parameters (all list endpoints)

| Parameter | Type | Default | Notes |
|---|---|---|---|
| `page` | integer | 1 | Pagination page number |
| `per_page` | integer | 15 | Max 100 |
| `sort_by` | string | `created_at` | Column name |
| `sort_dir` | string | `desc` | `asc` or `desc` |
| `search` | string | null | Full-text search on name/reference fields |
| `date_from` | date | null | `YYYY-MM-DD` |
| `date_to` | date | null | `YYYY-MM-DD` |

---

## Auth Endpoints

---

### `POST /auth/login`
**Auth required:** No

**Request:**
```json
{
  "email": "admin@gmn.gov.ph",
  "password": "SecurePassword123!"
}
```

**Response 200 — Login success (no MFA):**
```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "user": {
      "id": "uuid",
      "first_name": "Juan",
      "last_name": "dela Cruz",
      "email": "admin@gmn.gov.ph",
      "role": "admin",
      "status": "active",
      "acceptable_use_policy_accepted_at": "2024-01-01T00:00:00Z",
      "two_factor_enabled": false
    },
    "requires_two_factor": false
  }
}
```

**Response 200 — MFA required:**
```json
{
  "success": true,
  "message": "Two-factor authentication required.",
  "data": {
    "requires_two_factor": true
  }
}
```

**Response 422 — Validation error:**
```json
{
  "success": false,
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
}
```

**Response 401 — Invalid credentials:**
```json
{
  "success": false,
  "message": "These credentials do not match our records."
}
```

---

### `POST /auth/two-factor-challenge`
**Auth required:** Partial (session exists, MFA not yet passed)

**Request:**
```json
{
  "code": "123456"
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Two-factor authentication passed.",
  "data": {
    "user": {
      "id": "uuid",
      "first_name": "Juan",
      "last_name": "dela Cruz",
      "role": "admin"
    }
  }
}
```

**Response 422:**
```json
{
  "success": false,
  "message": "The provided two-factor authentication code was invalid."
}
```

---

### `POST /auth/logout`
**Auth required:** Yes

**Request:** _(empty body)_

**Response 200:**
```json
{
  "success": true,
  "message": "Logged out successfully."
}
```

---

### `POST /auth/forgot-password`
**Auth required:** No

**Request:**
```json
{
  "email": "staff@gmn.gov.ph"
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Password reset link sent to your email."
}
```

---

### `POST /auth/reset-password`
**Auth required:** No

**Request:**
```json
{
  "token": "reset-token-from-email",
  "email": "staff@gmn.gov.ph",
  "password": "NewSecurePassword123!",
  "password_confirmation": "NewSecurePassword123!"
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Password has been reset successfully."
}
```

---

### `GET /auth/user`
**Auth required:** Yes

**Response 200:**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "first_name": "Juan",
    "last_name": "dela Cruz",
    "middle_name": "Santos",
    "name_extension": null,
    "email": "admin@gmn.gov.ph",
    "role": "admin",
    "status": "active",
    "is_online": true,
    "profile_picture_url": "https://signed-url...",
    "two_factor_enabled": true,
    "acceptable_use_policy_accepted_at": "2024-01-01T00:00:00Z",
    "created_at": "2024-01-01T00:00:00Z"
  }
}
```

---

### `POST /auth/accept-aup`
**Auth required:** Yes

**Request:** _(empty body)_

**Response 200:**
```json
{
  "success": true,
  "message": "Acceptable Use Policy accepted."
}
```

---

## Account Endpoints (All Authenticated Roles)

---

### `PUT /account`
**Auth required:** Yes
**Content-Type:** `multipart/form-data` (if uploading profile picture), else `application/json`

**Request:**
```json
{
  "first_name": "Juan",
  "last_name": "dela Cruz",
  "middle_name": "Santos",
  "name_extension": null,
  "email": "juan@gmn.gov.ph",
  "current_password": "CurrentPassword123!",
  "password": "NewPassword123!",
  "password_confirmation": "NewPassword123!",
  "profile_picture": "(file — optional)"
}
```

**Validation Rules:**
- `first_name`: required, string, max:100
- `last_name`: required, string, max:100
- `middle_name`: nullable, string, max:100
- `name_extension`: nullable, string, max:10
- `email`: required, email, unique:users except current user
- `current_password`: required only if `password` is present
- `password`: nullable, min:12, mixed case, numbers, symbols, uncompromised
- `password_confirmation`: required_with:password, same:password
- `profile_picture`: nullable, file, mimes:jpg,jpeg,png, max:2048 (KB)

**Response 200:**
```json
{
  "success": true,
  "message": "Account updated successfully.",
  "data": {
    "id": "uuid",
    "first_name": "Juan",
    "last_name": "dela Cruz",
    "email": "juan@gmn.gov.ph",
    "profile_picture_url": "https://signed-url..."
  }
}
```

---

### `GET /account/two-factor/qr`
**Auth required:** Yes

**Response 200:**
```json
{
  "success": true,
  "data": {
    "qr_code_svg": "<svg>...</svg>",
    "secret_key": "BASE32SECRET"
  }
}
```

---

### `POST /account/two-factor/enable`
**Auth required:** Yes

**Request:**
```json
{
  "code": "123456"
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Two-factor authentication enabled."
}
```

---

### `DELETE /account/two-factor`
**Auth required:** Yes

**Request:**
```json
{
  "password": "CurrentPassword123!"
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Two-factor authentication disabled."
}
```

---

## Public Endpoints

---

### `GET /categories`
**Auth required:** No

**Response 200:**
```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "category_name": "Medical Assistance",
      "category_description": "Financial assistance for medical expenses.",
      "required_documents": [
        {
          "id": "uuid",
          "doc_name": "Medical Certificate",
          "doc_description": "Issued by attending physician.",
          "is_mandatory": true
        },
        {
          "id": "uuid",
          "doc_name": "Hospital Bill",
          "doc_description": "Original or certified true copy.",
          "is_mandatory": true
        }
      ]
    }
  ]
}
```

---

### `POST /applications`
**Auth required:** No
**Content-Type:** `multipart/form-data`

**Request (form fields):**
```
category_id                           (uuid, required)
claimant_last_name                    (string, required, max:100)
claimant_first_name                   (string, required, max:100)
claimant_middle_name                  (string, nullable, max:100)
claimant_name_extension               (string, nullable, max:10)
claimant_sex                          (enum: male|female, required)
claimant_dob                          (date, required, before:today)
claimant_address                      (string, required)
claimant_phone                        (string, required, regex:/^(09|\+639)\d{9}$/)
claimant_email                        (email, nullable)
claimant_relationship_to_beneficiary  (string, required, max:100)
beneficiary_last_name                 (string, required, max:100)
beneficiary_first_name                (string, required, max:100)
beneficiary_middle_name               (string, nullable, max:100)
beneficiary_name_extension            (string, nullable, max:10)
beneficiary_sex                       (enum: male|female, required)
beneficiary_dob                       (date, required, before:today)
beneficiary_address                   (string, required)
documents[{required_doc_id}]          (file, mimes:jpg,jpeg,png,pdf — required per is_mandatory)
```

**Validation Rules (additional):**
- `category_id`: must exist in `assistance_categories` where `is_active = 1`
- `documents`: array of files keyed by `required_doc_id`; all mandatory doc IDs must be present
- Each file: max size from `system_settings.max_file_size_kb`; mimes: jpg, jpeg, png, pdf

**Response 201:**
```json
{
  "success": true,
  "message": "Application submitted successfully.",
  "data": {
    "reference_code": "GMN-2024-000001",
    "status": "submitted",
    "submitted_at": "2024-06-01T08:00:00Z"
  }
}
```

---

### `GET /applications/track/{reference_code}`
**Auth required:** No

**Response 200:**
```json
{
  "success": true,
  "data": {
    "reference_code": "GMN-2024-000001",
    "category_name": "Medical Assistance",
    "beneficiary_name": "Maria dela Cruz",
    "status": "returned_to_applicant",
    "status_label": "Returned — Resubmission Needed",
    "submitted_at": "2024-06-01T08:00:00Z",
    "resubmission_remarks": "Please resubmit a clearer copy of your hospital bill.",
    "flagged_documents": [
      {
        "required_doc_id": "uuid",
        "doc_name": "Hospital Bill",
        "doc_description": "Original or certified true copy."
      }
    ],
    "review_trail": [
      {
        "stage": "aics_screening",
        "stage_label": "AICS Staff Screening",
        "decision": "returned",
        "decision_label": "Returned",
        "remarks": "Please resubmit a clearer copy of your hospital bill.",
        "from_status": "submitted",
        "to_status": "returned_to_applicant",
        "reviewed_by": "Ana Reyes",
        "reviewed_at": "2024-06-02T10:30:00Z"
      }
    ]
  }
}
```

**Response 404:**
```json
{
  "success": false,
  "message": "No application found with that reference code."
}
```

---

### `POST /applications/{reference_code}/resubmit`
**Auth required:** No
**Content-Type:** `multipart/form-data`

**Request:**
```
documents[{required_doc_id}]   (file — only flagged documents required)
```

**Validation:**
- Reference code must exist and `status = 'returned_to_applicant'`
- Only documents listed in latest `reviews.resubmission_docs_required` are accepted
- Each file: same MIME and size rules as initial submission

**Response 200:**
```json
{
  "success": true,
  "message": "Documents resubmitted successfully. Your application is now under review.",
  "data": {
    "reference_code": "GMN-2024-000001",
    "status": "submitted",
    "resubmission_number": 1
  }
}
```

---

## Admin Endpoints

All routes prefixed `/admin`. Role middleware: `admin` only.

---

### `GET /admin/dashboard`

**Response 200:**
```json
{
  "success": true,
  "data": {
    "kpis": {
      "total_users": 12,
      "active_accounts": 10,
      "inactive_accounts": 2,
      "online_users": 3,
      "new_users_today": 1,
      "audit_log_entries_today": 47
    },
    "recent_activities": [...],
    "unusual_activities": [...],
    "system_status": {
      "sms_api": "operational",
      "last_sms_sent_at": "2024-06-01T09:15:00Z"
    }
  }
}
```

---

### `GET /admin/users`
**Query params:** `page`, `per_page`, `search`, `role`, `status`, `date_from`, `date_to`, `sort_by`, `sort_dir`

**Response 200:**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": "uuid",
        "full_name": "Juan dela Cruz",
        "email": "juan@gmn.gov.ph",
        "role": "aics_staff",
        "status": "active",
        "is_online": false,
        "created_at": "2024-01-01T00:00:00Z"
      }
    ],
    "pagination": { "current_page": 1, "per_page": 15, "total": 12, "last_page": 1 }
  }
}
```

---

### `POST /admin/users`

**Request:**
```json
{
  "first_name": "Maria",
  "last_name": "Santos",
  "middle_name": null,
  "name_extension": null,
  "email": "maria@gmn.gov.ph",
  "role": "mswdo",
  "password": "InitialPassword123!",
  "password_confirmation": "InitialPassword123!"
}
```

**Validation:**
- `first_name`, `last_name`: required, string, max:100
- `middle_name`, `name_extension`: nullable, string
- `email`: required, email, unique:users
- `role`: required, in: `aics_staff`, `mswdo`, `accountant`, `treasurer`, `mayors_office`, `admin`
- `password`: required, min:12, mixed case, numbers, symbols, uncompromised
- `password_confirmation`: required, same:password

**Response 201:**
```json
{
  "success": true,
  "message": "User created successfully.",
  "data": { "id": "uuid", "email": "maria@gmn.gov.ph", "role": "mswdo" }
}
```

---

### `PUT /admin/users/{id}`
Same request/validation as `POST /admin/users`. Password optional on update.

---

### `PATCH /admin/users/{id}/toggle-status`

**Response 200:**
```json
{
  "success": true,
  "message": "User account deactivated.",
  "data": { "id": "uuid", "status": "inactive" }
}
```

---

### `DELETE /admin/users/{id}/sessions`

**Response 200:**
```json
{
  "success": true,
  "message": "All sessions revoked. User has been logged out."
}
```

---

### `GET /admin/audit-logs`
**Query params:** `page`, `per_page`, `user_id`, `role`, `module`, `action`, `date_from`, `date_to`, `export` (boolean — triggers CSV download)

**Response 200:**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": "uuid",
        "user_name": "Juan dela Cruz",
        "role": "aics_staff",
        "module": "applications",
        "action": "approved",
        "description": "Approved application GMN-2024-000001.",
        "entity_type": "application",
        "entity_id": "uuid",
        "ip_address": "192.168.1.100",
        "created_at": "2024-06-01T08:30:00Z"
      }
    ],
    "pagination": { "current_page": 1, "per_page": 15, "total": 350, "last_page": 24 }
  }
}
```

---

### `GET /admin/settings`

**Response 200:**
```json
{
  "success": true,
  "data": {
    "branding": {
      "system_name": "ALALAY",
      "system_logo": "https://signed-url...",
      "primary_color": "#1e40af",
      "secondary_color": "#0f172a"
    },
    "sms": {
      "sms_api_key": "***masked***",
      "sms_sender_name": "ALALAY",
      "sms_api_endpoint": "https://api.semaphore.co/api/v4/messages"
    },
    "notifications": {
      "sms_template_submission_complete": "Your AICS application {reference_code} has been submitted. Track it at {track_url}.",
      "sms_template_under_review": "Your application {reference_code} is now under review.",
      "sms_template_resubmission_needed": "Your application {reference_code} requires document resubmission. Reason: {remarks}. Track at {track_url}.",
      "sms_template_cheque_claiming": "Your AICS assistance cheque is ready for claiming. Please visit the MSWDO office. Reference: {reference_code}."
    },
    "application": {
      "max_file_size_kb": 5120,
      "allowed_mime_types": "image/jpeg,image/png,application/pdf"
    }
  }
}
```

---

### `PUT /admin/settings`

**Request:**
```json
{
  "group": "branding",
  "settings": {
    "system_name": "ALALAY",
    "primary_color": "#1e40af"
  }
}
```

---

## AICS Staff Endpoints

All routes prefixed `/aics`. Role middleware: `aics_staff` only.

---

### `GET /aics/dashboard`

**Response 200:**
```json
{
  "success": true,
  "data": {
    "kpis": {
      "new_applications_today": 5,
      "total_pending": 12,
      "total_screened": 48,
      "total_returned": 7,
      "pending_assistance_coding": 3,
      "resubmissions_today": 2
    },
    "action_required": [...]
  }
}
```

---

### `GET /aics/applications`
**Query params:** `status` (required: `pending`|`screened`|`returned`), + standard params

**Response 200:**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": "uuid",
        "reference_code": "GMN-2024-000001",
        "beneficiary_name": "Maria dela Cruz",
        "category_name": "Medical Assistance",
        "submission_type": "online",
        "status": "submitted",
        "status_label": "Submitted",
        "created_at": "2024-06-01T08:00:00Z"
      }
    ],
    "pagination": { "current_page": 1, "per_page": 15, "total": 12, "last_page": 1 }
  }
}
```

---

### `GET /aics/applications/{id}`

**Response 200:**
```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "reference_code": "GMN-2024-000001",
    "category_name": "Medical Assistance",
    "status": "submitted",
    "status_label": "Submitted",
    "submission_type": "online",
    "claimant": {
      "last_name": "dela Cruz",
      "first_name": "Jose",
      "middle_name": "Santos",
      "name_extension": null,
      "sex": "male",
      "dob": "1985-03-15",
      "address": "123 Rizal St., Brgy. Poblacion, GMN, NE",
      "phone": "09171234567",
      "email": null,
      "relationship_to_beneficiary": "Son"
    },
    "beneficiary": {
      "last_name": "dela Cruz",
      "first_name": "Maria",
      "middle_name": "Santos",
      "name_extension": null,
      "sex": "female",
      "dob": "1955-07-20",
      "address": "123 Rizal St., Brgy. Poblacion, GMN, NE"
    },
    "documents": [
      {
        "id": "uuid",
        "doc_name": "Medical Certificate",
        "is_mandatory": true,
        "is_resubmission": false,
        "resubmission_number": 0,
        "mime_type": "application/pdf",
        "uploaded_at": "2024-06-01T08:00:00Z",
        "view_url_endpoint": "/aics/applications/{id}/documents/{docId}/url"
      }
    ],
    "review_trail": [
      {
        "stage": "aics_screening",
        "stage_label": "AICS Staff Screening",
        "decision": "returned",
        "decision_label": "Returned",
        "remarks": "Please resubmit hospital bill.",
        "from_status": "submitted",
        "to_status": "returned_to_applicant",
        "reviewed_by": "Ana Reyes",
        "reviewed_at": "2024-06-02T10:00:00Z"
      }
    ]
  }
}
```

---

### `GET /aics/applications/{id}/documents/{docId}/url`

**Response 200:**
```json
{
  "success": true,
  "data": {
    "url": "https://project.supabase.co/storage/v1/object/sign/...",
    "expires_at": "2024-06-01T09:00:00Z"
  }
}
```

---

### `POST /aics/applications/{id}/approve`

**Request:**
```json
{
  "remarks": "All documents verified. Forwarding to MSWDO."
}
```

**Validation:**
- Application must exist and `status = 'submitted'`
- `remarks`: nullable, string, max:1000

**Response 200:**
```json
{
  "success": true,
  "message": "Application approved and forwarded to MSWDO.",
  "data": {
    "id": "uuid",
    "status": "mswdo_review",
    "status_label": "Under MSWDO Review"
  }
}
```

---

### `POST /aics/applications/{id}/return`

**Request:**
```json
{
  "remarks": "Please submit a clearer copy of the hospital bill.",
  "resubmission_docs_required": ["uuid-of-required-doc-1", "uuid-of-required-doc-2"]
}
```

**Validation:**
- Application must exist and `status = 'submitted'`
- `remarks`: required, string, max:1000
- `resubmission_docs_required`: required, array, min:1; each item must exist in `required_documents` for this application's category

**Response 200:**
```json
{
  "success": true,
  "message": "Application returned to applicant for document resubmission.",
  "data": {
    "id": "uuid",
    "status": "returned_to_applicant",
    "status_label": "Returned — Resubmission Needed"
  }
}
```

---

### `GET /aics/assistance-codes`
**Query params:** `status` (required: `pending`|`coded`), + standard params

---

### `GET /aics/assistance-codes/{id}`

**Response 200:** Same as `GET /aics/applications/{id}` plus:
```json
{
  "social_case_study": {
    "id": "uuid",
    "conducted_by": "Ana Reyes",
    "conducted_at": "2024-06-03T14:00:00Z",
    "view_url_endpoint": "/aics/assistance-codes/{id}/case-study/url"
  },
  "assistance_code": null
}
```

---

### `GET /aics/assistance-codes/{id}/case-study/url`
Same structure as document signed URL endpoint.

---

### `POST /aics/assistance-codes/{id}/code`

**Request:**
```json
{
  "assistance_code_reference_id": "uuid",
  "amount": 5000.00
}
```

**Validation:**
- Application must exist and `status = 'assistance_coding'`
- `assistance_code_reference_id`: required, exists in `assistance_code_references` where `is_active = 1`
- `amount`: required, numeric, min:1, max:999999.99

**Response 201:**
```json
{
  "success": true,
  "message": "Assistance code assigned. Application forwarded for voucher creation.",
  "data": {
    "application_id": "uuid",
    "assistance_code_type": "Medical Assistance",
    "amount": 5000.00,
    "status": "voucher_creation"
  }
}
```

---

## MSWDO Endpoints

All routes prefixed `/mswdo`. Role middleware: `mswdo` only.

---

### `GET /mswdo/applications`
**Query params:** `status` (required: `screened`|`approved`|`returned`), + standard params

---

### `POST /mswdo/applications/{id}/approve`
**Content-Type:** `multipart/form-data`

**Request:**
```
social_case_study   (file, required, mimes:jpg,jpeg,png,pdf, max: system_settings.max_file_size_kb)
remarks             (string, nullable, max:1000)
```

**Validation:**
- Application must exist and `status = 'mswdo_review'`

**Response 200:**
```json
{
  "success": true,
  "message": "Application approved. Social case study uploaded. Forwarded to AICS Staff for assistance coding.",
  "data": { "id": "uuid", "status": "assistance_coding" }
}
```

---

### `POST /mswdo/applications/{id}/return`
Same request/validation as `POST /aics/applications/{id}/return` but application `status` must be `mswdo_review`.

---

### `GET /mswdo/vouchers`
**Query params:** `status` (required: `pending`|`created`), + standard params

---

### `POST /mswdo/vouchers/{id}/create`
**Content-Type:** `multipart/form-data`

**Request:**
```
voucher_file          (file, required, mimes:jpg,jpeg,png,pdf)
adjustment_remarks    (string, nullable, max:1000)
```

**Validation:**
- Application must exist and `status = 'voucher_creation'`
- Assistance code must exist for this application

**Response 201:**
```json
{
  "success": true,
  "message": "Voucher created and submitted to Accountant for review.",
  "data": { "id": "uuid", "voucher_id": "uuid", "version": 1, "status": "voucher_checking" }
}
```

---

## Accountant Endpoints

All routes prefixed `/accountant`. Role middleware: `accountant` only.

---

### `GET /accountant/vouchers`
**Query params:** `status` (required: `pending`|`approved`|`returned`), + standard params

**Response items include:**
```json
{
  "id": "uuid",
  "reference_code": "GMN-2024-000001",
  "beneficiary_name": "Maria dela Cruz",
  "category_name": "Medical Assistance",
  "amount": 5000.00,
  "voucher_version": 1,
  "voucher_created_at": "2024-06-04T09:00:00Z"
}
```

---

### `GET /accountant/vouchers/{id}`

**Response includes:** application summary + voucher details + signed URL endpoint + review trail.

---

### `POST /accountant/vouchers/{id}/approve`

**Request:**
```json
{
  "remarks": "Voucher verified. Forwarding to Treasurer."
}
```

**Validation:**
- Application `status = 'voucher_checking'`
- `remarks`: nullable, string, max:1000

**Response 200:**
```json
{
  "success": true,
  "message": "Voucher approved and forwarded to Treasurer.",
  "data": { "id": "uuid", "status": "with_treasurer" }
}
```

---

### `POST /accountant/vouchers/{id}/return`

**Request:**
```json
{
  "remarks": "Amount does not match assistance code. Please re-calculate."
}
```

**Validation:**
- Application `status = 'voucher_checking'`
- `remarks`: required, string, max:1000

**Response 200:**
```json
{
  "success": true,
  "message": "Voucher returned for re-creation.",
  "data": { "id": "uuid", "status": "voucher_returned" }
}
```

---

### `GET /accountant/budget`
**Query params:** `status` (required: `pending`|`cheque_ready`|`on_hold`), + standard params

---

### `POST /accountant/budget/{id}/mark-ready`

**Request:**
```json
{
  "remarks": "Budget available. Cheque ready for release."
}
```

**Validation:**
- Application `status = 'budget_checking'`

**Response 200:**
```json
{
  "success": true,
  "message": "Application marked as Cheque Ready. Applicant notified via SMS.",
  "data": { "id": "uuid", "status": "cheque_ready" }
}
```

---

### `POST /accountant/budget/{id}/hold`

**Request:**
```json
{
  "remarks": "Insufficient budget allocation for this period."
}
```

**Response 200:**
```json
{
  "success": true,
  "message": "Application placed on hold.",
  "data": { "id": "uuid", "status": "on_hold" }
}
```

---

### `POST /accountant/budget/{id}/re-evaluate`
Same as `mark-ready` — moves `on_hold` application back to `budget_checking` then immediately evaluates.

---

## Treasurer Endpoints

All routes prefixed `/treasurer`. Role middleware: `treasurer` only.

---

### `GET /treasurer/cheques`
**Query params:** `status` (required: `pending`|`ready`|`on_hold`), + standard params

---

### `POST /treasurer/cheques/{id}/acknowledge`

**Request:**
```json
{
  "remarks": "Voucher reviewed. Forwarding for budget checking."
}
```

**Validation:**
- Application `status = 'with_treasurer'`

**Response 200:**
```json
{
  "success": true,
  "message": "Voucher acknowledged. Forwarded to Accountant for budget checking.",
  "data": { "id": "uuid", "status": "budget_checking" }
}
```

---

## Mayor's Office Endpoints

All routes prefixed `/mayors-office`. Role middleware: `mayors_office` only. All GET, no mutations.

---

### `GET /mayors-office/dashboard`

**Response 200:**
```json
{
  "success": true,
  "data": {
    "kpis": {
      "total_applications": 250,
      "applications_today": 8,
      "total_approved": 180,
      "total_returned": 30,
      "total_on_hold": 5,
      "total_claimed": 160,
      "total_disbursed_amount": 850000.00,
      "total_pending_amount": 125000.00
    },
    "recent_activity": [...],
    "by_category": [...]
  }
}
```

---

### `GET /mayors-office/analytics`
**Query params:** `period` (`all`|`last_year`|`last_3_months`|`last_month`|`this_month`|`custom`), `date_from`, `date_to`

**Response 200:**
```json
{
  "success": true,
  "data": {
    "applications_over_time": [
      { "date": "2024-01", "count": 45 }
    ],
    "by_category": [
      { "category": "Medical Assistance", "count": 120 }
    ],
    "approval_vs_return_rate": [
      { "month": "2024-01", "approved": 40, "returned": 5 }
    ],
    "disbursement_over_time": [
      { "month": "2024-01", "amount": 200000.00 }
    ],
    "stage_bottleneck": [
      { "stage": "aics_screening", "avg_days": 1.2 },
      { "stage": "mswdo_review", "avg_days": 3.5 }
    ],
    "submission_type_breakdown": {
      "online": 180,
      "walk_in": 70
    }
  }
}
```

---

*Document prepared for AI consumption and development reference — ALALAY System, Municipality of General Mamerto Natividad, Nueva Ecija.*

# ALALAY: Validation, Sanitization & Security Specification
**All Forms — All Roles — All Fields**
**Municipality of General Mamerto Natividad, Nueva Ecija**

---

## How to Use This Document

Read this before writing or modifying any Form Request class in ALALAY.
Every field in every form has exactly one source of truth for its validation
rules, sanitization steps, and error messages — this document.

**Architecture reminder:**
- Server-side validation lives in Laravel Form Request classes — this is
  the only authoritative gate. Nothing passes to the database without going
  through a Form Request.
- SQL injection protection is provided by Eloquent's parameterized queries.
  No raw SQL with user input is ever written in ALALAY.
- Sanitization happens in the Form Request `prepareForValidation()` method
  before validation rules run.
- Client-side validation (Vue) is for UX only — it provides immediate
  feedback but is never trusted as the final gate.

---

## SECTION 1 — Global Rules (Apply to Every Form)

### 1.1 SQL Injection Protection

Laravel's Eloquent ORM uses PDO parameterized queries for all database
operations. This means user input is never interpolated into SQL strings.
These rules enforce that protection stays intact:

**ALWAYS use:**
```php
Application::where('reference_code', $request->input('reference_code'))->first();
User::where('email', $request->input('email'))->exists();
DB::table('assistance_categories')->where('id', $categoryId)->value('id');
```

**NEVER write:**
```php
// These are SQL injection vulnerabilities — never do this
DB::select("SELECT * FROM applications WHERE reference_code = '" . $value . "'");
DB::statement("UPDATE users SET role = '{$request->role}' WHERE id = '{$id}'");
```

**NEVER pass raw user input to `DB::raw()`:**
```php
// WRONG
DB::table('applications')->orderBy(DB::raw($request->sort_by));

// CORRECT — whitelist allowed values before using in queries
$allowedSortColumns = ['created_at', 'status', 'reference_code'];
$sortBy = in_array($request->sort_by, $allowedSortColumns)
    ? $request->sort_by
    : 'created_at';
DB::table('applications')->orderBy($sortBy);
```

### 1.2 Global Sanitization in `prepareForValidation()`

Every Form Request that handles text input must override
`prepareForValidation()` to normalize data before validation rules run:

```php
protected function prepareForValidation(): void
{
    $stringFields = [/* list text fields for this form */];

    $merged = [];
    foreach ($stringFields as $field) {
        if ($this->has($field) && is_string($this->input($field))) {
            $merged[$field] = trim(strip_tags($this->input($field)));
        }
    }

    // Normalize phone — strip all non-numeric except leading +
    if ($this->has('claimant_phone')) {
        $merged['claimant_phone'] = preg_replace('/[^\d+]/', '', $this->input('claimant_phone'));
    }

    // Normalize email — lowercase
    foreach (['claimant_email', 'email'] as $field) {
        if ($this->has($field) && $this->input($field)) {
            $merged[$field] = strtolower(trim($this->input($field)));
        }
    }

    // Normalize name fields — trim + collapse multiple spaces
    $nameFields = [
        'claimant_first_name', 'claimant_last_name', 'claimant_middle_name',
        'beneficiary_first_name', 'beneficiary_last_name', 'beneficiary_middle_name',
        'first_name', 'last_name', 'middle_name',
    ];
    foreach ($nameFields as $field) {
        if ($this->has($field) && is_string($this->input($field))) {
            $merged[$field] = preg_replace('/\s+/', ' ', trim($this->input($field)));
        }
    }

    if (!empty($merged)) {
        $this->merge($merged);
    }
}
```

### 1.3 Global Error Message Format

All Form Request classes must define a `messages()` method.
Error messages follow this format: **clear, specific, Filipino-government-friendly.**
No technical jargon. No "The field is required" defaults.

```php
// Base pattern
'field.rule' => 'Human-readable message that tells the user exactly what to fix.'
```

### 1.4 Sort/Filter Parameter Whitelisting

All index/list endpoints that accept sort or filter query parameters
must whitelist allowed values before using them in queries:

```php
// In controller index methods — before any query
$allowedSortBy = ['created_at', 'status', 'reference_code', 'updated_at'];
$allowedSortDir = ['asc', 'desc'];
$allowedStatuses = ['submitted', 'mswdo_review', 'assistance_coding', ...];

$sortBy  = in_array($request->sort_by, $allowedSortBy)   ? $request->sort_by  : 'created_at';
$sortDir = in_array($request->sort_dir, $allowedSortDir) ? $request->sort_dir : 'desc';
$status  = in_array($request->status, $allowedStatuses)  ? $request->status   : null;
```

---

## SECTION 2 — Public Forms

---

### 2.1 `SubmitApplicationRequest`

**File:** `app/Http/Requests/Public/SubmitApplicationRequest.php`
**Used by:** `Public/ApplicationController@store`

#### Direct Relatives (Authorization Letter not required for these)
```php
private const DIRECT_RELATIVES = [
    'Spouse', 'Parent', 'Child', 'Sibling', 'Grandparent', 'Grandchild',
];
```

#### Field Validation Rules

```php
public function rules(): array
{
    $authLetterDocId = \DB::table('required_documents')
        ->where('category_id', $this->input('category_id'))
        ->where('doc_name', 'Authorization Letter')
        ->value('id');

    $isDirectRelative = in_array(
        $this->input('claimant_relationship_to_beneficiary'),
        self::DIRECT_RELATIVES
    );

    return [
        // ── Category ──────────────────────────────────────────────────
        'category_id' => [
            'required',
            'uuid',
            Rule::exists('assistance_categories', 'id')->where('is_active', true),
        ],

        // ── Claimant Information ──────────────────────────────────────
        'claimant_last_name' => [
            'required',
            'string',
            'min:1',
            'max:100',
            'regex:/^[\p{L}\s\'\-\.]+$/u',  // letters, spaces, apostrophe, hyphen, dot
        ],
        'claimant_first_name' => [
            'required', 'string', 'min:1', 'max:100',
            'regex:/^[\p{L}\s\'\-\.]+$/u',
        ],
        'claimant_middle_name' => [
            'nullable', 'string', 'max:100',
            'regex:/^[\p{L}\s\'\-\.]+$/u',
        ],
        'claimant_name_extension' => [
            'nullable',
            Rule::in(['Jr.', 'Sr.', 'II', 'III', 'IV', 'V']),
        ],
        'claimant_sex' => [
            'required',
            Rule::in(['male', 'female']),
        ],
        'claimant_dob' => [
            'required',
            'date',
            'date_format:Y-m-d',
            'before:today',
            'after:1900-01-01',
        ],
        'claimant_province' => [
            'required', 'string', 'max:100',
        ],
        'claimant_city_municipality' => [
            'required', 'string', 'max:150',
        ],
        'claimant_barangay' => [
            'required', 'string', 'max:150',
        ],
        'claimant_street' => [
            'required', 'string', 'max:255',
        ],
        'claimant_phone' => [
            'required',
            'string',
            'regex:/^(09|\+639)\d{9}$/',  // 09XXXXXXXXX or +639XXXXXXXXX
        ],
        'claimant_email' => [
            'nullable',
            'email:rfc,dns',
            'max:255',
        ],
        'claimant_relationship_to_beneficiary' => [
            'required',
            Rule::in([
                'Spouse', 'Parent', 'Child', 'Sibling',
                'Grandparent', 'Grandchild', 'Representative',
            ]),
        ],

        // ── Beneficiary Information ───────────────────────────────────
        'beneficiary_last_name' => [
            'required', 'string', 'min:1', 'max:100',
            'regex:/^[\p{L}\s\'\-\.]+$/u',
        ],
        'beneficiary_first_name' => [
            'required', 'string', 'min:1', 'max:100',
            'regex:/^[\p{L}\s\'\-\.]+$/u',
        ],
        'beneficiary_middle_name' => [
            'nullable', 'string', 'max:100',
            'regex:/^[\p{L}\s\'\-\.]+$/u',
        ],
        'beneficiary_name_extension' => [
            'nullable',
            Rule::in(['Jr.', 'Sr.', 'II', 'III', 'IV', 'V']),
        ],
        'beneficiary_sex' => [
            'required',
            Rule::in(['male', 'female']),
        ],
        'beneficiary_dob' => [
            'required',
            'date',
            'date_format:Y-m-d',
            'before:today',
            'after:1900-01-01',
        ],
        'beneficiary_province' => [
            'required', 'string', 'max:100',
        ],
        'beneficiary_city_municipality' => [
            'required', 'string', 'max:150',
        ],
        'beneficiary_barangay' => [
            'required', 'string', 'max:150',
        ],
        'beneficiary_street' => [
            'required', 'string', 'max:255',
        ],
        'same_address_as_claimant' => [
            'boolean',
        ],

        // ── Documents ─────────────────────────────────────────────────
        'documents'   => ['required', 'array'],
        'documents.*' => [
            'file',
            'mimes:pdf',
            'max:' . $this->getMaxFileSizeKb(),
        ],

        // Authorization Letter — required only for non-direct-relatives
        "documents.{$authLetterDocId}" => $authLetterDocId
            ? ($isDirectRelative
                ? 'nullable|file|mimes:pdf'
                : 'required|file|mimes:pdf|max:' . $this->getMaxFileSizeKb())
            : 'nullable',

        // Mandatory documents must be present
        // (enforced dynamically per required_documents.is_mandatory)
    ];
}

private function getMaxFileSizeKb(): int
{
    return (int) \DB::table('system_settings')
        ->where('setting_key', 'max_file_size_kb')
        ->value('setting_value') ?: 10240;
}
```

#### Sanitization

```php
protected function prepareForValidation(): void
{
    $this->merge([
        'claimant_last_name'   => preg_replace('/\s+/', ' ', trim(strip_tags($this->claimant_last_name ?? ''))),
        'claimant_first_name'  => preg_replace('/\s+/', ' ', trim(strip_tags($this->claimant_first_name ?? ''))),
        'claimant_middle_name' => $this->claimant_middle_name
            ? preg_replace('/\s+/', ' ', trim(strip_tags($this->claimant_middle_name)))
            : null,
        'claimant_name_extension' => $this->claimant_name_extension ?: null,
        'claimant_email'       => $this->claimant_email
            ? strtolower(trim($this->claimant_email))
            : null,
        'claimant_phone'       => preg_replace('/[^\d+]/', '', $this->claimant_phone ?? ''),
        'claimant_province'    => trim(strip_tags($this->claimant_province ?? '')),
        'claimant_city_municipality' => trim(strip_tags($this->claimant_city_municipality ?? '')),
        'claimant_barangay'    => trim(strip_tags($this->claimant_barangay ?? '')),
        'claimant_street'      => trim(strip_tags($this->claimant_street ?? '')),

        'beneficiary_last_name'   => preg_replace('/\s+/', ' ', trim(strip_tags($this->beneficiary_last_name ?? ''))),
        'beneficiary_first_name'  => preg_replace('/\s+/', ' ', trim(strip_tags($this->beneficiary_first_name ?? ''))),
        'beneficiary_middle_name' => $this->beneficiary_middle_name
            ? preg_replace('/\s+/', ' ', trim(strip_tags($this->beneficiary_middle_name)))
            : null,
        'beneficiary_name_extension' => $this->beneficiary_name_extension ?: null,
        'beneficiary_province'    => trim(strip_tags($this->beneficiary_province ?? '')),
        'beneficiary_city_municipality' => trim(strip_tags($this->beneficiary_city_municipality ?? '')),
        'beneficiary_barangay'    => trim(strip_tags($this->beneficiary_barangay ?? '')),
        'beneficiary_street'      => trim(strip_tags($this->beneficiary_street ?? '')),
        'same_address_as_claimant' => (bool) ($this->same_address_as_claimant ?? false),
    ]);
}
```

#### Error Messages

```php
public function messages(): array
{
    return [
        // Category
        'category_id.required'                  => 'Please select an assistance category.',
        'category_id.exists'                    => 'The selected assistance category is not available.',

        // Claimant names
        'claimant_last_name.required'           => 'Claimant\'s last name is required.',
        'claimant_last_name.max'                => 'Claimant\'s last name must not exceed 100 characters.',
        'claimant_last_name.regex'              => 'Claimant\'s last name may only contain letters, spaces, apostrophes, and hyphens.',
        'claimant_first_name.required'          => 'Claimant\'s first name is required.',
        'claimant_first_name.max'               => 'Claimant\'s first name must not exceed 100 characters.',
        'claimant_first_name.regex'             => 'Claimant\'s first name may only contain letters, spaces, apostrophes, and hyphens.',
        'claimant_middle_name.regex'            => 'Claimant\'s middle name may only contain letters, spaces, apostrophes, and hyphens.',
        'claimant_name_extension.in'            => 'Please select a valid name extension (Jr., Sr., II, III, IV, or V).',

        // Claimant personal info
        'claimant_sex.required'                 => 'Please select the claimant\'s sex.',
        'claimant_sex.in'                       => 'Please select a valid sex.',
        'claimant_dob.required'                 => 'Claimant\'s date of birth is required.',
        'claimant_dob.date'                     => 'Please enter a valid date of birth.',
        'claimant_dob.before'                   => 'Claimant\'s date of birth must be in the past.',
        'claimant_dob.after'                    => 'Please enter a valid date of birth.',

        // Claimant address
        'claimant_province.required'            => 'Please select the claimant\'s province.',
        'claimant_city_municipality.required'   => 'Please select the claimant\'s city or municipality.',
        'claimant_barangay.required'            => 'Please select the claimant\'s barangay.',
        'claimant_street.required'              => 'Please enter the claimant\'s house number and street.',
        'claimant_street.max'                   => 'Street address must not exceed 255 characters.',

        // Claimant contact
        'claimant_phone.required'               => 'Claimant\'s mobile number is required.',
        'claimant_phone.regex'                  => 'Please enter a valid Philippine mobile number (e.g. 09171234567).',
        'claimant_email.email'                  => 'Please enter a valid email address.',
        'claimant_email.max'                    => 'Email address must not exceed 255 characters.',

        // Relationship
        'claimant_relationship_to_beneficiary.required' => 'Please select the claimant\'s relationship to the beneficiary.',
        'claimant_relationship_to_beneficiary.in'       => 'Please select a valid relationship.',

        // Beneficiary names
        'beneficiary_last_name.required'        => 'Beneficiary\'s last name is required.',
        'beneficiary_last_name.regex'           => 'Beneficiary\'s last name may only contain letters, spaces, apostrophes, and hyphens.',
        'beneficiary_first_name.required'       => 'Beneficiary\'s first name is required.',
        'beneficiary_first_name.regex'          => 'Beneficiary\'s first name may only contain letters, spaces, apostrophes, and hyphens.',
        'beneficiary_middle_name.regex'         => 'Beneficiary\'s middle name may only contain letters, spaces, apostrophes, and hyphens.',
        'beneficiary_name_extension.in'         => 'Please select a valid name extension (Jr., Sr., II, III, IV, or V).',

        // Beneficiary personal info
        'beneficiary_sex.required'              => 'Please select the beneficiary\'s sex.',
        'beneficiary_dob.required'              => 'Beneficiary\'s date of birth is required.',
        'beneficiary_dob.date'                  => 'Please enter a valid date of birth.',
        'beneficiary_dob.before'                => 'Beneficiary\'s date of birth must be in the past.',

        // Beneficiary address
        'beneficiary_province.required'         => 'Please select the beneficiary\'s province.',
        'beneficiary_city_municipality.required' => 'Please select the beneficiary\'s city or municipality.',
        'beneficiary_barangay.required'         => 'Please select the beneficiary\'s barangay.',
        'beneficiary_street.required'           => 'Please enter the beneficiary\'s house number and street.',

        // Documents
        'documents.required'                    => 'Please capture all required documents.',
        'documents.*.mimes'                     => 'Documents must be submitted as PDF files.',
        'documents.*.max'                       => 'Each document file must not exceed the allowed size limit.',
    ];
}
```

---

### 2.2 `ResubmitDocumentsRequest`

**File:** `app/Http/Requests/Public/ResubmitDocumentsRequest.php`
**Used by:** `Public/ApplicationController@resubmit`

```php
public function rules(): array
{
    return [
        'reference_code' => [
            'required',
            'string',
            'max:20',
            Rule::exists('applications', 'reference_code')
                ->where('status', 'returned_to_applicant'),
        ],
        'documents'   => ['required', 'array', 'min:1'],
        'documents.*' => [
            'required',
            'file',
            'mimes:pdf',
            'max:' . $this->getMaxFileSizeKb(),
        ],
    ];
}

public function messages(): array
{
    return [
        'reference_code.required'   => 'Reference code is required.',
        'reference_code.exists'     => 'No application found with this reference code, or this application is not awaiting resubmission.',
        'documents.required'        => 'Please capture the required documents.',
        'documents.min'             => 'Please capture at least one document.',
        'documents.*.required'      => 'Please capture this document.',
        'documents.*.mimes'         => 'Documents must be submitted as PDF files.',
        'documents.*.max'           => 'Document file size exceeds the allowed limit.',
    ];
}
```

---

## SECTION 3 — Auth Forms

---

### 3.1 `LoginRequest`

**File:** `app/Http/Requests/Auth/LoginRequest.php`

```php
public function rules(): array
{
    return [
        'email'    => ['required', 'string', 'email:rfc', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'max:255'],
    ];
}

protected function prepareForValidation(): void
{
    $this->merge([
        'email' => strtolower(trim($this->email ?? '')),
    ]);
}

public function messages(): array
{
    return [
        'email.required'    => 'Email address is required.',
        'email.email'       => 'Please enter a valid email address.',
        'password.required' => 'Password is required.',
        // Note: Do NOT give specific "wrong password" vs "wrong email"
        // messages — this leaks account existence information
        // Laravel Fortify handles the generic "credentials do not match" message
    ];
}
```

---

### 3.2 `OtpChallengeRequest`

**File:** `app/Http/Requests/Auth/OtpChallengeRequest.php`

```php
public function rules(): array
{
    return [
        'otp' => [
            'required',
            'string',
            'digits:6',         // exactly 6 numeric digits
            'max:6',
        ],
    ];
}

public function messages(): array
{
    return [
        'otp.required' => 'Please enter the 6-digit code sent to your email.',
        'otp.digits'   => 'The OTP must be exactly 6 digits.',
    ];
}
```

---

### 3.3 `ForgotPasswordRequest`

```php
public function rules(): array
{
    return [
        'email' => ['required', 'email:rfc', 'max:255'],
    ];
}

protected function prepareForValidation(): void
{
    $this->merge(['email' => strtolower(trim($this->email ?? ''))]);
}

public function messages(): array
{
    return [
        'email.required' => 'Email address is required.',
        'email.email'    => 'Please enter a valid email address.',
        // Do NOT say "email not found" — leaks account existence
    ];
}
```

---

### 3.4 `ResetPasswordRequest`

```php
public function rules(): array
{
    return [
        'token'                 => ['required', 'string'],
        'email'                 => ['required', 'email:rfc', 'max:255'],
        'password'              => [
            'required',
            'string',
            'confirmed',
            Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised(),
        ],
        'password_confirmation' => ['required', 'string'],
    ];
}

public function messages(): array
{
    return [
        'password.required'  => 'New password is required.',
        'password.min'       => 'Password must be at least 12 characters.',
        'password.confirmed' => 'Password confirmation does not match.',
        'password.mixed_case' => 'Password must include both uppercase and lowercase letters.',
        'password.numbers'   => 'Password must include at least one number.',
        'password.symbols'   => 'Password must include at least one special character.',
        'password.uncompromised' => 'This password has appeared in a data breach. Please choose a different password.',
    ];
}
```

---

## SECTION 4 — AICS Staff Forms

---

### 4.1 `ApproveApplicationRequest` (AICS)

**File:** `app/Http/Requests/Aics/ApproveApplicationRequest.php`

```php
public function rules(): array
{
    return [
        'remarks' => ['nullable', 'string', 'max:1000'],
    ];
}

protected function prepareForValidation(): void
{
    if ($this->has('remarks') && $this->remarks) {
        $this->merge(['remarks' => trim(strip_tags($this->remarks))]);
    }
}

// Also validate application state in controller (not Form Request):
// Application must have status = 'submitted'
// Throw 422 if status is not 'submitted' — prevents double-processing

public function messages(): array
{
    return [
        'remarks.max' => 'Remarks must not exceed 1,000 characters.',
    ];
}
```

---

### 4.2 `ReturnApplicationRequest` (AICS)

**File:** `app/Http/Requests/Aics/ReturnApplicationRequest.php`

```php
public function rules(): array
{
    return [
        'remarks' => ['required', 'string', 'min:10', 'max:1000'],
        'resubmission_docs_required' => [
            'required',
            'array',
            'min:1',
        ],
        'resubmission_docs_required.*' => [
            'required',
            'uuid',
            Rule::exists('required_documents', 'id'),
        ],
    ];
}

protected function prepareForValidation(): void
{
    if ($this->has('remarks')) {
        $this->merge(['remarks' => trim(strip_tags($this->remarks ?? ''))]);
    }
}

public function messages(): array
{
    return [
        'remarks.required'                        => 'Please provide remarks explaining why the application is being returned.',
        'remarks.min'                             => 'Remarks must be at least 10 characters.',
        'remarks.max'                             => 'Remarks must not exceed 1,000 characters.',
        'resubmission_docs_required.required'     => 'Please select at least one document that needs resubmission.',
        'resubmission_docs_required.min'          => 'Please select at least one document that needs resubmission.',
        'resubmission_docs_required.*.exists'     => 'One or more selected documents are invalid.',
    ];
}
```

---

### 4.3 `CreateAssistanceCodeRequest`

**File:** `app/Http/Requests/Aics/CreateAssistanceCodeRequest.php`

```php
public function rules(): array
{
    return [
        'assistance_code_reference_id' => [
            'required',
            'uuid',
            Rule::exists('assistance_code_references', 'id')
                ->where('is_active', true),
        ],
        'amount' => [
            'required',
            'numeric',
            'min:1',
            'max:999999.99',
            'regex:/^\d+(\.\d{1,2})?$/',  // max 2 decimal places
        ],
    ];
}

protected function prepareForValidation(): void
{
    if ($this->has('amount')) {
        // Strip commas (user might type 5,000.00)
        $this->merge([
            'amount' => str_replace(',', '', $this->amount ?? ''),
        ]);
    }
}

public function messages(): array
{
    return [
        'assistance_code_reference_id.required' => 'Please select an assistance code type.',
        'assistance_code_reference_id.exists'   => 'The selected assistance code type is not available.',
        'amount.required'                        => 'Please enter the assistance amount.',
        'amount.numeric'                         => 'Assistance amount must be a valid number.',
        'amount.min'                             => 'Assistance amount must be greater than zero.',
        'amount.max'                             => 'Assistance amount must not exceed ₱999,999.99.',
        'amount.regex'                           => 'Assistance amount must have at most 2 decimal places.',
    ];
}
```

---

## SECTION 5 — MSWDO Forms

---

### 5.1 `ApproveApplicationRequest` (MSWDO)

**File:** `app/Http/Requests/Mswdo/ApproveApplicationRequest.php`

```php
public function rules(): array
{
    return [
        'social_case_study' => [
            'required',
            'file',
            'mimes:pdf',
            'max:20480',  // 20MB — case study can be multi-page
        ],
        'page_count' => [
            'required',
            'integer',
            'min:1',
            'max:20',
        ],
        'remarks' => ['nullable', 'string', 'max:1000'],
    ];
}

protected function prepareForValidation(): void
{
    if ($this->has('remarks') && $this->remarks) {
        $this->merge(['remarks' => trim(strip_tags($this->remarks))]);
    }
    if ($this->has('page_count')) {
        $this->merge(['page_count' => (int) $this->page_count]);
    }
}

public function messages(): array
{
    return [
        'social_case_study.required'  => 'Please capture the social case study document.',
        'social_case_study.mimes'     => 'The social case study must be a PDF file.',
        'social_case_study.max'       => 'The social case study file must not exceed 20MB.',
        'page_count.required'         => 'Page count is required.',
        'page_count.min'              => 'Page count must be at least 1.',
        'page_count.max'              => 'Page count must not exceed 20.',
        'remarks.max'                 => 'Remarks must not exceed 1,000 characters.',
    ];
}
```

---

### 5.2 `ReturnApplicationRequest` (MSWDO)

Same as AICS `ReturnApplicationRequest` — application status check
in controller must verify `status = 'mswdo_review'`.

---

### 5.3 `CreateVoucherRequest`

**File:** `app/Http/Requests/Mswdo/CreateVoucherRequest.php`

```php
public function rules(): array
{
    return [
        'voucher_file' => [
            'required',
            'file',
            'mimes:pdf',
            'max:20480',
        ],
        'page_count' => [
            'required',
            'integer',
            'min:1',
            'max:10',
        ],
        'adjustment_remarks' => ['nullable', 'string', 'max:1000'],
    ];
}

protected function prepareForValidation(): void
{
    if ($this->has('adjustment_remarks') && $this->adjustment_remarks) {
        $this->merge([
            'adjustment_remarks' => trim(strip_tags($this->adjustment_remarks))
        ]);
    }
}

public function messages(): array
{
    return [
        'voucher_file.required'  => 'Please capture the voucher document.',
        'voucher_file.mimes'     => 'The voucher must be a PDF file.',
        'voucher_file.max'       => 'The voucher file must not exceed 20MB.',
        'page_count.required'    => 'Page count is required.',
        'page_count.min'         => 'Page count must be at least 1.',
        'adjustment_remarks.max' => 'Adjustment remarks must not exceed 1,000 characters.',
    ];
}
```

---

## SECTION 6 — Accountant Forms

---

### 6.1 `ApproveVoucherRequest`

**File:** `app/Http/Requests/Accountant/ApproveVoucherRequest.php`

```php
public function rules(): array
{
    return [
        'remarks' => ['nullable', 'string', 'max:1000'],
    ];
}

protected function prepareForValidation(): void
{
    if ($this->has('remarks') && $this->remarks) {
        $this->merge(['remarks' => trim(strip_tags($this->remarks))]);
    }
}

public function messages(): array
{
    return [
        'remarks.max' => 'Remarks must not exceed 1,000 characters.',
    ];
}
```

---

### 6.2 `ReturnVoucherRequest`

**File:** `app/Http/Requests/Accountant/ReturnVoucherRequest.php`

```php
public function rules(): array
{
    return [
        'remarks' => ['required', 'string', 'min:10', 'max:1000'],
    ];
}

protected function prepareForValidation(): void
{
    $this->merge(['remarks' => trim(strip_tags($this->remarks ?? ''))]);
}

public function messages(): array
{
    return [
        'remarks.required' => 'Please provide a reason for returning the voucher.',
        'remarks.min'      => 'Remarks must be at least 10 characters.',
        'remarks.max'      => 'Remarks must not exceed 1,000 characters.',
    ];
}
```

---

## SECTION 7 — Treasurer Forms

---

### 7.1 `AcknowledgeVoucherRequest`

**File:** `app/Http/Requests/Treasurer/AcknowledgeVoucherRequest.php`

```php
public function rules(): array
{
    return [
        'remarks' => ['nullable', 'string', 'max:1000'],
    ];
}

protected function prepareForValidation(): void
{
    if ($this->has('remarks') && $this->remarks) {
        $this->merge(['remarks' => trim(strip_tags($this->remarks))]);
    }
}

public function messages(): array
{
    return [
        'remarks.max' => 'Remarks must not exceed 1,000 characters.',
    ];
}
```

---

### 7.2 `HoldChequeRequest`

**File:** `app/Http/Requests/Treasurer/HoldChequeRequest.php`

```php
public function rules(): array
{
    return [
        'remarks' => ['required', 'string', 'min:10', 'max:1000'],
    ];
}

protected function prepareForValidation(): void
{
    $this->merge(['remarks' => trim(strip_tags($this->remarks ?? ''))]);
}

public function messages(): array
{
    return [
        'remarks.required' => 'Please provide a reason for placing this application on hold.',
        'remarks.min'      => 'Remarks must be at least 10 characters.',
        'remarks.max'      => 'Remarks must not exceed 1,000 characters.',
    ];
}
```

---

## SECTION 8 — Admin Forms

---

### 8.1 `CreateUserRequest`

**File:** `app/Http/Requests/Admin/CreateUserRequest.php`

```php
public function rules(): array
{
    return [
        'first_name'     => ['required', 'string', 'min:1', 'max:100',
                             'regex:/^[\p{L}\s\'\-\.]+$/u'],
        'last_name'      => ['required', 'string', 'min:1', 'max:100',
                             'regex:/^[\p{L}\s\'\-\.]+$/u'],
        'middle_name'    => ['nullable', 'string', 'max:100',
                             'regex:/^[\p{L}\s\'\-\.]+$/u'],
        'name_extension' => ['nullable', Rule::in(['Jr.', 'Sr.', 'II', 'III', 'IV', 'V'])],
        'email'          => ['required', 'email:rfc,dns', 'max:255',
                             Rule::unique('users', 'email')->whereNull('deleted_at')],
        'role'           => ['required', Rule::in([
            'admin', 'aics_staff', 'mswdo', 'accountant', 'treasurer', 'mayors_office',
        ])],
        'password'       => [
            'required', 'string', 'confirmed',
            Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised(),
        ],
        'password_confirmation' => ['required', 'string'],
    ];
}

protected function prepareForValidation(): void
{
    $this->merge([
        'first_name'  => preg_replace('/\s+/', ' ', trim(strip_tags($this->first_name ?? ''))),
        'last_name'   => preg_replace('/\s+/', ' ', trim(strip_tags($this->last_name ?? ''))),
        'middle_name' => $this->middle_name
            ? preg_replace('/\s+/', ' ', trim(strip_tags($this->middle_name)))
            : null,
        'name_extension' => $this->name_extension ?: null,
        'email'       => strtolower(trim($this->email ?? '')),
    ]);
}

public function messages(): array
{
    return [
        'first_name.required'    => 'First name is required.',
        'first_name.regex'       => 'First name may only contain letters, spaces, apostrophes, and hyphens.',
        'last_name.required'     => 'Last name is required.',
        'last_name.regex'        => 'Last name may only contain letters, spaces, apostrophes, and hyphens.',
        'middle_name.regex'      => 'Middle name may only contain letters, spaces, apostrophes, and hyphens.',
        'name_extension.in'      => 'Please select a valid name extension.',
        'email.required'         => 'Email address is required.',
        'email.email'            => 'Please enter a valid email address.',
        'email.unique'           => 'This email address is already registered in the system.',
        'role.required'          => 'Please select a role for this user.',
        'role.in'                => 'Please select a valid role.',
        'password.required'      => 'Password is required.',
        'password.min'           => 'Password must be at least 12 characters.',
        'password.confirmed'     => 'Password confirmation does not match.',
        'password.mixed_case'    => 'Password must include both uppercase and lowercase letters.',
        'password.numbers'       => 'Password must include at least one number.',
        'password.symbols'       => 'Password must include at least one special character.',
        'password.uncompromised' => 'This password has appeared in a known data breach. Please choose a different password.',
        'password_confirmation.required' => 'Please confirm the password.',
    ];
}
```

---

### 8.2 `UpdateUserRequest`

**File:** `app/Http/Requests/Admin/UpdateUserRequest.php`

Same as `CreateUserRequest` with these differences:
- `password` becomes nullable (empty = keep existing)
- `email` unique rule excludes the current user:
  ```php
  Rule::unique('users', 'email')
      ->ignore($this->route('user'))
      ->whereNull('deleted_at')
  ```
- `password_confirmation` is `required_with:password`

---

### 8.3 `UpdateSystemSettingRequest`

**File:** `app/Http/Requests/Admin/UpdateSystemSettingRequest.php`

```php
public function rules(): array
{
    return [
        'group'           => ['required', Rule::in(['branding', 'sms', 'notifications', 'application'])],
        'settings'        => ['required', 'array'],
        'settings.*'      => ['nullable', 'string', 'max:5000'],

        // Branding-specific
        'settings.system_name'      => ['nullable', 'string', 'max:100'],
        'settings.primary_color'    => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
        'settings.secondary_color'  => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],

        // Application-specific
        'settings.max_file_size_kb' => ['nullable', 'integer', 'min:512', 'max:51200'],
        'settings.allowed_mime_types' => ['nullable', 'string', 'max:500'],
    ];
}

protected function prepareForValidation(): void
{
    if (is_array($this->settings)) {
        $sanitized = [];
        foreach ($this->settings as $key => $value) {
            // Whitelist allowed setting keys — prevent arbitrary key injection
            $allowedKeys = [
                'system_name', 'system_logo', 'primary_color', 'secondary_color',
                'sms_api_key', 'sms_sender_name', 'sms_api_endpoint',
                'sms_template_submission_complete', 'sms_template_under_review',
                'sms_template_resubmission_needed', 'sms_template_cheque_claiming',
                'max_file_size_kb', 'allowed_mime_types',
            ];
            if (in_array($key, $allowedKeys)) {
                $sanitized[$key] = is_string($value) ? trim(strip_tags($value)) : $value;
            }
        }
        $this->merge(['settings' => $sanitized]);
    }
}

public function messages(): array
{
    return [
        'group.required'                   => 'Setting group is required.',
        'group.in'                         => 'Invalid setting group.',
        'settings.required'                => 'No settings provided.',
        'settings.system_name.max'         => 'System name must not exceed 100 characters.',
        'settings.primary_color.regex'     => 'Primary color must be a valid hex color code (e.g. #1B4F72).',
        'settings.secondary_color.regex'   => 'Secondary color must be a valid hex color code.',
        'settings.max_file_size_kb.min'    => 'Maximum file size must be at least 512 KB.',
        'settings.max_file_size_kb.max'    => 'Maximum file size must not exceed 50 MB.',
    ];
}
```

---

## SECTION 9 — Shared Forms

---

### 9.1 `UpdateAccountRequest`

**File:** `app/Http/Requests/Shared/UpdateAccountRequest.php`
**Used by:** All roles — Account Settings

```php
public function rules(): array
{
    return [
        'first_name'     => ['required', 'string', 'min:1', 'max:100',
                             'regex:/^[\p{L}\s\'\-\.]+$/u'],
        'last_name'      => ['required', 'string', 'min:1', 'max:100',
                             'regex:/^[\p{L}\s\'\-\.]+$/u'],
        'middle_name'    => ['nullable', 'string', 'max:100',
                             'regex:/^[\p{L}\s\'\-\.]+$/u'],
        'name_extension' => ['nullable', Rule::in(['Jr.', 'Sr.', 'II', 'III', 'IV', 'V'])],
        'email'          => [
            'required', 'email:rfc,dns', 'max:255',
            Rule::unique('users', 'email')
                ->ignore(auth()->id())
                ->whereNull('deleted_at'),
        ],
        'current_password' => ['required_with:password', 'current_password'],
        'password' => [
            'nullable', 'string', 'confirmed',
            Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised(),
        ],
        'password_confirmation' => ['required_with:password', 'string'],
        'profile_picture' => [
            'nullable', 'file',
            'mimes:jpg,jpeg,png',
            'max:2048',  // 2MB — profile pictures only
        ],
    ];
}

protected function prepareForValidation(): void
{
    $this->merge([
        'first_name'     => preg_replace('/\s+/', ' ', trim(strip_tags($this->first_name ?? ''))),
        'last_name'      => preg_replace('/\s+/', ' ', trim(strip_tags($this->last_name ?? ''))),
        'middle_name'    => $this->middle_name
            ? preg_replace('/\s+/', ' ', trim(strip_tags($this->middle_name)))
            : null,
        'name_extension' => $this->name_extension ?: null,
        'email'          => strtolower(trim($this->email ?? '')),
    ]);
}

public function messages(): array
{
    return [
        'first_name.required'      => 'First name is required.',
        'first_name.regex'         => 'First name may only contain letters, spaces, apostrophes, and hyphens.',
        'last_name.required'       => 'Last name is required.',
        'last_name.regex'          => 'Last name may only contain letters, spaces, apostrophes, and hyphens.',
        'email.required'           => 'Email address is required.',
        'email.unique'             => 'This email address is already used by another account.',
        'current_password.required_with' => 'Current password is required to set a new password.',
        'current_password.current_password' => 'Your current password is incorrect.',
        'password.min'             => 'New password must be at least 12 characters.',
        'password.confirmed'       => 'Password confirmation does not match.',
        'password.mixed_case'      => 'Password must include both uppercase and lowercase letters.',
        'password.numbers'         => 'Password must include at least one number.',
        'password.symbols'         => 'Password must include at least one special character.',
        'password.uncompromised'   => 'This password has appeared in a known data breach. Please choose a different password.',
        'profile_picture.mimes'    => 'Profile picture must be a JPG or PNG image.',
        'profile_picture.max'      => 'Profile picture must not exceed 2MB.',
    ];
}
```

---

## SECTION 10 — Validation Controller (Live Checks)

**File:** `app/Http/Controllers/ValidationController.php`
**Routes:** Public + Auth-gated `/validate/*` endpoints

```php
public function referenceCode(Request $request): JsonResponse
{
    // Sanitize before query
    $code = strtoupper(trim($request->input('value', '')));

    // Validate format before hitting DB
    if (!preg_match('/^GMN-\d{4}-[A-Z0-9]{6}$/', $code)) {
        return response()->json([
            'valid'   => false,
            'message' => 'Reference code format is invalid (e.g. GMN-2024-XXXXXX).',
        ]);
    }

    $exists = Application::where('reference_code', $code)->exists();

    return response()->json([
        'valid'   => $exists,
        'message' => $exists ? null : 'No application found with this reference code.',
    ]);
}

public function phone(Request $request): JsonResponse
{
    $phone = preg_replace('/[^\d+]/', '', $request->input('value', ''));

    // Validate format
    if (!preg_match('/^(09|\+639)\d{9}$/', $phone)) {
        return response()->json([
            'valid'   => false,
            'warning' => null,
            'message' => 'Please enter a valid Philippine mobile number.',
        ]);
    }

    $hasActive = Application::where('claimant_phone', $phone)
        ->whereNotIn('status', ['claimed'])
        ->exists();

    return response()->json([
        'valid'   => true,  // phone can have multiple applications
        'warning' => $hasActive
            ? 'This phone number already has an active application in the system.'
            : null,
    ]);
}

public function email(Request $request): JsonResponse
{
    $email     = strtolower(trim($request->input('value', '')));
    $excludeId = $request->input('exclude_id');

    // Basic format check before DB
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return response()->json(['valid' => false, 'message' => 'Invalid email format.']);
    }

    $taken = User::where('email', $email)
        ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
        ->whereNull('deleted_at')
        ->exists();

    return response()->json([
        'valid'   => !$taken,
        'message' => $taken ? 'This email address is already registered.' : null,
    ]);
}
```

---

## SECTION 11 — Controller-Level State Validation

Form Requests validate field values. Controllers must also validate
**application workflow state** — preventing double-processing if
someone submits a form twice or hits the endpoint out of sequence.

Add these checks at the top of every mutating controller method:

```php
// Aics/ApplicationController@approve
if ($application->status !== 'submitted') {
    return redirect()->back()->with('error',
        'This application has already been processed and cannot be approved again.'
    );
}

// Mswdo/ApplicationController@approve
if ($application->status !== 'mswdo_review') {
    return redirect()->back()->with('error',
        'This application is not currently awaiting MSWDO review.'
    );
}

// Aics/AssistanceCodeController@store
if ($application->status !== 'assistance_coding') {
    return redirect()->back()->with('error',
        'This application is not ready for assistance coding.'
    );
}

// Mswdo/VoucherController@store
if ($application->status !== 'voucher_creation') {
    return redirect()->back()->with('error',
        'A voucher can only be created after an assistance code has been assigned.'
    );
}

// Accountant/VoucherController@approve
if ($application->status !== 'voucher_checking') {
    return redirect()->back()->with('error',
        'This voucher is not currently awaiting accountant review.'
    );
}

// Treasurer/ChequeController@acknowledge
if ($application->status !== 'with_treasurer') {
    return redirect()->back()->with('error',
        'This application is not currently with the Treasurer.'
    );
}
```

---

## SECTION 12 — What Is Already Protected (Do Not Duplicate)

| Protection | How It's Already Handled |
|---|---|
| SQL injection | Eloquent ORM — parameterized queries throughout |
| XSS in rendered output | Vue 3 auto-escapes all template bindings |
| CSRF | Laravel CSRF middleware on all web routes |
| Mass assignment | Eloquent `$fillable` arrays on all models |
| Unauthorized access | `RoleMiddleware` + Laravel Policies |
| Session hijacking | Database session driver + HTTPS in production |
| File execution via upload | Files go to Supabase Storage — never served through PHP |
| Sensitive field exposure | Eloquent `encrypted` cast on PII columns |
| Audit trail tampering | MySQL `REVOKE UPDATE, DELETE` on `audit_logs` and `reviews` |

---

## SECTION 13 — PROCESS.md Additions

Add these items to Phase 3 (Controllers) and Phase 5 (Testing):

```
### Phase 3 Additions

- [ ] Create all Form Request classes per this spec
      (Section 2–9 above — one file per form)
- [ ] Add prepareForValidation() to every Form Request with text fields
- [ ] Add messages() to every Form Request
- [ ] Add controller-level workflow state checks (Section 11)
      to every mutating controller method
- [ ] Whitelist sort/filter params in all index controller methods
      (Section 1.4) — never pass raw query params to orderBy()
- [ ] Add setting key whitelist to UpdateSystemSettingRequest
      to prevent arbitrary key injection

### Phase 5 Additions — Validation Testing

- [ ] Submit Apply form with empty fields — verify all required errors
- [ ] Submit Apply form with invalid phone (09123) — verify phone regex error
- [ ] Submit Apply form with HTML in name fields (<script>alert(1)</script>)
      — verify strip_tags removes it before saving
- [ ] Submit Apply form as Representative without Authorization Letter
      — verify Authorization Letter required error
- [ ] Submit Apply form as Spouse without Authorization Letter
      — verify Authorization Letter is NOT required
- [ ] Submit wrong file type (jpg instead of pdf) as supporting doc
      — verify mimes validation error
- [ ] Submit file over size limit — verify max error
- [ ] Submit OTP with 5 digits — verify digits:6 error
- [ ] Submit reset password shorter than 12 chars — verify min error
- [ ] Submit reset password without symbol — verify symbols error
- [ ] Submit createUser with duplicate email — verify unique error
- [ ] Submit approve application when status is not 'submitted'
      — verify controller-level state check blocks it
- [ ] Test sort_by parameter injection: ?sort_by=id;DROP TABLE users
      — verify whitelist prevents this from reaching orderBy()
- [ ] Submit remarks with HTML tags — verify strip_tags removes them
- [ ] Verify amounts with commas (5,000.00) are normalized correctly
- [ ] Submit system settings with unknown key not in whitelist
      — verify the key is silently dropped
```

---

*Document prepared for AI consumption and development reference — ALALAY System,
Municipality of General Mamerto Natividad, Nueva Ecija.*

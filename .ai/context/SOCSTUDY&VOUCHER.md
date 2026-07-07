# ALALAY: Social Case Study & Voucher Specification
**Document Capture, Database Schema, and Staff Review**
**Municipality of General Mamerto Natividad, Nueva Ecija**

---

## Overview

This document covers two specific document types in ALALAY that are uploaded
by MSWDO staff (not applicants) during the workflow:

1. **Social Case Study** — uploaded by MSWDO after reviewing and approving
   an application. The MSWDO staff member physically has the printed and
   completed social case study form and scans it using the DocumentScanner
   component.

2. **Voucher** — uploaded by MSWDO after receiving the assistance code from
   AICS Staff. The MSWDO staff member prepares the voucher externally
   (using whatever tool they currently use — Word, Excel, or a printed form)
   then scans the physical document using the DocumentScanner component.

Both are scanned as A4 portrait PDFs using the same scanner pipeline
defined in `alalay_document_scanner_spec.md`.

---

## Key Business Rules

| Rule | Social Case Study | Voucher |
|---|---|---|
| How many per application | **One only** — strictly 1:1 with application | **One active at a time** — but version is tracked when re-created |
| Who uploads | MSWDO staff only | MSWDO staff only |
| When uploaded | During MSWDO Review stage (Stage 2) | During Voucher Creation stage (Stage 4) |
| What triggers it | MSWDO clicks "Next" on the review page | MSWDO opens Voucher Creation page |
| What happens after | Application moves to `assistance_coding` | Application moves to `voucher_checking` |
| Can it be replaced | No — one per application, permanent | Yes — Accountant can return voucher for re-creation; version increments, previous record kept |
| Scanner config | `captureType="multi"`, `scannerSize="a4"` | `captureType="single"`, `scannerSize="a4"` |
| PDF output | Always A4 portrait, multi-page supported | Always A4 portrait, single page |

---

## Social Case Study

### What It Is

The Social Case Study is a DSWD-standard form completed by the MSWDO
social worker during their assessment of the applicant and beneficiary.
It documents the family background, socioeconomic situation, presenting
problem, and the social worker's recommendation for assistance.

In GMN's MSWDO office, this is a printed physical form — either filled
by hand or typewritten — that the social worker completes after conducting
a home visit or interview with the applicant.

It is **1 to 3 pages** depending on case complexity. The MSWDO staff
member scans all pages sequentially into one multi-page PDF using the
DocumentScanner component's `multi` capture type.

### Scanner Configuration

```javascript
// In Mswdo/Applications/Review.vue — Social Case Study step
<DocumentScanner
  docName="Social Case Study"
  :required="true"
  captureType="multi"
  scannerSize="a4"
  v-model="form.social_case_study"
/>
```

### Database Table: `social_case_studies`

#### Current Schema (from schema dictionary)

```
case_id          uuid PK
application_id   uuid FK → applications.id (UNIQUE — one per application)
conducted_by     uuid FK → users.id (MSWDO user)
file_name        varchar(255)
file_path        text
file_size        int unsigned
mime_type        varchar(100)
created_at       timestamp
updated_at       timestamp
```

#### Required Changes

Add these columns to support metadata display for staff reviewers:

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| `page_count` | `tinyint unsigned` | NOT NULL, default 1 | Number of pages in the PDF — shown to reviewers so they know how many pages to expect |
| `conducted_at` | `timestamp` | NOT NULL, default `now()` | When the social case study was conducted — separate from `created_at` which is the upload timestamp |

Remove these columns (redundant — `conducted_at` replaces `created_at`
for business meaning, and `updated_at` is unnecessary since this record
is never updated after creation):

> **Do not actually remove `created_at` and `updated_at`** — they are
> Laravel defaults and removing them causes issues. Keep them but use
> `conducted_at` as the business-meaningful timestamp shown in the UI.

#### Final Column List After Changes

```
case_id          uuid PK
application_id   uuid FK → applications.id UNIQUE
conducted_by     uuid FK → users.id
file_name        varchar(255)
file_path        text             ← Supabase Storage path
file_size        int unsigned     ← bytes
mime_type        varchar(100)     ← always 'application/pdf'
page_count       tinyint unsigned ← NEW — number of scanned pages
conducted_at     timestamp        ← NEW — when case study was conducted
created_at       timestamp        ← upload timestamp (Laravel default)
updated_at       timestamp        ← Laravel default, keep but not shown in UI
```

#### Updated Migration

```php
Schema::create('social_case_studies', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('application_id')->unique(); // one per application
    $table->uuid('conducted_by');
    $table->string('file_name');
    $table->text('file_path');
    $table->unsignedInteger('file_size');
    $table->string('mime_type', 100)->default('application/pdf');
    $table->unsignedTinyInteger('page_count')->default(1); // NEW
    $table->timestamp('conducted_at');                      // NEW
    $table->timestamps();

    $table->foreign('application_id')
          ->references('id')
          ->on('applications')
          ->onDelete('restrict');

    $table->foreign('conducted_by')
          ->references('id')
          ->on('users')
          ->onDelete('restrict');
});
```

#### Model: `SocialCaseStudy.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SocialCaseStudy extends Model
{
    use HasUuids;

    protected $fillable = [
        'application_id',
        'conducted_by',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'page_count',
        'conducted_at',
    ];

    protected $casts = [
        'conducted_at' => 'datetime',
        'file_size'    => 'integer',
        'page_count'   => 'integer',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function conductedBy()
    {
        return $this->belongsTo(User::class, 'conducted_by');
    }

    // Human-readable file size for display
    public function getFileSizeLabelAttribute(): string
    {
        $kb = $this->file_size / 1024;
        if ($kb < 1024) return round($kb, 1) . ' KB';
        return round($kb / 1024, 2) . ' MB';
    }
}
```

### What Staff See When Reviewing

When AICS Staff open the Assistance Coding page for an application, they
see the social case study PDF alongside its metadata:

```
┌─────────────────────────────────────────────┐
│  Social Case Study                          │
│  ─────────────────────────────────────────  │
│  Uploaded by:   Ana Reyes (MSWDO)           │
│  Conducted on:  June 3, 2024 at 2:00 PM    │
│  Pages:         2                           │
│  File size:     284 KB                      │
│  ─────────────────────────────────────────  │
│  [  PDF viewer renders here — inline  ]     │
│                                             │
└─────────────────────────────────────────────┘
```

The metadata comes from the `social_case_studies` table. The PDF is
displayed via the signed URL from SignedUrlService.

### Controller: `Mswdo/ApplicationController@approve`

When MSWDO approves an application and submits the scanned social case
study:

```php
public function approve(ApproveApplicationRequest $request, Application $application): RedirectResponse
{
    $this->authorize('approve', $application);

    // 1. Upload PDF to Supabase Storage via FileUploadService
    $file = $request->file('social_case_study');
    $uploadResult = $this->fileUploadService->upload(
        file: $file,
        disk: 'supabase',
        path: "social_case_studies/{$application->id}",
        filename: "scs_" . now()->format('YmdHis') . ".pdf"
    );

    // 2. Save social case study record
    SocialCaseStudy::create([
        'application_id' => $application->id,
        'conducted_by'   => auth()->id(),
        'file_name'      => $uploadResult['file_name'],
        'file_path'      => $uploadResult['file_path'],
        'file_size'      => $uploadResult['file_size'],
        'mime_type'      => 'application/pdf',
        'page_count'     => $request->input('page_count', 1),
        'conducted_at'   => now(),
    ]);

    // 3. Update application status
    $application->update([
        'status'      => 'assistance_coding',
        'reviewed_by' => auth()->id(),
        'reviewed_at' => now(),
    ]);

    // 4. Insert review trail entry
    Review::create([
        'application_id' => $application->id,
        'reviewed_by'    => auth()->id(),
        'stage'          => 'mswdo_review',
        'decision'       => 'approved',
        'from_status'    => 'mswdo_review',
        'to_status'      => 'assistance_coding',
        'remarks'        => $request->input('remarks'),
        'reviewed_at'    => now(),
    ]);

    // 5. Dispatch SMS notification
    SendSmsJob::dispatch($application, 'application_under_review');

    return redirect()
        ->route('mswdo.applications.index')
        ->with('success', 'Application approved. Social case study uploaded.');
}
```

### Form Request: `ApproveApplicationRequest.php` (MSWDO)

```php
public function rules(): array
{
    return [
        'social_case_study' => 'required|file|mimes:pdf|max:20480', // 20MB max
        'page_count'        => 'required|integer|min:1|max:20',
        'remarks'           => 'nullable|string|max:1000',
    ];
}
```

### Vue: Inertia Props for Social Case Study Review Page

The controller passes this data to `Mswdo/Applications/Review.vue`:

```php
return Inertia::render('Mswdo/Applications/Review', [
    'application'      => $application->load(['category', 'documents.requiredDocument', 'reviews.reviewedBy']),
    'socialCaseStudy'  => $application->socialCaseStudy?->append('file_size_label'),
    // socialCaseStudy is null if not yet uploaded (step 1 — before approval)
    // socialCaseStudy is populated on subsequent views (coded, voucher stages)
]);
```

---

## Voucher

### What It Is

The voucher is a disbursement voucher prepared by the MSWDO office after
receiving the assistance code from AICS Staff. It is created externally
using whatever tool or template the MSWDO office currently uses (Word,
Excel, or a pre-printed form). MSWDO prints and signs it, then scans
it into ALALAY using the DocumentScanner component.

It is a **single-page document** in most cases. If GMN's voucher form
turns out to be multi-page, change `captureType` from `"single"` to
`"multi"` — no other changes needed.

### Version Tracking

When the Accountant returns a voucher for re-creation, the MSWDO creates
a new physical voucher (revised amounts or corrections) and scans it again.
ALALAY **replaces the existing voucher record** with the new scan and
increments the `version` column. The previous file is overwritten in
Supabase Storage. Only the latest voucher version is kept — historical
versions are not retained since the voucher is an internal working document.

> **Why overwrite instead of keeping history:** The voucher is MSWDO's
> internal document. Keeping multiple versions would complicate the
> review flow (which version does the Accountant review?). The `version`
> column and the `reviews` table audit trail already record that a return
> and re-creation happened — the version number is the evidence.

### Scanner Configuration

```javascript
// In Mswdo/Vouchers/Create.vue — Step 2 voucher upload
<DocumentScanner
  docName="Voucher Document"
  :required="true"
  captureType="single"
  scannerSize="a4"
  v-model="form.voucher_file"
/>
```

### Database Table: `vouchers`

#### Current Schema (from schema dictionary)

```
id                   uuid PK
application_id       uuid FK → applications.id
assistance_code_id   uuid FK → assistance_codes.id
prepared_by          uuid FK → users.id
file_name            varchar(255)
file_path            text
file_size            int unsigned
mime_type            varchar(100)
version              tinyint unsigned (default 1)
adjustment_remarks   text nullable
created_at           timestamp
updated_at           timestamp
```

#### Required Changes

Add these columns to support metadata display for staff reviewers and
track re-creation events:

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| `page_count` | `tinyint unsigned` | NOT NULL, default 1 | Number of pages in the voucher PDF |
| `prepared_at` | `timestamp` | NOT NULL, default `now()` | When the voucher was prepared — shown to reviewers as the official voucher date |
| `returned_at` | `timestamp` | NULLABLE | When the voucher was returned by the Accountant — populated on return, cleared on re-creation |
| `returned_by` | `uuid` | NULLABLE, FK → `users.id` | Which Accountant returned it |

#### Final Column List After Changes

```
id                   uuid PK
application_id       uuid FK → applications.id
assistance_code_id   uuid FK → assistance_codes.id
prepared_by          uuid FK → users.id (MSWDO)
file_name            varchar(255)
file_path            text             ← Supabase Storage path
file_size            int unsigned     ← bytes
mime_type            varchar(100)     ← always 'application/pdf'
version              tinyint unsigned ← starts at 1, increments on re-creation
page_count           tinyint unsigned ← NEW
prepared_at          timestamp        ← NEW — official voucher date
adjustment_remarks   text nullable    ← notes on what was adjusted
returned_at          timestamp nullable ← NEW — when returned by Accountant
returned_by          uuid nullable FK → users.id ← NEW — which Accountant returned it
created_at           timestamp        ← Laravel default
updated_at           timestamp        ← Laravel default
```

#### Updated Migration

```php
Schema::create('vouchers', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->uuid('application_id');
    $table->uuid('assistance_code_id');
    $table->uuid('prepared_by');
    $table->string('file_name');
    $table->text('file_path');
    $table->unsignedInteger('file_size');
    $table->string('mime_type', 100)->default('application/pdf');
    $table->unsignedTinyInteger('version')->default(1);
    $table->unsignedTinyInteger('page_count')->default(1); // NEW
    $table->timestamp('prepared_at');                       // NEW
    $table->text('adjustment_remarks')->nullable();
    $table->timestamp('returned_at')->nullable();           // NEW
    $table->uuid('returned_by')->nullable();                // NEW
    $table->timestamps();

    $table->foreign('application_id')
          ->references('id')->on('applications')
          ->onDelete('restrict');

    $table->foreign('assistance_code_id')
          ->references('id')->on('assistance_codes')
          ->onDelete('restrict');

    $table->foreign('prepared_by')
          ->references('id')->on('users')
          ->onDelete('restrict');

    $table->foreign('returned_by')
          ->references('id')->on('users')
          ->onDelete('restrict');
});
```

#### Model: `Voucher.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Voucher extends Model
{
    use HasUuids;

    protected $fillable = [
        'application_id',
        'assistance_code_id',
        'prepared_by',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'version',
        'page_count',
        'prepared_at',
        'adjustment_remarks',
        'returned_at',
        'returned_by',
    ];

    protected $casts = [
        'prepared_at'  => 'datetime',
        'returned_at'  => 'datetime',
        'file_size'    => 'integer',
        'page_count'   => 'integer',
        'version'      => 'integer',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function assistanceCode()
    {
        return $this->belongsTo(AssistanceCode::class);
    }

    public function preparedBy()
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function returnedBy()
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function getFileSizeLabelAttribute(): string
    {
        $kb = $this->file_size / 1024;
        if ($kb < 1024) return round($kb, 1) . ' KB';
        return round($kb / 1024, 2) . ' MB';
    }

    public function isReturned(): bool
    {
        return !is_null($this->returned_at);
    }
}
```

### What Staff See When Reviewing

When the Accountant opens the Voucher Review page, they see the voucher
PDF alongside its metadata:

```
┌─────────────────────────────────────────────┐
│  Voucher                                    │
│  ─────────────────────────────────────────  │
│  Prepared by:   Ana Reyes (MSWDO)           │
│  Prepared on:   June 5, 2024 at 9:00 AM    │
│  Version:       2 (re-created after return) │
│  Pages:         1                           │
│  File size:     198 KB                      │
│  ─────────────────────────────────────────  │
│  [  PDF viewer renders here — inline  ]     │
│                                             │
└─────────────────────────────────────────────┘
```

If `version > 1`, also show:
```
│  ⚠ This voucher was re-created. Previous    │
│    version was returned by: Juan dela Cruz  │
│    Returned on: June 4, 2024 at 4:15 PM    │
│    Reason: (adjustment_remarks from review) │
```

### Controller: `Mswdo/VoucherController@store`

When MSWDO submits the scanned voucher (first creation or re-creation):

```php
public function store(CreateVoucherRequest $request, Application $application): RedirectResponse
{
    $this->authorize('create', Voucher::class);

    $file = $request->file('voucher_file');

    // Upload PDF to Supabase Storage
    $existingVoucher = $application->voucher;
    $version = $existingVoucher ? $existingVoucher->version + 1 : 1;

    $uploadResult = $this->fileUploadService->upload(
        file: $file,
        disk: 'supabase',
        path: "vouchers/{$application->id}",
        filename: "voucher_v{$version}_" . now()->format('YmdHis') . ".pdf"
    );

    // Update or create voucher record (one per application)
    $application->voucher()->updateOrCreate(
        ['application_id' => $application->id],
        [
            'assistance_code_id'  => $application->assistanceCode->id,
            'prepared_by'         => auth()->id(),
            'file_name'           => $uploadResult['file_name'],
            'file_path'           => $uploadResult['file_path'],
            'file_size'           => $uploadResult['file_size'],
            'mime_type'           => 'application/pdf',
            'version'             => $version,
            'page_count'          => $request->input('page_count', 1),
            'prepared_at'         => now(),
            'adjustment_remarks'  => $request->input('adjustment_remarks'),
            'returned_at'         => null,  // clear return flag on re-creation
            'returned_by'         => null,  // clear return flag on re-creation
        ]
    );

    // Update application status
    $application->update([
        'status'      => 'voucher_checking',
        'reviewed_by' => auth()->id(),
        'reviewed_at' => now(),
    ]);

    // Insert review trail entry
    Review::create([
        'application_id' => $application->id,
        'reviewed_by'    => auth()->id(),
        'stage'          => 'voucher_creation',
        'decision'       => 'voucher_created',
        'from_status'    => 'voucher_creation',
        'to_status'      => 'voucher_checking',
        'remarks'        => $request->input('adjustment_remarks'),
        'reviewed_at'    => now(),
    ]);

    return redirect()
        ->route('mswdo.vouchers.index')
        ->with('success', "Voucher v{$version} submitted for Accountant review.");
}
```

### Controller: `Accountant/VoucherController@return`

When the Accountant returns a voucher for re-creation:

```php
public function return(ReturnVoucherRequest $request, Application $application): RedirectResponse
{
    $this->authorize('return', $application->voucher);

    // Mark voucher as returned
    $application->voucher->update([
        'returned_at' => now(),
        'returned_by' => auth()->id(),
    ]);

    // Update application status back to voucher_creation
    $application->update([
        'status'      => 'voucher_returned',
        'reviewed_by' => auth()->id(),
        'reviewed_at' => now(),
    ]);

    // Insert review trail entry
    Review::create([
        'application_id' => $application->id,
        'reviewed_by'    => auth()->id(),
        'stage'          => 'voucher_checking',
        'decision'       => 'voucher_returned',
        'from_status'    => 'voucher_checking',
        'to_status'      => 'voucher_returned',
        'remarks'        => $request->input('remarks'),
        'reviewed_at'    => now(),
    ]);

    return redirect()
        ->route('accountant.vouchers.index')
        ->with('success', 'Voucher returned to MSWDO for re-creation.');
}
```

### Form Request: `CreateVoucherRequest.php`

```php
public function rules(): array
{
    return [
        'voucher_file'       => 'required|file|mimes:pdf|max:20480',
        'page_count'         => 'required|integer|min:1|max:10',
        'adjustment_remarks' => 'nullable|string|max:1000',
    ];
}
```

### Vue: Inertia Props for Voucher Creation Page

```php
// Mswdo/VoucherController@show
return Inertia::render('Mswdo/Vouchers/Create', [
    'application'    => $application->load(['category', 'reviews.reviewedBy']),
    'assistanceCode' => $application->assistanceCode->load(['assistanceCodeReference', 'assignedBy']),
    'socialCaseStudy' => $application->socialCaseStudy->append('file_size_label'),
    'existingVoucher' => $application->voucher?->append('file_size_label'),
    // existingVoucher is null on first creation
    // existingVoucher is populated if re-creating after return
    //   — show "You are re-creating Voucher v1. Previous version was returned." warning
]);
```

---

## Signed URL Endpoints

Both documents need a signed URL endpoint so staff can view the PDF
in the inline viewer. Add these to the respective route groups:

```php
// MSWDO — view social case study (own uploads + for assistance coding ref)
Route::get('/applications/{application}/case-study/url',
    [Mswdo\ApplicationController::class, 'caseStudyUrl'])
    ->name('mswdo.applications.case-study-url');

// AICS Staff — view social case study during assistance coding
Route::get('/assistance-codes/{application}/case-study/url',
    [Aics\AssistanceCodeController::class, 'caseStudyUrl'])
    ->name('aics.assistance-codes.case-study-url');

// MSWDO — view voucher reference during creation step 1
Route::get('/vouchers/{application}/url',
    [Mswdo\VoucherController::class, 'voucherUrl'])
    ->name('mswdo.vouchers.url');

// Accountant — view voucher during review
Route::get('/vouchers/{application}/url',
    [Accountant\VoucherController::class, 'voucherUrl'])
    ->name('accountant.vouchers.url');

// Treasurer — view voucher during acknowledgment
Route::get('/cheques/{application}/voucher/url',
    [Treasurer\ChequeController::class, 'voucherUrl'])
    ->name('treasurer.cheques.voucher-url');
```

Each URL endpoint returns a short-lived signed URL:

```php
public function caseStudyUrl(Application $application): \Illuminate\Http\JsonResponse
{
    $this->authorize('view', $application->socialCaseStudy);

    $url = $this->signedUrlService->generate(
        path: $application->socialCaseStudy->file_path,
        ttlMinutes: 30
    );

    return response()->json(['url' => $url]);
}
```

---

## Pages That Display These Documents

### Social Case Study — Displayed On

| Page | Role | Purpose |
|---|---|---|
| `Mswdo/Applications/Review.vue` | MSWDO | Upload step — DocumentScanner component |
| `Aics/AssistanceCodes/Code.vue` | AICS Staff | Reference during assistance coding — PDF viewer |
| `Mswdo/Vouchers/Create.vue` | MSWDO | Reference during voucher creation Step 1 — PDF viewer |

### Voucher — Displayed On

| Page | Role | Purpose |
|---|---|---|
| `Mswdo/Vouchers/Create.vue` | MSWDO | Upload step — DocumentScanner component |
| `Accountant/Vouchers/Review.vue` | Accountant | Review and approve/return — PDF viewer |
| `Treasurer/Cheques/Review.vue` | Treasurer | Acknowledge — PDF viewer |
| `Accountant/Budget/Check.vue` | Accountant | Budget check reference — PDF viewer |

---

## Metadata Display Component

Create a reusable `DocumentMeta.vue` component for displaying the
metadata strip above the PDF viewer on all review pages:

```vue
<!-- resources/js/Components/Application/DocumentMeta.vue -->
<script setup>
defineProps({
  uploadedBy: String,    // full name of uploader
  uploadedAt: String,    // formatted timestamp
  pageCount: Number,
  fileSize: String,      // human-readable e.g. "284 KB"
  version: Number,       // null for social case study
  returnedBy: String,    // null if not returned
  returnedAt: String,    // null if not returned
  returnRemarks: String, // null if not returned
})
</script>
```

Used in all review pages that display social case study or voucher.

---

## Summary of All Changes

### New Columns

| Table | Column | Type | Purpose |
|---|---|---|---|
| `social_case_studies` | `page_count` | `tinyint unsigned` | Number of scanned pages |
| `social_case_studies` | `conducted_at` | `timestamp` | When case study was conducted |
| `vouchers` | `page_count` | `tinyint unsigned` | Number of scanned pages |
| `vouchers` | `prepared_at` | `timestamp` | When voucher was prepared |
| `vouchers` | `returned_at` | `timestamp nullable` | When returned by Accountant |
| `vouchers` | `returned_by` | `uuid nullable FK` | Which Accountant returned it |

### Files to Create or Modify

| File | Action | Change |
|---|---|---|
| `social_case_studies` migration | Modify | Add `page_count`, `conducted_at` |
| `vouchers` migration | Modify | Add `page_count`, `prepared_at`, `returned_at`, `returned_by` |
| `SocialCaseStudy.php` model | Modify | Add new fillable fields, casts, `file_size_label` accessor |
| `Voucher.php` model | Modify | Add new fillable fields, casts, `file_size_label` accessor, `isReturned()` method, `returnedBy()` relationship |
| `Mswdo/ApplicationController.php` | Modify | `approve()` — save `page_count` and `conducted_at` |
| `Mswdo/VoucherController.php` | Modify | `store()` — version increment, `updateOrCreate`, clear return flags |
| `Accountant/VoucherController.php` | Modify | `return()` — set `returned_at` and `returned_by` on voucher |
| `ApproveApplicationRequest.php` (MSWDO) | Modify | Add `page_count` validation rule |
| `CreateVoucherRequest.php` | Modify | Add `page_count` validation rule |
| `ReturnVoucherRequest.php` | Modify | Add `remarks` required rule |
| `DocumentMeta.vue` | Create | Reusable metadata display component |
| `Mswdo/Applications/Review.vue` | Modify | Pass `page_count` with DocumentScanner submission |
| `Mswdo/Vouchers/Create.vue` | Modify | Pass `page_count` + show re-creation warning if `existingVoucher` present |
| `Aics/AssistanceCodes/Code.vue` | Modify | Show `DocumentMeta` + PDF viewer for social case study |
| `Accountant/Vouchers/Review.vue` | Modify | Show `DocumentMeta` + PDF viewer + return warning if v>1 |
| `Treasurer/Cheques/Review.vue` | Modify | Show `DocumentMeta` + PDF viewer |
| `Accountant/Budget/Check.vue` | Modify | Show `DocumentMeta` + PDF viewer |
| `web.php` | Modify | Add signed URL routes for case study and voucher per role |

---

*Document prepared for AI consumption and development reference — ALALAY System,
Municipality of General Mamerto Natividad, Nueva Ecija.*

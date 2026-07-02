# ALALAY: Seeder Data — Assistance Categories & Required Documents
**Municipality of General Mamerto Natividad, Nueva Ecija**
**Source: GMN MSWDO Actual AICS Document Requirements**

---

## Overview

This document contains the exact seed data for:
- `assistance_categories` table — 3 categories
- `required_documents` table — 21 documents across 3 categories

All primary keys use UUIDs generated at seed time via `Str::uuid()`.
Foreign key `category_id` in `required_documents` references `assistance_categories.id`.

---

## Business Rule: Authorization Letter

The Authorization Letter appears in all 3 categories with `is_mandatory = false`.

**When it becomes required (enforced at application form level, not DB level):**
The Authorization Letter must be submitted when the claimant is **not a direct
relative** of the beneficiary. Direct relatives are defined as: spouse, parent,
child, sibling, or grandparent. Any other relationship requires an Authorization
Letter regardless of category.

This rule is enforced in:
- `SubmitApplicationRequest` — validate Authorization Letter is present when
  `claimant_relationship_to_beneficiary` is not in the direct relative list
- Apply page (Vue) — show Authorization Letter upload field as required when
  the relationship field value is not a direct relative
- `is_mandatory = false` in the database because it is conditionally mandatory,
  not always mandatory — the DB flag alone cannot express conditional logic

---

## Assistance Categories

| # | category_name | category_description | is_active |
|---|---|---|---|
| 1 | Medical Assistance | Financial assistance for outpatient medical expenses including consultations, medicines, and laboratory fees. | 1 |
| 2 | Hospital Assistance | Financial assistance for inpatient hospital expenses including hospital bills, medicines, and medical procedures. | 1 |
| 3 | Burial Assistance | Financial assistance for burial and funeral expenses of indigent residents. | 1 |

---

## Required Documents per Category

### Category 1 — Medical Assistance

| doc_name | description | is_mandatory |
|---|---|---|
| Medical Certificate | A certificate issued by a licensed physician confirming the medical condition of the beneficiary. | true |
| Prescription | A prescription issued by a licensed physician for the required medicines or treatment. | true |
| Applicant's Government ID | Any valid government-issued ID of the applicant (claimant). | true |
| Beneficiary's Government ID | Any valid government-issued ID of the beneficiary. | true |
| Applicant's Cedula | Community tax certificate of the applicant (claimant). | true |
| Barangay Indigency | A certificate of indigency issued by the barangay where the beneficiary resides. | true |
| Authorization Letter | A letter authorizing the applicant to claim assistance on behalf of the beneficiary. Required if claimant is not a direct relative. | false |

---

### Category 2 — Hospital Assistance

| doc_name | description | is_mandatory |
|---|---|---|
| Hospital Bill | Official hospital bill or statement of account from the hospital where the beneficiary is or was confined. | true |
| Prescription | A prescription issued by a licensed physician for the required medicines or treatment. | true |
| Medical Certificate/Abstract | A medical certificate or abstract issued by the attending physician summarizing the beneficiary's condition and treatment. | true |
| Applicant's Government ID | Any valid government-issued ID of the applicant (claimant). | true |
| Beneficiary's Government ID | Any valid government-issued ID of the beneficiary. | true |
| Applicant's Cedula | Community tax certificate of the applicant (claimant). | true |
| Barangay Indigency | A certificate of indigency issued by the barangay where the beneficiary resides. | true |
| Authorization Letter | A letter authorizing the applicant to claim assistance on behalf of the beneficiary. Required if claimant is not a direct relative. | false |

---

### Category 3 — Burial Assistance

| doc_name | description | is_mandatory |
|---|---|---|
| Certified Copy of Birth Certificate | A certified true copy of the birth certificate of the deceased beneficiary issued by the PSA or local civil registry. | true |
| Applicant's Government ID | Any valid government-issued ID of the applicant (claimant). | true |
| Applicant's Cedula | Community tax certificate of the applicant (claimant). | true |
| Beneficiary's Barangay Residency | A certificate of residency issued by the barangay confirming the deceased beneficiary was a resident of General Mamerto Natividad. | true |
| Barangay Indigency | A certificate of indigency issued by the barangay on behalf of the bereaved family. | true |
| Authorization Letter | A letter authorizing the applicant to claim assistance on behalf of the bereaved family. Required if claimant is not a direct relative. | false |

---

## Laravel Seeder Implementation

### `AssistanceCategorySeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AssistanceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'id'                   => Str::uuid()->toString(),
                'category_name'        => 'Medical Assistance',
                'category_description' => 'Financial assistance for outpatient medical expenses including consultations, medicines, and laboratory fees.',
                'is_active'            => true,
                'created_at'           => now(),
                'updated_at'           => now(),
            ],
            [
                'id'                   => Str::uuid()->toString(),
                'category_name'        => 'Hospital Assistance',
                'category_description' => 'Financial assistance for inpatient hospital expenses including hospital bills, medicines, and medical procedures.',
                'is_active'            => true,
                'created_at'           => now(),
                'updated_at'           => now(),
            ],
            [
                'id'                   => Str::uuid()->toString(),
                'category_name'        => 'Burial Assistance',
                'category_description' => 'Financial assistance for burial and funeral expenses of indigent residents.',
                'is_active'            => true,
                'created_at'           => now(),
                'updated_at'           => now(),
            ],
        ];

        DB::table('assistance_categories')->insert($categories);
    }
}
```

---

### `RequiredDocumentSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RequiredDocumentSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch category IDs by name — safe regardless of UUID values
        $medical  = DB::table('assistance_categories')->where('category_name', 'Medical Assistance')->value('id');
        $hospital = DB::table('assistance_categories')->where('category_name', 'Hospital Assistance')->value('id');
        $burial   = DB::table('assistance_categories')->where('category_name', 'Burial Assistance')->value('id');

        $documents = [

            // -------------------------------------------------------
            // CATEGORY 1 — Medical Assistance
            // -------------------------------------------------------
            [
                'id'           => Str::uuid()->toString(),
                'category_id'  => $medical,
                'doc_name'     => 'Medical Certificate',
                'doc_description' => 'A certificate issued by a licensed physician confirming the medical condition of the beneficiary.',
                'is_mandatory' => true,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => Str::uuid()->toString(),
                'category_id'  => $medical,
                'doc_name'     => 'Prescription',
                'doc_description' => 'A prescription issued by a licensed physician for the required medicines or treatment.',
                'is_mandatory' => true,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => Str::uuid()->toString(),
                'category_id'  => $medical,
                'doc_name'     => "Applicant's Government ID",
                'doc_description' => 'Any valid government-issued ID of the applicant (claimant).',
                'is_mandatory' => true,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => Str::uuid()->toString(),
                'category_id'  => $medical,
                'doc_name'     => "Beneficiary's Government ID",
                'doc_description' => 'Any valid government-issued ID of the beneficiary.',
                'is_mandatory' => true,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => Str::uuid()->toString(),
                'category_id'  => $medical,
                'doc_name'     => "Applicant's Cedula",
                'doc_description' => 'Community tax certificate of the applicant (claimant).',
                'is_mandatory' => true,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => Str::uuid()->toString(),
                'category_id'  => $medical,
                'doc_name'     => 'Barangay Indigency',
                'doc_description' => 'A certificate of indigency issued by the barangay where the beneficiary resides.',
                'is_mandatory' => true,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => Str::uuid()->toString(),
                'category_id'  => $medical,
                'doc_name'     => 'Authorization Letter',
                'doc_description' => 'A letter authorizing the applicant to claim assistance on behalf of the beneficiary. Required if the claimant is not a direct relative of the beneficiary.',
                'is_mandatory' => false, // conditionally required — enforced in SubmitApplicationRequest
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],

            // -------------------------------------------------------
            // CATEGORY 2 — Hospital Assistance
            // -------------------------------------------------------
            [
                'id'           => Str::uuid()->toString(),
                'category_id'  => $hospital,
                'doc_name'     => 'Hospital Bill',
                'doc_description' => 'Official hospital bill or statement of account from the hospital where the beneficiary is or was confined.',
                'is_mandatory' => true,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => Str::uuid()->toString(),
                'category_id'  => $hospital,
                'doc_name'     => 'Prescription',
                'doc_description' => 'A prescription issued by a licensed physician for the required medicines or treatment.',
                'is_mandatory' => true,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => Str::uuid()->toString(),
                'category_id'  => $hospital,
                'doc_name'     => 'Medical Certificate/Abstract',
                'doc_description' => 'A medical certificate or abstract issued by the attending physician summarizing the beneficiary\'s condition and treatment.',
                'is_mandatory' => true,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => Str::uuid()->toString(),
                'category_id'  => $hospital,
                'doc_name'     => "Applicant's Government ID",
                'doc_description' => 'Any valid government-issued ID of the applicant (claimant).',
                'is_mandatory' => true,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => Str::uuid()->toString(),
                'category_id'  => $hospital,
                'doc_name'     => "Beneficiary's Government ID",
                'doc_description' => 'Any valid government-issued ID of the beneficiary.',
                'is_mandatory' => true,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => Str::uuid()->toString(),
                'category_id'  => $hospital,
                'doc_name'     => "Applicant's Cedula",
                'doc_description' => 'Community tax certificate of the applicant (claimant).',
                'is_mandatory' => true,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => Str::uuid()->toString(),
                'category_id'  => $hospital,
                'doc_name'     => 'Barangay Indigency',
                'doc_description' => 'A certificate of indigency issued by the barangay where the beneficiary resides.',
                'is_mandatory' => true,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => Str::uuid()->toString(),
                'category_id'  => $hospital,
                'doc_name'     => 'Authorization Letter',
                'doc_description' => 'A letter authorizing the applicant to claim assistance on behalf of the beneficiary. Required if the claimant is not a direct relative of the beneficiary.',
                'is_mandatory' => false, // conditionally required — enforced in SubmitApplicationRequest
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],

            // -------------------------------------------------------
            // CATEGORY 3 — Burial Assistance
            // -------------------------------------------------------
            [
                'id'           => Str::uuid()->toString(),
                'category_id'  => $burial,
                'doc_name'     => 'Certified Copy of Birth Certificate',
                'doc_description' => 'A certified true copy of the birth certificate of the deceased beneficiary issued by the PSA or local civil registry.',
                'is_mandatory' => true,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => Str::uuid()->toString(),
                'category_id'  => $burial,
                'doc_name'     => "Applicant's Government ID",
                'doc_description' => 'Any valid government-issued ID of the applicant (claimant).',
                'is_mandatory' => true,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => Str::uuid()->toString(),
                'category_id'  => $burial,
                'doc_name'     => "Applicant's Cedula",
                'doc_description' => 'Community tax certificate of the applicant (claimant).',
                'is_mandatory' => true,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => Str::uuid()->toString(),
                'category_id'  => $burial,
                'doc_name'     => "Beneficiary's Barangay Residency",
                'doc_description' => 'A certificate of residency issued by the barangay confirming the deceased beneficiary was a resident of General Mamerto Natividad.',
                'is_mandatory' => true,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => Str::uuid()->toString(),
                'category_id'  => $burial,
                'doc_name'     => 'Barangay Indigency',
                'doc_description' => 'A certificate of indigency issued by the barangay on behalf of the bereaved family.',
                'is_mandatory' => true,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
            [
                'id'           => Str::uuid()->toString(),
                'category_id'  => $burial,
                'doc_name'     => 'Authorization Letter',
                'doc_description' => 'A letter authorizing the applicant to claim assistance on behalf of the bereaved family. Required if the claimant is not a direct relative of the beneficiary.',
                'is_mandatory' => false, // conditionally required — enforced in SubmitApplicationRequest
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ],
        ];

        DB::table('required_documents')->insert($documents);
    }
}
```

---

## `DatabaseSeeder.php` — Call Order

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            AssistanceCategorySeeder::class,   // must run before RequiredDocumentSeeder
            RequiredDocumentSeeder::class,      // depends on assistance_categories rows
            AssistanceCodeReferenceSeeder::class,
            SystemSettingsSeeder::class,
        ]);
    }
}
```

---

## Authorization Letter — Validation Logic Reference

Implement this rule in `app/Http/Requests/Public/SubmitApplicationRequest.php`:

```php
// Direct relatives — Authorization Letter NOT required for these
private const DIRECT_RELATIVES = [
    'spouse',
    'parent',
    'child',
    'son',
    'daughter',
    'sibling',
    'brother',
    'sister',
    'grandparent',
    'grandfather',
    'grandmother',
];

public function rules(): array
{
    $relationship = strtolower(trim($this->input('claimant_relationship_to_beneficiary', '')));
    $isDirectRelative = in_array($relationship, self::DIRECT_RELATIVES);

    // Find the Authorization Letter required_doc_id for the selected category
    $authLetterDocId = \DB::table('required_documents')
        ->where('category_id', $this->input('category_id'))
        ->where('doc_name', 'Authorization Letter')
        ->value('id');

    return [
        // ... other validation rules ...

        // Authorization Letter is required only when claimant is NOT a direct relative
        "documents.{$authLetterDocId}" => $isDirectRelative ? 'nullable|file' : 'required|file|mimes:jpg,jpeg,png,pdf',
    ];
}

public function messages(): array
{
    return [
        // ... other messages ...
        'documents.*.required' => 'An Authorization Letter is required when the claimant is not a direct relative of the beneficiary.',
    ];
}
```

And in the Vue Apply page, conditionally mark the Authorization Letter field
as required based on the relationship field value:

```vue
<script setup>
const DIRECT_RELATIVES = [
  'spouse', 'parent', 'child', 'son', 'daughter',
  'sibling', 'brother', 'sister',
  'grandparent', 'grandfather', 'grandmother',
]

const isDirectRelative = computed(() =>
  DIRECT_RELATIVES.includes(
    form.claimant_relationship_to_beneficiary?.toLowerCase().trim()
  )
)

// In your document list rendering, mark Authorization Letter as required
// when isDirectRelative is false
const isDocumentRequired = (doc) => {
  if (doc.doc_name === 'Authorization Letter') {
    return !isDirectRelative.value
  }
  return doc.is_mandatory
}
</script>
```

---

## Summary

| Category | Mandatory Documents | Conditional Documents |
|---|---|---|
| Medical Assistance | 6 | 1 (Authorization Letter) |
| Hospital Assistance | 7 | 1 (Authorization Letter) |
| Burial Assistance | 5 | 1 (Authorization Letter) |
| **Total** | **18** | **3** |

Total rows in `required_documents`: **21**

---

*Document prepared for AI consumption and development reference — ALALAY System,
Municipality of General Mamerto Natividad, Nueva Ecija.*

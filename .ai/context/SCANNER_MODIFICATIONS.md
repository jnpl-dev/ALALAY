# ALALAY: Document Scanner Specification
**Camera-Based Document Capture — Apply Page & Staff Review Pages**
**Municipality of General Mamerto Natividad, Nueva Ecija**

---

## Overview

ALALAY uses a camera-based document scanner instead of a traditional file
upload input for all document capture across both applicant-facing pages
and staff panel pages. There is no `<input type="file">` for document
fields — everything is captured using the device camera and processed
in-browser before being sent to the server.

The scanner is implemented as a reusable Vue 3 component (`DocumentScanner.vue`)
and a Vue 3 composable (`useDocumentScanner.js`). The same component is
used everywhere — Apply page, Track page resubmission, MSWDO social case
study upload, and MSWDO voucher upload.

---

## Core Principles

- **Scanner overlay shape matches the physical document** — the guide frame
  the user aligns to reflects the actual dimensions of the document being
  scanned (landscape frame for IDs and Cedula, portrait frame for A4 papers)
- **PDF output is always A4 portrait** — regardless of how the document
  was oriented during scanning, the final saved file is always a standard
  A4 portrait PDF, exactly like CamScanner output
- **No OpenCV, no heavy library** — all image processing uses pure
  JavaScript canvas operations only
- **No `<input type="file">` as primary input** — camera capture is the
  only primary path; a fallback file input appears only when camera
  permission is denied

---

## Image Enhancement Pipeline

After capture, before showing the preview, run this exact pipeline on
the canvas in this exact order:

### Step 1 — Downscale
Scale the captured image down to a maximum width of 1200px before any
processing. This keeps processing fast on low-end phones. Preserve the
original aspect ratio.

```javascript
function downscale(sourceCanvas, maxWidth = 1200) {
  const scale = Math.min(1, maxWidth / sourceCanvas.width)
  const dest = document.createElement('canvas')
  dest.width = sourceCanvas.width * scale
  dest.height = sourceCanvas.height * scale
  dest.getContext('2d').drawImage(sourceCanvas, 0, 0, dest.width, dest.height)
  return dest
}
```

### Step 2 — Grayscale
Convert to grayscale using luminance weights:
```
gray = 0.299 × R + 0.587 × G + 0.114 × B
```
Set R, G, B all to the same gray value. Alpha unchanged.

### Step 3 — Contrast Stretch
Find the minimum and maximum pixel values across the entire image.
Stretch all values to fill the 0–255 range:
```
stretched = ((pixel - min) / (max - min)) × 255
```
This whitens the paper background and darkens the text simultaneously.

### Step 4 — Adaptive Local Mean Threshold
For each pixel, compute the mean brightness of the surrounding 40×40
pixel block (the local neighborhood). If the pixel value is less than
`(local mean − 10)`, set it to 0 (black). Otherwise set it to 255
(white). This handles uneven lighting, shadows on document edges, and
yellowed paper — the same technique CamScanner uses for document mode.

Parameters:
- Block size: 40px × 40px
- Constant C: 10

### Step 5 — Export as JPEG Blob
```javascript
canvas.toBlob((blob) => {
  // use blob for PDF generation
}, 'image/jpeg', 0.88)
```
Target output size after enhancement: 150–350KB per captured page.

---

## PDF Generation

All captured images — regardless of orientation, scanner size, or number
of pages — are saved as a single **A4 portrait PDF**.

### PDF Config (fixed, never changes)
```javascript
const PDF_CONFIG = {
  format: 'a4',
  orientation: 'portrait',
  unit: 'mm',
  margin: 10,       // mm on all sides
  jpegQuality: 0.88,
}
```

### How Images Fit Into A4 Portrait
Scale each captured image to fit within the A4 page (210×297mm minus
margins) while preserving its aspect ratio. Center the image on the
page both horizontally and vertically. White space fills the remaining
area. A landscape-captured ID card ends up as a wide rectangle centered
on the A4 page — same result as scanning it with CamScanner.

```javascript
import { jsPDF } from 'jspdf'

function buildPdf(capturedPages) {
  // capturedPages = array of { data: base64DataUrl, width: px, height: px }

  const pdf = new jsPDF({
    orientation: 'portrait',
    unit: 'mm',
    format: 'a4',
  })

  const pageW = pdf.internal.pageSize.getWidth()   // 210
  const pageH = pdf.internal.pageSize.getHeight()  // 297
  const margin = PDF_CONFIG.margin
  const maxW = pageW - margin * 2
  const maxH = pageH - margin * 2

  capturedPages.forEach((img, index) => {
    if (index > 0) pdf.addPage('a4', 'portrait')

    const imgAspect = img.width / img.height
    const pageAspect = maxW / maxH

    let drawW, drawH
    if (imgAspect > pageAspect) {
      drawW = maxW
      drawH = maxW / imgAspect
    } else {
      drawH = maxH
      drawW = maxH * imgAspect
    }

    const x = (pageW - drawW) / 2
    const y = (pageH - drawH) / 2

    pdf.addImage(img.data, 'JPEG', x, y, drawW, drawH)
  })

  return pdf.output('blob') // PDF Blob — sent to server as multipart field
}
```

Install jsPDF:
```bash
npm install jspdf
```

---

## Scanner Presets

Three presets define the scanner overlay shape and phone rotation behavior.
These affect the scanner UI only — PDF output is always A4 portrait.

```javascript
const SCANNER_PRESETS = {

  // Standard A4 paper documents — certificates, bills, letters
  a4: {
    overlayAspectRatio: 3 / 4,         // portrait, 0.75
    overlayWidthPercent: 0.85,          // guide box = 85% of scanner width
    showRotateHint: false,
    cameraFacingMode: 'environment',    // rear camera
    label: 'Align document within the frame',
  },

  // Credit card-sized documents — Philippine government IDs
  card: {
    overlayAspectRatio: 85.6 / 54,     // landscape card ratio, ~1.585
    overlayWidthPercent: 0.80,
    showRotateHint: true,               // user must rotate phone to landscape
    cameraFacingMode: 'environment',
    label: 'Rotate phone sideways — align ID card within the frame',
  },

  // Half-sheet landscape — Cedula (BIR Form 0016)
  half_sheet: {
    overlayAspectRatio: 210 / 148,     // A5 landscape ratio, ~1.42
    overlayWidthPercent: 0.88,
    showRotateHint: true,               // user must rotate phone to landscape
    cameraFacingMode: 'environment',
    label: 'Rotate phone sideways — align Cedula within the frame',
  },
}
```

### Scanner Overlay Shapes

```
a4 preset (portrait):        card preset (landscape):     half_sheet preset (landscape):
┌─────────────────────┐      ┌─────────────────────┐      ┌─────────────────────┐
│                     │      │                     │      │                     │
│  ┌───────────────┐  │      │  ┌───────────────┐  │      │  ┌─────────────┐   │
│  │               │  │      │  │  [ID CARD]    │  │      │  │  [CEDULA]   │   │
│  │   A4 paper    │  │      │  └───────────────┘  │      │  └─────────────┘   │
│  │   fits here   │  │      │                     │      │                     │
│  │               │  │      └─────────────────────┘      └─────────────────────┘
│  └───────────────┘  │       phone rotated landscape       phone rotated landscape
│                     │
└─────────────────────┘
   phone upright portrait
```

---

## Capture Types

Three capture types control how many scans are needed per document slot:

| Type | Description | UX |
|---|---|---|
| `single` | One scan, one PDF page | Scan → Preview → Use This |
| `double` | Two scans (front + back), combined into one 2-page PDF | Scan Front → Use This → Scan Back → Use This → Document Complete |
| `multi` | Variable pages, user decides when done | Scan Page 1 → Use This → [Add Another Page] → repeat → Done |

### `double` UX Flow (Government ID)
```
Step 1: Scanner opens with label "Front Side"
        User aligns ID front → captures → preview shown
        User confirms "Use This" → front captured ✓
Step 2: Scanner opens automatically with label "Back Side"
        User aligns ID back → captures → preview shown
        User confirms "Use This" → back captured ✓
Result: Both pages combined into one 2-page PDF
        "Document Complete" confirmation shown
```

### `multi` UX Flow (Hospital Bill)
```
Step 1: Scanner opens with label "Page 1"
        User captures → preview → Use This → Page 1 captured ✓
        [+ Add Another Page] button appears
Step 2: User taps [+ Add Another Page]
        Scanner opens with label "Page 2"
        User captures → preview → Use This → Page 2 captured ✓
        [+ Add Another Page] button appears again
... repeat until all pages scanned
Final:  [Done — X pages scanned] button
        All pages combined into one multi-page PDF
```

---

## Document Configuration

Every document has three scanner-related fields stored in the
`required_documents` table and returned by the categories API:

| Field | Values | Purpose |
|---|---|---|
| `capture_type` | `single`, `double`, `multi` | How many scans per document |
| `scanner_size` | `a4`, `card`, `half_sheet` | Which scanner preset to use |

### Complete Document Configuration Table

| Document | Category | capture_type | scanner_size | Phone rotation |
|---|---|---|---|---|
| Medical Certificate | Medical | single | a4 | No |
| Prescription | Medical | single | a4 | No |
| Applicant's Government ID | Medical | double | card | Yes |
| Beneficiary's Government ID | Medical | double | card | Yes |
| Applicant's Cedula | Medical | single | half_sheet | Yes |
| Barangay Indigency | Medical | single | a4 | No |
| Authorization Letter | Medical | single | a4 | No |
| Hospital Bill | Hospital | multi | a4 | No |
| Prescription | Hospital | single | a4 | No |
| Medical Certificate/Abstract | Hospital | single | a4 | No |
| Applicant's Government ID | Hospital | double | card | Yes |
| Beneficiary's Government ID | Hospital | double | card | Yes |
| Applicant's Cedula | Hospital | single | half_sheet | Yes |
| Barangay Indigency | Hospital | single | a4 | No |
| Authorization Letter | Hospital | single | a4 | No |
| Certified Copy of Birth Certificate | Burial | single | a4 | No |
| Applicant's Government ID | Burial | double | card | Yes |
| Applicant's Cedula | Burial | single | half_sheet | Yes |
| Beneficiary's Barangay Residency | Burial | single | a4 | No |
| Barangay Indigency | Burial | single | a4 | No |
| Authorization Letter | Burial | single | a4 | No |

---

## Database Changes

### Migration: `required_documents` table

Add these two columns to the `required_documents` migration:

```php
$table->enum('capture_type', ['single', 'double', 'multi'])->default('single');
$table->enum('scanner_size', ['a4', 'card', 'half_sheet'])->default('a4');
```

### Full Updated `RequiredDocumentSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RequiredDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $medical  = DB::table('assistance_categories')->where('category_name', 'Medical Assistance')->value('id');
        $hospital = DB::table('assistance_categories')->where('category_name', 'Hospital Assistance')->value('id');
        $burial   = DB::table('assistance_categories')->where('category_name', 'Burial Assistance')->value('id');

        $documents = [

            // ---------------------------------------------------
            // CATEGORY 1 — Medical Assistance
            // ---------------------------------------------------
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $medical,
                'doc_name'        => 'Medical Certificate',
                'doc_description' => 'A certificate issued by a licensed physician confirming the medical condition of the beneficiary.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'a4',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $medical,
                'doc_name'        => 'Prescription',
                'doc_description' => 'A prescription issued by a licensed physician for the required medicines or treatment.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'a4',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $medical,
                'doc_name'        => "Applicant's Government ID",
                'doc_description' => 'Any valid government-issued ID of the applicant (claimant). Both front and back sides will be scanned.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'double',
                'scanner_size'    => 'card',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $medical,
                'doc_name'        => "Beneficiary's Government ID",
                'doc_description' => 'Any valid government-issued ID of the beneficiary. Both front and back sides will be scanned.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'double',
                'scanner_size'    => 'card',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $medical,
                'doc_name'        => "Applicant's Cedula",
                'doc_description' => 'Community tax certificate (BIR Form 0016) of the applicant (claimant).',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'half_sheet',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $medical,
                'doc_name'        => 'Barangay Indigency',
                'doc_description' => 'A certificate of indigency issued by the barangay where the beneficiary resides.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'a4',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $medical,
                'doc_name'        => 'Authorization Letter',
                'doc_description' => 'A letter authorizing the applicant to claim assistance on behalf of the beneficiary. Required if the claimant is not a direct relative of the beneficiary.',
                'is_mandatory'    => false,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'a4',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],

            // ---------------------------------------------------
            // CATEGORY 2 — Hospital Assistance
            // ---------------------------------------------------
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $hospital,
                'doc_name'        => 'Hospital Bill',
                'doc_description' => 'Official hospital bill or statement of account. Scan all pages if the bill spans multiple pages.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'multi',
                'scanner_size'    => 'a4',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $hospital,
                'doc_name'        => 'Prescription',
                'doc_description' => 'A prescription issued by a licensed physician for the required medicines or treatment.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'a4',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $hospital,
                'doc_name'        => 'Medical Certificate/Abstract',
                'doc_description' => 'A medical certificate or abstract issued by the attending physician summarizing the beneficiary\'s condition and treatment.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'a4',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $hospital,
                'doc_name'        => "Applicant's Government ID",
                'doc_description' => 'Any valid government-issued ID of the applicant (claimant). Both front and back sides will be scanned.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'double',
                'scanner_size'    => 'card',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $hospital,
                'doc_name'        => "Beneficiary's Government ID",
                'doc_description' => 'Any valid government-issued ID of the beneficiary. Both front and back sides will be scanned.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'double',
                'scanner_size'    => 'card',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $hospital,
                'doc_name'        => "Applicant's Cedula",
                'doc_description' => 'Community tax certificate (BIR Form 0016) of the applicant (claimant).',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'half_sheet',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $hospital,
                'doc_name'        => 'Barangay Indigency',
                'doc_description' => 'A certificate of indigency issued by the barangay where the beneficiary resides.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'a4',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $hospital,
                'doc_name'        => 'Authorization Letter',
                'doc_description' => 'A letter authorizing the applicant to claim assistance on behalf of the beneficiary. Required if the claimant is not a direct relative of the beneficiary.',
                'is_mandatory'    => false,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'a4',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],

            // ---------------------------------------------------
            // CATEGORY 3 — Burial Assistance
            // ---------------------------------------------------
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $burial,
                'doc_name'        => 'Certified Copy of Birth Certificate',
                'doc_description' => 'A certified true copy of the birth certificate of the deceased beneficiary issued by the PSA or local civil registry.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'a4',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $burial,
                'doc_name'        => "Applicant's Government ID",
                'doc_description' => 'Any valid government-issued ID of the applicant (claimant). Both front and back sides will be scanned.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'double',
                'scanner_size'    => 'card',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $burial,
                'doc_name'        => "Applicant's Cedula",
                'doc_description' => 'Community tax certificate (BIR Form 0016) of the applicant (claimant).',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'half_sheet',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $burial,
                'doc_name'        => "Beneficiary's Barangay Residency",
                'doc_description' => 'A certificate of residency issued by the barangay confirming the deceased beneficiary was a resident of General Mamerto Natividad.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'a4',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $burial,
                'doc_name'        => 'Barangay Indigency',
                'doc_description' => 'A certificate of indigency issued by the barangay on behalf of the bereaved family.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'a4',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $burial,
                'doc_name'        => 'Authorization Letter',
                'doc_description' => 'A letter authorizing the applicant to claim assistance on behalf of the bereaved family. Required if the claimant is not a direct relative of the beneficiary.',
                'is_mandatory'    => false,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'a4',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ];

        DB::table('required_documents')->insert($documents);
    }
}
```

---

## Vue Components & Composables

### `useDocumentScanner.js`
Location: `resources/js/Composables/useDocumentScanner.js`

Manages:
- Camera stream via `navigator.mediaDevices.getUserMedia`
- Applying the enhancement pipeline on captured canvas frames
- Managing `capturedPages` array (one entry per scan for multi/double)
- Generating the final PDF Blob via jsPDF
- Exposing reactive state and methods to the component

Reactive state exposed:
```javascript
{
  isScanning: Boolean,       // camera is open
  isProcessing: Boolean,     // enhancement pipeline running
  previewUrl: String|null,   // data URL of last captured + enhanced image
  capturedPages: Array,      // array of { data, width, height } per page
  cameraError: String|null,  // error message if camera access fails
  hasCapture: Boolean,       // true if at least one page is captured
  isComplete: Boolean,       // true if all required pages are captured
  pdfBlob: Blob|null,        // final PDF blob, ready for form submission
}
```

Methods exposed:
```javascript
{
  startCamera(),    // requests camera permission, opens stream
  capture(),        // captures current video frame, runs enhancement
  retakeLast(),     // removes last captured page, reopens camera
  addPage(),        // for multi type — opens camera for next page
  confirmAll(),     // builds final PDF from all captured pages
  stopCamera(),     // stops camera stream
  reset(),          // clears all state, ready for fresh start
}
```

---

### `DocumentScanner.vue`
Location: `resources/js/Components/Application/DocumentScanner.vue`

Props:
```javascript
{
  docName: String,          // document label shown above the scanner
  required: Boolean,        // whether this document is mandatory
  captureType: String,      // 'single' | 'double' | 'multi'
  scannerSize: String,      // 'a4' | 'card' | 'half_sheet'
  modelValue: Blob|null,    // v-model — the final PDF blob
}
```

Emits:
```javascript
{
  'update:modelValue',  // emits PDF Blob when document is complete
  'captured',           // emits when any single page is confirmed
  'cleared',            // emits when user retakes and clears
}
```

Renders (in order of states):

**State 1 — Rotate hint (card and half_sheet presets only):**
A brief overlay showing an animated phone rotation icon and the text
"Rotate your phone sideways to scan this document." Auto-dismisses
after 2 seconds or on tap. Only shown once per document slot.

**State 2 — Camera active:**
- Full-width `<video>` element showing live camera feed
- Semi-transparent dark mask overlay with a clear rectangle cutout
  sized to the preset's `overlayAspectRatio` and `overlayWidthPercent`
- Label inside or above the guide frame (e.g., "Front Side", "Page 1")
- Capture button centered below the video

**State 3 — Processing:**
- Camera feed hidden
- Loading spinner with text "Processing document..."

**State 4 — Preview:**
- Enhanced image shown in place of camera feed
- "Use This" button (primary) and "Retake" button (secondary)
- For `double` type: label shows "Front captured ✓ — now scan the Back"
  after front is confirmed, before back is opened

**State 5 — Document Complete:**
- Green checkmark icon
- Text: "Document Captured" for single/double
- Text: "X pages captured" for multi type
- "Recapture" link in small text below to restart if needed
- The PDF blob is emitted via `update:modelValue` at this point

**State 6 — Camera denied / fallback:**
- Error message: "Camera access was denied."
- Secondary option below: "Upload image instead" — standard
  `<input type="file" accept="image/jpeg,image/png">` (no PDF accepted)
- This fallback emits the selected file directly (converted to PDF
  on the client side before emitting — same PDF pipeline applied)

---

## Affected Pages

### 1. Public Apply Page
`resources/js/Pages/Public/Apply.vue`

Step 3 — Document Capture section:
- Render one `DocumentScanner` component per required document
- Pass `capture_type` and `scanner_size` from the API response as props
- For the Authorization Letter: only render the `DocumentScanner` when
  `claimant_relationship_to_beneficiary === 'Representative'`
- Each `DocumentScanner` v-model binds to `form.documents[doc.id]`
- Form cannot proceed to Step 4 until all mandatory documents have
  a non-null PDF blob in `form.documents`

Step 4 — Summary:
- Show a thumbnail preview of each captured document PDF alongside
  the document name and a green checkmark
- Use `<iframe>` or `<object>` with a blob URL to preview the PDF

---

### 2. Public Track Page — Resubmission Section
`resources/js/Pages/Public/Track.vue`

Resubmission section (visible only when `status = 'returned_to_applicant'`):
- Render one `DocumentScanner` per flagged document
  (flagged documents come from `reviews.resubmission_docs_required`)
- Each flagged document's `capture_type` and `scanner_size` come from
  the `required_documents` data already returned with the application
- Same v-model and submission pattern as the Apply page

---

### 3. MSWDO Application Review — Social Case Study Upload
`resources/js/Pages/Mswdo/Applications/Review.vue`

Social Case Study upload step (Step 2, after clicking "Next"):
- Replace the file upload field with a single `DocumentScanner`
- Props for this instance:
  ```javascript
  docName="Social Case Study"
  :required="true"
  captureType="single"
  scannerSize="a4"
  ```
- The MSWDO staff member has the printed social case study in hand
  and scans it on the spot using the device camera
- The resulting PDF blob is submitted as the `social_case_study` field

---

### 4. MSWDO Voucher Creation — Voucher Upload
`resources/js/Pages/Mswdo/Vouchers/Create.vue`

Voucher upload step (Step 2):
- Replace the file upload field with a single `DocumentScanner`
- Props for this instance:
  ```javascript
  docName="Voucher Document"
  :required="true"
  captureType="single"
  scannerSize="a4"
  ```
- The MSWDO staff member scans the physical printed voucher
- The resulting PDF blob is submitted as the `voucher_file` field

---

## Backend Changes

### Accepted MIME Types
All document capture fields on the backend now accept only:
- `application/pdf` — the primary output from the scanner

Update MIME validation in these Form Request files:

```php
// SubmitApplicationRequest.php
'documents.*' => 'required|file|mimes:pdf|max:10240',

// ResubmitDocumentsRequest.php
'documents.*' => 'required|file|mimes:pdf|max:10240',

// ApproveApplicationRequest.php (MSWDO — social case study)
'social_case_study' => 'required|file|mimes:pdf|max:10240',

// CreateVoucherRequest.php (MSWDO — voucher)
'voucher_file' => 'required|file|mimes:pdf|max:10240',
```

### FileUploadService — No Structural Changes
The service receives a standard file (now always PDF) and uploads it
to Supabase Storage. No logic changes needed — only the MIME validation
in Form Requests changes.

### Supabase Storage Paths — No Changes
File paths remain:
```
application_documents/{application_id}/{required_doc_id}_{timestamp}.pdf
social_case_studies/{application_id}/scs_{timestamp}.pdf
vouchers/{application_id}/voucher_v{version}_{timestamp}.pdf
```
Extension changes from `.jpg` to `.pdf` — no structural path changes.

---

## Staff Document Viewer

All staff review pages that display uploaded documents use the existing
`DocumentViewer.vue` component. Update it to display PDFs:

```vue
<!-- DocumentViewer.vue — updated for PDF -->
<template>
  <div class="document-viewer">
    <iframe
      v-if="signedUrl"
      :src="signedUrl"
      type="application/pdf"
      width="100%"
      height="700px"
      style="border: none; border-radius: 8px;"
    />
    <div v-else class="flex items-center justify-center h-64 text-gray-400">
      Loading document...
    </div>
  </div>
</template>
```

Or use `vue-pdf-embed` for richer controls (zoom, page navigation):
```bash
npm install vue-pdf-embed
```

```vue
<script setup>
import VuePdfEmbed from 'vue-pdf-embed'
</script>

<template>
  <VuePdfEmbed :source="signedUrl" />
</template>
```

Staff review pages that render `DocumentViewer`:
- `Aics/Applications/Review.vue` — supporting documents list
- `Mswdo/Applications/Review.vue` — supporting documents list
- `Aics/AssistanceCodes/Code.vue` — social case study viewer
- `Mswdo/Vouchers/Create.vue` — social case study viewer (step 1)
- `Accountant/Vouchers/Review.vue` — voucher viewer
- `Treasurer/Cheques/Review.vue` — voucher viewer

---

## Testing on Mobile Device

The camera (`getUserMedia`) requires HTTPS. Use ngrok during development:

```bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev

# Terminal 3
ngrok http 8000
```

Open the ngrok HTTPS URL on your phone. Camera permission will work
normally. Desktop Chrome/Firefox work on `localhost` without HTTPS.

Test each scanner preset on a real device:
- `a4` — scan any A4 paper held upright
- `card` — rotate phone landscape, scan any card-sized item
- `half_sheet` — rotate phone landscape, scan the Cedula form

---

## Summary of All Changes

| File | Type | Change |
|---|---|---|
| `required_documents` migration | Database | Add `capture_type` and `scanner_size` columns |
| `RequiredDocumentSeeder.php` | Database | Add `capture_type` and `scanner_size` to all 21 documents |
| `useDocumentScanner.js` | New composable | Full scanner logic, enhancement pipeline, PDF generation |
| `DocumentScanner.vue` | New component | Scanner UI — all states, all presets, all capture types |
| `Apply.vue` | Modified | Step 3: replace file inputs with DocumentScanner per document |
| `Track.vue` | Modified | Resubmission: replace file inputs with DocumentScanner |
| `Mswdo/Applications/Review.vue` | Modified | Social case study upload: replace file input with DocumentScanner |
| `Mswdo/Vouchers/Create.vue` | Modified | Voucher upload step 2: replace file input with DocumentScanner |
| `DocumentViewer.vue` | Modified | Update to render PDF via iframe or vue-pdf-embed |
| `SubmitApplicationRequest.php` | Modified | MIME validation: accept `pdf` only |
| `ResubmitDocumentsRequest.php` | Modified | MIME validation: accept `pdf` only |
| `ApproveApplicationRequest.php` | Modified | MIME validation: accept `pdf` only |
| `CreateVoucherRequest.php` | Modified | MIME validation: accept `pdf` only |

---

*Document prepared for AI consumption and development reference — ALALAY System,
Municipality of General Mamerto Natividad, Nueva Ecija.*

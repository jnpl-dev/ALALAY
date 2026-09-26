<?php

namespace App\Http\Controllers\InternalAudit;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasPollCache;
use App\Http\Requests\InternalAudit\ApproveCodingReviewRequest;
use App\Http\Requests\InternalAudit\ReturnCodingReviewRequest;
use App\Models\Application;
use App\Models\AssistanceCategory;
use App\Models\Review;
use App\Services\SignedUrlService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CodingReviewController extends Controller
{
    use HasPollCache;

    protected function getPollData(Request $request): array
    {
        $tab = $request->query('tab', 'pending');
        $search = $request->query('search');
        $category = $request->query('category');

        $query = Application::with('category', 'assistanceCode.reference');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_code', 'like', "%{$search}%")
                  ->orWhere('claimant_first_name', 'like', "%{$search}%")
                  ->orWhere('claimant_last_name', 'like', "%{$search}%")
                  ->orWhereHas('category', fn($q) => $q->where('category_name', 'like', "%{$search}%"));
            });
        }

        if ($category) {
            $query->whereHas('category', fn($q) => $q->where('category_name', $category));
        }

        $applications = match ($tab) {
            'audited' => (clone $query)->where('status', 'voucher_creation'),
            default => (clone $query)->where('status', 'internal_audit_review'),
        };

        return $applications->latest()->get()->map(fn ($app) => [
            'id' => $app->id,
            'reference_code' => $app->reference_code,
            'status' => $app->status,
            'category_name' => $app->category?->category_name,
            'claimant_name' => $app->claimant_first_name . ' ' . $app->claimant_last_name,
            'code_type' => $app->assistanceCode?->reference?->code_type,
            'amount' => $app->assistanceCode?->amount,
            'created_at' => $app->created_at,
        ])->values()->toArray();
    }

    public function index()
    {
        $this->authorize('viewAny', Application::class);
        $tab = request('tab', 'pending');
        $search = request('search');
        $category = request('category');
        $from = request('from');
        $to = request('to');

        $query = Application::with('category', 'assistanceCode.reference');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_code', 'like', "%{$search}%")
                  ->orWhere('claimant_first_name', 'like', "%{$search}%")
                  ->orWhere('claimant_last_name', 'like', "%{$search}%")
                  ->orWhereHas('category', fn($q) => $q->where('category_name', 'like', "%{$search}%"));
            });
        }

        if ($category) {
            $query->whereHas('category', fn($q) => $q->where('category_name', $category));
        }

        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        $applications = match ($tab) {
            'audited' => (clone $query)->where('status', 'voucher_creation'),
            default => (clone $query)->where('status', 'internal_audit_review'),
        };

        $categories = AssistanceCategory::where('is_active', true)->pluck('category_name');

        return Inertia::render('InternalAudit/CodingReview/Index', [
            'applications' => Inertia::defer(fn () =>
                $applications->latest()
                    ->paginate(10)
                    ->through(fn ($app) => [
                        'id' => $app->id,
                        'reference_code' => $app->reference_code,
                        'status' => $app->status,
                        'category_name' => $app->category?->category_name,
                        'claimant_name' => $app->claimant_first_name . ' ' . $app->claimant_last_name,
                        'code_type' => $app->assistanceCode?->reference?->code_type,
                        'amount' => $app->assistanceCode?->amount,
                        'created_at' => $app->created_at,
                    ])
            ),
            'filters' => request()->only(['search', 'category', 'from', 'to']),
            'tab' => $tab,
            'categories' => $categories,
        ]);
    }

    public function export()
    {
        $this->authorize('viewAny', Application::class);
        $tab = request('tab', 'pending');
        $search = request('search');
        $category = request('category');
        $from = request('from');
        $to = request('to');

        $query = Application::with('category', 'assistanceCode.reference');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_code', 'like', "%{$search}%")
                  ->orWhere('claimant_first_name', 'like', "%{$search}%")
                  ->orWhere('claimant_last_name', 'like', "%{$search}%")
                  ->orWhereHas('category', fn($q) => $q->where('category_name', 'like', "%{$search}%"));
            });
        }

        if ($category) {
            $query->whereHas('category', fn($q) => $q->where('category_name', $category));
        }

        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        $applications = (match ($tab) {
            'audited' => (clone $query)->where('status', 'voucher_creation'),
            default => (clone $query)->where('status', 'internal_audit_review'),
        })->latest()->get();

        $filename = 'alalay-coding-reviews-' . $tab . '-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($applications) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Reference Code', 'Beneficiary Name', 'Category', 'Code Type', 'Amount', 'Status', 'Date Submitted']);

            foreach ($applications as $app) {
                fputcsv($handle, [
                    $app->reference_code,
                    $app->claimant_first_name . ' ' . $app->claimant_last_name,
                    $app->category?->category_name,
                    $app->assistanceCode?->reference?->code_type,
                    $app->assistanceCode?->amount,
                    $app->status,
                    $app->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function show($id, SignedUrlService $signedUrl)
    {
        $application = Application::with([
            'category',
            'documents.requiredDocument',
            'reviews.reviewer',
            'socialCaseStudy',
            'assistanceCode.reference',
        ])->findOrFail($id);

        $this->authorize('view', $application);

        $reviews = $application->reviews()
            ->latest()
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'stage' => $r->stage,
                'decision' => $r->decision,
                'remarks' => $r->remarks,
                'from_status' => $r->from_status,
                'to_status' => $r->to_status,
                'user_name' => $r->reviewer?->full_name ?? 'System',
                'created_at' => $r->created_at,
            ]);

        $documents = $application->documents->map(fn ($d) => [
            'id' => $d->id,
            'doc_name' => $d->requiredDocument?->doc_name ?? 'Document',
            'file_name' => $d->file_name,
            'file_path' => $d->file_path,
            'mime_type' => $d->mime_type,
            'is_resubmission' => $d->is_resubmission,
            'signed_url' => $signedUrl->generate($d->file_path),
        ]);

        $scsUrl = $application->socialCaseStudy
            ? $signedUrl->generate($application->socialCaseStudy->file_path)
            : null;

        return Inertia::render('InternalAudit/CodingReview/Review', [
            'application' => [
                'id' => $application->id,
                'reference_code' => $application->reference_code,
                'status' => $application->status,
                'category_name' => $application->category?->category_name,
                'submission_type' => $application->submission_type,
                'assistance_code' => $application->assistanceCode ? [
                    'id' => $application->assistanceCode->id,
                    'code_type' => $application->assistanceCode->reference?->code_type,
                    'description' => $application->assistanceCode->reference?->description,
                    'amount' => $application->assistanceCode->amount,
                    'default_amount' => $application->assistanceCode->reference?->default_amount,
                    'assigned_by' => $application->assistanceCode->assignedBy?->full_name,
                    'assigned_at' => $application->assistanceCode->created_at?->format('M d, Y'),
                ] : null,
                'claimant_first_name' => $application->claimant_first_name,
                'claimant_middle_name' => $application->claimant_middle_name,
                'claimant_last_name' => $application->claimant_last_name,
                'claimant_name_extension' => $application->claimant_name_extension,
                'claimant_sex' => $application->claimant_sex,
                'claimant_dob' => $application->claimant_dob,
                'claimant_address' => $application->claimant_address,
                'claimant_phone' => $application->claimant_phone,
                'claimant_email' => $application->claimant_email,
                'claimant_relationship_to_beneficiary' => $application->claimant_relationship_to_beneficiary,
                'beneficiary_first_name' => $application->beneficiary_first_name,
                'beneficiary_middle_name' => $application->beneficiary_middle_name,
                'beneficiary_last_name' => $application->beneficiary_last_name,
                'beneficiary_name_extension' => $application->beneficiary_name_extension,
                'beneficiary_sex' => $application->beneficiary_sex,
                'beneficiary_dob' => $application->beneficiary_dob,
                'beneficiary_address' => $application->beneficiary_address,
                'created_at' => $application->created_at,
            ],
            'documents' => $documents,
            'reviews' => $reviews,
            'socialCaseStudy' => $application->socialCaseStudy ? [
                'id' => $application->socialCaseStudy->id,
                'signed_url' => $scsUrl,
                'uploaded_by' => $application->socialCaseStudy->conductedBy?->full_name,
                'conducted_at' => $application->socialCaseStudy->conducted_at,
                'page_count' => $application->socialCaseStudy->page_count,
                'file_size_label' => $application->socialCaseStudy->file_size_label,
            ] : null,
        ]);
    }

    public function approve(ApproveCodingReviewRequest $request, $id)
    {
        $application = Application::findOrFail($id);
        $this->authorize('reviewCoding', $application);

        if ($application->status !== 'internal_audit_review') {
            return redirect()->back()->with('error', 'Coding cannot be approved at this stage.');
        }

        $application->update([
            'status' => 'voucher_creation',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        Review::create([
            'application_id' => $application->id,
            'reviewed_by' => $request->user()->id,
            'stage' => 'internal_audit_review',
            'decision' => 'approved',
            'from_status' => 'internal_audit_review',
            'to_status' => 'voucher_creation',
            'remarks' => $request->input('remarks'),
            'created_at' => now(),
        ]);

        $this->bustPollCache();

        return redirect()->route('internal-audit.applications.index')
            ->with('success', 'Coding approved. Application is now ready for voucher creation.');
    }

    public function return(ReturnCodingReviewRequest $request, $id)
    {
        $application = Application::findOrFail($id);
        $this->authorize('reviewCoding', $application);

        if ($application->status !== 'internal_audit_review') {
            return redirect()->back()->with('error', 'Coding cannot be returned at this stage.');
        }

        $validated = $request->validated();

        $application->update([
            'status' => 'returned_assistance_coding',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        Review::create([
            'application_id' => $application->id,
            'reviewed_by' => $request->user()->id,
            'stage' => 'internal_audit_review',
            'decision' => 'returned',
            'from_status' => 'internal_audit_review',
            'to_status' => 'returned_assistance_coding',
            'remarks' => $validated['remarks'],
            'created_at' => now(),
        ]);

        $this->bustPollCache();

        return redirect()->route('internal-audit.applications.index')
            ->with('success', 'Coding returned to AICS for re-coding.');
    }
}

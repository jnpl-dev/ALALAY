<?php

namespace App\Http\Controllers\Aics;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AssistanceCategory;
use App\Models\AssistanceCode;
use App\Models\AssistanceCodeReference;
use App\Models\Review;
use App\Jobs\SendSmsJob;
use App\Services\SignedUrlService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AssistanceCodeController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', AssistanceCode::class);
        $tab = request('tab', 'pending');
        $search = request('search');
        $category = request('category');

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
            'coded' => (clone $query)->where('status', 'voucher_creation'),
            default => (clone $query)->where('status', 'assistance_coding'),
        };

        $apps = $applications->latest()
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
            ]);

        $categories = AssistanceCategory::where('is_active', true)->pluck('category_name');

        return Inertia::render('Aics/AssistanceCodes/Index', [
            'applications' => $apps,
            'tab' => $tab,
            'search' => $search,
            'category' => $category,
            'categories' => $categories,
        ]);
    }

    public function show($id, SignedUrlService $signedUrl)
    {
        $this->authorize('viewAny', AssistanceCode::class);

        $application = Application::with([
            'category',
            'documents.requiredDocument',
            'reviews.reviewer',
            'socialCaseStudy',
            'assistanceCode.reference',
        ])->findOrFail($id);

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
        ]);

        $scsUrl = $application->socialCaseStudy
            ? $signedUrl->generate($application->socialCaseStudy->file_path)
            : null;

        $codeReferences = AssistanceCodeReference::active()
            ->orderBy('code_type')
            ->get()
            ->map(fn ($ref) => [
                'id' => $ref->id,
                'code_type' => $ref->code_type,
                'default_amount' => $ref->default_amount,
                'description' => $ref->description,
            ]);

        return Inertia::render('Aics/AssistanceCodes/Code', [
            'application' => [
                'id' => $application->id,
                'reference_code' => $application->reference_code,
                'status' => $application->status,
                'category_name' => $application->category?->category_name,
                'submission_type' => $application->submission_type,
                'assistance_code' => $application->assistanceCode ? [
                    'id' => $application->assistanceCode->id,
                    'code_type' => $application->assistanceCode->reference?->code_type,
                    'amount' => $application->assistanceCode->amount,
                    'assigned_by' => $application->assistanceCode->assignedBy?->full_name,
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
            'code_references' => $codeReferences,
        ]);
    }

    public function store(Request $request, $id)
    {
        $application = Application::findOrFail($id);
        $this->authorize('create', AssistanceCode::class);

        if ($application->status !== 'assistance_coding') {
            return redirect()->back()->with('error', 'Application is not ready for assistance coding.');
        }

        $validated = $request->validate([
            'assistance_code_reference_id' => ['required', 'exists:assistance_code_references,id'],
            'amount' => ['required', 'numeric', 'min:0'],
        ]);

        $reference = AssistanceCodeReference::findOrFail($validated['assistance_code_reference_id']);

        AssistanceCode::create([
            'application_id' => $application->id,
            'assistance_code_reference_id' => $reference->id,
            'amount' => $validated['amount'],
            'assigned_by' => $request->user()->id,
        ]);

        $application->update([
            'status' => 'voucher_creation',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        Review::create([
            'application_id' => $application->id,
            'reviewed_by' => $request->user()->id,
            'stage' => 'assistance_coding',
            'decision' => 'coded',
            'from_status' => 'assistance_coding',
            'to_status' => 'voucher_creation',
            'created_at' => now(),
        ]);

        return redirect()->route('aics.assistance-codes.index')
            ->with('success', 'Assistance code assigned successfully.');
    }
}

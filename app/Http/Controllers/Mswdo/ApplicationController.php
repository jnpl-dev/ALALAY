<?php

namespace App\Http\Controllers\Mswdo;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasPollCache;
use App\Http\Requests\Mswdo\ApproveApplicationRequest;
use App\Http\Requests\Mswdo\ReturnApplicationRequest;
use App\Jobs\SendSmsJob;
use App\Models\Application;
use App\Models\AssistanceCategory;
use App\Models\Review;
use App\Models\SocialCaseStudy;
use App\Services\FileUploadService;
use App\Services\SignedUrlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationController extends Controller
{
    use HasPollCache;

    public function __construct(
        protected FileUploadService $fileUploadService,
        protected SignedUrlService $signedUrlService,
    ) {}

    protected function getPollData(Request $request): array
    {
        $tab = $request->query('tab', 'pending');
        $search = $request->query('search');
        $category = $request->query('category');

        $query = Application::with('category', 'encoder');

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
            'scs_uploaded' => (clone $query)->where('status', 'assistance_coding'),
            'returned' => (clone $query)->where('status', 'returned_to_applicant'),
            default => (clone $query)->where('status', 'mswdo_review'),
        };

        return $applications->latest()->get()->map(fn ($app) => [
            'id' => $app->id,
            'reference_code' => $app->reference_code,
            'status' => $app->status,
            'category_name' => $app->category?->category_name,
            'claimant_name' => $app->claimant_first_name . ' ' . $app->claimant_last_name,
            'submission_type' => $app->submission_type,
            'created_at' => $app->created_at,
        ])->values()->toArray();
    }

    public function index(): Response
    {
        $tab = request('tab', 'pending');
        $search = request('search');
        $category = request('category');

        $query = Application::with('category', 'encoder');

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
            'scs_uploaded' => (clone $query)->where('status', 'assistance_coding'),
            'returned' => (clone $query)->where('status', 'returned_to_applicant'),
            default => (clone $query)->where('status', 'mswdo_review'),
        };

        $categories = AssistanceCategory::where('is_active', true)->pluck('category_name');

        return Inertia::render('Mswdo/Applications/Index', [
            'applications' => Inertia::defer(fn () =>
                $applications->latest()
                    ->paginate(10)
                    ->through(fn ($app) => [
                        'id' => $app->id,
                        'reference_code' => $app->reference_code,
                        'status' => $app->status,
                        'category_name' => $app->category?->category_name,
                        'claimant_name' => $app->claimant_first_name . ' ' . $app->claimant_last_name,
                        'submission_type' => $app->submission_type,
                        'created_at' => $app->created_at,
                    ])
            ),
            'tab' => $tab,
            'search' => $search,
            'category' => $category,
            'categories' => $categories,
        ]);
    }

    public function show($id): Response
    {
        $application = Application::with([
            'category',
            'documents.requiredDocument',
            'reviews.reviewer',
            'socialCaseStudy.conductedBy',
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
            'signed_url' => $this->signedUrlService->generate($d->file_path),
        ]);

        $scs = $application->socialCaseStudy;
        $socialCaseStudy = $scs ? [
            'id' => $scs->id,
            'uploaded_by' => $scs->conductedBy?->full_name,
            'conducted_at' => $scs->conducted_at,
            'page_count' => $scs->page_count,
            'file_size_label' => $scs->file_size_label,
            'signed_url' => $this->signedUrlService->generate($scs->file_path),
        ] : null;

        return Inertia::render('Mswdo/Applications/Review', [
            'application' => [
                'id' => $application->id,
                'reference_code' => $application->reference_code,
                'status' => $application->status,
                'category_name' => $application->category?->category_name,
                'submission_type' => $application->submission_type,
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
            'socialCaseStudy' => $socialCaseStudy,
        ]);
    }

    public function approve(ApproveApplicationRequest $request, $id): RedirectResponse
    {
        $application = Application::findOrFail($id);
        $this->authorize('approve', $application);

        $file = $request->file('social_case_study');
        $uploadResult = $this->fileUploadService->upload(
            file: $file,
            table: 'social_case_studies',
            entityId: $application->id,
        );

        SocialCaseStudy::create([
            'application_id' => $application->id,
            'conducted_by' => $request->user()->id,
            'file_name' => $uploadResult['file_name'],
            'file_path' => $uploadResult['file_path'],
            'file_size' => $uploadResult['file_size'],
            'mime_type' => 'application/pdf',
            'page_count' => $request->input('page_count', 1),
            'conducted_at' => now(),
        ]);

        $application->update([
            'status' => 'assistance_coding',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        Review::create([
            'application_id' => $application->id,
            'reviewed_by' => $request->user()->id,
            'stage' => 'mswdo_review',
            'decision' => 'approved',
            'from_status' => 'mswdo_review',
            'to_status' => 'assistance_coding',
            'remarks' => $request->input('remarks'),
            'created_at' => now(),
        ]);

        SendSmsJob::dispatch($application, 'application_under_review');

        $this->bustPollCache();

        return redirect()
            ->route('mswdo.applications.index')
            ->with('success', 'Application approved. Social case study uploaded.');
    }

    public function return(ReturnApplicationRequest $request, $id): RedirectResponse
    {
        $application = Application::findOrFail($id);
        $this->authorize('returnApp', $application);

        $application->update([
            'status' => 'returned_to_applicant',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'resubmission_remarks' => $request->input('remarks'),
        ]);

        Review::create([
            'application_id' => $application->id,
            'reviewed_by' => $request->user()->id,
            'stage' => 'mswdo_review',
            'decision' => 'returned',
            'from_status' => 'mswdo_review',
            'to_status' => 'returned_to_applicant',
            'remarks' => $request->input('remarks'),
            'resubmission_docs_required' => $request->input('document_ids', []),
            'created_at' => now(),
        ]);

        SendSmsJob::dispatch($application, 'resubmission_needed');

        $this->bustPollCache();

        return redirect()
            ->route('mswdo.applications.index')
            ->with('success', 'Application returned to applicant.');
    }

    public function documentUrl($appId, $docId): \Illuminate\Http\RedirectResponse
    {
        $application = Application::findOrFail($appId);
        $document = $application->documents()->findOrFail($docId);

        $url = $this->signedUrlService->generate($document->file_path);

        return redirect()->away($url);
    }

    public function caseStudyUrl($id): JsonResponse
    {
        $application = Application::findOrFail($id);
        $this->authorize('view', $application->socialCaseStudy);

        $url = $this->signedUrlService->generate($application->socialCaseStudy->file_path);

        return response()->json(['url' => $url]);
    }
}

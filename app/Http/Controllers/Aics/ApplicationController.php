<?php

namespace App\Http\Controllers\Aics;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasPollCache;
use App\Models\Application;
use App\Models\AssistanceCategory;
use App\Models\Review;
use App\Jobs\SendSmsJob;
use App\Services\SignedUrlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApplicationController extends Controller
{
    use HasPollCache;

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
            'screening' => (clone $query)->where('status', 'mswdo_review'),
            'returned' => (clone $query)->where('status', 'returned_to_applicant'),
            default => (clone $query)->whereIn('status', ['submitted', 'screening']),
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

    public function index()
    {
        $this->authorize('viewAny', Application::class);
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
            'screening' => (clone $query)->where('status', 'mswdo_review'),
            'returned' => (clone $query)->where('status', 'returned_to_applicant'),
            default => (clone $query)->whereIn('status', ['submitted', 'screening']),
        };

        $apps = $applications->latest()
            ->paginate(10)
            ->through(fn ($app) => [
                'id' => $app->id,
                'reference_code' => $app->reference_code,
                'status' => $app->status,
                'category_name' => $app->category?->category_name,
                'claimant_name' => $app->claimant_first_name . ' ' . $app->claimant_last_name,
                'submission_type' => $app->submission_type,
                'created_at' => $app->created_at,
            ]);

        $categories = AssistanceCategory::where('is_active', true)->pluck('category_name');

        return Inertia::render('Aics/Applications/Index', [
            'applications' => $apps,
            'tab' => $tab,
            'search' => $search,
            'category' => $category,
            'categories' => $categories,
        ]);
    }

    public function show($id, SignedUrlService $signedUrl)
    {
        $application = Application::with([
            'category',
            'documents.requiredDocument',
            'reviews.reviewer',
            'socialCaseStudy',
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

        return Inertia::render('Aics/Applications/Review', [
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
        ]);
    }

    public function documentUrl($appId, $docId, SignedUrlService $signedUrl)
    {
        $application = Application::findOrFail($appId);
        $this->authorize('documentUrl', $application);
        $document = $application->documents()->findOrFail($docId);

        $url = $signedUrl->generate($document->file_path);

        return redirect()->away($url);
    }

    public function approve(Request $request, $id)
    {
        $application = Application::findOrFail($id);
        $this->authorize('approve', $application);

        if (! in_array($application->status, ['submitted', 'screening'])) {
            return redirect()->back()->with('error', 'Application cannot be approved at this stage.');
        }

        $fromStatus = $application->status;

        $application->update([
            'status' => 'mswdo_review',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        Review::create([
            'application_id' => $application->id,
            'reviewed_by' => $request->user()->id,
            'stage' => 'aics_screening',
            'decision' => 'approved',
            'from_status' => $fromStatus,
            'to_status' => 'mswdo_review',
            'created_at' => now(),
        ]);

        SendSmsJob::dispatch($application, 'application_under_review');

        $this->bustPollCache();

        return redirect()->route('aics.applications.index')
            ->with('success', 'Application approved and forwarded to MSWDO.');
    }

    public function return(Request $request, $id)
    {
        $application = Application::findOrFail($id);
        $this->authorize('returnApp', $application);

        if (! in_array($application->status, ['submitted', 'screening'])) {
            return redirect()->back()->with('error', 'Application cannot be returned at this stage.');
        }

        $validated = $request->validate([
            'remarks' => ['required', 'string'],
            'document_ids' => ['nullable', 'array'],
            'document_ids.*' => ['exists:application_documents,id'],
        ]);

        $fromStatus = $application->status;

        $application->update([
            'status' => 'returned_to_applicant',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'resubmission_remarks' => $validated['remarks'],
        ]);

        Review::create([
            'application_id' => $application->id,
            'reviewed_by' => $request->user()->id,
            'stage' => 'aics_screening',
            'decision' => 'returned',
            'from_status' => $fromStatus,
            'to_status' => 'returned_to_applicant',
            'remarks' => $validated['remarks'],
            'resubmission_docs_required' => $validated['document_ids'] ?? [],
            'created_at' => now(),
        ]);

        SendSmsJob::dispatch($application, 'resubmission_needed');

        $this->bustPollCache();

        return redirect()->route('aics.applications.index')
            ->with('success', 'Application returned to applicant.');
    }
}

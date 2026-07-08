<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreApplicationRequest;
use App\Jobs\SendSmsJob;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Review;
use App\Services\FileUploadService;
use App\Services\ReferenceCodeService;
use App\Services\SignedUrlService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ApplicationController extends Controller
{
    public function store(StoreApplicationRequest $request)
    {
        $referenceCode = app(ReferenceCodeService::class)->generate();

        $application = Application::create([
            'category_id' => $request->category_id,
            'reference_code' => $referenceCode,
            'status' => 'submitted',
            'submission_type' => 'online',
            'claimant_last_name' => $request->claimant_last_name,
            'claimant_first_name' => $request->claimant_first_name,
            'claimant_middle_name' => $request->claimant_middle_name,
            'claimant_name_extension' => $request->claimant_name_extension,
            'claimant_sex' => $request->claimant_sex,
            'claimant_dob' => $request->claimant_dob,
            'claimant_address' => $request->claimant_address,
            'claimant_phone' => $request->claimant_phone,
            'claimant_email' => $request->claimant_email,
            'claimant_relationship_to_beneficiary' => $request->claimant_relationship_to_beneficiary,
            'beneficiary_last_name' => $request->beneficiary_last_name,
            'beneficiary_first_name' => $request->beneficiary_first_name,
            'beneficiary_middle_name' => $request->beneficiary_middle_name,
            'beneficiary_name_extension' => $request->beneficiary_name_extension,
            'beneficiary_sex' => $request->beneficiary_sex,
            'beneficiary_dob' => $request->beneficiary_dob,
            'beneficiary_address' => $request->beneficiary_address,
        ]);

        try {
            foreach ($request->file('documents', []) as $i => $file) {
                $requiredDocId = $request->document_ids[$i];

                $result = app(FileUploadService::class)->upload(
                    $file,
                    'application_documents',
                    $application->id,
                );

                ApplicationDocument::create([
                    'application_id' => $application->id,
                    'required_doc_id' => $requiredDocId,
                    'file_name' => $result['file_name'],
                    'file_path' => $result['file_path'],
                    'file_size' => $result['file_size'],
                    'mime_type' => $result['mime_type'],
                    'is_resubmission' => false,
                ]);
            }
        } catch (HttpException $e) {
            $application->delete();
            return redirect()->route('apply')
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            $application->delete();
            \Illuminate\Support\Facades\Log::error('Application submission failed', [
                'error' => $e->getMessage(),
                'application_id' => $application->id,
            ]);
            return redirect()->route('apply')
                ->with('error', 'Failed to upload documents. Please try again.');
        }

        SendSmsJob::dispatch($application, 'submission_complete');

        return redirect()->route('apply')
            ->with('success', 'Your application has been submitted successfully.')
            ->with('reference_code', $application->reference_code);
    }

    public function track()
    {
        return Inertia::render('Public/Track');
    }

    public function show(string $referenceCode)
    {
        $application = Application::where('reference_code', $referenceCode)
            ->with([
                'category',
                'documents.requiredDocument',
                'reviews' => fn($q) => $q->with('reviewer')->latest(),
            ])
            ->firstOrFail();

        $documents = $application->documents->map(function ($doc) {
            return [
                'id' => $doc->id,
                'doc_name' => $doc->requiredDocument?->doc_name,
                'file_name' => $doc->file_name,
                'is_resubmission' => $doc->is_resubmission,
                'resubmission_number' => $doc->resubmission_number,
                'file_url' => app(SignedUrlService::class)->generate($doc->file_path),
            ];
        });

        $reviews = $application->reviews->map(function ($review) {
            return [
                'stage' => $review->stage,
                'decision' => $review->decision,
                'remarks' => $review->remarks,
                'from_status' => $review->from_status,
                'to_status' => $review->to_status,
                'created_at' => $review->created_at->format('M d, Y g:i A'),
                'reviewed_by' => $review->reviewer?->first_name . ' ' . $review->reviewer?->last_name,
            ];
        });

        $reviewerRole = null;
        $resubmissionDocsRequired = [];
        if ($application->status === 'returned_to_applicant') {
            $latestReview = $application->reviews->first();
            $requiredDocIds = $latestReview?->resubmission_docs_required ?? [];
            $resubmissionDocsRequired = $application->documents
                ->filter(fn($d) => in_array($d->id, $requiredDocIds))
                ->map(fn($d) => [
                    'id' => $d->id,
                    'doc_name' => $d->requiredDocument?->doc_name ?? 'Document',
                    'capture_type' => $d->requiredDocument?->capture_type ?? 'single',
                    'scanner_size' => $d->requiredDocument?->scanner_size ?? 'a4',
                ])
                ->values();
            $reviewerRole = $latestReview?->reviewer?->role;
        }

        $roleLabels = [
            'admin' => 'Admin',
            'aics_staff' => 'AICS',
            'mswdo' => 'MSWDO',
            'accountant' => 'Accountant',
            'treasurer' => 'Treasurer',
            'mayors_office' => "Mayor's Office",
        ];

        return Inertia::render('Public/Track', [
            'application' => [
                'id' => $application->id,
                'reference_code' => $application->reference_code,
                'category_name' => $application->category?->category_name,
                'beneficiary_name' => trim(
                    $application->beneficiary_first_name . ' ' . $application->beneficiary_last_name
                ),
                'created_at' => $application->created_at->format('M d, Y g:i A'),
                'claimed_at' => $application->claimed_at?->format('M d, Y g:i A'),
                'status' => $application->status,
                'resubmission_remarks' => $application->resubmission_remarks,
                'reviewer_role' => $roleLabels[$reviewerRole] ?? $reviewerRole,
            ],
            'documents' => $documents,
            'reviews' => $reviews,
            'resubmission_docs_required' => $resubmissionDocsRequired,
        ]);
    }

    public function resubmit(string $referenceCode, Request $request)
    {
        $validated = $request->validate([
            'documents' => ['required', 'array', 'min:1'],
            'documents.*' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'document_ids' => ['required', 'array'],
            'document_ids.*' => ['required', 'exists:application_documents,id'],
        ]);

        $application = Application::where('reference_code', $referenceCode)
            ->where('status', 'returned_to_applicant')
            ->firstOrFail();

        $latestReview = Review::where('application_id', $application->id)
            ->latest('created_at')
            ->first();

        $requiredDocIds = $latestReview?->resubmission_docs_required ?? [];

        $maxResubmission = ApplicationDocument::where('application_id', $application->id)
            ->where('is_resubmission', true)
            ->max('resubmission_number') ?? 0;
        $resubmissionNumber = $maxResubmission + 1;

        foreach ($request->file('documents', []) as $i => $file) {
            $appDocId = $request->document_ids[$i];

            if (!in_array($appDocId, $requiredDocIds)) {
                continue;
            }

            $result = app(FileUploadService::class)->upload(
                $file,
                'application_documents',
                $application->id,
            );

            ApplicationDocument::where('id', $appDocId)->update([
                'file_name' => $result['file_name'],
                'file_path' => $result['file_path'],
                'file_size' => $result['file_size'],
                'mime_type' => $result['mime_type'],
                'is_resubmission' => true,
                'resubmission_number' => $resubmissionNumber,
            ]);
        }

        $returnStage = $latestReview?->stage;
        $nextStatus = $returnStage === 'mswdo_review' ? 'mswdo_review' : 'submitted';

        $application->update([
            'status' => $nextStatus,
            'resubmission_remarks' => null,
        ]);

        SendSmsJob::dispatch($application, 'application_under_review');

        return redirect()->route('track.show', $referenceCode)->with('success', 'Your documents have been resubmitted successfully.');
    }
}

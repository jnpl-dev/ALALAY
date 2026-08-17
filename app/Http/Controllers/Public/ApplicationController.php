<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreApplicationRequest;
use App\Http\Requests\Public\ResubmitDocumentsRequest;
use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\Review;
use App\Mail\SendApplicationOtpMail;
use App\Services\ApplicationSubmissionService;
use App\Services\FileUploadService;
use App\Services\SignedUrlService;
use App\Services\SmsService;
use App\Rules\Turnstile;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ApplicationController extends Controller
{
    public function store(StoreApplicationRequest $request)
    {
        try {
            $application = app(ApplicationSubmissionService::class)->submit($request, 'online');
        } catch (HttpException $e) {
            return redirect()->route('apply')
                ->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return redirect()->route('apply')
                ->with('error', 'Failed to upload documents. Please try again.');
        }

        return redirect()->route('apply')
            ->with('success', 'Your application has been submitted successfully.')
            ->with('reference_code', $application->reference_code);
    }

    public function track()
    {
        return Inertia::render('Public/Track');
    }

    public function show(string $referenceCode, Request $request)
    {
        $application = Application::where('reference_code', $referenceCode)
            ->with([
                'category',
                'documents.requiredDocument',
                'reviews' => fn($q) => $q->with('reviewer')->latest(),
            ])
            ->firstOrFail();

        $otpVerified = $request->session()->get('track_verified_' . $referenceCode, false);

        if (!$otpVerified) {
            $otpSent = $request->session()->has('track_otp_' . $referenceCode);
            $otpExpired = false;
            if ($otpSent) {
                $stored = $request->session()->get('track_otp_' . $referenceCode);
                $otpExpired = now() > $stored['expires_at'];
            }
            $resendData = $request->session()->get('track_resend_' . $referenceCode);
            return Inertia::render('Public/Track', [
                'application' => null,
                'documents' => [],
                'reviews' => [],
                'resubmission_docs_required' => [],
                'otp_required' => true,
                'otp_sent' => $otpSent,
                'otp_expired' => $otpExpired,
                'otp_attempts' => $otpSent ? ($request->session()->get('track_otp_' . $referenceCode)['attempts'] ?? 0) : 0,
                'otp_resend_count' => $resendData['count'] ?? 0,
                'otp_resend_available_at' => $resendData['available_at'] ?? null,
                'otp_resend_limit' => 3,
                'otp_cooldown_seconds' => 300,
                'reference_code' => $referenceCode,
            ]);
        }

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
            'internal_audit' => 'Internal Audit',
            'budget_officer' => 'Budget Office',
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
            'otp_required' => false,
            'reference_code' => $referenceCode,
        ]);
    }

    public function sendTrackOtp(string $referenceCode, Request $request)
    {
        $application = Application::where('reference_code', $referenceCode)->firstOrFail();

        if (config('turnstile.enabled')) {
            $request->validate([
                'cf-turnstile-response' => ['nullable', new Turnstile],
            ]);
        }

        $isResend = $request->session()->has('track_otp_' . $referenceCode);
        $resendData = $request->session()->get('track_resend_' . $referenceCode);
        $resendCount = $isResend ? ($resendData['count'] ?? 0) : 0;
        $availableAt = isset($resendData['available_at'])
            ? \Illuminate\Support\Carbon::parse($resendData['available_at'])
            : null;

        if ($isResend && $resendCount >= 3) {
            throw ValidationException::withMessages([
                'otp_code' => ['Resend limit reached. Please try again later.'],
            ]);
        }

        if ($isResend && $availableAt && now()->lt($availableAt)) {
            $remaining = max(1, (int) ceil(now()->diffInSeconds($availableAt)));
            throw ValidationException::withMessages([
                'otp_code' => ["Please wait {$remaining}s before requesting a new code."],
            ]);
        }

        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        $request->session()->put('track_otp_' . $referenceCode, [
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
        ]);

        $request->session()->put('track_resend_' . $referenceCode, [
            'count' => $isResend ? $resendCount + 1 : 0,
            'available_at' => now()->addMinutes(5),
        ]);

        $phone = $application->claimant_phone;
        $email = $application->claimant_email;

        if ($phone) {
            try {
                app(SmsService::class)->send(
                    $phone,
                    "ALALAY OTP: $code. Use this to track your application. Valid for 5 minutes.",
                    $application->id,
                    'track_otp',
                );
            } catch (\Exception $e) {
                if ($email) {
                    Mail::to($email)->send(new SendApplicationOtpMail($code, $application));
                }
            }
        } elseif ($email) {
            Mail::to($email)->send(new SendApplicationOtpMail($code, $application));
        }

        return redirect()->route('track.show', $referenceCode)
            ->with('success', 'OTP sent to your registered contact.');
    }

    public function verifyTrackOtp(string $referenceCode, Request $request)
    {
        $request->validate([
            'otp_code' => 'required|string|size:6',
        ]);

        $stored = $request->session()->get('track_otp_' . $referenceCode);

        if (!$stored || now() > $stored['expires_at']) {
            throw ValidationException::withMessages([
                'otp_code' => ['The verification code is invalid.'],
            ]);
        }

        if ($stored['attempts'] >= 5) {
            throw ValidationException::withMessages([
                'otp_code' => ['Too many incorrect attempts. Request a new OTP.'],
            ]);
        }

        if (!Hash::check($request->otp_code, $stored['code'])) {
            $stored['attempts']++;
            $request->session()->put('track_otp_' . $referenceCode, $stored);
            throw ValidationException::withMessages([
                'otp_code' => ['The verification code is invalid.'],
            ]);
        }

        $request->session()->put('track_verified_' . $referenceCode, true);
        $request->session()->forget('track_otp_' . $referenceCode);

        return redirect()->route('track.show', $referenceCode)
            ->with('success', 'Verification successful.');
    }

    public function trackPoll(Request $request): JsonResponse
    {
        $referenceCode = $request->query('reference_code');
        $since = $request->query('since');

        $application = Application::where('reference_code', $referenceCode)
            ->select('id', 'status', 'updated_at', 'claimed_at')
            ->firstOrFail();

        if ($since !== null && $application->updated_at->lte($since)) {
            return response()->json([
                'changed' => false,
                'data' => [],
                'last_checked' => now()->toIso8601String(),
            ]);
        }

        return response()->json([
            'changed' => true,
            'data' => [
                'status' => $application->status,
                'claimed_at' => $application->claimed_at?->format('M d, Y g:i A'),
            ],
            'last_checked' => now()->toIso8601String(),
        ]);
    }

    public function resubmit(string $referenceCode, ResubmitDocumentsRequest $request)
    {
        $validated = $request->validated();

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

        return redirect()->route('track.show', $referenceCode)->with('success', 'Your documents have been resubmitted successfully.');
    }
}

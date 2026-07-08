<?php

namespace App\Http\Controllers\Mswdo;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mswdo\CreateVoucherRequest;
use App\Models\Application;
use App\Models\Review;
use App\Services\FileUploadService;
use App\Services\SignedUrlService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VoucherController extends Controller
{
    public function __construct(
        protected FileUploadService $fileUploadService,
        protected SignedUrlService $signedUrlService,
    ) {}

    public function index(): Response
    {
        $tab = request('tab', 'to_create');
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
            'completed' => (clone $query)->where('status', 'voucher_checking'),
            default => (clone $query)->whereIn('status', ['voucher_creation', 'voucher_returned']),
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

        $categories = \App\Models\AssistanceCategory::where('is_active', true)->pluck('category_name');

        return Inertia::render('Mswdo/Vouchers/Index', [
            'applications' => $apps,
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
            'reviews.reviewer',
            'socialCaseStudy.conductedBy',
            'assistanceCode.reference',
            'assistanceCode.assignedBy',
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

        $scs = $application->socialCaseStudy;
        $socialCaseStudy = $scs ? [
            'id' => $scs->id,
            'uploaded_by' => $scs->conductedBy?->full_name,
            'conducted_at' => $scs->conducted_at,
            'page_count' => $scs->page_count,
            'file_size_label' => $scs->file_size_label,
            'signed_url' => $this->signedUrlService->generate($scs->file_path),
        ] : null;

        $existingVoucher = $application->vouchers()->latest()->first();
        $voucher = $existingVoucher ? [
            'id' => $existingVoucher->id,
            'version' => $existingVoucher->version,
            'file_name' => $existingVoucher->file_name,
            'file_size_label' => $existingVoucher->file_size_label,
            'page_count' => $existingVoucher->page_count,
            'prepared_at' => $existingVoucher->prepared_at,
            'prepared_by' => $existingVoucher->preparedBy?->full_name,
            'returned_at' => $existingVoucher->returned_at,
            'returned_by' => $existingVoucher->returnedBy?->full_name,
            'adjustment_remarks' => $existingVoucher->adjustment_remarks,
            'signed_url' => $this->signedUrlService->generate($existingVoucher->file_path),
        ] : null;

        $canEdit = in_array($application->status, ['voucher_creation', 'voucher_returned']);

        return Inertia::render('Mswdo/Vouchers/Create', [
            'canEdit' => $canEdit,
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
            'reviews' => $reviews,
            'socialCaseStudy' => $socialCaseStudy,
            'assistanceCode' => $application->assistanceCode ? [
                'id' => $application->assistanceCode->id,
                'code_type' => $application->assistanceCode->reference?->code_type,
                'amount' => $application->assistanceCode->amount,
                'assigned_by' => $application->assistanceCode->assignedBy?->full_name,
            ] : null,
            'existingVoucher' => $voucher,
        ]);
    }

    public function store(CreateVoucherRequest $request, $id): RedirectResponse
    {
        $application = Application::findOrFail($id);
        $this->authorize('create', \App\Models\Voucher::class);

        $file = $request->file('voucher_file');

        $existingVoucher = $application->vouchers()->latest()->first();
        $version = $existingVoucher ? $existingVoucher->version + 1 : 1;

        $uploadResult = $this->fileUploadService->upload(
            file: $file,
            table: 'vouchers',
            entityId: $application->id,
        );

        $application->vouchers()->create([
            'assistance_code_id' => $application->assistanceCode->id,
            'prepared_by' => $request->user()->id,
            'file_name' => $uploadResult['file_name'],
            'file_path' => $uploadResult['file_path'],
            'file_size' => $uploadResult['file_size'],
            'mime_type' => 'application/pdf',
            'version' => $version,
            'page_count' => $request->input('page_count', 1),
            'prepared_at' => now(),
            'adjustment_remarks' => $request->input('adjustment_remarks'),
        ]);

        $application->update([
            'status' => 'voucher_checking',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        Review::create([
            'application_id' => $application->id,
            'reviewed_by' => $request->user()->id,
            'stage' => 'voucher_creation',
            'decision' => 'voucher_created',
            'from_status' => $application->status === 'voucher_returned' ? 'voucher_returned' : 'voucher_creation',
            'to_status' => 'voucher_checking',
            'remarks' => $request->input('adjustment_remarks'),
            'created_at' => now(),
        ]);

        return redirect()
            ->route('mswdo.vouchers.index')
            ->with('success', "Voucher v{$version} submitted for Accountant review.");
    }

    public function voucherUrl($id): \Illuminate\Http\JsonResponse
    {
        $application = Application::findOrFail($id);
        $voucher = $application->vouchers()->latest()->firstOrFail();
        $this->authorize('view', $voucher);

        $url = $this->signedUrlService->generate($voucher->file_path);

        return response()->json(['url' => $url]);
    }
}

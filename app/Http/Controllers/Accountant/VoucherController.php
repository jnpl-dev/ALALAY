<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasPollCache;
use App\Models\Application;
use App\Models\Review;
use App\Services\SignedUrlService;
use App\Jobs\SendSmsJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VoucherController extends Controller
{
    use HasPollCache;

    public function __construct(
        protected SignedUrlService $signedUrlService,
    ) {}

    protected function getPollData(Request $request): array
    {
        $tab = $request->query('tab', 'pending');
        $search = $request->query('search');
        $category = $request->query('category');

        $query = Application::with('category', 'assistanceCode.reference', 'vouchers');

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
            'approved' => (clone $query)->where('status', 'with_treasurer'),
            'returned' => (clone $query)->where('status', 'voucher_returned'),
            default => (clone $query)->where('status', 'voucher_checking'),
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

    public function index(): Response
    {
        $tab = request('tab', 'pending');
        $search = request('search');
        $category = request('category');

        $categories = \App\Models\AssistanceCategory::where('is_active', true)->pluck('category_name');

        return Inertia::render('Accountant/Vouchers/Index', [
            'tab' => $tab,
            'search' => $search,
            'category' => $category,
            'categories' => $categories,
            'applications' => Inertia::defer(fn () => $this->loadApplications($tab, $search, $category)),
        ]);
    }

    protected function loadApplications(string $tab, ?string $search, ?string $category)
    {
        $query = Application::with('category', 'assistanceCode.reference', 'vouchers');

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
            'approved' => (clone $query)->where('status', 'with_treasurer'),
            'returned' => (clone $query)->where('status', 'voucher_returned'),
            default => (clone $query)->where('status', 'voucher_checking'),
        };

        return $applications->latest()
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
    }

    public function show($id): Response
    {
        $application = Application::with([
            'category',
            'reviews.reviewer',
            'assistanceCode.reference',
            'assistanceCode.assignedBy',
            'vouchers' => fn($q) => $q->latest(),
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

        $voucher = $application->vouchers()->latest()->first();
        $voucherData = $voucher ? [
            'id' => $voucher->id,
            'version' => $voucher->version,
            'file_name' => $voucher->file_name,
            'file_size_label' => $voucher->file_size_label,
            'page_count' => $voucher->page_count,
            'prepared_at' => $voucher->prepared_at,
            'prepared_by' => $voucher->preparedBy?->full_name,
            'returned_at' => $voucher->returned_at,
            'returned_by' => $voucher->returnedBy?->full_name,
            'adjustment_remarks' => $voucher->adjustment_remarks,
            'signed_url' => $this->signedUrlService->generate($voucher->file_path),
        ] : null;

        return Inertia::render('Accountant/Vouchers/Review', [
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
            'assistanceCode' => $application->assistanceCode ? [
                'id' => $application->assistanceCode->id,
                'code_type' => $application->assistanceCode->reference?->code_type,
                'amount' => $application->assistanceCode->amount,
                'assigned_by' => $application->assistanceCode->assignedBy?->full_name,
            ] : null,
            'voucher' => $voucherData,
            'reviews' => $reviews,
        ]);
    }

    public function approve(Request $request, $id): RedirectResponse
    {
        $application = Application::findOrFail($id);
        $this->authorize('approve', $application->vouchers()->latest()->first());

        $application->update([
            'status' => 'with_treasurer',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        Review::create([
            'application_id' => $application->id,
            'reviewed_by' => $request->user()->id,
            'stage' => 'accountant_review',
            'decision' => 'approved',
            'from_status' => 'voucher_checking',
            'to_status' => 'with_treasurer',
            'remarks' => $request->input('remarks'),
            'created_at' => now(),
        ]);

        SendSmsJob::dispatch($application, 'application_under_review');

        $this->bustPollCache();

        return redirect()
            ->route('accountant.vouchers.index')
            ->with('success', 'Voucher approved. Application forwarded to Treasurer.');
    }

    public function return(Request $request, $id): RedirectResponse
    {
        $application = Application::findOrFail($id);
        $voucher = $application->vouchers()->latest()->first();
        $this->authorize('returnVoucher', $voucher);

        $voucher->update([
            'returned_at' => now(),
            'returned_by' => $request->user()->id,
            'adjustment_remarks' => $request->input('remarks'),
        ]);

        $application->update([
            'status' => 'voucher_returned',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        Review::create([
            'application_id' => $application->id,
            'reviewed_by' => $request->user()->id,
            'stage' => 'accountant_review',
            'decision' => 'returned',
            'from_status' => 'voucher_checking',
            'to_status' => 'voucher_returned',
            'remarks' => $request->input('remarks'),
            'created_at' => now(),
        ]);

        $this->bustPollCache();

        return redirect()
            ->route('accountant.vouchers.index')
            ->with('success', 'Voucher returned to MSWDO for revision.');
    }
}

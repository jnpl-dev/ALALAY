<?php

namespace App\Http\Controllers\Treasurer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Review;
use App\Services\SignedUrlService;
use App\Jobs\SendSmsJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function __construct(
        protected SignedUrlService $signedUrlService,
    ) {}

    public function index(): Response
    {
        $tab = request('tab', 'pending');
        $search = request('search');
        $category = request('category');

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
            'cheque_ready' => (clone $query)->where('status', 'cheque_ready'),
            'hold' => (clone $query)->where('status', 'on_hold'),
            default => (clone $query)->where('status', 'budget_checking'),
        };

        $categories = \App\Models\AssistanceCategory::where('is_active', true)->pluck('category_name');

        return Inertia::render('Treasurer/Budget/Index', [
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
            'signed_url' => $this->signedUrlService->generate($voucher->file_path),
        ] : null;

        return Inertia::render('Treasurer/Budget/Check', [
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

    public function markReady(Request $request, $id): RedirectResponse
    {
        $application = Application::findOrFail($id);
        $voucher = $application->vouchers()->latest()->first();
        $this->authorize('markReady', $voucher);

        $application->update([
            'status' => 'cheque_ready',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        Review::create([
            'application_id' => $application->id,
            'reviewed_by' => $request->user()->id,
            'stage' => 'treasurer_review',
            'decision' => 'approved',
            'from_status' => 'budget_checking',
            'to_status' => 'cheque_ready',
            'remarks' => $request->input('remarks'),
            'created_at' => now(),
        ]);

        SendSmsJob::dispatch($application, 'cheque_claiming');

        return redirect()
            ->route('treasurer.budget.index')
            ->with('success', 'Cheque marked as ready for claiming.');
    }

    public function hold(Request $request, $id): RedirectResponse
    {
        $application = Application::findOrFail($id);
        $voucher = $application->vouchers()->latest()->first();
        $this->authorize('hold', $voucher);

        $application->update([
            'status' => 'on_hold',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        Review::create([
            'application_id' => $application->id,
            'reviewed_by' => $request->user()->id,
            'stage' => 'treasurer_review',
            'decision' => 'hold',
            'from_status' => $application->status === 'budget_checking' ? 'budget_checking' : 'cheque_ready',
            'to_status' => 'on_hold',
            'remarks' => $request->input('remarks'),
            'created_at' => now(),
        ]);

        return redirect()
            ->route('treasurer.budget.index')
            ->with('success', 'Application placed on hold.');
    }

    public function reEvaluate(Request $request, $id): RedirectResponse
    {
        $application = Application::findOrFail($id);
        $voucher = $application->vouchers()->latest()->first();
        $this->authorize('reEvaluate', $voucher);

        $application->update([
            'status' => 'cheque_ready',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        Review::create([
            'application_id' => $application->id,
            'reviewed_by' => $request->user()->id,
            'stage' => 'treasurer_review',
            'decision' => 'approved',
            'from_status' => 'on_hold',
            'to_status' => 'cheque_ready',
            'remarks' => $request->input('remarks'),
            'created_at' => now(),
        ]);

        SendSmsJob::dispatch($application, 'cheque_claiming');

        return redirect()
            ->route('treasurer.budget.index')
            ->with('success', 'Application re-evaluated and marked as cheque ready.');
    }
}

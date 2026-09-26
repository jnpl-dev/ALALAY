<?php

namespace App\Http\Controllers\BudgetOffice;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasPollCache;
use App\Models\Application;
use App\Models\AssistanceCategory;
use App\Models\Review;
use App\Models\Voucher;
use App\Services\SignedUrlService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VoucherController extends Controller
{
    use HasPollCache;

    public function __construct(
        protected SignedUrlService $signedUrlService,
    ) {}

    protected function getPollData(Request $request): array
    {
        $tab = $request->query('tab', 'budget_checking');
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
            'approved' => (clone $query)->where('status', 'voucher_recording'),
            'voucher_on_hold' => (clone $query)->where('status', 'voucher_on_hold'),
            default => (clone $query)->where('status', 'budget_checking'),
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
        $this->authorize('viewAny', \App\Models\Voucher::class);
        $tab = request('tab', 'budget_checking');
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
            'approved' => (clone $query)->where('status', 'voucher_recording'),
            'voucher_on_hold' => (clone $query)->where('status', 'voucher_on_hold'),
            default => (clone $query)->where('status', 'budget_checking'),
        };

        $categories = AssistanceCategory::where('is_active', true)->pluck('category_name');

        return Inertia::render('BudgetOffice/Vouchers/Index', [
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

    public function export()
    {
        $this->authorize('viewAny', \App\Models\Voucher::class);
        $tab = request('tab', 'budget_checking');
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

        $applications = (match ($tab) {
            'approved' => (clone $query)->where('status', 'voucher_recording'),
            'voucher_on_hold' => (clone $query)->where('status', 'voucher_on_hold'),
            default => (clone $query)->where('status', 'budget_checking'),
        })->latest()->get();

        $filename = 'alalay-budget-vouchers-' . $tab . '-' . now()->format('Y-m-d') . '.csv';

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

    public function show($id)
    {
        $application = Application::with([
            'category',
            'documents.requiredDocument',
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

        $documents = $application->documents->map(fn ($d) => [
            'id' => $d->id,
            'doc_name' => $d->requiredDocument?->doc_name ?? 'Document',
            'file_name' => $d->file_name,
            'file_path' => $d->file_path,
            'mime_type' => $d->mime_type,
            'is_resubmission' => $d->is_resubmission,
            'signed_url' => $this->signedUrlService->generate($d->file_path),
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

        return Inertia::render('BudgetOffice/Vouchers/Check', [
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
                'description' => $application->assistanceCode->reference?->description,
                'amount' => $application->assistanceCode->amount,
                'default_amount' => $application->assistanceCode->reference?->default_amount,
                'assigned_by' => $application->assistanceCode->assignedBy?->full_name,
                'assigned_at' => $application->assistanceCode->created_at?->format('M d, Y'),
            ] : null,
            'documents' => $documents,
            'voucher' => $voucherData,
            'reviews' => $reviews,
        ]);
    }

    public function approve(Request $request, $id)
    {
        $application = Application::findOrFail($id);
        $voucher = $application->vouchers()->latest()->first();
        $this->authorize('budgetApprove', $voucher ?? new Voucher);

        if ($application->status !== 'budget_checking') {
            return redirect()->back()->with('error', 'Voucher cannot be approved at this stage.');
        }

        $application->update([
            'status' => 'voucher_recording',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        Review::create([
            'application_id' => $application->id,
            'reviewed_by' => $request->user()->id,
            'stage' => 'budget_checking',
            'decision' => 'approved',
            'from_status' => 'budget_checking',
            'to_status' => 'voucher_recording',
            'remarks' => $request->input('remarks'),
            'created_at' => now(),
        ]);

        $this->bustPollCache();

        return redirect()->route('budget-office.vouchers.index')
            ->with('success', 'Voucher approved and forwarded to the Accountant.');
    }

    public function hold(Request $request, $id)
    {
        $application = Application::findOrFail($id);
        $voucher = $application->vouchers()->latest()->first();
        $this->authorize('budgetHold', $voucher ?? new Voucher);

        if ($application->status !== 'budget_checking') {
            return redirect()->back()->with('error', 'Voucher cannot be put on hold at this stage.');
        }

        $application->update([
            'status' => 'voucher_on_hold',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        Review::create([
            'application_id' => $application->id,
            'reviewed_by' => $request->user()->id,
            'stage' => 'budget_checking',
            'decision' => 'hold',
            'from_status' => 'budget_checking',
            'to_status' => 'voucher_on_hold',
            'remarks' => $request->input('remarks'),
            'created_at' => now(),
        ]);

        $this->bustPollCache();

        return redirect()->route('budget-office.vouchers.index')
            ->with('success', 'Voucher placed on hold.');
    }

    public function releaseHold(Request $request, $id)
    {
        $application = Application::findOrFail($id);
        $voucher = $application->vouchers()->latest()->first();
        $this->authorize('budgetRelease', $voucher ?? new Voucher);

        if ($application->status !== 'voucher_on_hold') {
            return redirect()->back()->with('error', 'Voucher is not currently on hold.');
        }

        $application->update([
            'status' => 'voucher_recording',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        Review::create([
            'application_id' => $application->id,
            'reviewed_by' => $request->user()->id,
            'stage' => 'budget_checking',
            'decision' => 'approved',
            'from_status' => 'voucher_on_hold',
            'to_status' => 'voucher_recording',
            'remarks' => $request->input('remarks'),
            'created_at' => now(),
        ]);

        $this->bustPollCache();

        return redirect()->route('budget-office.vouchers.index')
            ->with('success', 'Hold released. Voucher forwarded to the Accountant.');
    }
}

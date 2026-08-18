<?php

namespace Tests\Feature;

use App\Http\Middleware\AuditLogMiddleware;
use App\Models\Application;
use App\Models\AssistanceCategory;
use App\Models\AssistanceCode;
use App\Models\AssistanceCodeReference;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuditLogMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private function registerTestRoutes(): void
    {
        // Mimic real controllers: parameters are received as raw ids ($id),
        // so Laravel does NOT resolve them to models via implicit binding.
        Route::post('/audit-test/applications/{application}/approve', function ($application) {
            return response('ok');
        })->middleware([SubstituteBindings::class, AuditLogMiddleware::class])->name('aics.applications.approve');

        Route::get('/audit-test/empty', function () {
            return response('ok');
        })->middleware([SubstituteBindings::class, AuditLogMiddleware::class])->name('audit.test.empty');

        Route::post('/audit-test/vouchers/{voucher}/acknowledge', function ($voucher) {
            return response('ok');
        })->middleware([SubstituteBindings::class, AuditLogMiddleware::class])->name('treasurer.cheques.acknowledge');
    }

    private function makeUser(string $role = 'aics_staff'): User
    {
        return User::factory()->create([
            'role' => $role,
            'status' => 'active',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
        ]);
    }

    private function makeApplication(string $ref): Application
    {
        $category = AssistanceCategory::create([
            'category_name' => 'Medical Assistance',
            'is_active' => true,
        ]);

        return Application::create([
            'category_id' => $category->id,
            'reference_code' => $ref,
            'status' => 'submitted',
            'submission_type' => 'online',
            'claimant_last_name' => 'Test',
            'claimant_first_name' => 'One',
            'claimant_sex' => 'female',
            'claimant_dob' => '1990-01-01',
            'claimant_address' => 'Address',
            'claimant_phone' => '09123456789',
            'claimant_relationship_to_beneficiary' => 'self',
            'beneficiary_last_name' => 'Test',
            'beneficiary_first_name' => 'One',
            'beneficiary_sex' => 'female',
            'beneficiary_dob' => '1990-01-01',
            'beneficiary_address' => 'Address',
        ]);
    }

    private function makeVoucher(User $user, Application $application): Voucher
    {
        $reference = AssistanceCodeReference::create([
            'code_type' => 'burial',
            'default_amount' => 1000,
            'is_active' => true,
        ]);

        $assistanceCode = AssistanceCode::create([
            'application_id' => $application->id,
            'assistance_code_reference_id' => $reference->id,
            'amount' => 1000,
            'assigned_by' => $user->id,
        ]);

        return Voucher::create([
            'application_id' => $application->id,
            'assistance_code_id' => $assistanceCode->id,
            'prepared_by' => $user->id,
            'file_name' => 'voucher.pdf',
            'file_path' => 'vouchers/voucher.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'version' => 1,
            'prepared_at' => now(),
        ]);
    }

    public function test_approve_logs_human_description_with_ref_code(): void
    {
        $this->registerTestRoutes();
        $user = $this->makeUser();
        $application = $this->makeApplication('APP-2026-0001');

        $this->actingAs($user)
            ->post('/audit-test/applications/' . $application->id . '/approve')
            ->assertOk();

        $log = AuditLog::latest()->first();

        $this->assertSame('aics', $log->module);
        $this->assertSame('approve', $log->action);
        $this->assertSame('Application', $log->entity_type);
        $this->assertSame($application->id, $log->entity_id);
        $this->assertSame('Juan Dela Cruz approved application APP-2026-0001', $log->description);
        $this->assertStringNotContainsString('POST', $log->description);
    }

    public function test_get_requests_are_not_logged(): void
    {
        $this->registerTestRoutes();
        $user = $this->makeUser();

        $this->actingAs($user)->get('/audit-test/empty')->assertOk();

        $this->assertSame(0, AuditLog::count());
    }

    public function test_voucher_route_fills_entity(): void
    {
        $this->registerTestRoutes();
        $user = $this->makeUser('treasurer');
        $application = $this->makeApplication('APP-2026-0003');
        $voucher = $this->makeVoucher($user, $application);

        $this->actingAs($user)
            ->post('/audit-test/vouchers/' . $voucher->id . '/acknowledge')
            ->assertOk();

        $log = AuditLog::latest()->first();

        $this->assertSame('treasurer', $log->module);
        $this->assertSame('acknowledge', $log->action);
        $this->assertSame('Voucher', $log->entity_type);
        $this->assertSame($voucher->id, $log->entity_id);
        $this->assertSame('Juan Dela Cruz acknowledged the cheque for application APP-2026-0003', $log->description);
    }
}
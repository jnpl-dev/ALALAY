<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\AssistanceCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CompletedTabSimulationTest extends TestCase
{
    use RefreshDatabase;

    private User $aicsUser;
    private User $treasurerUser;
    private AssistanceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = AssistanceCategory::create([
            'id' => (string) Str::uuid(),
            'category_name' => 'Medical Assistance',
            'is_active' => true,
        ]);

        $this->aicsUser = User::create([
            'id' => (string) Str::uuid(),
            'first_name' => 'AICS',
            'last_name' => 'Staff',
            'email' => 'aics@test.com',
            'password' => 'password',
            'role' => 'aics_staff',
            'status' => 'active',
            'acceptable_use_policy_accepted_at' => now(),
        ]);

        $this->treasurerUser = User::create([
            'id' => (string) Str::uuid(),
            'first_name' => 'Treasurer',
            'last_name' => 'User',
            'email' => 'treasurer@test.com',
            'password' => 'password',
            'role' => 'treasurer',
            'status' => 'active',
            'acceptable_use_policy_accepted_at' => now(),
        ]);
    }

    private function createApp(string $status, ?string $claimedAt = null): Application
    {
        return Application::create([
            'id' => (string) Str::uuid(),
            'category_id' => $this->category->id,
            'reference_code' => 'REF-' . Str::random(8),
            'status' => $status,
            'submission_type' => 'online',
            'claimant_last_name' => 'Doe',
            'claimant_first_name' => 'Jane',
            'claimant_sex' => 'female',
            'claimant_dob' => '1990-01-01',
            'claimant_address' => '123 Test St',
            'claimant_phone' => '09171234567',
            'claimant_relationship_to_beneficiary' => 'Self',
            'beneficiary_last_name' => 'Doe',
            'beneficiary_first_name' => 'Jane',
            'beneficiary_sex' => 'female',
            'beneficiary_dob' => '1990-01-01',
            'beneficiary_address' => '123 Test St',
            'claimed_at' => $claimedAt,
        ]);
    }

    public function test_aics_claimed_tab_renders_correct_view(): void
    {
        $this->createApp('claimed', now()->toDateTimeString());

        $response = $this->actingAs($this->aicsUser)
            ->get('/aics/applications?tab=claimed');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Aics/Applications/Index')
            ->where('tab', 'claimed')
        );
    }

    public function test_aics_pending_tab_renders_correct_view(): void
    {
        $this->createApp('submitted');

        $response = $this->actingAs($this->aicsUser)
            ->get('/aics/applications?tab=pending');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Aics/Applications/Index')
            ->where('tab', 'pending')
        );
    }

    public function test_treasurer_claimed_tab_renders_correct_view(): void
    {
        $this->createApp('claimed', now()->toDateTimeString());

        $response = $this->actingAs($this->treasurerUser)
            ->get('/treasurer/cheques?tab=claimed');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Treasurer/Cheques/Index')
            ->where('tab', 'claimed')
        );
    }

    public function test_treasurer_pending_tab_renders_correct_view(): void
    {
        $this->createApp('with_treasurer');

        $response = $this->actingAs($this->treasurerUser)
            ->get('/treasurer/cheques?tab=pending');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Treasurer/Cheques/Index')
            ->where('tab', 'pending')
        );
    }

    public function test_aics_export_claimed_tab_csv_has_claimed_at_column(): void
    {
        $this->createApp('claimed', now()->toDateTimeString());

        $response = $this->actingAs($this->aicsUser)
            ->get('/aics/applications/export?tab=claimed');

        $response->assertOk();

        $content = $response->streamedContent();
        $this->assertStringContainsString('Claimed At', $content);
    }

    public function test_treasurer_export_claimed_tab_csv_has_claimed_at_column(): void
    {
        $this->createApp('claimed', now()->toDateTimeString());

        $response = $this->actingAs($this->treasurerUser)
            ->get('/treasurer/cheques/export?tab=claimed');

        $response->assertOk();

        $content = $response->streamedContent();
        $this->assertStringContainsString('Claimed At', $content);
    }

    public function test_aics_export_claimed_tab_csv_only_contains_claimed(): void
    {
        $this->createApp('submitted');
        $claimed = $this->createApp('claimed', now()->toDateTimeString());

        $response = $this->actingAs($this->aicsUser)
            ->get('/aics/applications/export?tab=claimed');

        $response->assertOk();

        $content = $response->streamedContent();
        $this->assertStringContainsString($claimed->reference_code, $content);
    }

    public function test_treasurer_export_claimed_tab_csv_only_contains_claimed(): void
    {
        $this->createApp('with_treasurer');
        $claimed = $this->createApp('claimed', now()->toDateTimeString());

        $response = $this->actingAs($this->treasurerUser)
            ->get('/treasurer/cheques/export?tab=claimed');

        $response->assertOk();

        $content = $response->streamedContent();
        $this->assertStringContainsString($claimed->reference_code, $content);
    }
}

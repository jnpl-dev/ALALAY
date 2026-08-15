<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\AssistanceCategory;
use App\Models\RequiredDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssistedApplicationTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(): RequiredDocument
    {
        $category = AssistanceCategory::create([
            'category_name' => 'Medical Assistance',
            'category_description' => 'Test category',
            'is_active' => true,
        ]);

        return RequiredDocument::create([
            'category_id' => $category->id,
            'doc_name' => 'Medical Certificate',
            'doc_description' => 'Required',
            'is_mandatory' => true,
            'is_active' => true,
            'capture_type' => 'document',
            'scanner_size' => 'a4',
        ]);
    }

    private function validPayload($category, $docId): array
    {
        return [
            'category_id' => $category->id,
            'claimant_last_name' => 'Dela Cruz',
            'claimant_first_name' => 'Juan',
            'claimant_middle_name' => null,
            'claimant_name_extension' => null,
            'claimant_sex' => 'Male',
            'claimant_dob' => '1990-01-01',
            'claimant_address' => 'Poblacion, General Mamerto Natividad, Nueva Ecija',
            'claimant_phone' => '09171234567',
            'claimant_email' => null,
            'claimant_relationship_to_beneficiary' => 'Parent',
            'beneficiary_last_name' => 'Dela Cruz',
            'beneficiary_first_name' => 'Maria',
            'beneficiary_middle_name' => null,
            'beneficiary_name_extension' => null,
            'beneficiary_sex' => 'Female',
            'beneficiary_dob' => '2015-05-05',
            'beneficiary_address' => 'Poblacion, General Mamerto Natividad, Nueva Ecija',
            'documents' => [
                UploadedFile::fake()->create('medical.pdf', 200, 'application/pdf'),
            ],
            'document_ids' => [$docId],
        ];
    }

    public function test_aics_staff_can_view_assisted_application_form(): void
    {
        $user = User::factory()->aicsStaff()->create();
        $category = AssistanceCategory::create([
            'category_name' => 'Financial Assistance',
            'category_description' => 'Test',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('aics.applications.create'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Aics/Applications/Create')
        );
    }

    public function test_aics_staff_can_submit_assisted_application(): void
    {
        $user = User::factory()->aicsStaff()->create();
        $document = $this->makeCategory();
        $category = $document->category;
        Storage::fake('supabase');

        $response = $this->actingAs($user)->post(
            route('aics.applications.store-assisted'),
            $this->validPayload($category, $document->id),
        );

        $response->assertRedirect(route('aics.applications.create'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('reference_code');

        $code = $response->getSession()->get('reference_code');
        $msg = $response->getSession()->get('success');
        $this->assertNotNull($code);

        $follow = $this->actingAs($user)->get(route('aics.applications.create'));
        $follow->assertInertia(fn ($page) => $page
            ->component('Aics/Applications/Create')
            ->where('flash.reference_code', $code)
            ->where('flash.success', $msg)
        );

        $application = Application::where('category_id', $category->id)->first();
        $this->assertNotNull($application);
        $this->assertEquals('walk_in', $application->submission_type);
        $this->assertEquals($user->id, $application->encoded_by);
        $this->assertEquals('submitted', $application->status);
        $this->assertNotNull($application->reference_code);
        $this->assertCount(1, $application->documents);
        $this->assertFalse($application->documents->first()->is_resubmission);
    }

    public function test_public_store_still_creates_online_submission(): void
    {
        $document = $this->makeCategory();
        $category = $document->category;
        Storage::fake('supabase');

        $response = $this->post('/apply', $this->validPayload($category, $document->id));

        $response->assertRedirect(route('apply'));
        $response->assertSessionHas('success');
        $response->assertSessionHas('reference_code');

        $application = Application::where('category_id', $category->id)->first();
        $this->assertNotNull($application);
        $this->assertEquals('online', $application->submission_type);
        $this->assertNull($application->encoded_by);
    }

    public function test_non_aics_role_cannot_access_assisted_form(): void
    {
        $user = User::factory()->mswdo()->create();

        $this->actingAs($user)->get(route('aics.applications.create'))->assertForbidden();
        $this->actingAs($user)->post(route('aics.applications.store-assisted'), [])->assertForbidden();
    }
}
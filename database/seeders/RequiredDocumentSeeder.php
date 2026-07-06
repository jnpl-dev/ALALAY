<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RequiredDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $medical  = DB::table('assistance_categories')->where('category_name', 'Medical Assistance')->value('id');
        $hospital = DB::table('assistance_categories')->where('category_name', 'Hospital Assistance')->value('id');
        $burial   = DB::table('assistance_categories')->where('category_name', 'Burial Assistance')->value('id');

        $documents = [

            // ---------------------------------------------------
            // CATEGORY 1 — Medical Assistance
            // ---------------------------------------------------
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $medical,
                'doc_name'        => 'Medical Certificate',
                'doc_description' => 'A certificate issued by a licensed physician confirming the medical condition of the beneficiary.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'a4',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $medical,
                'doc_name'        => 'Prescription',
                'doc_description' => 'A prescription issued by a licensed physician for the required medicines or treatment.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'a4',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $medical,
                'doc_name'        => "Applicant's Government ID",
                'doc_description' => 'Any valid government-issued ID of the applicant (claimant). Both front and back sides will be scanned.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'double',
                'scanner_size'    => 'card',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $medical,
                'doc_name'        => "Beneficiary's Government ID",
                'doc_description' => 'Any valid government-issued ID of the beneficiary. Both front and back sides will be scanned.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'double',
                'scanner_size'    => 'card',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $medical,
                'doc_name'        => "Applicant's Cedula",
                'doc_description' => 'Community tax certificate (BIR Form 0016) of the applicant (claimant).',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'half_sheet',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $medical,
                'doc_name'        => 'Barangay Indigency',
                'doc_description' => 'A certificate of indigency issued by the barangay where the beneficiary resides.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'a4',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $medical,
                'doc_name'        => 'Authorization Letter',
                'doc_description' => 'A letter authorizing the applicant to claim assistance on behalf of the beneficiary. Required if the claimant is not a direct relative of the beneficiary.',
                'is_mandatory'    => false,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'a4',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],

            // ---------------------------------------------------
            // CATEGORY 2 — Hospital Assistance
            // ---------------------------------------------------
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $hospital,
                'doc_name'        => 'Hospital Bill',
                'doc_description' => 'Official hospital bill or statement of account. Scan all pages if the bill spans multiple pages.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'multi',
                'scanner_size'    => 'a4',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $hospital,
                'doc_name'        => 'Prescription',
                'doc_description' => 'A prescription issued by a licensed physician for the required medicines or treatment.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'a4',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $hospital,
                'doc_name'        => 'Medical Certificate/Abstract',
                'doc_description' => 'A medical certificate or abstract issued by the attending physician summarizing the beneficiary\'s condition and treatment.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'a4',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $hospital,
                'doc_name'        => "Applicant's Government ID",
                'doc_description' => 'Any valid government-issued ID of the applicant (claimant). Both front and back sides will be scanned.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'double',
                'scanner_size'    => 'card',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $hospital,
                'doc_name'        => "Beneficiary's Government ID",
                'doc_description' => 'Any valid government-issued ID of the beneficiary. Both front and back sides will be scanned.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'double',
                'scanner_size'    => 'card',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $hospital,
                'doc_name'        => "Applicant's Cedula",
                'doc_description' => 'Community tax certificate (BIR Form 0016) of the applicant (claimant).',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'half_sheet',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $hospital,
                'doc_name'        => 'Barangay Indigency',
                'doc_description' => 'A certificate of indigency issued by the barangay where the beneficiary resides.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'a4',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $hospital,
                'doc_name'        => 'Authorization Letter',
                'doc_description' => 'A letter authorizing the applicant to claim assistance on behalf of the beneficiary. Required if the claimant is not a direct relative of the beneficiary.',
                'is_mandatory'    => false,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'a4',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],

            // ---------------------------------------------------
            // CATEGORY 3 — Burial Assistance
            // ---------------------------------------------------
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $burial,
                'doc_name'        => 'Certified Copy of Birth Certificate',
                'doc_description' => 'A certified true copy of the birth certificate of the deceased beneficiary issued by the PSA or local civil registry.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'a4',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $burial,
                'doc_name'        => "Applicant's Government ID",
                'doc_description' => 'Any valid government-issued ID of the applicant (claimant). Both front and back sides will be scanned.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'double',
                'scanner_size'    => 'card',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $burial,
                'doc_name'        => "Applicant's Cedula",
                'doc_description' => 'Community tax certificate (BIR Form 0016) of the applicant (claimant).',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'half_sheet',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $burial,
                'doc_name'        => "Beneficiary's Barangay Residency",
                'doc_description' => 'A certificate of residency issued by the barangay confirming the deceased beneficiary was a resident of General Mamerto Natividad.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'a4',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $burial,
                'doc_name'        => 'Barangay Indigency',
                'doc_description' => 'A certificate of indigency issued by the barangay on behalf of the bereaved family.',
                'is_mandatory'    => true,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'a4',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'id'              => Str::uuid()->toString(),
                'category_id'     => $burial,
                'doc_name'        => 'Authorization Letter',
                'doc_description' => 'A letter authorizing the applicant to claim assistance on behalf of the bereaved family. Required if the claimant is not a direct relative of the beneficiary.',
                'is_mandatory'    => false,
                'is_active'       => true,
                'capture_type'    => 'single',
                'scanner_size'    => 'a4',
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('required_documents')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        DB::table('required_documents')->insert($documents);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\ApplicationDocument;
use App\Models\AssistanceCategory;
use App\Models\Review;
use App\Models\SmsNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApplicationDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        SmsNotification::truncate();
        Review::truncate();
        ApplicationDocument::truncate();
        Application::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $category = AssistanceCategory::first()->id;

        $barangays = [
            'Balangkare Norte', 'Balangkare Sur', 'Balaring',
            'Belen', 'Bravo', 'Burol', 'Kabulihan',
            'Mag-asawang Sampaloc', 'Manarog', 'Mataas na Kahoy',
            'Panacsac', 'Picaleon', 'Pinahan', 'Platero',
            'Poblacion', 'Pula', 'Pulong Singkamas',
            'Sapang Bato', 'Talabutab Norte', 'Talabutab Sur',
        ];

        $relationships = ['Self', 'Parent', 'Spouse', 'Sibling', 'Child', 'Guardian'];
        $submissionTypes = ['online', 'walk_in'];

        $statuses = [
            'submitted', 'returned_to_applicant', 'mswdo_review',
            'social_case_study_uploaded', 'assistance_coding', 'internal_audit_review',
            'returned_assistance_coding', 'voucher_creation', 'budget_checking',
            'voucher_on_hold', 'voucher_recording', 'with_treasurer',
            'cheque_ready', 'claimed',
        ];

        $claimantNames = [
            ['Juan', 'Miguel', 'Dela Cruz'],
            ['Maria', 'Angela', 'Santos'],
            ['Carlo', 'Emmanuel', 'Reyes'],
            ['Patricia', 'Anne', 'Mendoza'],
            ['Joshua', 'Daniel', 'Navarro'],
            ['Alyssa', 'Mae', 'Villanueva'],
            ['Mark', 'Anthony', 'Flores'],
            ['Christine', 'Joy', 'Ramos'],
            ['Francis', 'Xavier', 'Castillo'],
            ['Camille', 'Rose', 'Aquino'],
            ['John', 'Patrick', 'Bautista'],
            ['Samantha', 'Nicole', 'Garcia'],
            ['Kenneth', 'Paul', 'Morales'],
            ['Erika', 'Louise', 'Cruz'],
            ['Vincent', 'Adrian', 'Perez'],
            ['Jasmine', 'Claire', 'Torres'],
            ['Ryan', 'Joseph', 'Fernandez'],
            ['Bea', 'Kristine', 'Gonzales'],
            ['Angelo', 'Marco', 'Rivera'],
            ['Denise', 'Mae', 'Herrera'],
            ['Gabriel', 'Luis', 'Santiago'],
            ['Katrina', 'Faith', 'Domingo'],
            ['Christian', 'Paolo', 'Valdez'],
            ['Michelle', 'Anne', 'Cabrera'],
            ['Nathan', 'Elijah', 'Salazar'],
            ['Charmaine', 'Grace', 'Espino'],
            ['Jerome', 'Vincent', 'Padilla'],
            ['Bianca', 'Sofia', 'Mercado'],
            ['Rafael', 'Antonio', 'Lim'],
            ['Angela', 'Marie', 'Manalo'],
        ];

        for ($i = 0; $i < 30; $i++) {
            $status = $statuses[$i % count($statuses)];
            $isWalkIn = $i % 5 === 0;
            $barangay = $barangays[$i % count($barangays)];
            $purok = rand(1, 10);
            $claimantAge = rand(22, 70);
            $beneficiaryAge = max(5, $claimantAge - rand(5, 50));

            [$first, $middle, $last] = $claimantNames[$i];
            $sex = in_array($first, ['Maria', 'Patricia', 'Alyssa', 'Christine', 'Camille', 'Samantha', 'Erika', 'Jasmine', 'Bea', 'Denise', 'Katrina', 'Michelle', 'Charmaine', 'Bianca', 'Angela']) ? 'female' : 'male';
            $isSelf = $i % 3 === 0;
            $commonAddress = "Purok {$purok}, Barangay {$barangay}, General Mamerto Natividad, Nueva Ecija";

            Application::create([
                'id' => (string) Str::uuid(),
                'category_id' => $category,
                'reference_code' => 'GMN-2026-' . Str::upper(Str::random(6)),
                'status' => $status,
                'submission_type' => $submissionTypes[$isWalkIn ? 1 : 0],
                'claimant_last_name' => $last,
                'claimant_first_name' => $first,
                'claimant_middle_name' => $middle,
                'claimant_name_extension' => null,
                'claimant_sex' => $sex,
                'claimant_dob' => now()->subYears($claimantAge)->subDays(rand(1, 365))->format('Y-m-d'),
                'claimant_address' => $commonAddress,
                'claimant_phone' => '09763265198',
                'claimant_email' => strtolower($first) . '.' . strtolower($last) . '@example.com',
                'claimant_relationship_to_beneficiary' => $isSelf ? 'Self' : $relationships[$i % count($relationships)],
                'beneficiary_last_name' => $isSelf ? $last : $claimantNames[($i + 5) % 30][2],
                'beneficiary_first_name' => $isSelf ? $first : $claimantNames[($i + 5) % 30][0],
                'beneficiary_middle_name' => $isSelf ? $middle : $claimantNames[($i + 7) % 30][1],
                'beneficiary_name_extension' => null,
                'beneficiary_sex' => $i % 3 === 0 ? 'female' : 'male',
                'beneficiary_dob' => now()->subYears($beneficiaryAge)->subDays(rand(1, 365))->format('Y-m-d'),
                'beneficiary_address' => $commonAddress,
                'beneficiary_barangay' => $barangay,
                'resubmission_remarks' => $status === 'returned_to_applicant' ? 'Please attach updated proof of income and barangay certificate.' : null,
                'claimed_at' => $status === 'claimed' ? now()->subDays(rand(1, 30)) : null,
                'claiming_date' => $status === 'claimed' ? now()->subDays(rand(1, 30))->format('Y-m-d') : null,
                'created_at' => now()->subDays($i % 4 === 0 ? rand(8, 14) : rand(0, 7))->startOfDay()->addMinutes(rand(0, 1439)),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Seeded 30 demo applications with various statuses.');
    }
}
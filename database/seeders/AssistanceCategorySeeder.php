<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssistanceCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'id'                   => Str::uuid()->toString(),
                'category_name'        => 'Medical Assistance',
                'category_description' => 'Financial assistance for outpatient medical expenses including consultations, medicines, and laboratory fees.',
                'is_active'            => true,
                'created_at'           => now(),
                'updated_at'           => now(),
            ],
            [
                'id'                   => Str::uuid()->toString(),
                'category_name'        => 'Hospital Assistance',
                'category_description' => 'Financial assistance for inpatient hospital expenses including hospital bills, medicines, and medical procedures.',
                'is_active'            => true,
                'created_at'           => now(),
                'updated_at'           => now(),
            ],
            [
                'id'                   => Str::uuid()->toString(),
                'category_name'        => 'Burial Assistance',
                'category_description' => 'Financial assistance for burial and funeral expenses of indigent residents.',
                'is_active'            => true,
                'created_at'           => now(),
                'updated_at'           => now(),
            ],
        ];

        DB::table('assistance_categories')->insert($categories);
    }
}

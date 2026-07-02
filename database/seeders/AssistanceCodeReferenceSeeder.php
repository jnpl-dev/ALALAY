<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssistanceCodeReferenceSeeder extends Seeder
{
    public function run(): void
    {
        $codes = [
            [
                'id'             => Str::uuid()->toString(),
                'code_type'      => 'A',
                'default_amount' => 500.00,
                'description'    => 'Standard assistance type A — base level support.',
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'id'             => Str::uuid()->toString(),
                'code_type'      => 'B',
                'default_amount' => 1000.00,
                'description'    => 'Standard assistance type B.',
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'id'             => Str::uuid()->toString(),
                'code_type'      => 'C',
                'default_amount' => 1500.00,
                'description'    => 'Standard assistance type C.',
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'id'             => Str::uuid()->toString(),
                'code_type'      => 'D',
                'default_amount' => 2000.00,
                'description'    => 'Standard assistance type D.',
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'id'             => Str::uuid()->toString(),
                'code_type'      => 'E',
                'default_amount' => 2500.00,
                'description'    => 'Standard assistance type E.',
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'id'             => Str::uuid()->toString(),
                'code_type'      => 'F',
                'default_amount' => 3000.00,
                'description'    => 'Standard assistance type F — highest base level support.',
                'is_active'      => true,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ];

        DB::table('assistance_code_references')->insert($codes);
    }
}

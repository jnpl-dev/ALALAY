<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Branding
            [
                'id'            => Str::uuid()->toString(),
                'setting_key'   => 'system_name',
                'setting_value' => 'ALALAY',
                'setting_group' => 'branding',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'id'            => Str::uuid()->toString(),
                'setting_key'   => 'system_tagline',
                'setting_value' => 'A Digital AICS Management and Notification System',
                'setting_group' => 'branding',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'id'            => Str::uuid()->toString(),
                'setting_key'   => 'municipality_name',
                'setting_value' => 'General Mamerto Natividad, Nueva Ecija',
                'setting_group' => 'branding',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'id'            => Str::uuid()->toString(),
                'setting_key'   => 'primary_color',
                'setting_value' => '#3B82F6',
                'setting_group' => 'branding',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],

            // File Upload
            [
                'id'            => Str::uuid()->toString(),
                'setting_key'   => 'file_max_size_mb',
                'setting_value' => '10',
                'setting_group' => 'uploads',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'id'            => Str::uuid()->toString(),
                'setting_key'   => 'allowed_file_types',
                'setting_value' => 'jpg,jpeg,png,pdf',
                'setting_group' => 'uploads',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],

            // SMS
            [
                'id'            => Str::uuid()->toString(),
                'setting_key'   => 'sms_enabled',
                'setting_value' => 'true',
                'setting_group' => 'sms',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'id'            => Str::uuid()->toString(),
                'setting_key'   => 'sms_sender_name',
                'setting_value' => 'ALALAY',
                'setting_group' => 'sms',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
        ];

        DB::table('system_settings')->insert($settings);
    }
}

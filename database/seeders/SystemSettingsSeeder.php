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

            // SMS Templates — Updates
            [
                'id'            => Str::uuid()->toString(),
                'setting_key'   => 'sms_template_submission_complete',
                'setting_value' => 'Your AICS application {reference_code} has been received. We will notify you once it is reviewed. Track: {track_url}',
                'setting_group' => 'sms_templates',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'id'            => Str::uuid()->toString(),
                'setting_key'   => 'sms_template_under_review',
                'setting_value' => 'Good day! Your AICS application {reference_code} is now under review by our office. We will update you on the next steps.',
                'setting_group' => 'sms_templates',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'id'            => Str::uuid()->toString(),
                'setting_key'   => 'sms_template_resubmission_needed',
                'setting_value' => 'Your application {reference_code} needs resubmission. Reason: {remarks}. Please resubmit via {track_url}.',
                'setting_group' => 'sms_templates',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'id'            => Str::uuid()->toString(),
                'setting_key'   => 'sms_template_cheque_ready',
                'setting_value' => 'Your AICS cheque is ready for claiming at the MSWDO office. Ref: {reference_code}. Please bring a valid ID.',
                'setting_group' => 'sms_templates',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],

            // SMS Template — Claiming
            [
                'id'            => Str::uuid()->toString(),
                'setting_key'   => 'sms_template_cheque_claiming',
                'setting_value' => 'Your AICS cheque is scheduled for claiming on {claiming_date}. Please visit the MSWDO office on the said date. Ref: {reference_code}.',
                'setting_group' => 'sms_templates',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
        ];

        DB::table('system_settings')->upsert(
            $settings,
            ['setting_key'],
            ['setting_value', 'setting_group', 'updated_at']
        );

        $this->command->info('System settings seeded successfully.');
    }
}

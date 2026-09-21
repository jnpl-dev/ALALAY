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
            // File Upload
            [
                'id'            => Str::uuid()->toString(),
                'setting_key'   => 'max_file_size_kb',
                'setting_value' => '5120',
                'setting_group' => 'uploads',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'id'            => Str::uuid()->toString(),
                'setting_key'   => 'allowed_mime_types',
                'setting_value' => 'image/jpeg,image/png,application/pdf',
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

        $validKeys = array_column($settings, 'setting_key');
        $deleted = DB::table('system_settings')
            ->whereNotIn('setting_key', $validKeys)
            ->delete();

        $this->command->info('System settings seeded successfully.' . ($deleted ? " Removed {$deleted} obsolete setting(s)." : ''));
    }
}

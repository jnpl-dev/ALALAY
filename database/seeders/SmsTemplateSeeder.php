<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SmsTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
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
                'setting_value' => 'Your AICS cheque is now ready. You will receive a separate notice once the claiming date is set. Ref: {reference_code}.',
                'setting_group' => 'sms_templates',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'id'            => Str::uuid()->toString(),
                'setting_key'   => 'sms_template_cheque_claiming',
                'setting_value' => 'Your AICS cheque claiming is scheduled on {claiming_date}. Please proceed to the MSWDO office on the said date. Ref: {reference_code}.',
                'setting_group' => 'sms_templates',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
        ];

        DB::table('system_settings')->upsert(
            $templates,
            ['setting_key'],
            ['setting_value', 'setting_group', 'updated_at']
        );

        $this->command->info('SMS templates seeded successfully.');
    }
}

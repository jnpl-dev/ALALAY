<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM(
            'submitted', 'screening', 'returned_to_applicant', 'resubmitted', 'mswdo_review',
            'social_case_study_uploaded', 'assistance_coding', 'voucher_creation',
            'voucher_checking', 'voucher_returned', 'with_treasurer',
            'budget_checking', 'on_hold', 'cheque_ready', 'claimed'
        ) NOT NULL DEFAULT 'submitted'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM(
            'submitted', 'screening', 'returned_to_applicant', 'mswdo_review',
            'social_case_study_uploaded', 'assistance_coding', 'voucher_creation',
            'voucher_checking', 'voucher_returned', 'with_treasurer',
            'budget_checking', 'on_hold', 'cheque_ready', 'claimed'
        ) NOT NULL DEFAULT 'submitted'");
    }
};

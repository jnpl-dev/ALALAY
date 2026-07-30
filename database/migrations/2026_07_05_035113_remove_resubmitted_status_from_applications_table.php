<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM(
            'submitted', 'screening', 'returned_to_applicant', 'mswdo_review',
            'social_case_study_uploaded', 'assistance_coding', 'voucher_creation',
            'voucher_checking', 'voucher_returned', 'with_treasurer',
            'budget_checking', 'on_hold', 'cheque_ready', 'claimed'
        ) NOT NULL DEFAULT 'submitted'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM(
            'submitted', 'screening', 'returned_to_applicant', 'resubmitted', 'mswdo_review',
            'social_case_study_uploaded', 'assistance_coding', 'voucher_creation',
            'voucher_checking', 'voucher_returned', 'with_treasurer',
            'budget_checking', 'on_hold', 'cheque_ready', 'claimed'
        ) NOT NULL DEFAULT 'submitted'");
    }
};

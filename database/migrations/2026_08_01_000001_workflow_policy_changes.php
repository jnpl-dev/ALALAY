<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // The backfill targets (voucher_on_hold, voucher_recording, internal_audit_review,
        // returned_assistance_coding) are not yet in the live enums, so we must first
        // expand each enum to a superset, run the data backfill, then drop the old values.

        // ---- Step 1: expand enums to supersets ----

        $statusSuperset = "'submitted', 'screening', 'returned_to_applicant', 'mswdo_review',
            'social_case_study_uploaded', 'assistance_coding', 'internal_audit_review',
            'returned_assistance_coding', 'voucher_creation', 'voucher_checking', 'voucher_returned',
            'budget_checking', 'voucher_recording', 'voucher_on_hold', 'on_hold',
            'with_treasurer', 'cheque_ready', 'claimed'";

        DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM($statusSuperset) NOT NULL DEFAULT 'submitted'");
        DB::statement("ALTER TABLE reviews MODIFY COLUMN from_status ENUM($statusSuperset) NOT NULL");
        DB::statement("ALTER TABLE reviews MODIFY COLUMN to_status ENUM($statusSuperset) NOT NULL");

        DB::statement("ALTER TABLE reviews MODIFY COLUMN stage ENUM(
            'aics_screening', 'mswdo_review', 'assistance_coding', 'internal_audit_review',
            'voucher_creation', 'voucher_checking', 'accountant_review', 'budget_checking',
            'voucher_recording', 'treasurer_acknowledgment', 'treasurer_review'
        ) NOT NULL");

        // ---- Step 2: data backfill ----

        // AICS 'screening' is retired; those applications are back under plain 'submitted'.
        DB::statement("UPDATE applications SET status = 'submitted' WHERE status = 'screening'");
        DB::statement("UPDATE reviews SET from_status = 'submitted' WHERE from_status = 'screening'");
        DB::statement("UPDATE reviews SET to_status = 'submitted' WHERE to_status = 'screening'");

        // Treasurer hold is replaced by the Budget Office hold.
        DB::statement("UPDATE applications SET status = 'voucher_on_hold' WHERE status = 'on_hold'");
        // Accountant stage renamed voucher_checking -> voucher_recording.
        DB::statement("UPDATE applications SET status = 'voucher_recording' WHERE status = 'voucher_checking'");
        // Voucher-return flow removed; treat returned vouchers as pending budget check (dev/demo data).
        DB::statement("UPDATE applications SET status = 'budget_checking' WHERE status = 'voucher_returned'");

        DB::statement("UPDATE reviews SET from_status = 'voucher_on_hold' WHERE from_status = 'on_hold'");
        DB::statement("UPDATE reviews SET to_status = 'voucher_on_hold' WHERE to_status = 'on_hold'");
        DB::statement("UPDATE reviews SET from_status = 'voucher_recording' WHERE from_status = 'voucher_checking'");
        DB::statement("UPDATE reviews SET to_status = 'voucher_recording' WHERE to_status = 'voucher_checking'");
        DB::statement("UPDATE reviews SET from_status = 'budget_checking' WHERE from_status = 'voucher_returned'");
        DB::statement("UPDATE reviews SET to_status = 'budget_checking' WHERE to_status = 'voucher_returned'");
        DB::statement("UPDATE reviews SET stage = 'voucher_recording' WHERE stage IN ('voucher_checking', 'accountant_review')");

        // Mayor's Office role is removed; keep the demo account but disable it.
        DB::statement("UPDATE users SET role = 'admin', status = 'inactive' WHERE role = 'mayors_office'");

        // ---- Step 3: drop retired values ----

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM(
            'admin', 'aics_staff', 'mswdo', 'accountant', 'treasurer',
            'internal_audit', 'budget_officer'
        ) NOT NULL");

        $finalStatuses = "'submitted', 'returned_to_applicant', 'mswdo_review', 'social_case_study_uploaded',
            'assistance_coding', 'internal_audit_review', 'returned_assistance_coding',
            'voucher_creation', 'budget_checking', 'voucher_on_hold', 'voucher_recording',
            'with_treasurer', 'cheque_ready', 'claimed'";

        DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM($finalStatuses) NOT NULL DEFAULT 'submitted'");
        DB::statement("ALTER TABLE reviews MODIFY COLUMN from_status ENUM($finalStatuses) NOT NULL");
        DB::statement("ALTER TABLE reviews MODIFY COLUMN to_status ENUM($finalStatuses) NOT NULL");

        DB::statement("ALTER TABLE reviews MODIFY COLUMN stage ENUM(
            'aics_screening', 'mswdo_review', 'assistance_coding', 'internal_audit_review',
            'voucher_creation', 'budget_checking', 'voucher_recording',
            'treasurer_acknowledgment', 'treasurer_review'
        ) NOT NULL");

        DB::statement("ALTER TABLE vouchers DROP FOREIGN KEY vouchers_returned_by_foreign,
            DROP COLUMN returned_by,
            DROP COLUMN returned_at,
            DROP COLUMN adjustment_remarks");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE vouchers
            ADD COLUMN returned_at TIMESTAMP NULL,
            ADD COLUMN returned_by CHAR(36) NULL,
            ADD COLUMN adjustment_remarks TEXT NULL,
            ADD CONSTRAINT vouchers_returned_by_foreign FOREIGN KEY (returned_by) REFERENCES users (id)");

        // Best-effort reverse backfill: expand enums to supersets first so targets are legal.
        $statusSuperset = "'submitted', 'screening', 'returned_to_applicant', 'mswdo_review',
            'social_case_study_uploaded', 'assistance_coding', 'internal_audit_review',
            'returned_assistance_coding', 'voucher_creation', 'voucher_checking', 'voucher_returned',
            'budget_checking', 'voucher_recording', 'voucher_on_hold', 'on_hold',
            'with_treasurer', 'cheque_ready', 'claimed'";

        DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM($statusSuperset) NOT NULL DEFAULT 'submitted'");
        DB::statement("ALTER TABLE reviews MODIFY COLUMN from_status ENUM($statusSuperset) NOT NULL");
        DB::statement("ALTER TABLE reviews MODIFY COLUMN to_status ENUM($statusSuperset) NOT NULL");
        DB::statement("ALTER TABLE reviews MODIFY COLUMN stage ENUM(
            'aics_screening', 'mswdo_review', 'assistance_coding', 'internal_audit_review',
            'voucher_creation', 'voucher_checking', 'accountant_review', 'budget_checking',
            'voucher_recording', 'treasurer_acknowledgment', 'treasurer_review'
        ) NOT NULL");

        DB::statement("UPDATE applications SET status = 'on_hold' WHERE status = 'voucher_on_hold'");
        DB::statement("UPDATE applications SET status = 'voucher_checking' WHERE status = 'voucher_recording'");
        DB::statement("UPDATE reviews SET from_status = 'on_hold' WHERE from_status = 'voucher_on_hold'");
        DB::statement("UPDATE reviews SET to_status = 'on_hold' WHERE to_status = 'voucher_on_hold'");
        DB::statement("UPDATE reviews SET from_status = 'voucher_checking' WHERE from_status = 'voucher_recording'");
        DB::statement("UPDATE reviews SET to_status = 'voucher_checking' WHERE to_status = 'voucher_recording'");
        DB::statement("UPDATE reviews SET stage = 'accountant_review' WHERE stage = 'voucher_recording'");

        $oldStatuses = "'submitted', 'screening', 'returned_to_applicant', 'mswdo_review',
            'social_case_study_uploaded', 'assistance_coding', 'voucher_creation',
            'voucher_checking', 'voucher_returned', 'with_treasurer',
            'budget_checking', 'on_hold', 'cheque_ready', 'claimed'";

        DB::statement("ALTER TABLE applications MODIFY COLUMN status ENUM($oldStatuses) NOT NULL DEFAULT 'submitted'");
        DB::statement("ALTER TABLE reviews MODIFY COLUMN from_status ENUM($oldStatuses) NOT NULL");
        DB::statement("ALTER TABLE reviews MODIFY COLUMN to_status ENUM($oldStatuses) NOT NULL");

        DB::statement("ALTER TABLE reviews MODIFY COLUMN stage ENUM(
            'aics_screening', 'mswdo_review', 'assistance_coding',
            'voucher_creation', 'voucher_checking', 'accountant_review',
            'treasurer_acknowledgment', 'treasurer_review', 'budget_checking'
        ) NOT NULL");

        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM(
            'admin', 'aics_staff', 'mswdo', 'accountant', 'treasurer', 'mayors_office'
        ) NOT NULL");
    }
};

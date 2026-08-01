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

        // Budget Office hold writes decision 'hold' (see BudgetOffice\VoucherController::hold).
        // Keep the legacy 'on_hold' value so historical reviews are not truncated.
        DB::statement("ALTER TABLE reviews MODIFY COLUMN decision ENUM(
            'approved', 'returned', 'coded', 'voucher_created', 'voucher_approved',
            'voucher_returned', 'cheque_ready', 'on_hold', 'hold', 'claimed'
        ) NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE reviews MODIFY COLUMN decision ENUM(
            'approved', 'returned', 'coded', 'voucher_created', 'voucher_approved',
            'voucher_returned', 'cheque_ready', 'on_hold', 'claimed'
        ) NOT NULL");
    }
};

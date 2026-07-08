<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE reviews MODIFY COLUMN stage ENUM(
            'aics_screening', 'mswdo_review', 'assistance_coding',
            'voucher_creation', 'voucher_checking', 'accountant_review',
            'treasurer_acknowledgment', 'treasurer_review', 'budget_checking'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE reviews MODIFY COLUMN stage ENUM(
            'aics_screening', 'mswdo_review', 'assistance_coding',
            'voucher_creation', 'voucher_checking', 'treasurer_acknowledgment',
            'budget_checking'
        ) NOT NULL");
    }
};

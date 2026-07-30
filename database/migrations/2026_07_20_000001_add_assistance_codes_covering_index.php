<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistance_codes', function (Blueprint $table) {
            $table->index(['application_id', 'amount']);
        });
    }

    public function down(): void
    {
        Schema::table('assistance_codes', function (Blueprint $table) {
            $table->dropIndex(['application_id', 'amount']);
        });
    }
};

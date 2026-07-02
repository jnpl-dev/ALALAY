<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assistance_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('application_id')->unique()->constrained('applications')->cascadeOnDelete();
            $table->foreignUuid('assistance_code_reference_id')->constrained('assistance_code_references');
            $table->decimal('amount', 12, 2);
            $table->foreignUuid('assigned_by')->constrained('users');
            $table->timestamps();

            $table->index('application_id');
            $table->index('assistance_code_reference_id');
            $table->index('assigned_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assistance_codes');
    }
};

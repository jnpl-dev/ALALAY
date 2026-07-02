<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignUuid('assistance_code_id')->constrained('assistance_codes');
            $table->foreignUuid('prepared_by')->constrained('users');
            $table->string('file_name', 255);
            $table->text('file_path');
            $table->unsignedInteger('file_size');
            $table->string('mime_type', 100);
            $table->unsignedTinyInteger('version')->default(1);
            $table->text('adjustment_remarks')->nullable();
            $table->timestamps();

            $table->index('application_id');
            $table->index('assistance_code_id');
            $table->index('prepared_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};

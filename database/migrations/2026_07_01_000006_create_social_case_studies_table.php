<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_case_studies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('application_id')->unique()->constrained('applications')->cascadeOnDelete();
            $table->foreignUuid('conducted_by')->constrained('users');
            $table->string('file_name', 255);
            $table->text('file_path');
            $table->unsignedInteger('file_size');
            $table->string('mime_type', 100);
            $table->timestamps();

            $table->index('application_id');
            $table->index('conducted_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_case_studies');
    }
};

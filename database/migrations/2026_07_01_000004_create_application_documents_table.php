<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignUuid('required_doc_id')->constrained('required_documents');
            $table->string('file_name', 255);
            $table->text('file_path');
            $table->unsignedInteger('file_size');
            $table->string('mime_type', 100);
            $table->boolean('is_resubmission')->default(false);
            $table->unsignedTinyInteger('resubmission_number')->default(0);
            $table->timestamps();

            $table->index('application_id');
            $table->index('required_doc_id');
            $table->index('is_resubmission');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};

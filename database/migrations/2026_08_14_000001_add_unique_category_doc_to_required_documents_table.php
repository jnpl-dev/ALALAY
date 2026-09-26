<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('required_documents', function (Blueprint $table) {
            $table->unique(['category_id', 'doc_name']);
        });
    }

    public function down(): void
    {
        Schema::table('required_documents', function (Blueprint $table) {
            $table->dropUnique(['category_id', 'doc_name']);
        });
    }
};
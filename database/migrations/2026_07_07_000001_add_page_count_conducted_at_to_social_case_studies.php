<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_case_studies', function (Blueprint $table) {
            $table->unsignedTinyInteger('page_count')->default(1)->after('mime_type');
            $table->timestamp('conducted_at')->after('page_count');
        });
    }

    public function down(): void
    {
        Schema::table('social_case_studies', function (Blueprint $table) {
            $table->dropColumn(['page_count', 'conducted_at']);
        });
    }
};

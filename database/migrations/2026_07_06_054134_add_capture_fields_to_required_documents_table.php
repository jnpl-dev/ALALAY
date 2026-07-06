<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('required_documents', function (Blueprint $table) {
            $table->string('capture_type', 10)->default('single')->after('is_active');
            $table->string('scanner_size', 12)->default('a4')->after('capture_type');
        });

        DB::statement("UPDATE required_documents SET capture_type = 'double', scanner_size = 'card' WHERE doc_name LIKE '%Government ID%'");
        DB::statement("UPDATE required_documents SET scanner_size = 'half_sheet' WHERE doc_name LIKE '%Cedula%'");
        DB::statement("UPDATE required_documents SET capture_type = 'multi' WHERE doc_name = 'Hospital Bill'");
    }

    public function down(): void
    {
        Schema::table('required_documents', function (Blueprint $table) {
            $table->dropColumn(['capture_type', 'scanner_size']);
        });
    }
};

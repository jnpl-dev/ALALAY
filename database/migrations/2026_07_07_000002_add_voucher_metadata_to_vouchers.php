<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->unsignedTinyInteger('page_count')->default(1)->after('version');
            $table->timestamp('prepared_at')->after('page_count');
            $table->timestamp('returned_at')->nullable()->after('adjustment_remarks');
            $table->foreignUuid('returned_by')->nullable()->constrained('users')->after('returned_at');
        });
    }

    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('returned_by');
            $table->dropColumn(['page_count', 'prepared_at', 'returned_at']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'status']);
        });

        Schema::table('required_documents', function (Blueprint $table) {
            $table->index(['category_id', 'is_active']);
        });

        Schema::table('application_documents', function (Blueprint $table) {
            $table->index(['application_id', 'required_doc_id']);
            $table->index(['application_id', 'is_resubmission']);
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table->index(['application_id', 'version']);
            $table->index(['prepared_by', 'created_at']);
        });

        Schema::table('sms_notifications', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->index(['claimant_last_name', 'claimant_first_name']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['reviewed_by', 'created_at']);
        });

        Schema::table('social_case_studies', function (Blueprint $table) {
            $table->index(['conducted_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role', 'status']);
        });

        Schema::table('required_documents', function (Blueprint $table) {
            $table->dropIndex(['category_id', 'is_active']);
        });

        Schema::table('application_documents', function (Blueprint $table) {
            $table->dropIndex(['application_id', 'required_doc_id']);
            $table->dropIndex(['application_id', 'is_resubmission']);
        });

        Schema::table('vouchers', function (Blueprint $table) {
            $table->dropIndex(['application_id', 'version']);
            $table->dropIndex(['prepared_by', 'created_at']);
        });

        Schema::table('sms_notifications', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex(['claimant_last_name', 'claimant_first_name']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['reviewed_by', 'created_at']);
        });

        Schema::table('social_case_studies', function (Blueprint $table) {
            $table->dropIndex(['conducted_by', 'created_at']);
        });
    }
};

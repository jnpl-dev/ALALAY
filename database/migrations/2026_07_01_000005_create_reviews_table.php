<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignUuid('reviewed_by')->constrained('users');
            $table->enum('stage', [
                'aics_screening', 'mswdo_review', 'assistance_coding',
                'voucher_creation', 'voucher_checking', 'treasurer_acknowledgment',
                'budget_checking',
            ]);
            $table->enum('decision', [
                'approved', 'returned', 'coded', 'voucher_created',
                'voucher_approved', 'voucher_returned', 'cheque_ready',
                'on_hold', 'claimed',
            ]);
            $table->enum('from_status', [
                'submitted', 'screening', 'returned_to_applicant', 'mswdo_review',
                'social_case_study_uploaded', 'assistance_coding', 'voucher_creation',
                'voucher_checking', 'voucher_returned', 'with_treasurer',
                'budget_checking', 'on_hold', 'cheque_ready', 'claimed',
            ]);
            $table->enum('to_status', [
                'submitted', 'screening', 'returned_to_applicant', 'mswdo_review',
                'social_case_study_uploaded', 'assistance_coding', 'voucher_creation',
                'voucher_checking', 'voucher_returned', 'with_treasurer',
                'budget_checking', 'on_hold', 'cheque_ready', 'claimed',
            ]);
            $table->text('remarks')->nullable();
            $table->json('resubmission_docs_required')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('application_id');
            $table->index('reviewed_by');
            $table->index('stage');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};

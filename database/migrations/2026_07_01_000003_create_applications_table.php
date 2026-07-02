<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->constrained('assistance_categories');
            $table->string('reference_code', 20)->unique();
            $table->enum('status', [
                'submitted', 'screening', 'returned_to_applicant', 'mswdo_review',
                'social_case_study_uploaded', 'assistance_coding', 'voucher_creation',
                'voucher_checking', 'voucher_returned', 'with_treasurer',
                'budget_checking', 'on_hold', 'cheque_ready', 'claimed',
            ])->default('submitted');
            $table->enum('submission_type', ['online', 'walk_in'])->default('online');
            $table->foreignUuid('encoded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('claimant_last_name', 100);
            $table->string('claimant_first_name', 100);
            $table->string('claimant_middle_name', 100)->nullable();
            $table->string('claimant_name_extension', 10)->nullable();
            $table->enum('claimant_sex', ['male', 'female']);
            $table->date('claimant_dob');
            $table->text('claimant_address');
            $table->text('claimant_phone');
            $table->text('claimant_email')->nullable();
            $table->string('claimant_relationship_to_beneficiary', 100);
            $table->string('beneficiary_last_name', 100);
            $table->string('beneficiary_first_name', 100);
            $table->string('beneficiary_middle_name', 100)->nullable();
            $table->string('beneficiary_name_extension', 10)->nullable();
            $table->enum('beneficiary_sex', ['male', 'female']);
            $table->date('beneficiary_dob');
            $table->text('beneficiary_address');
            $table->text('resubmission_remarks')->nullable();
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            $table->index('category_id');
            $table->index('status');
            $table->index('reference_code');
            $table->index('submission_type');
            $table->index('encoded_by');
            $table->index('reviewed_by');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};

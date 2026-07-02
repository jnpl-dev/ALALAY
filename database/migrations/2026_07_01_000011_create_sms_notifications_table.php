<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('application_id')->constrained('applications')->cascadeOnDelete();
            $table->string('recipient_phone', 20);
            $table->string('trigger_event', 100);
            $table->text('message_body');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->json('provider_response')->nullable();
            $table->timestamps();

            $table->index('application_id');
            $table->index('trigger_event');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_notifications');
    }
};

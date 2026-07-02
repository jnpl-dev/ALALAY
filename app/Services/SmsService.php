<?php

namespace App\Services;

use App\Models\SmsNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(string $recipient, string $message, ?string $referenceCode = null): SmsNotification
    {
        $recipient = ltrim($recipient, '+');

        $notification = SmsNotification::create([
            'recipient' => $recipient,
            'message_body' => $message,
            'reference_code' => $referenceCode ?? 'N/A',
            'status' => 'pending',
        ]);

        try {
            $driver = config('sms.driver');

            if ($driver === 'philsms') {
                $this->sendViaPhilsms($notification);
            } else {
                $this->sendViaLog($notification);
            }
        } catch (\Throwable $e) {
            $notification->update([
                'status' => 'failed',
                'provider_response' => ['error' => $e->getMessage()],
            ]);

            Log::error('SmsService: failed to send SMS', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $notification->fresh();
    }

    protected function sendViaPhilsms(SmsNotification $notification): void
    {
        $token = config('sms.philsms.api_token');
        $senderId = config('sms.philsms.sender_id');
        $endpoint = config('sms.philsms.endpoint');

        if (blank($token)) {
            $notification->update([
                'status' => 'failed',
                'provider_response' => ['error' => 'PHILSMS_API_TOKEN is not configured'],
            ]);
            return;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($endpoint, [
            'recipient' => $notification->recipient,
            'sender_id' => $senderId,
            'type' => 'plain',
            'message' => $notification->message_body,
        ]);

        $body = $response->json();

        if ($response->successful() && ($body['status'] ?? '') === 'success') {
            $notification->update([
                'status' => 'sent',
                'provider_response' => $body,
                'sent_at' => now(),
            ]);
        } else {
            $notification->update([
                'status' => 'failed',
                'provider_response' => $body ?? ['http_status' => $response->status()],
            ]);
        }
    }

    protected function sendViaLog(SmsNotification $notification): void
    {
        Log::info('SmsService: SMS logged (driver=log)', [
            'notification_id' => $notification->id,
            'recipient' => $notification->recipient,
            'message' => $notification->message_body,
        ]);

        $notification->update([
            'status' => 'sent',
            'provider_response' => ['driver' => 'log', 'message' => 'SMS logged to console'],
            'sent_at' => now(),
        ]);
    }
}

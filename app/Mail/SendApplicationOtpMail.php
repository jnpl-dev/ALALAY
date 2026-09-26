<?php

namespace App\Mail;

use App\Models\Application;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendApplicationOtpMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public string $otpCode;
    public Application $application;

    public function __construct(string $otpCode, Application $application)
    {
        $this->otpCode = $otpCode;
        $this->application = $application;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your ALALAY Application Tracking OTP',
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.application-otp',
        );
    }
}

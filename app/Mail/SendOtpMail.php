<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otpCode;

    public User $user;

    public function __construct(string $otpCode, User $user)
    {
        $this->otpCode = $otpCode;
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your ALALAY Login Verification Code',
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.otp',
        );
    }
}

<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendContactMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $senderName;
    public string $senderEmail;
    public string $senderMessage;

    public function __construct(string $name, string $email, string $message)
    {
        $this->senderName = $name;
        $this->senderEmail = $email;
        $this->senderMessage = $message;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Contact Message from ' . $this->senderName,
            replyTo: $this->senderEmail,
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.contact',
        );
    }
}

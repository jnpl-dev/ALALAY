<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLoginDetected extends Notification
{
    public function __construct(
        public string $ip,
        public string $userAgent,
        public Carbon $loginAt,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New Login Detected — ALALAY')
            ->line('A login to your ALALAY account was detected from a new device or location.')
            ->line('IP Address: ' . $this->ip)
            ->line('Time: ' . $this->loginAt->setTimezone('Asia/Manila')->format('F j, Y g:i A'))
            ->line('If this was not you, contact your system administrator immediately.');
    }
}

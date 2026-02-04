<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class EmailVerificationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $verificationCode;

    public function __construct(string $verificationCode)
    {
        $this->verificationCode = $verificationCode;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Verify Your Email Address')
            ->greeting('Hello ' . ($notifiable->name ?? 'User') . '!')
            ->line('Thank you for registering with ZARYQ.')
            ->line('Your verification code is:')
            ->line($this->verificationCode)
            ->line('This code will expire in 15 minutes.')
            ->line('If you did not create an account, no further action is required.')
            ->salutation('Best regards,')
            ->subject('ZARYQ Team');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'verification_code' => $this->verificationCode,
        ];
    }
}

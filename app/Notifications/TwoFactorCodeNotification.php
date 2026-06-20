<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorCodeNotification extends Notification
{
    use Queueable;

    public function __construct(public string $code)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your login verification code')
            ->greeting('Login verification')
            ->line('Use the code below to finish signing in. It expires in 10 minutes.')
            ->line('**'.$this->code.'**')
            ->line('If you did not try to sign in, please change your password immediately.');
    }
}

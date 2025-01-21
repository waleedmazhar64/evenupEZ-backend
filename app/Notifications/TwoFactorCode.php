<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorCode extends Notification
{
    protected $code;

    public function __construct($code)
    {
        $this->code = $code;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your Two-Factor Authentication Code for EvenupEZ')
            ->line('Your 2FA code is: ' . $this->code)
            ->line('The code will expire in 10 minutes.')
            ->line('If you did not request this, please ignore this email.');
    }
}

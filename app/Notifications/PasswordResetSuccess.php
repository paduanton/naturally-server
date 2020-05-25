<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class PasswordResetSuccess extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('You are changed your password successful.')
            ->line('If you did change password, no further action is required.')
            ->line('If you did not change your password, protect your account and contact us.');
    }

    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}

<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class PasswordChangingAlert extends Notification implements ShouldQueue
{
    use Queueable;

    protected $appName;

    public function __construct()
    {
        $this->appName = config('app.name');
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject("Security Alert - {$this->appName}")
            ->line('Your password has been updated through our website successfully just now.')
            ->line('If you did actually change your password, no further action is required.')
            ->line('If you did not change your password, protect your account and contact us.');
    }

    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}

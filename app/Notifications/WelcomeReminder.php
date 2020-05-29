<?php

namespace App\Notifications;

use App\Users;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class WelcomeReminder extends Notification implements ShouldQueue
{
    use Queueable;

    protected $user, $appName, $frontendURI;

    public function __construct(Users $user)
    {
        $this->user = $user;
        $this->appName = config('app.name');
        $this->frontendURI = config('app.frontend_url');
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $userFirstName = strtok($this->user->name, ' ');

        return (new MailMessage)
            ->subject("Start enjoying food in a very unique way with {$this->appName}")
            ->greeting("Welcome to {$this->appName}, {$userFirstName}!")
            ->line("We're excited to have begun a relationship with you and we hope you can enjoy the best experience you could ever have with us. 🥂")
            ->line("We see food in an unexampled way and if you don't... We would like you to do it too 🥘😋. We look forward for your contribution to our Website.")
            ->line("Your username is {$this->user->username} and you can sign-in in our Website with it or your email account.")
            ->line("If you would like so, you may log-in with your social media accounts too =)... Like Google, Facebook or even Twitter!!!")
            ->action('Enjoy now', $this->frontendURI);
    }

    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}

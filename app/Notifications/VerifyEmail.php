<?php

namespace App\Notifications;

use App\EmailVerifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmail extends Notification implements ShouldQueue
{
    use Queueable;

    protected $frontendURI, $emailVerification, $appName;

    public function __construct(EmailVerifications $verify)
    {
        $this->frontendURI = config('app.frontend_url');
        $this->emailVerification = $verify;
        $this->appName = config('app.name');
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $id = $this->emailVerification->id;
        $token = $this->emailVerification->token;
        $signature =  $this->emailVerification->signature;

        $frontendRoute = $this->frontendURI . "/verify/{$id}/email/{$token}?signature={$signature}/";

        return (new MailMessage)
            ->subject("Verify Email - {$this->appName}")
            ->line('Please click the button below to verify your email address.')
            ->action('Verify Email Address', $frontendRoute)
            ->line('If you did not create an account or have not requested an email verification from us, no further action is required.');
    }

    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}

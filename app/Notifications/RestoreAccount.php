<?php

namespace App\Notifications;

use App\RestoredAccounts;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class RestoreAccount extends Notification implements ShouldQueue
{
    use Queueable;

    protected $frontendURI, $restoredAccount, $appName;

    public function __construct(RestoredAccounts $restore)
    {
        $this->frontendURI = config('app.frontend_url');
        $this->restoredAccount = $restore;
        $this->appName = config('app.name');
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $id = $this->restoredAccount->id;
        $token = $this->restoredAccount->token;
        $signature =  $this->restoredAccount->signature;

        $frontendRoute = $this->frontendURI . "/restore/{$id}/account/{$token}?signature={$signature}/";

        return (new MailMessage)
            ->subject("Restore your account - {$this->appName}")
            ->line('Please click the button below to restore your account immediately.')
            ->action('RESTORE ACCOUNT NOW 😁', $frontendRoute)
            ->line('If you did not had an account with us before or have not requested an account restore, no further action is required.');
    }

    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}

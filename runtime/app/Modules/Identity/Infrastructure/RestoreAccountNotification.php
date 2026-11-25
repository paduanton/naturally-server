<?php

declare(strict_types=1);

namespace App\Modules\Identity\Infrastructure;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class RestoreAccountNotification extends Notification
{
    public function __construct(#[\SensitiveParameter] private readonly string $token) {}
    public function via(object $notifiable): array { return ['mail']; }
    public function toMail(object $notifiable): MailMessage
    {
        $fragment = http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], '', '&', PHP_QUERY_RFC3986);
        $url = rtrim(config('app.url'), '/').'/auth/restore-account#'.$fragment;

        return (new MailMessage())
            ->subject('Restore your Naturally account')
            ->line('A request was made to restore your account.')
            ->action('Restore account', $url)
            ->line('This link expires in 30 minutes. If you did not request it, ignore this email.');
    }
    public function __debugInfo(): array { return ['proof' => '[REDACTED]']; }
    public function __serialize(): array { throw new \LogicException('Restoration credentials cannot be serialized.'); }
}

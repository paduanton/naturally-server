<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class Report extends Notification implements ShouldQueue
{
    use Queueable;

    protected $appName, $content, $appEnv;

    public function __construct($content)
    {
        $this->content = $content;
        $this->appName = config('app.name');
        $this->appEnv = config('app.env');
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $isUser = (isset($this->content['user']) && !empty($this->content['user'])) ? true : false;

        return (new MailMessage)
            ->subject("Report from - {$this->appName} Application - ({$this->appEnv})")
            ->line("Title: {$this->content['title']}")
            ->line("Category: {$this->content['category']}")
            ->line("Description: {$this->content['description']}")
            ->line("Who Reported: {$this->content['who_reported']}")
            ->line("From: {$this->content['email']}")
            ->line($isUser ? "Is User: Yes - From User Id: {$this->content['user']->id}" : "");
    }

    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}

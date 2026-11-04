<?php

namespace App\Notifications;

use App\Models\EventNews;
use App\Notifications\Concerns\RespectsGmailQuota;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventNewsPublished extends Notification implements ShouldQueue
{
    use RespectsGmailQuota;

    public function __construct(public EventNews $news) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->news->loadMissing('event');
        $event = $this->news->event;
        $title = $event?->title ?? 'evento';

        return (new MailMessage)
            ->subject('Novidade: '.$title)
            ->greeting('Olá, '.$notifiable->name)
            ->line($this->news->title)
            ->line($this->news->content)
            ->action('Ver o evento', route('events.show', $event?->id ?? 0));
    }
}

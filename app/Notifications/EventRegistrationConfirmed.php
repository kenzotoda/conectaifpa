<?php

namespace App\Notifications;

use App\Models\Event;
use App\Notifications\Concerns\QueuesOutboundMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventRegistrationConfirmed extends Notification implements ShouldQueue
{
    use QueuesOutboundMail;

    public function __construct(public Event $event) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $start = $this->event->start_date?->format('d/m/Y');
        $end = $this->event->end_date?->format('d/m/Y');
        $period = $start ?? '';
        if ($end && $end !== $start) {
            $period = $start.' a '.$end;
        }

        $message = (new MailMessage)
            ->subject('Inscrição confirmada: '.$this->event->title)
            ->greeting('Olá, '.$notifiable->name)
            ->line('Sua inscrição em '.$this->event->title.' foi confirmada.')
            ->line('Modalidade: '.$this->event->modality.'.');

        if ($period !== '') {
            $message->line('Período: '.$period.'.');
        }

        return $message->action('Ver o evento', route('events.show', $this->event->id));
    }
}

<?php

namespace App\Notifications;

use App\Models\Event;
use App\Notifications\Concerns\QueuesOutboundMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SatisfactionSurveyAvailable extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject('Pesquisa de satisfação: '.$this->event->title)
            ->greeting('Olá, '.$notifiable->name)
            ->line('O evento '.$this->event->title.' foi encerrado.')
            ->line('A pesquisa é curta, de 1 a 5, e fica separada do parecer dos trabalhos.')
            ->action('Responder pesquisa', route('events.satisfaction.form', $this->event));
    }
};

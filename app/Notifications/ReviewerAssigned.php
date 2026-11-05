<?php

namespace App\Notifications;

use App\Models\Work;
use App\Notifications\Concerns\QueuesOutboundMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewerAssigned extends Notification implements ShouldQueue
{
    use QueuesOutboundMail;

    public function __construct(public Work $work) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->work->loadMissing('event');

        return (new MailMessage)
            ->subject('Trabalho para avaliar: '.$this->work->event->title)
            ->greeting('Olá, '.$notifiable->name)
            ->line('A coordenação designou você para avaliar um trabalho no evento '.$this->work->event->title.'.')
            ->line('Trabalho: '.$this->work->listTitle(true).'.')
            ->action('Abrir a avaliação', route('reviews.form', $this->work->id));
    }
}

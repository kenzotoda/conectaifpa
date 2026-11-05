<?php

namespace App\Notifications;

use App\Models\Work;
use App\Notifications\Concerns\QueuesOutboundMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkSubmitted extends Notification implements ShouldQueue
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
        $type = Work::workTypeLabels()[$this->work->work_type] ?? $this->work->work_type;

        return (new MailMessage)
            ->subject('Submissão recebida: '.$this->work->event->title)
            ->greeting('Olá, '.$notifiable->name)
            ->line('Recebemos sua submissão de '.$type.' no evento '.$this->work->event->title.'.')
            ->line('Identificação: '.$this->work->listTitle(true).'.')
            ->action('Ver minhas submissões', route('works.my'));
    }
}

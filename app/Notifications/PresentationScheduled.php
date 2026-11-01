<?php

namespace App\Notifications;

use App\Models\Work;
use App\Models\WorkPresentation;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PresentationScheduled extends Notification
{
    public function __construct(public Work $work, public WorkPresentation $presentation) {}

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
        $tz = config('app.timezone');
        $start = $this->presentation->scheduled_start?->timezone($tz)->format('d/m/Y H:i');
        $end = $this->presentation->scheduled_end?->timezone($tz)->format('d/m/Y H:i');
        $types = [
            WorkPresentation::TYPE_ORAL => 'Oral',
            WorkPresentation::TYPE_POSTER => 'Pôster',
            WorkPresentation::TYPE_ONLINE => 'Online',
        ];
        $type = $types[$this->presentation->presentation_type] ?? $this->presentation->presentation_type;

        $message = (new MailMessage)
            ->subject('Apresentação agendada: '.$this->work->event->title)
            ->greeting('Olá, '.$notifiable->name)
            ->line('A apresentação do trabalho "'.$this->work->listTitle(true).'" foi agendada.')
            ->line('Tipo: '.$type.'.');

        if ($start) {
            $when = $end ? $start.' às '.$end : $start;
            $message->line('Horário: '.$when.'.');
        }

        if (filled($this->presentation->location)) {
            $message->line('Local ou link: '.$this->presentation->location.'.');
        }

        return $message->action('Ver minha apresentação', route('works.my-presentation'));
    }
}

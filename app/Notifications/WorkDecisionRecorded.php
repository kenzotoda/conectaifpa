<?php

namespace App\Notifications;

use App\Models\Work;
use App\Notifications\Concerns\RespectsGmailQuota;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkDecisionRecorded extends Notification implements ShouldQueue
{
    use RespectsGmailQuota;

    public const ACCEPTED = 'accepted';

    public const CORRECTIONS = 'corrections';

    public const REJECTED = 'rejected';

    public const DESK_REJECTED = 'desk_rejected';

    public function __construct(public Work $work, public string $outcome) {}

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
        $title = $this->work->listTitle(true);
        $event = $this->work->event->title;

        $message = (new MailMessage)->greeting('Olá, '.$notifiable->name);

        if ($this->outcome === self::ACCEPTED) {
            $message->subject('Trabalho aceito: '.$event)
                ->line('Seu trabalho "'.$title.'" foi aceito no evento '.$event.'.');
        } elseif ($this->outcome === self::CORRECTIONS) {
            $deadline = $this->work->correction_deadline_at
                ? $this->work->correction_deadline_at->timezone(config('app.timezone'))->format('d/m/Y H:i')
                : null;
            $message->subject('Correção solicitada: '.$event)
                ->line('A coordenação pediu correções no trabalho "'.$title.'" do evento '.$event.'.');
            if ($deadline) {
                $message->line('Envie a versão corrigida até '.$deadline.'.');
            }
        } elseif ($this->outcome === self::DESK_REJECTED) {
            $message->subject('Trabalho recusado na triagem: '.$event)
                ->line('Seu trabalho "'.$title.'" foi recusado na triagem do evento '.$event.' e não seguirá para avaliação.');
        } else {
            $message->subject('Trabalho não aceito: '.$event)
                ->line('Seu trabalho "'.$title.'" não foi aceito no evento '.$event.'.');
        }

        $feedback = trim((string) ($this->work->author_feedback ?? ''));
        if ($feedback !== '') {
            $message->line('Retorno da coordenação: '.$feedback);
        }

        return $message->action('Ver o trabalho', route('works.show', $this->work->id));
    }
}

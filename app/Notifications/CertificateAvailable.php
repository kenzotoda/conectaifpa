<?php

namespace App\Notifications;

use App\Models\Certificate;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CertificateAvailable extends Notification
{
    public function __construct(public Certificate $certificate) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->certificate->loadMissing('event');
        $type = Certificate::typeLabel($this->certificate->tipo);
        $event = $this->certificate->event?->title ?? 'evento';

        return (new MailMessage)
            ->subject('Certificado disponível: '.$event)
            ->greeting('Olá, '.$notifiable->name)
            ->line('Seu certificado de '.$type.' no evento '.$event.' está disponível.')
            ->line('Código de validação: '.$this->certificate->codigo_validacao.'.')
            ->action('Ver meus certificados', route('certificates.my'));
    }
}

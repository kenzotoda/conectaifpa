<?php

namespace App\Notifications;

use App\Notifications\Concerns\QueuesOutboundMail;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailQueued extends VerifyEmail implements ShouldQueue
{
    use QueuesOutboundMail;

    public function toMail($notifiable)
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Confirme seu e-mail no ConectaIFPA')
            ->greeting('Olá!')
            ->line('Sua conta foi criada. Confirme o e-mail '.$notifiable->email.' para entrar no sistema.')
            ->action('Confirmar e-mail', $url)
            ->line('O link vale por 60 minutos e abre neste computador, no endereço do ConectaIFPA.')
            ->line('Se você não criou esta conta, ignore esta mensagem.')
            ->salutation('Equipe ConectaIFPA');
    }
}

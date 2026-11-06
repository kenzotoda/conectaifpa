<?php

namespace App\Notifications;

use App\Notifications\Concerns\QueuesOutboundMail;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class VerifyEmailQueued extends VerifyEmail implements ShouldQueue
{
    use QueuesOutboundMail;

    protected function verificationUrl($notifiable)
    {
        $relative = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
            absolute: false,
        );

        return rtrim((string) config('app.url'), '/').$relative;
    }

    public function toMail($notifiable)
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Confirme seu e-mail no ConectaIFPA')
            ->greeting('Olá!')
            ->line('Sua conta foi criada. Confirme o e-mail '.$notifiable->email.' para entrar no sistema.')
            ->action('Confirmar e-mail', $url)
            ->line('O link vale por 60 minutos. Pode abrir no celular ou no computador.')
            ->line('A tela em que a conta foi criada libera o acesso sozinha.')
            ->line('Se você não criou esta conta, ignore esta mensagem.')
            ->salutation('Equipe ConectaIFPA');
    }
}

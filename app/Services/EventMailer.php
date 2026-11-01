<?php

namespace App\Services;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class EventMailer
{
    /**
     * Envia pelo mailer padrão (log no local, Resend em produção).
     * Uma falha de entrega não desfaz a ação que já foi gravada.
     */
    public static function notify(object $notifiable, Notification $notification): void
    {
        try {
            $notifiable->notify($notification);
        } catch (\Throwable $e) {
            Log::warning('Falha ao enviar e-mail do evento.', [
                'notification' => $notification::class,
                'message' => self::redact($e->getMessage()),
            ]);
        }
    }

    private static function redact(string $message): string
    {
        $redacted = preg_replace('/re_[A-Za-z0-9_\-]+/', '[redacted]', $message);

        return $redacted ?? $message;
    }
}

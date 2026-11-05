<?php

namespace App\Mail;

class OutboundMailPace
{
    /** Abaixo do limite padrão da API do Resend (2 requisições por segundo). */
    public const PER_SECOND = 2;
}

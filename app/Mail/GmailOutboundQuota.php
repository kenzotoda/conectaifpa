<?php

namespace App\Mail;

class GmailOutboundQuota
{
    /** Abaixo do teto de rajada do Gmail comum. */
    public const PER_MINUTE = 8;

    /** Abaixo do teto diário de 500 mensagens da conta Gmail comum. */
    public const PER_DAY = 400;
}

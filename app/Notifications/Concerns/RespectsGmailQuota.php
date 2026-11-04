<?php

namespace App\Notifications\Concerns;

use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\Middleware\RateLimited;

trait RespectsGmailQuota
{
    use Queueable;

    public int $tries = 120;

    public function retryUntil(): DateTimeInterface
    {
        return now()->addDays(3);
    }

    /**
     * @return list<RateLimited>
     */
    public function middleware(): array
    {
        if (app()->runningUnitTests()) {
            return [];
        }

        return [new RateLimited('gmail-outbound')];
    }
}

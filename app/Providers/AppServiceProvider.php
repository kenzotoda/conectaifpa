<?php

namespace App\Providers;

use App\Mail\GmailOutboundQuota;
use App\Livewire\Profile\TwoFactorAuthenticationForm as ProfileTwoFactorAuthenticationForm;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));

        RateLimiter::for('gmail-outbound', function () {
            return [
                Limit::perMinute(GmailOutboundQuota::PER_MINUTE)->by('gmail-smtp'),
                Limit::perDay(GmailOutboundQuota::PER_DAY)->by('gmail-smtp'),
            ];
        });

        Paginator::defaultView('vendor.pagination.tailwind');
        Paginator::defaultSimpleView('vendor.pagination.simple-tailwind');

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        Livewire::component(
            'profile.two-factor-authentication-form',
            ProfileTwoFactorAuthenticationForm::class
        );
    }
}

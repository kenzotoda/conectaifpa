<?php

namespace App\Providers;

use App\Mail\OutboundMailPace;
use App\Livewire\Profile\TwoFactorAuthenticationForm as ProfileTwoFactorAuthenticationForm;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
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

        RateLimiter::for('outbound-mail', function () {
            return Limit::perSecond(OutboundMailPace::PER_SECOND)->by('resend');
        });

        Paginator::defaultView('vendor.pagination.tailwind');
        Paginator::defaultSimpleView('vendor.pagination.simple-tailwind');

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        Event::listen(RouteMatched::class, function (RouteMatched $event) {
            if ($event->route->getName() !== 'verification.verify') {
                return;
            }

            $middleware = [];
            foreach ($event->route->action['middleware'] ?? [] as $name) {
                if (in_array($name, ['auth', 'auth:web', 'signed'], true)) {
                    continue;
                }

                $middleware[] = $name;
            }
            $middleware[] = \App\Http\Middleware\ValidateVerificationSignature::class;

            $event->route->action['middleware'] = $middleware;
            $event->route->action['uses'] = \App\Http\Controllers\Auth\ActivateEmailController::class.'@__invoke';
            $event->route->action['controller'] = \App\Http\Controllers\Auth\ActivateEmailController::class.'@__invoke';
            $event->route->computedMiddleware = null;
        });

        Livewire::component(
            'profile.two-factor-authentication-form',
            ProfileTwoFactorAuthenticationForm::class
        );
    }
}

<?php

namespace App\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Http\Livewire\TwoFactorAuthenticationForm as JetstreamTwoFactorAuthenticationForm;

class TwoFactorAuthenticationForm extends JetstreamTwoFactorAuthenticationForm
{
    /**
     * Preserve an unconfirmed two-factor enrollment across remounts (page refresh / Livewire
     * rehydration); the Jetstream default clears it here, leaving the Authenticator app out of sync.
     */
    public function mount(): void
    {
        if (! Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm')) {
            return;
        }

        $user = Auth::user();

        if ($user !== null
            && ! empty($user->two_factor_secret)
            && is_null($user->two_factor_confirmed_at)) {
            $this->showingQrCode = true;
            $this->showingConfirmation = true;
            $this->showingRecoveryCodes = false;
        }
    }

    public function confirmTwoFactorAuthentication(ConfirmTwoFactorAuthentication $confirm): void
    {
        if (Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword')) {
            $this->ensurePasswordIsConfirmed();
        }

        $code = $this->code;

        if (is_string($code)) {
            $code = preg_replace('/\s+/', '', trim($code));
        } else {
            $code = '';
        }

        $confirm(Auth::user(), $code);

        $this->showingQrCode = false;
        $this->showingConfirmation = false;
        $this->showingRecoveryCodes = true;
    }
}

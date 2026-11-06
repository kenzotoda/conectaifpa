<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ActivateEmailController extends Controller
{
    public function __invoke(Request $request, string $id, string $hash)
    {
        $user = User::query()->find($id);
        $hashMatches = $user instanceof MustVerifyEmail
            && hash_equals(sha1($user->getEmailForVerification()), $hash);

        if (! $hashMatches) {
            return response()->view('auth.verification-link-invalid', [], 403);
        }

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return view('auth.email-activated', [
            'continuesHere' => $request->user()
                && (int) $request->user()->getKey() === (int) $user->getKey(),
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'verified' => $user instanceof MustVerifyEmail && $user->hasVerifiedEmail(),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Contracts\RegisterViewResponse;
use Laravel\Fortify\Fortify;

class RegisteredUserController extends Controller
{
    protected $guard;

    public function __construct(StatefulGuard $guard)
    {
        $this->guard = $guard;
    }

    public function create(Request $request): RegisterViewResponse
    {
        return app(RegisterViewResponse::class);
    }

    public function store(Request $request, CreatesNewUsers $creator): RegisterResponse
    {
        if (config('fortify.lowercase_usernames') && $request->has(Fortify::username())) {
            $request->merge([
                Fortify::username() => Str::lower($request->{Fortify::username()}),
            ]);
        }

        event(new Registered($user = $creator->create($request->all())));

        $this->guard->login($user, $request->boolean('remember'));

        return app(RegisterResponse::class);
    }

    public function storeCoordinator(Request $request, CreatesNewUsers $creator)
    {
        return $this->storePanelAccount($request, $creator, User::ROLE_COORDINATOR, 'Novo coordenador criado com sucesso!');
    }

    public function storeReviewer(Request $request, CreatesNewUsers $creator)
    {
        return $this->storePanelAccount($request, $creator, User::ROLE_REVIEWER, 'Novo avaliador criado com sucesso!');
    }

    public function storeGuest(Request $request, CreatesNewUsers $creator)
    {
        return $this->storePanelAccount($request, $creator, User::ROLE_GUEST, 'Nova conta de convidado criada com sucesso!');
    }

    public function storeOrganizer(Request $request, CreatesNewUsers $creator)
    {
        return $this->storePanelAccount($request, $creator, User::ROLE_ORGANIZER, 'Nova conta de organizador criada com sucesso!');
    }

    private function storePanelAccount(Request $request, CreatesNewUsers $creator, string $role, string $message)
    {
        if (! auth()->user()?->isCoordinator()) {
            abort(403, 'Acesso negado');
        }

        if (config('fortify.lowercase_usernames') && $request->has(Fortify::username())) {
            $request->merge([
                Fortify::username() => Str::lower($request->{Fortify::username()}),
            ]);
        }

        $payload = $request->all();
        $payload['role'] = $role;
        $user = $creator->create($payload);
        $user->created_by_user_id = auth()->id();
        $user->save();

        event(new Registered($user));

        return redirect()->route('accounts.managed')->with('msg', $message);
    }
}

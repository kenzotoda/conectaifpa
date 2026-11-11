<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class ManagedAccountController extends Controller
{
    public function index(): View
    {
        $accounts = User::query()
            ->where('created_by_user_id', auth()->id())
            ->whereIn('role', [
                User::ROLE_GUEST,
                User::ROLE_ORGANIZER,
                User::ROLE_REVIEWER,
                User::ROLE_COORDINATOR,
            ])
            ->orderBy('name')
            ->get()
            ->groupBy('role');

        return view('accounts.managed', [
            'groups' => [
                User::ROLE_GUEST => 'Convidados',
                User::ROLE_ORGANIZER => 'Organizadores',
                User::ROLE_REVIEWER => 'Avaliadores',
                User::ROLE_COORDINATOR => 'Coordenadores',
            ],
            'accounts' => $accounts,
        ]);
    }
}

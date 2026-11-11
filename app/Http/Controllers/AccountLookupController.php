<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountLookupController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $viewer = $request->user();
        $role = (string) $request->query('papel', '');
        $email = strtolower(trim((string) $request->query('email', '')));

        $allowed = match (true) {
            $viewer->isCoordinator() || $viewer->isOrganizer() => [User::ROLE_PARTICIPANT, User::ROLE_GUEST, User::ROLE_ORGANIZER],
            $viewer->isParticipant() => [User::ROLE_PARTICIPANT],
            default => [],
        };

        if (! in_array($role, $allowed, true)) {
            abort(403);
        }

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'found' => false,
                'message' => 'Informe um e-mail válido.',
            ]);
        }

        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        $coordinatorIncludesSelf = $role === User::ROLE_ORGANIZER
            && $viewer->isCoordinator()
            && $user
            && (int) $user->id === (int) $viewer->id;

        if (! $user || ($user->role !== $role && ! $coordinatorIncludesSelf)) {
            $message = match ($role) {
                User::ROLE_GUEST => 'Não existe conta de convidado com este e-mail. Crie a conta no painel antes de vincular.',
                User::ROLE_ORGANIZER => 'Não existe conta de organizador com este e-mail. Crie a conta no painel ou use o seu próprio e-mail de coordenador.',
                default => 'Não existe conta de participante com este e-mail. A pessoa precisa criar a conta antes.',
            };

            return response()->json([
                'found' => false,
                'message' => $message,
            ]);
        }

        return response()->json([
            'found' => true,
            'name' => $user->name,
            'email' => $user->email,
            'institution' => $user->isParticipant() ? $user->participantAffiliationForWorks() : null,
        ]);
    }
}

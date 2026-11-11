<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Support\IfpaBelemCourses;
use App\Support\IfpaCampuses;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        $allowedRoles = [
            User::ROLE_PARTICIPANT,
            User::ROLE_COORDINATOR,
            User::ROLE_REVIEWER,
        ];
        if (auth()->user()?->isCoordinator()) {
            $allowedRoles[] = User::ROLE_GUEST;
            $allowedRoles[] = User::ROLE_ORGANIZER;
        }

        $isParticipant = ($input['role'] ?? '') === User::ROLE_PARTICIPANT;
        $profile = $isParticipant ? (string) ($input['participant_profile'] ?? '') : '';
        $isExternalParticipant = $isParticipant && in_array($profile, User::EXTERNAL_PARTICIPANT_PROFILES, true);
        $isOtherCampus = $profile === User::PROFILE_IFPA_OUTRO_CAMPUS;
        $requiresMatricula = $profile === User::PROFILE_DISCENTE;
        $requiresDetail = in_array($profile, User::PROFILES_WITH_DETAIL, true);
        $requiresOrganization = in_array($profile, User::PROFILES_WITH_REQUIRED_ORGANIZATION, true);

        $matriculaRules = ['nullable'];
        if ($requiresMatricula) {
            $matriculaRules = ['required', 'digits_between:1,20', Rule::unique('users', 'matricula')];
        } elseif ($isOtherCampus) {
            $matriculaRules = ['nullable', 'digits_between:1,20', Rule::unique('users', 'matricula')];
        }

        $cursoRules = ['nullable', 'string', 'max:255'];
        if ($profile === User::PROFILE_DISCENTE) {
            $cursoRules = ['required', 'string', Rule::in(IfpaBelemCourses::values())];
        } elseif ($isOtherCampus) {
            $cursoRules = ['required', 'string', 'max:255', 'regex:/^[\pL\pN\s.\'’\-\/,()]+$/u'];
        } elseif ($requiresDetail) {
            $cursoRules = ['required', 'string', 'max:255', 'regex:/^[\pL\pN\s]+$/u'];
        }

        $institutionRules = ['nullable', 'string', 'max:255'];
        if ($isOtherCampus) {
            $institutionRules = ['required', 'string', Rule::in(IfpaCampuses::institutionValues())];
        } elseif ($requiresOrganization) {
            $institutionRules = ['required', 'string', 'max:255'];
        }

        $bondRules = ['nullable'];
        if ($isOtherCampus) {
            $bondRules = ['required', Rule::in(array_keys(User::IFPA_BOND_LABELS))];
        }

        Validator::make(
            $input,
            [
                'name' => ['required', 'string', 'max:255', 'regex:/^[\pL\s]+$/u'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => $this->passwordRules(),
                'role' => ['required', Rule::in($allowedRoles)],

                'participant_profile' => [
                    Rule::requiredIf($isParticipant),
                    'nullable',
                    Rule::in(array_keys(User::PARTICIPANT_PROFILE_LABELS)),
                ],

                'matricula' => $matriculaRules,

                'institution' => $institutionRules,

                'ifpa_bond' => $bondRules,

                'curso' => $cursoRules,

                'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature()
                    ? ['accepted', 'required']
                    : '',
            ],
            [
                // 🔹 NAME
                'name.required' => 'O nome é obrigatório.',
                'name.regex' => 'O nome deve conter apenas letras e espaços.',
                'name.max' => 'O nome pode ter no máximo :max caracteres.',

                // 🔹 EMAIL
                'email.required' => 'O e-mail é obrigatório.',
                'email.email' => 'Informe um e-mail válido.',
                'email.unique' => 'Este e-mail já está cadastrado.',

                // 🔹 PASSWORD
                'password.required' => 'A senha é obrigatória.',
                'password.min' => 'A senha deve ter no mínimo :min caracteres.',
                'password.confirmed' => 'As senhas não conferem.',

                // 🔹 ROLE
                'role.required' => 'O papel do usuário é obrigatório.',
                'role.in' => 'O papel informado é inválido.',

                'participant_profile.required' => 'Selecione o perfil de participação.',
                'participant_profile.in' => 'Selecione um perfil de participação válido.',

                // 🔹 MATRÍCULA
                'matricula.required' => 'A matrícula é obrigatória para discentes do IFPA.',
                'matricula.digits_between' => 'A matrícula deve ter de 1 a 20 números.',
                'matricula.unique' => 'Esta matrícula já está cadastrada.',

                // 🔹 ORGANIZAÇÃO / ESCOLA
                'institution.required' => 'Informe a escola, o empreendimento, a organização ou o campus.',
                'institution.in' => 'Selecione um campus do IFPA.',
                'institution.max' => 'Este campo pode ter no máximo :max caracteres.',

                'ifpa_bond.required' => 'Selecione o vínculo no campus.',
                'ifpa_bond.in' => 'Selecione um vínculo válido.',

                // 🔹 CURSO / ÁREA / SETOR
                'curso.required' => 'Informe o curso, a área, o setor ou a série.',
                'curso.in' => 'Selecione um curso do IFPA Belém.',
                'curso.regex' => 'Use apenas letras, números e espaços.',
                'curso.max' => 'Este campo pode ter no máximo :max caracteres.',

                // 🔹 TERMOS
                'terms.required' => 'Você precisa aceitar os termos.',
                'terms.accepted' => 'Você precisa aceitar os termos.',
            ]
        )->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'role' => $input['role'],
            'matricula' => ($requiresMatricula || $isOtherCampus) ? ($input['matricula'] ?? null) : null,
            'curso' => $requiresDetail ? ($input['curso'] ?? null) : null,
            'is_external_participant' => $isExternalParticipant,
            'participant_profile' => $isParticipant ? $profile : null,
            'ifpa_bond' => $isOtherCampus ? ($input['ifpa_bond'] ?? null) : null,
            'institution' => $isExternalParticipant ? ($input['institution'] ?? null) : null,
        ]);
    }
}

<?php

namespace App\Actions\Fortify;

use App\Models\User;
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
        $isParticipant = ($input['role'] ?? '') === User::ROLE_PARTICIPANT;
        $profile = $isParticipant ? (string) ($input['participant_profile'] ?? '') : '';
        $isExternalParticipant = $isParticipant && in_array($profile, User::EXTERNAL_PARTICIPANT_PROFILES, true);
        $requiresMatricula = $profile === User::PROFILE_DISCENTE;
        $requiresDetail = in_array($profile, User::PROFILES_WITH_DETAIL, true);
        $requiresOrganization = in_array($profile, User::PROFILES_WITH_REQUIRED_ORGANIZATION, true);

        $matriculaRules = ['nullable'];
        if ($requiresMatricula) {
            $matriculaRules = ['required', 'digits:12', Rule::unique('users', 'matricula')];
        }

        $institutionRules = ['nullable', 'string', 'max:255'];
        if ($requiresOrganization) {
            $institutionRules = ['required', 'string', 'max:255'];
        }

        Validator::make(
            $input,
            [
                'name' => ['required', 'string', 'max:255', 'regex:/^[\pL\s]+$/u'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => $this->passwordRules(),
                'role' => ['required', Rule::in([
                    User::ROLE_PARTICIPANT,
                    User::ROLE_COORDINATOR,
                    User::ROLE_REVIEWER,
                ])],

                'participant_profile' => [
                    Rule::requiredIf($isParticipant),
                    'nullable',
                    Rule::in(array_keys(User::PARTICIPANT_PROFILE_LABELS)),
                ],

                'matricula' => $matriculaRules,

                'institution' => $institutionRules,

                'curso' => [
                    'nullable',
                    Rule::requiredIf($requiresDetail),
                    'string',
                    'max:100',
                    'regex:/^[\pL\pN\s]+$/u',
                ],

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
                'matricula.digits' => 'A matrícula deve conter exatamente 12 números.',
                'matricula.unique' => 'Esta matrícula já está cadastrada.',

                // 🔹 ORGANIZAÇÃO / ESCOLA
                'institution.required' => 'Informe a escola, o empreendimento ou a organização.',
                'institution.max' => 'Este campo pode ter no máximo :max caracteres.',

                // 🔹 CURSO / ÁREA / SETOR
                'curso.required' => 'Informe o curso, a área, o setor ou a série.',
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
            'matricula' => $requiresMatricula ? ($input['matricula'] ?? null) : null,
            'curso' => $requiresDetail ? ($input['curso'] ?? null) : null,
            'is_external_participant' => $isExternalParticipant,
            'participant_profile' => $isParticipant ? $profile : null,
            'institution' => $isExternalParticipant ? ($input['institution'] ?? null) : null,
        ]);
    }
}

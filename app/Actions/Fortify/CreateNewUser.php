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
        $isExternalParticipant = $isParticipant && filter_var($input['is_external_participant'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $matriculaRules = ['nullable'];
        if ($isParticipant && ! $isExternalParticipant) {
            $matriculaRules = ['required', 'digits:12', Rule::unique('users', 'matricula')];
        }

        $institutionRules = ['nullable', 'string', 'max:255'];
        if ($isParticipant && $isExternalParticipant) {
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

                'is_external_participant' => ['sometimes', 'boolean'],

                'matricula' => $matriculaRules,

                'institution' => $institutionRules,

                'curso' => [
                    'nullable',
                    Rule::requiredIf($input['role'] === 'participant'),
                    'string',
                    'max:100',
                    'regex:/^[\pL\s]+$/u',
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

                // 🔹 MATRÍCULA
                'matricula.required' => 'A matrícula é obrigatória.',
                'matricula.digits' => 'A matrícula deve conter exatamente 12 números.',
                'matricula.unique' => 'Esta matrícula já está cadastrada.',

                // 🔹 INSTITUIÇÃO (externo)
                'institution.required' => 'Informe a instituição de origem.',
                'institution.max' => 'A instituição pode ter no máximo :max caracteres.',

                // 🔹 CURSO
                'curso.required' => 'O curso é obrigatório.',
                'curso.regex' => 'O curso deve conter apenas letras.',
                'curso.max' => 'O curso pode ter no máximo :max caracteres.',

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
            'matricula' => $isParticipant && ! $isExternalParticipant ? ($input['matricula'] ?? null) : null,
            'curso' => $isParticipant ? ($input['curso'] ?? null) : null,
            'is_external_participant' => $isParticipant && $isExternalParticipant,
            'institution' => $isParticipant && $isExternalParticipant ? ($input['institution'] ?? null) : null,
        ]);
    }
}

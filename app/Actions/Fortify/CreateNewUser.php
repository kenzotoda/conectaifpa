<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;
use Illuminate\Validation\Rule;


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
        Validator::make(
        $input,
        [
            'name' => ['required', 'string', 'max:255', 'regex:/^[\pL\s]+$/u'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),

            'matricula' => [
                'nullable',
                Rule::requiredIf($input['role'] === 'participant'),
                'digits:12',
                'unique:users,matricula',
            ],

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

            // 🔹 MATRÍCULA
            'matricula.required' => 'A matrícula é obrigatória.',
            'matricula.digits' => 'A matrícula deve conter exatamente 12 números.',
            'matricula.unique' => 'Esta matrícula já está cadastrada.',

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
            'matricula' => $input['role'] === 'participant' ? $input['matricula'] : null,
            'curso' => $input['role'] === 'participant' ? $input['curso'] : null,
        ]);
    }
}

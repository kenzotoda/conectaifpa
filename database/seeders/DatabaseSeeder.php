<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $password = (string) (env('ADMIN_INITIAL_PASSWORD') ?: '12345678');

        // Essas caixas não existem. A data de verificação deixa o login entrar direto,
        // sem o aviso nem o e-mail de confirmação.
        $verified = ['email_verified_at' => now()];

        User::updateOrCreate([
            'email' => 'admin@conectaifpa.com',
        ], [
            'name' => 'Administrador Inicial',
            'password' => Hash::make($password),
            'role' => User::ROLE_COORDINATOR,
            ...$verified,
        ]);

        User::updateOrCreate([
            'email' => 'reviewer@conectaifpa.com',
        ], [
            'name' => 'Avaliador Inicial',
            'password' => Hash::make($password),
            'role' => User::ROLE_REVIEWER,
            ...$verified,
        ]);

        User::updateOrCreate([
            'email' => 'aluno@conectaifpa.com',
        ], [
            'name' => 'Aluno Inicial',
            'password' => Hash::make($password),
            'role' => User::ROLE_PARTICIPANT,
            ...$verified,
        ]);

    }
}

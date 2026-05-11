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

        User::updateOrCreate([
            'email' => 'admin@conectaifpa.com',
        ], [
            'name' => 'Administrador Inicial',
            'password' => Hash::make($password),
            'role' => User::ROLE_COORDINATOR,
        ]);

        User::updateOrCreate([
            'email' => 'reviewer@conectaifpa.com',
        ], [
            'name' => 'Avaliador Inicial',
            'password' => Hash::make($password),
            'role' => User::ROLE_REVIEWER,
        ]);

        User::updateOrCreate([
            'email' => 'aluno@conectaifpa.com',
        ], [
            'name' => 'Aluno Inicial',
            'password' => Hash::make($password),
            'role' => User::ROLE_PARTICIPANT,
        ]);

    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $password = env('ADMIN_INITIAL_PASSWORD');

        if (!$password) {
            throw new \RuntimeException(
                'ADMIN_INITIAL_PASSWORD não está definido no .env'
            );
        }

        User::updateOrCreate(
            ['email' => 'admin@conectaifpa.com'],
            [
                'name'     => 'Administrador Inicial',
                'password' => bcrypt($password),
                'role'     => User::ROLE_COORDINATOR,
            ]
        );
    }

}

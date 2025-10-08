<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
{
    \App\Models\User::factory()->create([
        'name' => 'Administrador Inicial',
        'email' => 'admin@conectaIFPA.com',
        'password' => bcrypt('senha123'),
        'role' => \App\Models\User::ROLE_COORDINATOR,
    ]);
}

}

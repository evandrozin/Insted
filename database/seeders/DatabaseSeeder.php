<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Administrador padrão (acesso total). Troque a senha após o primeiro login.
        User::updateOrCreate(
            ['email' => 'admin@insted.edu.br'],
            [
                'name' => 'Administrador Insted',
                'password' => 'Insted@2026',
                'is_admin' => true,
                'permissions' => [],
            ]
        );

        $this->call([
            ApiParametroSeeder::class,
        ]);
    }
}

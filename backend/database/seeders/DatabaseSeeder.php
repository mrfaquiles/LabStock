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
        $this->call(UnidadeMedidaSeeder::class);

        // Administrador inicial — troque a senha após o primeiro acesso
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@labstock.local')],
            [
                'nome' => 'Administrador',
                'password' => env('ADMIN_PASSWORD', 'labstock@2026'),
                'tipo' => User::PERFIL_ADMIN,
                'ativo' => 1,
            ]
        );
    }
}

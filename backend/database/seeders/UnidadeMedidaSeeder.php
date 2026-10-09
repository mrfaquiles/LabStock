<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnidadeMedidaSeeder extends Seeder
{
    // Unidades base do sistema; frações são cadastradas em decimal (ex.: 0.5 kg)
    public function run(): void
    {
        $unidades = [
            ['nome' => 'Quilograma', 'sigla' => 'kg'],
            ['nome' => 'Litro', 'sigla' => 'L'],
            ['nome' => 'Unidade', 'sigla' => 'un'],
        ];

        // updateOrInsert permite rodar o seeder mais de uma vez sem duplicar
        foreach ($unidades as $unidade) {
            DB::table('unidade_medidas')->updateOrInsert(
                ['sigla' => $unidade['sigla']],
                ['nome' => $unidade['nome'], 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnidadeMedidaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('unidade_medidas')->insert([
            ['nome' => 'Quilograma', 'sigla' => 'kg'],
            ['nome' => 'Litro', 'sigla' => 'L'],
            ['nome' => 'Unidade', 'sigla' => 'un'],
        ]);
    }
}
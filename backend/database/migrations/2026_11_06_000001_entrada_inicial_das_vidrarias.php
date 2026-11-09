<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Antes, a quantidade informada no cadastro da vidraria não gerava entrada,
 * então o saldo não batia com as movimentações (entradas − baixas aprovadas).
 * Esta migration registra a diferença como "Estoque inicial (cadastro)".
 */
return new class extends Migration
{
    public function up(): void
    {
        $usuario = DB::table('users')->where('tipo', 'admin')->orderBy('id')->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');
        if (!$usuario) {
            return; // banco vazio
        }

        foreach (DB::table('vidrarias')->get() as $v) {
            $entradas = (int) DB::table('entrada_vidrarias')->where('idvidraria', $v->idvidraria)->sum('quantidade');
            $baixas = (int) DB::table('saida_vidrarias')->where('idvidraria', $v->idvidraria)
                ->where('status', 'aprovada')->sum('quantidade');
            $diferenca = (int) $v->quantidade - ($entradas - $baixas);

            if ($diferenca > 0) {
                DB::table('entrada_vidrarias')->insert([
                    'idvidraria' => $v->idvidraria,
                    'idlaboratorio' => $v->idlaboratorio,
                    'idusuario' => $usuario,
                    'quantidade' => $diferenca,
                    'data' => $v->created_at ?? now(),
                    'observacao' => 'Estoque inicial (cadastro)',
                    'created_at' => $v->created_at ?? now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Mantém as entradas: sem elas o saldo volta a não bater
    }
};

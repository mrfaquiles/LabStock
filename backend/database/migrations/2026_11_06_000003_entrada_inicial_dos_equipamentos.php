<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Antes, a quantidade informada no cadastro do equipamento não gerava entrada,
 * então o total não batia com as movimentações (entradas − baixas).
 * Esta migration registra a diferença como "Estoque inicial (cadastro)",
 * no laboratório onde o equipamento foi cadastrado (o local mais antigo).
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

        foreach (DB::table('equipamentos')->get() as $e) {
            $entradas = (int) DB::table('entrada_equipamentos')->where('idequipamento', $e->idequipamento)->sum('quantidade');
            $baixas = (int) DB::table('saida_equipamentos')->where('idequipamento', $e->idequipamento)->sum('quantidade');
            $diferenca = (int) $e->quantidade - ($entradas - $baixas);
            if ($diferenca <= 0) {
                continue;
            }

            $localDoCadastro = DB::table('equipamento_locais')->where('idequipamento', $e->idequipamento)
                ->orderBy('created_at')->orderBy('id')->value('idlaboratorio');

            DB::table('entrada_equipamentos')->insert([
                'idequipamento' => $e->idequipamento,
                'idlaboratorio' => $localDoCadastro,
                'idusuario' => $usuario,
                'quantidade' => $diferenca,
                'data' => $e->created_at ?? now(),
                'observacao' => 'Estoque inicial (cadastro)',
                'created_at' => $e->created_at ?? now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Mantém as entradas: sem elas o total volta a não bater
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Controle de estoque por lote: cada entrada_reagente é um lote com saldo próprio.
     * O estoque do reagente é a soma dos saldos dos lotes.
     */
    public function up(): void
    {
        // Lote de reagente não precisa estar vinculado a um laboratório
        Schema::table('entrada_reagentes', function (Blueprint $table) {
            $table->unsignedBigInteger('idlaboratorio')->nullable()->change();
        });

        // Reagentes já cadastrados ganham o lote inicial com a quantidade atual,
        // para que o consumo possa ser descontado por lote
        $responsavel = DB::table('users')->where('tipo', 'admin')->value('id')
            ?? DB::table('users')->value('id');

        if (!$responsavel) {
            return;
        }

        $reagentesSemLote = DB::table('reagentes')
            ->where('quantidade', '>', 0)
            ->whereNotIn('idreagente', DB::table('entrada_reagentes')->select('idreagente'))
            ->get();

        foreach ($reagentesSemLote as $reagente) {
            DB::table('entrada_reagentes')->insert([
                'idreagente' => $reagente->idreagente,
                'idusuario' => $responsavel,
                'quantidade' => $reagente->quantidade,
                'lote' => $reagente->lote,
                'data_validade' => $reagente->data_validade,
                'data' => substr((string) ($reagente->created_at ?? now()), 0, 10),
                'observacao' => 'Estoque inicial (migração para controle por lote)',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('entrada_reagentes')
            ->where('observacao', 'Estoque inicial (migração para controle por lote)')
            ->delete();

        Schema::table('entrada_reagentes', function (Blueprint $table) {
            $table->unsignedBigInteger('idlaboratorio')->nullable(false)->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Código CAS e controle de produtos sujeitos à fiscalização da Polícia Federal
     * (Portaria MJSP nº 204/2022). Produto controlado exige justificativa de uso.
     */
    public function up(): void
    {
        Schema::table('reagentes', function (Blueprint $table) {
            $table->string('cas', 20)->nullable()->after('catmat');
            $table->boolean('controlado')->default(false)->after('cas');
            // Lista e código na portaria (ex.: "Lista V - 066")
            $table->string('classificacao_controle', 100)->nullable()->after('controlado');
            $table->index('controlado');
        });

        // A compra leva cerca de 1 ano: atualiza quem ainda estava com o padrão antigo de 6 meses
        DB::table('configuracoes')
            ->where('chave', 'tempo_compra_meses')
            ->where('valor', '6')
            ->update(['valor' => '12']);
    }

    public function down(): void
    {
        Schema::table('reagentes', function (Blueprint $table) {
            $table->dropIndex(['controlado']);
            $table->dropColumn(['cas', 'controlado', 'classificacao_controle']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Lotes antigos sem laboratório vão para o local que mais recebe lotes
 * (ou o primeiro cadastrado). A partir de agora o laboratório é obrigatório.
 */
return new class extends Migration
{
    public function up(): void
    {
        $local = DB::table('entrada_reagentes')->whereNotNull('idlaboratorio')
            ->select('idlaboratorio')->groupBy('idlaboratorio')
            ->orderByRaw('COUNT(*) DESC')->value('idlaboratorio')
            ?? DB::table('laboratorios')->orderBy('idlaboratorio')->value('idlaboratorio');

        if ($local) {
            DB::table('entrada_reagentes')->whereNull('idlaboratorio')->update(['idlaboratorio' => $local]);
        }
    }

    public function down(): void
    {
        // Não há como saber quais estavam sem laboratório
    }
};

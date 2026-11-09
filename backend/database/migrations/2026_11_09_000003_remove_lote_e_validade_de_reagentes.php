<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lote e validade pertencem ao lote (entrada_reagentes). A cópia que ficava
 * no cadastro do reagente podia divergir e deixa de existir.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reagentes', function (Blueprint $table) {
            $table->dropColumn(['lote', 'data_validade']);
        });
    }

    public function down(): void
    {
        Schema::table('reagentes', function (Blueprint $table) {
            $table->string('lote')->nullable();
            $table->date('data_validade')->nullable();
        });

        foreach (DB::table('reagentes')->pluck('idreagente') as $id) {
            $primeiro = DB::table('entrada_reagentes')->where('idreagente', $id)->orderBy('identradareagente')->first();
            if ($primeiro) {
                DB::table('reagentes')->where('idreagente', $id)
                    ->update(['lote' => $primeiro->lote, 'data_validade' => $primeiro->data_validade]);
            }
        }
    }
};

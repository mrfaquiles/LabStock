<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Onde a vidraria fica: laboratório (que pertence a uma unidade) e o local
     * dentro dele (armário, prateleira, gaveta).
     */
    public function up(): void
    {
        Schema::table('vidrarias', function (Blueprint $table) {
            $table->unsignedBigInteger('idlaboratorio')->nullable()->after('catmat');
            $table->string('localizacao')->nullable()->after('idlaboratorio');
            $table->foreign('idlaboratorio')->references('idlaboratorio')->on('laboratorios');
        });
    }

    public function down(): void
    {
        Schema::table('vidrarias', function (Blueprint $table) {
            $table->dropForeign(['idlaboratorio']);
            $table->dropColumn(['idlaboratorio', 'localizacao']);
        });
    }
};

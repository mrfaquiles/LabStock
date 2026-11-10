<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A chave primária foi criada como "identravidraria" (faltava o "da").
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entrada_vidrarias', function (Blueprint $table) {
            $table->renameColumn('identravidraria', 'identradavidraria');
        });
    }

    public function down(): void
    {
        Schema::table('entrada_vidrarias', function (Blueprint $table) {
            $table->renameColumn('identradavidraria', 'identravidraria');
        });
    }
};

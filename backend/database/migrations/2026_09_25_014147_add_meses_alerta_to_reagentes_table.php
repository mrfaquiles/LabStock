<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reagentes', function (Blueprint $table) {
            $table->integer('meses_alerta')->default(4)->after('localizacao');
        });
    }

    public function down(): void
    {
        Schema::table('reagentes', function (Blueprint $table) {
            $table->dropColumn('meses_alerta');
        });
    }
};
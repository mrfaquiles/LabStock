<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('entrada_reagentes', function (Blueprint $table) {
            $table->id('identradareagente');
            $table->unsignedBigInteger('idreagente');
            $table->unsignedBigInteger('idlaboratorio');
            $table->unsignedBigInteger('idusuario');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entrada_reagentes');
    }
};

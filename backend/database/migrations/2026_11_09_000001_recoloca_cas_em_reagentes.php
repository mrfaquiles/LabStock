<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O código CAS volta ao cadastro de reagentes, como campo opcional.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reagentes', function (Blueprint $table) {
            $table->string('cas', 20)->nullable()->after('catmat');
        });
    }

    public function down(): void
    {
        Schema::table('reagentes', function (Blueprint $table) {
            $table->dropColumn('cas');
        });
    }
};

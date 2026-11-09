<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * O código CAS deixou de ser usado no sistema.
     */
    public function up(): void
    {
        Schema::table('reagentes', function (Blueprint $table) {
            $table->dropColumn('cas');
        });
    }

    public function down(): void
    {
        Schema::table('reagentes', function (Blueprint $table) {
            $table->string('cas', 20)->nullable()->after('catmat');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Quantidades de reagente são guardadas na unidade base (kg, L, un).
     * Com 3 casas decimais o mínimo era 1 g / 1 mL; com 6 casas o sistema
     * registra até 1 mg / 1 µL (ex.: uso de 0,25 g = 0,00025 kg).
     */
    private array $colunas = [
        'reagentes' => true,          // com default 0
        'entrada_reagentes' => true,
        'saida_reagentes' => false,
        'pedidos_compra' => false,
    ];

    public function up(): void
    {
        foreach ($this->colunas as $tabela => $comPadrao) {
            Schema::table($tabela, function (Blueprint $table) use ($comPadrao) {
                $coluna = $table->decimal('quantidade', 16, 6);
                if ($comPadrao) {
                    $coluna->default(0);
                }
                $coluna->change();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->colunas as $tabela => $comPadrao) {
            Schema::table($tabela, function (Blueprint $table) use ($comPadrao) {
                $coluna = $table->decimal('quantidade', 10, 3);
                if ($comPadrao) {
                    $coluna->default(0);
                }
                $coluna->change();
            });
        }
    }
};

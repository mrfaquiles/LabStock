<?php

namespace App\Support;

/**
 * Formata quantidades guardadas na unidade base (kg, L) na unidade mais legível.
 * Ex.: 0.0005 kg → "0,5 g"; 0.25 L → "250 mL"; 2.5 kg → "2,5 kg".
 * Mesma regra do frontend (src/utils/unidades.js).
 */
class Quantidade
{
    private const ESCALAS = [
        'kg' => [['kg', 1], ['g', 0.001], ['mg', 0.000001]],
        'L' => [['L', 1], ['mL', 0.001], ['µL', 0.000001]],
    ];

    public static function formatar(float $valorBase, ?string $sigla): string
    {
        $sigla = $sigla ?? '';
        $escalas = self::ESCALAS[$sigla] ?? [[$sigla, 1]];

        // Maior unidade em que o valor fica >= 1 (zero fica na unidade base)
        [$unidade, $fator] = $escalas[0];
        if ($valorBase != 0) {
            foreach ($escalas as [$u, $f]) {
                [$unidade, $fator] = [$u, $f];
                if (abs($valorBase) >= $f) {
                    break;
                }
            }
        }

        $valor = round($valorBase / $fator, 6);
        $texto = rtrim(rtrim(number_format($valor, 6, ',', '.'), '0'), ',');
        return trim("{$texto} {$unidade}");
    }
}

<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Código CAS (identificação internacional da substância), ex.: 1336-21-6.
 * O último número é um dígito verificador, o que pega erros de digitação.
 */
class CodigoCas implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || !preg_match('/^(\d{2,7})-(\d{2})-(\d)$/', $value, $partes)) {
            $fail('O código CAS deve estar no formato 1336-21-6.');
            return;
        }

        $digitos = strrev($partes[1] . $partes[2]);
        $soma = 0;
        foreach (str_split($digitos) as $i => $digito) {
            $soma += ($i + 1) * (int) $digito;
        }

        if ($soma % 10 !== (int) $partes[3]) {
            $fail('O código CAS informado não é válido. Confira os números.');
        }
    }
}

<?php

namespace App\Traits;

trait ParsesFloatInput
{
    /**
     * Converte uma string de valor monetário (ex: "1.234,56" ou "1234.56") para float.
     *
     * Regras:
     *  - Se já é numérico, retorna direto.
     *  - Remove tudo que não seja dígito, vírgula, ponto ou sinal negativo.
     *  - Se contém tanto ponto quanto vírgula, assume formato BR (ponto = milhar, vírgula = decimal).
     *  - Se contém apenas vírgula, troca por ponto.
     */
    protected function parseFloat(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (empty($value) || $value === '') {
            return 0.0;
        }

        $value = preg_replace('/[^\d,.-]/', '', (string) $value);

        // Formato BR: "1.234,56" — remove separador de milhar antes de trocar decimal
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
        }

        $value = str_replace(',', '.', $value);

        return (float) $value;
    }
}

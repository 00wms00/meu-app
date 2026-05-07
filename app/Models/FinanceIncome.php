<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceIncome extends Model
{
    protected $fillable = [
        'descricao',
        'pessoa',
        'tipo',
        'valor',
        'mes_referencia',
        'data_recebimento',
        'recorrente',
        'observacao',
    ];

    protected $casts = [
        'mes_referencia'   => 'date',
        'data_recebimento' => 'date',
        'recorrente'       => 'boolean',
        'valor'            => 'decimal:2',
    ];

    // ---- Scopes ---------------------------------------------------------

    public function scopeDoMes($query, \Carbon\Carbon $mes)
    {
        return $query
            ->whereYear('mes_referencia', $mes->year)
            ->whereMonth('mes_referencia', $mes->month);
    }

    public function scopeRecorrentes($query)
    {
        return $query->where('recorrente', true);
    }

    // ---- Helpers --------------------------------------------------------

    public function getPessoaLabelAttribute(): string
    {
        return match ($this->pessoa) {
            'WIL'          => 'Willian',
            'MAY'          => 'Mayara',
            'compartilhado'=> 'Compartilhado',
            default        => $this->pessoa,
        };
    }

    public function getTipoLabelAttribute(): string
    {
        return match ($this->tipo) {
            'salario'   => 'Salário',
            'freelance' => 'Freelance',
            'presente'  => 'Presente',
            'aluguel'   => 'Aluguel',
            'outros'    => 'Outros',
            default     => $this->tipo,
        };
    }
}

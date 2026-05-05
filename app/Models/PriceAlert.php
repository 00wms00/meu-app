<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'limite_alerta',
        'variacao_percentual',
        'ativo',
    ];

    /**
     * $casts explícito evita que valores numéricos do banco cheguem
     * como string em operações de comparação (ex: $alerta->limite_alerta > 10
     * falharia silenciosamente se o valor fosse '10.00' string).
     */
    protected $casts = [
        'limite_alerta'       => 'float',
        'variacao_percentual' => 'float',
        'ativo'               => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

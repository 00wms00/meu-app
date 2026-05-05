<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'product_id', 'preco_referencia', 'preco_atual',
        'variacao_percentual', 'limite_alerta', 'ativo', 'ultimo_alerta',
    ];

    protected $casts = [
        'preco_referencia' => 'float',
        'preco_atual' => 'float',
        'variacao_percentual' => 'float',
        'limite_alerta' => 'float',
        'ativo' => 'boolean',
        'ultimo_alerta' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Scope: alertas ativos
    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    // Scope: alertas disparados (variação >= limite)
    public function scopeDisparados($query)
    {
        return $query->where('ativo', true)
                     ->whereRaw('variacao_percentual >= limite_alerta');
    }
}

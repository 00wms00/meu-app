<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuelEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vehicle_id',
        'data',
        'valor',
        'litros',
        'km_abastecimento',
        'tipo_combustivel',
        'posto',
        'tanque_cheio',
        'descricao',
    ];

    protected $casts = [
        'data'          => 'date',
        'valor'         => 'float',
        'litros'        => 'float',
        'preco_por_litro' => 'float',
        'km_abastecimento' => 'integer',
        'tanque_cheio'  => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Retorna o consumo médio (km/l) comparando este abastecimento
     * com o anterior que tenha km registrado.
     */
    public function consumoMedio(): ?float
    {
        if (! $this->km_abastecimento || ! $this->litros) {
            return null;
        }

        $anterior = static::where('vehicle_id', $this->vehicle_id)
            ->where('id', '<', $this->id)
            ->whereNotNull('km_abastecimento')
            ->orderByDesc('km_abastecimento')
            ->first();

        if (! $anterior || $this->km_abastecimento <= $anterior->km_abastecimento) {
            return null;
        }

        $km = $this->km_abastecimento - $anterior->km_abastecimento;

        return round($km / $this->litros, 2);
    }
}

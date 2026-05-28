<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

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
        'status',
    ];

    protected $casts = [
        'data'             => 'date',
        'valor'            => 'float',
        'litros'           => 'float',
        'preco_por_litro'  => 'float',
        'km_abastecimento' => 'integer',
        'tanque_cheio'     => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function consumoMedio(?Collection $allEntries = null): ?float
    {
        if (! $this->km_abastecimento || ! $this->litros) {
            return null;
        }

        if ($allEntries) {
            $anterior = $allEntries
                ->filter(fn($e) => $e->id < $this->id && $e->km_abastecimento)
                ->sortByDesc('km_abastecimento')
                ->first();
        } else {
            $anterior = static::where('vehicle_id', $this->vehicle_id)
                ->where('id', '<', $this->id)
                ->whereNotNull('km_abastecimento')
                ->orderByDesc('km_abastecimento')
                ->first();
        }

        if (! $anterior || $this->km_abastecimento <= $anterior->km_abastecimento) {
            return null;
        }

        $km = $this->km_abastecimento - $anterior->km_abastecimento;

        return round($km / $this->litros, 2);
    }

    public static function historicoConsumo(Collection $entries): array
    {
        $sorted = $entries
            ->filter(fn($e) => $e->km_abastecimento && $e->litros)
            ->sortBy(['data', 'id']);

        $pontos = [];
        $prev   = null;

        foreach ($sorted as $entry) {
            if ($prev && $entry->km_abastecimento > $prev->km_abastecimento) {
                $kmRodados = $entry->km_abastecimento - $prev->km_abastecimento;
                $consumo   = round($kmRodados / $entry->litros, 2);
                $custoKm   = $kmRodados > 0 ? round($entry->valor / $kmRodados, 4) : null;

                $pontos[] = [
                    'entry_id'   => $entry->id,
                    'label'      => $entry->data->format('d/m/Y'),
                    'consumo'    => $consumo,
                    'custo_km'   => $custoKm,
                    'km_rodados' => $kmRodados,
                    'km'         => $entry->km_abastecimento,
                    'litros'     => round($entry->litros, 3),
                    'valor'      => round($entry->valor, 2),
                ];
            }
            $prev = $entry;
        }

        return $pontos;
    }
}

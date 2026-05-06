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

    /**
     * Retorna o consumo médio (km/l) comparando este abastecimento
     * com o anterior que tenha km registrado.
     * Usa a collection já carregada (passada como parâmetro) para
     * evitar N+1 queries na view.
     */
    public function consumoMedio(?Collection $allEntries = null): ?float
    {
        if (! $this->km_abastecimento || ! $this->litros) {
            return null;
        }

        if ($allEntries) {
            // Usa a collection em memória — sem query extra
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

    /**
     * Retorna array de pontos para o gráfico de consumo:
     * [['label' => '12/05/2026', 'consumo' => 12.5], ...]
     * Apenas entradas com km e litros informados, em ordem cronológica.
     */
    public static function historicoConsumo(Collection $entries): array
    {
        // Ordena do mais antigo para o mais novo para calcular corretamente
        $sorted = $entries
            ->filter(fn($e) => $e->km_abastecimento && $e->litros)
            ->sortBy(['data', 'id']);

        $pontos = [];
        $prev = null;

        foreach ($sorted as $entry) {
            if ($prev && $entry->km_abastecimento > $prev->km_abastecimento) {
                $km = $entry->km_abastecimento - $prev->km_abastecimento;
                $pontos[] = [
                    'label'   => $entry->data->format('d/m/Y'),
                    'consumo' => round($km / $entry->litros, 2),
                    'km'      => $entry->km_abastecimento,
                    'litros'  => round($entry->litros, 3),
                    'valor'   => round($entry->valor, 2),
                ];
            }
            $prev = $entry;
        }

        return $pontos;
    }
}

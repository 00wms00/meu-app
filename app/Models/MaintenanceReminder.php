<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'vehicle_id',
        'descricao',
        'km_ultimo_servico',
        'intervalo_km',
        'data_ultimo_servico',
        'observacao',
        'ativo',
    ];

    protected $casts = [
        'km_ultimo_servico' => 'integer',
        'intervalo_km'      => 'integer',
        'km_alerta'         => 'integer',
        'data_ultimo_servico' => 'date',
        'ativo'             => 'boolean',
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
     * Status do alerta comparando km_alerta com km atual do veiculo.
     * Retorna: 'vencido' | 'proximo' | 'ok'
     *
     * @param int $kmAtual   km atual do veiculo
     * @param int $margem    km de antecedencia para alertar (padrao 500)
     */
    public function statusAlerta(int $kmAtual, int $margem = 500): string
    {
        if ($kmAtual >= $this->km_alerta) {
            return 'vencido';
        }

        if ($kmAtual >= ($this->km_alerta - $margem)) {
            return 'proximo';
        }

        return 'ok';
    }

    /**
     * Km restantes ate o proximo servico (negativo = ja passou).
     */
    public function kmRestantes(int $kmAtual): int
    {
        return $this->km_alerta - $kmAtual;
    }

    /**
     * Registra que o servico foi feito agora (atualiza km_ultimo_servico).
     */
    public function marcarFeito(int $kmAtual, ?string $data = null): void
    {
        $this->update([
            'km_ultimo_servico'   => $kmAtual,
            'data_ultimo_servico' => $data ?? now()->toDateString(),
        ]);
    }
}

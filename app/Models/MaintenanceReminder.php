<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicle_id',
        'descricao',
        'km_ultimo_servico',
        'intervalo_km',
        'data_ultimo_servico',
        'ativo',
    ];

    protected $casts = [
        'data_ultimo_servico' => 'date',
        'ativo'               => 'boolean',
        'km_ultimo_servico'   => 'integer',
        'intervalo_km'        => 'integer',
    ];

    // ----------------------------------------------------------------
    // Relações
    // ----------------------------------------------------------------

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    // ----------------------------------------------------------------
    // Helpers calculados
    // ----------------------------------------------------------------

    /**
     * Km no qual o alerta dispara.
     * Usa o atributo virtual do banco quando disponível,
     * ou calcula em PHP como fallback.
     */
    public function getKmAlertaAttribute(): ?int
    {
        if (! $this->km_ultimo_servico) {
            return null;
        }
        return $this->km_ultimo_servico + $this->intervalo_km;
    }

    /**
     * Quantos km faltam (ou passaram) para o próximo serviço.
     * Positivo = faltam; Negativo = já passou.
     */
    public function kmRestantes(?int $kmAtual): ?int
    {
        $alerta = $this->km_alerta;
        if ($alerta === null || $kmAtual === null) {
            return null;
        }
        return $alerta - $kmAtual;
    }

    /**
     * Retorna o status do lembrete.
     * 'ok'      => verde  (✅ Em dia)
     * 'proximo' => amarelo (⚠️ Próximo)
     * 'vencido' => vermelho (🔴 Vencido)
     * 'sem_km'  => cinza   (sem dados de km)
     */
    public function statusAlerta(?int $kmAtual): string
    {
        $restantes = $this->kmRestantes($kmAtual);

        if ($restantes === null) {
            return 'sem_km';
        }

        if ($restantes <= 0) {
            return 'vencido';
        }

        if ($restantes <= 500) {
            return 'proximo';
        }

        return 'ok';
    }
}

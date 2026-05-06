<?php

namespace App\Models;

use Carbon\Carbon;
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
        'intervalo_meses',
        'data_ultimo_servico',
        'ativo',
    ];

    protected $casts = [
        'data_ultimo_servico' => 'date',
        'ativo'               => 'boolean',
        'km_ultimo_servico'   => 'integer',
        'intervalo_km'        => 'integer',
        'intervalo_meses'     => 'integer',
    ];

    // ----------------------------------------------------------------
    // Relações
    // ----------------------------------------------------------------

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    // ----------------------------------------------------------------
    // Helpers calculados — km
    // ----------------------------------------------------------------

    public function getKmAlertaAttribute(): ?int
    {
        if (! $this->km_ultimo_servico || ! $this->intervalo_km) {
            return null;
        }
        return $this->km_ultimo_servico + $this->intervalo_km;
    }

    public function kmRestantes(?int $kmAtual): ?int
    {
        $alerta = $this->km_alerta;
        if ($alerta === null || $kmAtual === null) {
            return null;
        }
        return $alerta - $kmAtual;
    }

    // ----------------------------------------------------------------
    // Helpers calculados — data
    // ----------------------------------------------------------------

    public function getDataAlertaAttribute(): ?Carbon
    {
        if (! $this->data_ultimo_servico || ! $this->intervalo_meses) {
            return null;
        }
        return $this->data_ultimo_servico->copy()->addMonths($this->intervalo_meses);
    }

    /** Quantos dias faltam (positivo) ou passaram (negativo) para o alerta por data. */
    public function diasRestantes(?Carbon $hoje = null): ?int
    {
        $alerta = $this->data_alerta;
        if ($alerta === null) {
            return null;
        }
        $hoje ??= Carbon::today();
        return $hoje->diffInDays($alerta, false); // signed
    }

    // ----------------------------------------------------------------
    // Status consolidado (km + data — o mais restritivo vence)
    // ----------------------------------------------------------------

    /**
     * 'ok'      => ✅ Em dia
     * 'proximo' => ⚠️ Próximo  (< 500 km restantes  OU  < 30 dias)
     * 'vencido' => 🔴 Vencido  (km >= km_alerta  OU  hoje >= data_alerta)
     * 'sem_ref' => ❓ Sem referência (nem km nem data configurados)
     */
    public function statusAlerta(?int $kmAtual, ?Carbon $hoje = null): string
    {
        $hoje       ??= Carbon::today();
        $kmRest     = $this->kmRestantes($kmAtual);
        $diasRest   = $this->diasRestantes($hoje);

        // Sem nenhuma referência
        if ($kmRest === null && $diasRest === null) {
            return 'sem_ref';
        }

        // Vencido — qualquer uma das dimensões passou
        if (($kmRest !== null && $kmRest <= 0) || ($diasRest !== null && $diasRest <= 0)) {
            return 'vencido';
        }

        // Próximo — qualquer uma das dimensões está perto
        if (($kmRest !== null && $kmRest <= 500) || ($diasRest !== null && $diasRest <= 30)) {
            return 'proximo';
        }

        return 'ok';
    }

    /** Label human-readable do motivo do alerta (para tooltip/detalhe). */
    public function motivoAlerta(?int $kmAtual, ?Carbon $hoje = null): array
    {
        $hoje     ??= Carbon::today();
        $motivos    = [];
        $kmRest     = $this->kmRestantes($kmAtual);
        $diasRest   = $this->diasRestantes($hoje);

        if ($kmRest !== null && $kmRest <= 500) {
            $motivos[] = $kmRest <= 0
                ? 'Passou ' . number_format(abs($kmRest), 0, ',', '.') . ' km do prazo'
                : 'Faltam ' . number_format($kmRest, 0, ',', '.') . ' km';
        }
        if ($diasRest !== null && $diasRest <= 30) {
            $motivos[] = $diasRest <= 0
                ? 'Prazo por data vencido há ' . abs($diasRest) . ' dia(s)'
                : 'Vence em ' . $diasRest . ' dia(s) (' . $this->data_alerta->format('d/m/Y') . ')';
        }
        return $motivos;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\FuelEntry;
use App\Models\Vehicle;
use App\Models\VehicleExpense;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class VehicleReportController extends Controller
{
    /**
     * Resumo de custos por veículo com filtro de período livre.
     */
    public function monthly(Request $request): View
    {
        $userId = Auth::id();

        $modo = $request->get('modo', 'mes');

        [$dataInicio, $dataFim, $labelPeriodo] = match ($modo) {
            'dia' => (function () use ($request) {
                $dia = $request->get('dia', now()->toDateString());
                $d   = Carbon::parse($dia);
                return [$d->copy()->startOfDay(), $d->copy()->endOfDay(),
                        $d->translatedFormat('d \de F \de Y')];
            })(),
            'livre' => (function () use ($request) {
                $ini = Carbon::parse($request->get('data_inicio', now()->startOfMonth()->toDateString()));
                $fim = Carbon::parse($request->get('data_fim',    now()->toDateString()));
                if ($fim->lt($ini)) $fim = $ini->copy();
                return [$ini->copy()->startOfDay(), $fim->copy()->endOfDay(),
                        $ini->translatedFormat('d/m/Y') . ' até ' . $fim->translatedFormat('d/m/Y')];
            })(),
            default => (function () use ($request) {
                $ano = (int) $request->get('ano', now()->year);
                $mes = (int) $request->get('mes', now()->month);
                $ini = Carbon::create($ano, $mes, 1)->startOfDay();
                $fim = $ini->copy()->endOfMonth()->endOfDay();
                return [$ini, $fim, $ini->translatedFormat('F Y')];
            })(),
        };

        $mesesDisp = $this->mesesComDados($userId);
        if ($mesesDisp->isEmpty()) {
            $mesesDisp = collect([[
                'ano'   => now()->year,
                'mes'   => now()->month,
                'label' => now()->translatedFormat('F Y'),
            ]]);
        }

        $vehicles = Vehicle::where('user_id', $userId)->orderBy('apelido')->get();

        $resumo = [];

        foreach ($vehicles as $vehicle) {
            // Combustível no período
            $fuel = FuelEntry::where('vehicle_id', $vehicle->id)
                ->whereBetween('data', [$dataInicio->toDateString(), $dataFim->toDateString()])
                ->selectRaw('SUM(valor) as total_valor, SUM(litros) as total_litros, COUNT(*) as qtd')
                ->first();

            $totalCombust = (float) ($fuel->total_valor ?? 0);
            $totalLitros  = $fuel->total_litros ? (float) $fuel->total_litros : null;
            $qtdAbast     = (int) ($fuel->qtd ?? 0);
            $mediaPrecoL  = ($totalLitros && $totalLitros > 0)
                ? round($totalCombust / $totalLitros, 3)
                : null;

            // KM rodados no período:
            // máx KM dentro do intervalo  −  máx KM anterior ao início (ou primeiro KM do intervalo)
            $kmFim = FuelEntry::where('vehicle_id', $vehicle->id)
                ->whereBetween('data', [$dataInicio->toDateString(), $dataFim->toDateString()])
                ->whereNotNull('km_abastecimento')
                ->max('km_abastecimento');

            $kmInicio = FuelEntry::where('vehicle_id', $vehicle->id)
                ->where('data', '<', $dataInicio->toDateString())
                ->whereNotNull('km_abastecimento')
                ->max('km_abastecimento');

            // Fallback: se não há km anterior, usa o mínimo dentro do período
            if (is_null($kmInicio)) {
                $kmInicio = FuelEntry::where('vehicle_id', $vehicle->id)
                    ->whereBetween('data', [$dataInicio->toDateString(), $dataFim->toDateString()])
                    ->whereNotNull('km_abastecimento')
                    ->min('km_abastecimento');
            }

            $kmRodados = ($kmFim && $kmInicio && $kmFim > $kmInicio)
                ? $kmFim - $kmInicio
                : null;

            // Custo por km no período (só combustível)
            $custoPorKm = ($kmRodados && $totalCombust > 0)
                ? round($totalCombust / $kmRodados, 4)
                : null;

            // Despesas agrupadas por tipo
            $expenses = VehicleExpense::where('vehicle_id', $vehicle->id)
                ->whereBetween('data', [$dataInicio->toDateString(), $dataFim->toDateString()])
                ->selectRaw('tipo, SUM(valor) as subtotal')
                ->groupBy('tipo')
                ->pluck('subtotal', 'tipo');

            $totalManut  = (float) ($expenses->get('manutencao') ?? 0);
            $totalOutros = collect(['seguro', 'impostos', 'pedagio', 'outros'])
                ->sum(fn($t) => (float) ($expenses->get($t) ?? 0));

            $total = $totalCombust + $totalManut + $totalOutros;

            if ($total > 0 || $qtdAbast > 0) {
                $resumo[$vehicle->id] = [
                    'vehicle'           => $vehicle,
                    'combustivel'       => $totalCombust,
                    'manutencao'        => $totalManut,
                    'outros'            => $totalOutros,
                    'total'             => $total,
                    'litros'            => $totalLitros,
                    'media_preco_litro' => $mediaPrecoL,
                    'abastecimentos'    => $qtdAbast,
                    'por_tipo'          => $expenses,
                    'km_rodados'        => $kmRodados,
                    'custo_por_km'      => $custoPorKm,
                ];
            }
        }

        uasort($resumo, fn($a, $b) => $b['total'] <=> $a['total']);

        $totaisGerais = [
            'combustivel' => array_sum(array_column($resumo, 'combustivel')),
            'manutencao'  => array_sum(array_column($resumo, 'manutencao')),
            'outros'      => array_sum(array_column($resumo, 'outros')),
            'total'       => array_sum(array_column($resumo, 'total')),
            'km_rodados'  => array_sum(array_filter(array_column($resumo, 'km_rodados'))),
        ];

        $chartLabels  = [];
        $chartCombust = [];
        $chartManut   = [];
        $chartOutros  = [];
        foreach ($resumo as $row) {
            $chartLabels[]  = $row['vehicle']->apelido;
            $chartCombust[] = round($row['combustivel'], 2);
            $chartManut[]   = round($row['manutencao'], 2);
            $chartOutros[]  = round($row['outros'], 2);
        }

        return view('vehicles.report-monthly', compact(
            'vehicles', 'mesesDisp',
            'modo', 'dataInicio', 'dataFim', 'labelPeriodo',
            'resumo', 'totaisGerais',
            'chartLabels', 'chartCombust', 'chartManut', 'chartOutros',
        ));
    }

    private function mesesComDados(int $userId)
    {
        $vehicleIds = Vehicle::where('user_id', $userId)->pluck('id');

        if ($vehicleIds->isEmpty()) {
            return collect();
        }

        $mesesFuel = FuelEntry::whereIn('vehicle_id', $vehicleIds)
            ->selectRaw('EXTRACT(YEAR FROM data)::int as ano, EXTRACT(MONTH FROM data)::int as mes')
            ->distinct()->get();

        $mesesExp = VehicleExpense::whereIn('vehicle_id', $vehicleIds)
            ->selectRaw('EXTRACT(YEAR FROM data)::int as ano, EXTRACT(MONTH FROM data)::int as mes')
            ->distinct()->get();

        return $mesesFuel->concat($mesesExp)
            ->unique(fn($r) => $r->ano . '-' . str_pad($r->mes, 2, '0', '0'))
            ->sortByDesc(fn($r) => $r->ano * 100 + $r->mes)
            ->map(fn($r) => [
                'ano'   => (int) $r->ano,
                'mes'   => (int) $r->mes,
                'label' => Carbon::create($r->ano, $r->mes, 1)->translatedFormat('F Y'),
            ])
            ->values();
    }
}

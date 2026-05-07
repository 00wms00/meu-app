<?php

namespace App\Http\Controllers;

use App\Models\FuelEntry;
use App\Models\Vehicle;
use App\Models\VehicleExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class VehicleReportController extends Controller
{
    /**
     * Resumo mensal de custos por veículo.
     * URL: GET /vehicles/report/monthly?ano=2026&mes=5
     */
    public function monthly(Request $request): View
    {
        $userId = Auth::id();

        $mesesDisp = $this->mesesComDados($userId);

        $ano = (int) $request->get('ano', now()->year);
        $mes = (int) $request->get('mes', now()->month);

        if ($mesesDisp->isEmpty()) {
            $mesesDisp = collect([['ano' => $ano, 'mes' => $mes, 'label' => now()->translatedFormat('F Y')]]);
        }

        $vehicles = Vehicle::where('user_id', $userId)->orderBy('apelido')->get();

        $resumo = [];

        foreach ($vehicles as $vehicle) {
            // Combustível
            $fuel = FuelEntry::where('vehicle_id', $vehicle->id)
                ->whereYear('data', $ano)
                ->whereMonth('data', $mes)
                ->selectRaw('SUM(valor) as total_valor, SUM(litros) as total_litros, COUNT(*) as qtd')
                ->first();

            $totalCombust = (float) ($fuel->total_valor ?? 0);
            $totalLitros  = $fuel->total_litros ? (float) $fuel->total_litros : null;
            $qtdAbast     = (int) ($fuel->qtd ?? 0);
            $mediaPrecoL  = ($totalLitros && $totalLitros > 0) ? round($totalCombust / $totalLitros, 3) : null;

            // Despesas agrupadas por tipo
            $expenses = VehicleExpense::where('vehicle_id', $vehicle->id)
                ->whereYear('data', $ano)
                ->whereMonth('data', $mes)
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
                ];
            }
        }

        uasort($resumo, fn($a, $b) => $b['total'] <=> $a['total']);

        $totaisGerais = [
            'combustivel' => array_sum(array_column($resumo, 'combustivel')),
            'manutencao'  => array_sum(array_column($resumo, 'manutencao')),
            'outros'      => array_sum(array_column($resumo, 'outros')),
            'total'       => array_sum(array_column($resumo, 'total')),
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
            'vehicles', 'ano', 'mes', 'mesesDisp',
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
            ->selectRaw('YEAR(data) as ano, MONTH(data) as mes')
            ->distinct()->get();

        $mesesExp = VehicleExpense::whereIn('vehicle_id', $vehicleIds)
            ->selectRaw('YEAR(data) as ano, MONTH(data) as mes')
            ->distinct()->get();

        return $mesesFuel->concat($mesesExp)
            ->unique(fn($r) => $r->ano . '-' . str_pad($r->mes, 2, '0', STR_PAD_LEFT))
            ->sortByDesc(fn($r) => $r->ano * 100 + $r->mes)
            ->map(fn($r) => [
                'ano'   => (int) $r->ano,
                'mes'   => (int) $r->mes,
                'label' => \Carbon\Carbon::create($r->ano, $r->mes, 1)->translatedFormat('F Y'),
            ])
            ->values();
    }
}

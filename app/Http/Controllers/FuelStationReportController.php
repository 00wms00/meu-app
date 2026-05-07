<?php

namespace App\Http\Controllers;

use App\Models\FuelEntry;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FuelStationReportController extends Controller
{
    public function index(Request $request): View
    {
        $userId     = Auth::id();
        $vehicleIds = Vehicle::where('user_id', $userId)->pluck('id');

        // --- Filtros ---
        $tipoComb = $request->get('tipo', 'todos');
        $periodo  = $request->get('periodo', '12m'); // 3m | 6m | 12m | todos

        $dataCorte = match ($periodo) {
            '3m'  => now()->subMonths(3)->startOfDay(),
            '6m'  => now()->subMonths(6)->startOfDay(),
            '12m' => now()->subMonths(12)->startOfDay(),
            default => null,
        };

        // Base query — só entradas com posto e preço/litro válidos
        $base = FuelEntry::whereIn('vehicle_id', $vehicleIds)
            ->whereNotNull('posto')
            ->whereNotNull('litros')
            ->where('litros', '>', 0)
            ->whereRaw('valor > 0');

        if ($dataCorte) {
            $base->where('data', '>=', $dataCorte->toDateString());
        }
        if ($tipoComb !== 'todos') {
            $base->where('tipo_combustivel', $tipoComb);
        }

        // Todos os tipos disponíveis para o filtro
        $tiposDisp = FuelEntry::whereIn('vehicle_id', $vehicleIds)
            ->whereNotNull('tipo_combustivel')
            ->whereNotNull('posto')
            ->whereNotNull('litros')
            ->where('litros', '>', 0)
            ->distinct()
            ->pluck('tipo_combustivel')
            ->sort()
            ->values();

        // --- RANKING DE POSTOS ---
        $rankingRaw = (clone $base)
            ->selectRaw("
                posto,
                COUNT(*) as visitas,
                SUM(litros) as total_litros,
                SUM(valor) as total_valor,
                ROUND(SUM(valor)::numeric / NULLIF(SUM(litros), 0), 3) as preco_medio,
                MIN(ROUND(valor::numeric / NULLIF(litros, 0), 3)) as preco_minimo,
                MAX(data) as ultima_visita
            ")
            ->groupBy('posto')
            ->orderByRaw('preco_medio ASC')
            ->get();

        // Melhor preço geral para calcular % de economia
        $melhorPreco = $rankingRaw->min('preco_medio');

        $ranking = $rankingRaw->map(function ($r) use ($melhorPreco) {
            $economia = $melhorPreco > 0 && $r->preco_medio > $melhorPreco
                ? round((($r->preco_medio - $melhorPreco) / $melhorPreco) * 100, 1)
                : 0;
            return (object) [
                'posto'        => $r->posto,
                'visitas'      => (int) $r->visitas,
                'total_litros' => round((float) $r->total_litros, 2),
                'total_valor'  => round((float) $r->total_valor, 2),
                'preco_medio'  => round((float) $r->preco_medio, 3),
                'preco_minimo' => round((float) $r->preco_minimo, 3),
                'ultima_visita'=> Carbon::parse($r->ultima_visita)->format('d/m/Y'),
                'economia_pct' => $economia,
                'is_melhor'    => $economia === 0.0,
            ];
        });

        // --- EVOLUÇÃO DO PREÇO POR TIPO ao longo do tempo ---
        // Agrupa por mês + tipo e calcula preço médio
        $evolucaoRaw = FuelEntry::whereIn('vehicle_id', $vehicleIds)
            ->whereNotNull('tipo_combustivel')
            ->whereNotNull('litros')
            ->where('litros', '>', 0)
            ->whereRaw('valor > 0')
            ->when($dataCorte, fn($q) => $q->where('data', '>=', $dataCorte->toDateString()))
            ->selectRaw("
                tipo_combustivel,
                TO_CHAR(data, 'YYYY-MM') as mes,
                ROUND(SUM(valor)::numeric / NULLIF(SUM(litros), 0), 3) as preco_medio
            ")
            ->groupBy('tipo_combustivel', 'mes')
            ->orderBy('mes')
            ->get();

        // Pivota para Chart.js: { labels: [...], datasets: [{ label, data }] }
        $mesesEvolucao = $evolucaoRaw->pluck('mes')->unique()->sort()->values();
        $tiposEvolucao = $evolucaoRaw->pluck('tipo_combustivel')->unique()->sort()->values();

        $coresEvolucao = [
            'gasolina'          => 'rgba(59,130,246,1)',
            'gasolina_aditivada'=> 'rgba(99,102,241,1)',
            'etanol'            => 'rgba(34,197,94,1)',
            'diesel'            => 'rgba(234,88,12,1)',
            'gnv'               => 'rgba(234,179,8,1)',
            'eletrico'          => 'rgba(168,85,247,1)',
        ];

        $datasetsEvolucao = $tiposEvolucao->map(function ($tipo) use ($evolucaoRaw, $mesesEvolucao, $coresEvolucao) {
            $porMes = $evolucaoRaw->where('tipo_combustivel', $tipo)->keyBy('mes');
            return [
                'label'           => $tipo,
                'data'            => $mesesEvolucao->map(fn($m) => $porMes->has($m) ? (float) $porMes[$m]->preco_medio : null)->values(),
                'borderColor'     => $coresEvolucao[$tipo] ?? 'rgba(107,114,128,1)',
                'backgroundColor' => str_replace(',1)', ',0.08)', $coresEvolucao[$tipo] ?? 'rgba(107,114,128,0.08)'),
                'borderWidth'     => 2,
                'pointRadius'     => 4,
                'tension'         => 0.3,
                'spanGaps'        => true,
                'fill'            => false,
            ];
        })->values();

        $labelsEvolucao = $mesesEvolucao->map(fn($m) => Carbon::createFromFormat('Y-m', $m)->translatedFormat('M/y'))->values();

        // --- MELHOR POSTO POR TIPO ---
        $melhorPorTipo = (clone $base)
            ->selectRaw("
                tipo_combustivel,
                posto,
                ROUND(SUM(valor)::numeric / NULLIF(SUM(litros), 0), 3) as preco_medio,
                COUNT(*) as visitas
            ")
            ->whereNotNull('tipo_combustivel')
            ->groupBy('tipo_combustivel', 'posto')
            ->orderByRaw('tipo_combustivel, preco_medio ASC')
            ->get()
            ->groupBy('tipo_combustivel')
            ->map(fn($g) => $g->first());

        // --- TOTAIS GERAIS ---
        $totalEntradas = (clone $base)->count();
        $totalPostos   = $ranking->count();

        $tiposLabels = [
            'gasolina'           => 'Gasolina',
            'gasolina_aditivada' => 'Gasolina Aditivada',
            'etanol'             => 'Étanol',
            'diesel'             => 'Diesel',
            'gnv'                => 'GNV',
            'eletrico'           => 'Elétrico',
        ];

        return view('vehicles.fuel-stations', compact(
            'ranking', 'tiposDisp', 'tipoComb', 'periodo',
            'labelsEvolucao', 'datasetsEvolucao',
            'melhorPorTipo', 'tiposLabels',
            'totalEntradas', 'totalPostos',
        ));
    }
}

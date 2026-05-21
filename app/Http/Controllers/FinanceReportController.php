<?php

namespace App\Http\Controllers;

use App\Models\FinanceIncome;
use App\Models\FinanceExpense;
use App\Models\Invoice;
use App\Models\FuelEntry;
use App\Models\VehicleExpense;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinanceReportController extends Controller
{
    public function index(Request $request)
    {
        $preset = $request->input('preset', 'esse-mes');

        if ($preset === 'personalizado' && $request->filled(['data_inicio', 'data_fim'])) {
            $inicio = Carbon::createFromFormat('Y-m-d', $request->data_inicio)->startOfDay();
            $fim    = Carbon::createFromFormat('Y-m-d', $request->data_fim)->endOfDay();
        } else {
            $inicio = Carbon::now()->startOfMonth();
            $fim    = Carbon::now()->endOfMonth();
            $preset = 'esse-mes';
        }

        // ==================== RECEITAS ====================
        $incomes = FinanceIncome::whereBetween('mes_referencia', [$inicio, $fim])
            ->orderBy('mes_referencia')
            ->orderBy('pessoa')
            ->orderBy('descricao')
            ->get();

        $totalReceitas = $incomes->sum('valor');

        // ==================== DESPESAS MANUAIS ====================
        $expenses = FinanceExpense::whereBetween('mes_referencia', [$inicio, $fim])
            ->orderBy('tipo_despesa')
            ->orderBy('categoria')
            ->orderBy('descricao')
            ->get();

        $totalDespesasManuais = $expenses->sum('valor');

        // ==================== MERCADO ====================
        $invoices = Invoice::whereBetween('data_emissao', [$inicio, $fim])
            ->where('user_id', Auth::id())
            ->get();

        $totalMercadoPeriodo = $invoices->sum('valor_pago');

        // ==================== VEÍCULOS ====================
        $vehicleMaint = VehicleExpense::whereBetween('data', [$inicio, $fim])
            ->whereHas('vehicle', fn($q) => $q->where('user_id', Auth::id()))
            ->get();

        $fuel = FuelEntry::whereBetween('data', [$inicio, $fim])
            ->where('user_id', Auth::id())
            ->get();

        $totalVeiculosPeriodo = $vehicleMaint->sum('valor') + $fuel->sum('valor');

        $totalDespesas = $totalDespesasManuais + $totalMercadoPeriodo + $totalVeiculosPeriodo;

        // ==================== GRÁFICO 1: Receitas x Despesas por mês ====================
        $receitasPorMes = $incomes
            ->groupBy(fn($r) => $r->mes_referencia->format('Y-m'))
            ->map->sum('valor');

        $despesasPorMes = $expenses
            ->groupBy(fn($d) => $d->mes_referencia->format('Y-m'))
            ->map->sum('valor');

        $mesesSerie = $receitasPorMes->keys()
            ->merge($despesasPorMes->keys())
            ->unique()->sort()->values();

        $labelsMeses        = $mesesSerie->map(fn($m) => Carbon::createFromFormat('Y-m', $m)->format('m/Y'));
        $serieReceitasMes   = $mesesSerie->map(fn($m) => (float) ($receitasPorMes[$m] ?? 0))->values();
        $serieDespesasMes   = $mesesSerie->map(fn($m) => (float) ($despesasPorMes[$m] ?? 0))->values();

        // ==================== GRÁFICO 2: Despesas por categoria ====================
        $despesasPorCategoria = $expenses
            ->whereNotNull('categoria')
            ->groupBy('categoria')
            ->map->sum('valor');

        if ($totalMercadoPeriodo > 0) {
            $despesasPorCategoria['Mercado'] = ($despesasPorCategoria['Mercado'] ?? 0) + $totalMercadoPeriodo;
        }
        if ($totalVeiculosPeriodo > 0) {
            $despesasPorCategoria['Veículos'] = ($despesasPorCategoria['Veículos'] ?? 0) + $totalVeiculosPeriodo;
        }

        $despesasPorCategoria    = $despesasPorCategoria->sortDesc();
        $labelsCategorias        = $despesasPorCategoria->keys()->values();
        $serieDespesasCategorias = $despesasPorCategoria->map(fn($v) => (float) $v)->values();

        // ==================== GRÁFICO 3: Fixas x Variáveis — totais ====================
        $fixas    = $expenses->where('tipo_despesa', 'fixa');
        $variaveis = $expenses->where('tipo_despesa', 'variavel');

        $totalFixas    = (float) $fixas->sum('valor');
        $totalVariaveis = (float) $variaveis->sum('valor');

        // ==================== GRÁFICO 4: Fixas por categoria ====================
        $fixasPorCategoria = $fixas
            ->whereNotNull('categoria')
            ->groupBy('categoria')
            ->map(fn($g) => (float) $g->sum('valor'))
            ->sortDesc();

        $labelsFixasCat  = $fixasPorCategoria->keys()->values();
        $serieFixasCat   = $fixasPorCategoria->values();

        // ==================== GRÁFICO 5: Variáveis por categoria (inclui Mercado + Veículos) ====================
        $variaveisPorCategoria = $variaveis
            ->whereNotNull('categoria')
            ->groupBy('categoria')
            ->map(fn($g) => (float) $g->sum('valor'));

        if ($totalMercadoPeriodo > 0) {
            $variaveisPorCategoria['Mercado'] = ($variaveisPorCategoria['Mercado'] ?? 0) + $totalMercadoPeriodo;
        }
        if ($totalVeiculosPeriodo > 0) {
            $variaveisPorCategoria['Veículos'] = ($variaveisPorCategoria['Veículos'] ?? 0) + $totalVeiculosPeriodo;
        }

        $variaveisPorCategoria = $variaveisPorCategoria->sortDesc();
        $labelsVariaveisCat    = $variaveisPorCategoria->keys()->values();
        $serieVariaveisCat     = $variaveisPorCategoria->values();

        $saldo = $totalReceitas - $totalDespesas;

        return view('finance.report.index', compact(
            'inicio', 'fim', 'preset',
            'incomes', 'expenses',
            'totalReceitas', 'totalDespesas',
            'totalDespesasManuais', 'totalMercadoPeriodo', 'totalVeiculosPeriodo',
            'labelsMeses', 'serieReceitasMes', 'serieDespesasMes',
            'labelsCategorias', 'serieDespesasCategorias',
            'totalFixas', 'totalVariaveis',
            'labelsFixasCat', 'serieFixasCat',
            'labelsVariaveisCat', 'serieVariaveisCat',
            'saldo',
        ));
    }
}

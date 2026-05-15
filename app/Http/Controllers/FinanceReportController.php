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
        // Preset padrão: esse mês
        $preset = $request->input('preset', 'esse-mes');

        if ($preset === 'personalizado' && $request->filled(['data_inicio', 'data_fim'])) {
            $inicio = Carbon::createFromFormat('Y-m-d', $request->data_inicio)->startOfDay();
            $fim    = Carbon::createFromFormat('Y-m-d', $request->data_fim)->endOfDay();
        } else {
            // Esse mês: do 1º ao último dia do mês atual
            $inicio = Carbon::now()->startOfMonth();
            $fim    = Carbon::now()->endOfMonth();
            $preset = 'esse-mes';
        }

        // ==================== RECEITAS ====================
        // Usa mes_referencia entre início e fim (por mês)
        $incomes = FinanceIncome::whereBetween('mes_referencia', [
                $inicio->copy()->startOfMonth(),
                $fim->copy()->endOfMonth(),
            ])
            ->orderBy('mes_referencia')
            ->orderBy('pessoa')
            ->orderBy('descricao')
            ->get();

        $totalReceitas = $incomes->sum('valor');

        // ==================== DESPESAS MANUAIS ====================
        $expenses = FinanceExpense::whereBetween('mes_referencia', [
                $inicio->copy()->startOfMonth(),
                $fim->copy()->endOfMonth(),
            ])
            ->orderBy('tipo_despesa')
            ->orderBy('categoria')
            ->orderBy('descricao')
            ->get();

        $totalDespesasManuais = $expenses->sum('valor');

        // ==================== MERCADO (NOTAS IMPORTADAS) ====================
        $invoices = Invoice::whereBetween('data_emissao', [$inicio, $fim])
            ->where('user_id', Auth::id())
            ->get();

        $totalMercadoPeriodo = $invoices->sum('valor_pago');

        // ==================== VEÍCULOS (MANUTENÇÕES + COMBUSTÍVEL) ====================
        $vehicleMaint = VehicleExpense::whereBetween('data', [$inicio, $fim])
            ->whereHas('vehicle', fn($q) => $q->where('user_id', Auth::id()))
            ->get();

        $fuel = FuelEntry::whereBetween('data', [$inicio, $fim])
            ->where('user_id', Auth::id())
            ->get();

        $totalVeiculosPeriodo = $vehicleMaint->sum('valor') + $fuel->sum('valor');

        // Total geral de despesas considerando tudo
        $totalDespesas = $totalDespesasManuais + $totalMercadoPeriodo + $totalVeiculosPeriodo;

        // ==================== AGRUPAMENTOS AUXILIARES POR MÊS ====================
        $receitasPorMes = $incomes
            ->groupBy(fn($r) => $r->mes_referencia->format('Y-m'))
            ->map->sum('valor')
            ->sortKeys();

        $despesasPorMes = $expenses
            ->groupBy(fn($d) => $d->mes_referencia->format('Y-m'))
            ->map->sum('valor')
            ->sortKeys();

        $saldo = $totalReceitas - $totalDespesas;

        return view('finance.report.index', compact(
            'inicio',
            'fim',
            'preset',
            'incomes',
            'expenses',
            'totalReceitas',
            'totalDespesas',
            'totalDespesasManuais',
            'totalMercadoPeriodo',
            'totalVeiculosPeriodo',
            'receitasPorMes',
            'despesasPorMes',
            'saldo',
        ));
    }
}

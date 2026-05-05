<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        
        // Totais do mês atual
        $totalGasto = Invoice::where('user_id', $userId)
            ->whereMonth('data_emissao', now()->month)
            ->whereYear('data_emissao', now()->year)
            ->sum('valor_pago');
            
        $numNotas = Invoice::where('user_id', $userId)
            ->whereMonth('data_emissao', now()->month)
            ->whereYear('data_emissao', now()->year)
            ->count();
            
        $totalEstabelecimentos = Invoice::where('user_id', $userId)
            ->distinct('cnpj')
            ->count('cnpj');
            
        // Últimas notas
        $ultimosCupons = Invoice::where('user_id', $userId)
            ->orderBy('data_emissao', 'desc')
            ->take(10)
            ->get();

        // ✅ NOVO: Evolução dos gastos (últimos 6 meses)
        $evolucaoMensal = Invoice::where('user_id', $userId)
            ->where('data_emissao', '>=', now()->subMonths(6)->startOfMonth())
            ->selectRaw("TO_CHAR(data_emissao, 'YYYY-MM') as mes, SUM(valor_pago) as total")
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        // Preencher meses sem gastos com zero
        $mesesEvolucao = [];
        $valoresEvolucao = [];
        for ($i = 5; $i >= 0; $i--) {
            $mes = now()->subMonths($i)->format('Y-m');
            $valor = $evolucaoMensal->firstWhere('mes', $mes);
            $mesesEvolucao[] = now()->subMonths($i)->format('M/Y');
            $valoresEvolucao[] = $valor ? (float) $valor->total : 0;
        }

        // ✅ NOVO: Gastos diários dos últimos 30 dias
        $gastosDiarios = Invoice::where('user_id', $userId)
            ->where('data_emissao', '>=', now()->subDays(30))
            ->selectRaw("DATE(data_emissao) as data, SUM(valor_pago) as total")
            ->groupBy('data')
            ->orderBy('data')
            ->get();

        $diasEvolucao = [];
        $valoresDiarios = [];
        for ($i = 29; $i >= 0; $i--) {
            $data = now()->subDays($i)->format('Y-m-d');
            $valor = $gastosDiarios->firstWhere('data', $data);
            $diasEvolucao[] = now()->subDays($i)->format('d/m');
            $valoresDiarios[] = $valor ? (float) $valor->total : 0;
        }

        return view('dashboard', compact(
            'totalGasto',
            'numNotas', 
            'totalEstabelecimentos',
            'ultimosCupons',
            'mesesEvolucao',
            'valoresEvolucao',
            'diasEvolucao',
            'valoresDiarios'
        ));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\MaintenanceReminder;
use App\Models\Vehicle;
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

        // Evolução dos gastos (últimos 6 meses)
        $evolucaoMensal = Invoice::where('user_id', $userId)
            ->where('data_emissao', '>=', now()->subMonths(6)->startOfMonth())
            ->selectRaw("TO_CHAR(data_emissao, 'YYYY-MM') as mes, SUM(valor_pago) as total")
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        $mesesEvolucao   = [];
        $valoresEvolucao = [];
        for ($i = 5; $i >= 0; $i--) {
            $mes   = now()->subMonths($i)->format('Y-m');
            $valor = $evolucaoMensal->firstWhere('mes', $mes);
            $mesesEvolucao[]   = now()->subMonths($i)->format('M/Y');
            $valoresEvolucao[] = $valor ? (float) $valor->total : 0;
        }

        // Gastos diários dos últimos 30 dias
        $gastosDiarios = Invoice::where('user_id', $userId)
            ->where('data_emissao', '>=', now()->subDays(30))
            ->selectRaw("DATE(data_emissao) as data, SUM(valor_pago) as total")
            ->groupBy('data')
            ->orderBy('data')
            ->get();

        $diasEvolucao  = [];
        $valoresDiarios = [];
        for ($i = 29; $i >= 0; $i--) {
            $data  = now()->subDays($i)->format('Y-m-d');
            $valor = $gastosDiarios->firstWhere('data', $data);
            $diasEvolucao[]  = now()->subDays($i)->format('d/m');
            $valoresDiarios[] = $valor ? (float) $valor->total : 0;
        }

        // Fase 3: alertas de manutenção dos veículos do usuário
        $vehicles = Vehicle::where('user_id', $userId)->get();
        $kmPorVeiculo = $vehicles->keyBy('id')->map(fn($v) => $v->km_atual);

        $vehicleIds = $vehicles->pluck('id');
        $alertasVeiculos = MaintenanceReminder::whereIn('vehicle_id', $vehicleIds)
            ->where('ativo', true)
            ->get()
            ->map(function ($r) use ($kmPorVeiculo) {
                $r->status     = $r->statusAlerta($kmPorVeiculo->get($r->vehicle_id));
                $r->km_rest    = $r->kmRestantes($kmPorVeiculo->get($r->vehicle_id));
                return $r;
            })
            ->filter(fn($r) => in_array($r->status, ['vencido', 'proximo']))
            ->sortBy(fn($r) => $r->status === 'vencido' ? 0 : 1)
            ->values();

        // Eager load vehicle para exibir apelido no widget
        $alertasVeiculos->load('vehicle');

        return view('dashboard', compact(
            'totalGasto',
            'numNotas',
            'totalEstabelecimentos',
            'ultimosCupons',
            'mesesEvolucao',
            'valoresEvolucao',
            'diasEvolucao',
            'valoresDiarios',
            'alertasVeiculos',
        ));
    }
}

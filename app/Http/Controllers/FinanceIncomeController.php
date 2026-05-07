<?php

namespace App\Http\Controllers;

use App\Models\FinanceIncome;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FinanceIncomeController extends Controller
{
    public function index(Request $request)
    {
        $mes = $request->mes
            ? Carbon::createFromFormat('Y-m', $request->mes)->startOfMonth()
            : Carbon::now()->startOfMonth();

        $incomes = FinanceIncome::whereYear('mes_referencia', $mes->year)
            ->whereMonth('mes_referencia', $mes->month)
            ->orderBy('pessoa')
            ->orderBy('descricao')
            ->get();

        $totalGeral = $incomes->sum('valor');
        $totalWil   = $incomes->where('pessoa', 'WIL')->sum('valor');
        $totalMay   = $incomes->where('pessoa', 'MAY')->sum('valor');
        $totalComp  = $incomes->where('pessoa', 'compartilhado')->sum('valor');

        // Meses disponíveis para navegação (últimos 12 meses)
        $meses = collect(range(0, 11))->map(fn($i) => Carbon::now()->startOfMonth()->subMonths($i));

        return view('finance.incomes.index', compact(
            'incomes', 'mes', 'totalGeral', 'totalWil', 'totalMay', 'totalComp', 'meses'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'descricao'         => 'required|string|max:255',
            'pessoa'            => 'required|in:WIL,MAY,compartilhado',
            'tipo'              => 'required|in:salario,freelance,presente,aluguel,outros',
            'valor'             => 'required|numeric|min:0.01',
            'mes_referencia'    => 'required|date_format:Y-m',
            'data_recebimento'  => 'nullable|date',
            'recorrente'        => 'boolean',
            'observacao'        => 'nullable|string|max:500',
        ]);

        $data['mes_referencia'] = Carbon::createFromFormat('Y-m', $data['mes_referencia'])->startOfMonth();
        $data['recorrente']     = $request->boolean('recorrente');

        FinanceIncome::create($data);

        return redirect()
            ->route('finance.incomes.index', ['mes' => $request->mes_referencia])
            ->with('success', 'Receita adicionada com sucesso!');
    }

    public function update(Request $request, FinanceIncome $income)
    {
        $data = $request->validate([
            'descricao'         => 'required|string|max:255',
            'pessoa'            => 'required|in:WIL,MAY,compartilhado',
            'tipo'              => 'required|in:salario,freelance,presente,aluguel,outros',
            'valor'             => 'required|numeric|min:0.01',
            'mes_referencia'    => 'required|date_format:Y-m',
            'data_recebimento'  => 'nullable|date',
            'recorrente'        => 'boolean',
            'observacao'        => 'nullable|string|max:500',
        ]);

        $data['mes_referencia'] = Carbon::createFromFormat('Y-m', $data['mes_referencia'])->startOfMonth();
        $data['recorrente']     = $request->boolean('recorrente');

        $income->update($data);

        return redirect()
            ->route('finance.incomes.index', ['mes' => $request->mes_referencia])
            ->with('success', 'Receita atualizada!');
    }

    public function destroy(FinanceIncome $income)
    {
        $mes = $income->mes_referencia->format('Y-m');
        $income->delete();

        return redirect()
            ->route('finance.incomes.index', ['mes' => $mes])
            ->with('success', 'Receita removida.');
    }

    /**
     * Duplica as receitas recorrentes do mês anterior para o mês atual.
     */
    public function duplicarRecorrentes(Request $request)
    {
        $mes     = Carbon::createFromFormat('Y-m', $request->mes)->startOfMonth();
        $mesAnt  = $mes->copy()->subMonth();

        $recorrentes = FinanceIncome::whereYear('mes_referencia', $mesAnt->year)
            ->whereMonth('mes_referencia', $mesAnt->month)
            ->where('recorrente', true)
            ->get();

        $criados = 0;
        foreach ($recorrentes as $r) {
            // Evita duplicar se já existir
            $existe = FinanceIncome::whereYear('mes_referencia', $mes->year)
                ->whereMonth('mes_referencia', $mes->month)
                ->where('descricao', $r->descricao)
                ->where('pessoa', $r->pessoa)
                ->exists();

            if (!$existe) {
                FinanceIncome::create([
                    'descricao'        => $r->descricao,
                    'pessoa'           => $r->pessoa,
                    'tipo'             => $r->tipo,
                    'valor'            => $r->valor,
                    'mes_referencia'   => $mes,
                    'data_recebimento' => null,
                    'recorrente'       => true,
                    'observacao'       => $r->observacao,
                ]);
                $criados++;
            }
        }

        return redirect()
            ->route('finance.incomes.index', ['mes' => $mes->format('Y-m')])
            ->with('success', "{$criados} receita(s) recorrente(s) duplicada(s) do mês anterior.");
    }
}

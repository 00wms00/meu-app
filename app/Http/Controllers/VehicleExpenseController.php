<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceReminder;
use App\Models\Vehicle;
use App\Models\VehicleExpense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VehicleExpenseController extends Controller
{
    public function store(Request $request, Vehicle $vehicle): RedirectResponse
    {
        abort_if($vehicle->user_id !== Auth::id(), 403);

        $validated = $request->validate([
            'data'                     => ['required', 'date'],
            'tipo'                     => ['required', 'string', 'max:30'],
            'valor'                    => ['required', 'numeric', 'min:0'],
            'descricao'                => ['nullable', 'string', 'max:255'],
            'km_servico'               => ['nullable', 'integer', 'min:0'],
            'criar_lembrete'           => ['nullable', 'boolean'],
            'lembrete_descricao'       => ['nullable', 'string', 'max:120'],
            'lembrete_intervalo_km'    => ['nullable', 'integer', 'min:0', 'max:200000'],
            'lembrete_intervalo_meses' => ['nullable', 'integer', 'min:1', 'max:120'],
        ]);

        if (! empty($validated['km_servico']) && $validated['km_servico'] > ($vehicle->km_atual ?? 0)) {
            $vehicle->update(['km_atual' => $validated['km_servico']]);
        }

        VehicleExpense::create([
            'user_id'    => Auth::id(),
            'vehicle_id' => $vehicle->id,
            'data'       => $validated['data'],
            'tipo'       => $validated['tipo'],
            'valor'      => $validated['valor'],
            'descricao'  => $validated['descricao'] ?? null,
            'km_servico' => $validated['km_servico'] ?? null,
        ]);

        if (! empty($validated['criar_lembrete'])
            && (! empty($validated['lembrete_intervalo_km']) || ! empty($validated['lembrete_intervalo_meses']))
        ) {
            MaintenanceReminder::create([
                'vehicle_id'          => $vehicle->id,
                'descricao'           => $validated['lembrete_descricao'] ?? ($validated['descricao'] ?? $validated['tipo']),
                'km_ultimo_servico'   => $validated['km_servico'] ?? null,
                'intervalo_km'        => $validated['lembrete_intervalo_km'] ?? null,
                'intervalo_meses'     => $validated['lembrete_intervalo_meses'] ?? null,
                'data_ultimo_servico' => $validated['data'],
                'ativo'               => true,
            ]);
        }

        $fragment = ! empty($validated['criar_lembrete']) ? 'reminders' : 'expenses';

        return redirect()
            ->route('vehicles.show', $vehicle)
            ->with('success', 'Despesa cadastrada' . (! empty($validated['criar_lembrete']) ? ' e lembrete criado!' : ' com sucesso!'))
            ->withFragment($fragment);
    }

    public function update(Request $request, Vehicle $vehicle, VehicleExpense $expense): RedirectResponse
    {
        abort_if(
            $vehicle->user_id !== Auth::id()
            || $expense->user_id !== Auth::id()
            || $expense->vehicle_id !== $vehicle->id,
            403
        );

        $validated = $request->validate([
            'data'       => ['required', 'date'],
            'tipo'       => ['required', 'string', 'max:30'],
            'valor'      => ['required', 'numeric', 'min:0'],
            'descricao'  => ['nullable', 'string', 'max:255'],
            'km_servico' => ['nullable', 'integer', 'min:0'],
        ]);

        // Atualiza hodometro se km aumentou
        if (! empty($validated['km_servico']) && $validated['km_servico'] > ($vehicle->km_atual ?? 0)) {
            $vehicle->update(['km_atual' => $validated['km_servico']]);
        }

        $expense->update([
            'data'       => $validated['data'],
            'tipo'       => $validated['tipo'],
            'valor'      => $validated['valor'],
            'descricao'  => $validated['descricao'] ?? null,
            'km_servico' => $validated['km_servico'] ?? null,
        ]);

        return redirect()
            ->route('vehicles.show', $vehicle)
            ->with('success', 'Despesa atualizada com sucesso!')
            ->withFragment('expenses');
    }

    public function destroy(Vehicle $vehicle, VehicleExpense $expense): RedirectResponse
    {
        abort_if(
            $vehicle->user_id !== Auth::id()
            || $expense->user_id !== Auth::id()
            || $expense->vehicle_id !== $vehicle->id,
            403
        );

        $expense->delete();

        return redirect()
            ->route('vehicles.show', $vehicle)
            ->with('success', 'Despesa removida com sucesso!')
            ->withFragment('expenses');
    }
}

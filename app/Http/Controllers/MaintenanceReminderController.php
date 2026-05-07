<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceReminder;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MaintenanceReminderController extends Controller
{
    /**
     * Cria um novo lembrete de manutenção para o veículo.
     */
    public function store(Request $request, Vehicle $vehicle): RedirectResponse
    {
        abort_if($vehicle->user_id !== Auth::id(), 403);

        $validated = $request->validate([
            'descricao'           => ['required', 'string', 'max:120'],
            'km_ultimo_servico'   => ['nullable', 'integer', 'min:0'],
            'intervalo_km'        => ['required', 'integer', 'min:100'],
            'data_ultimo_servico' => ['nullable', 'date'],
        ]);

        $validated['vehicle_id'] = $vehicle->id;

        MaintenanceReminder::create($validated);

        return redirect()
            ->route('vehicles.show', $vehicle)
            ->with('success', 'Lembrete de manutenção criado!')
            ->withFragment('reminders');
    }

    /**
     * Marca o serviço como feito: atualiza km_ultimo_servico e data.
     */
    public function feito(Request $request, Vehicle $vehicle, MaintenanceReminder $reminder): RedirectResponse
    {
        abort_if($vehicle->user_id !== Auth::id(), 403);
        abort_if($reminder->vehicle_id !== $vehicle->id, 403);

        $validated = $request->validate([
            'km_realizado'   => ['required', 'integer', 'min:0'],
            'data_realizado' => ['nullable', 'date'],
        ]);

        $reminder->update([
            'km_ultimo_servico'   => $validated['km_realizado'],
            'data_ultimo_servico' => $validated['data_realizado'] ?? now()->toDateString(),
        ]);

        // Atualiza km_atual do veículo se o km informado for maior
        if ($validated['km_realizado'] > ($vehicle->km_atual ?? 0)) {
            $vehicle->update(['km_atual' => $validated['km_realizado']]);
        }

        return redirect()
            ->route('vehicles.show', $vehicle)
            ->with('success', 'Serviço marcado como feito! Lembrete reiniciado.')
            ->withFragment('reminders');
    }

    /**
     * Remove o lembrete.
     */
    public function destroy(Vehicle $vehicle, MaintenanceReminder $reminder): RedirectResponse
    {
        abort_if($vehicle->user_id !== Auth::id(), 403);
        abort_if($reminder->vehicle_id !== $vehicle->id, 403);

        $reminder->delete();

        return redirect()
            ->route('vehicles.show', $vehicle)
            ->with('success', 'Lembrete removido.')
            ->withFragment('reminders');
    }
}

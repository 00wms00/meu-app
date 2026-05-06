<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceReminder;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MaintenanceReminderController extends Controller
{
    public function store(Request $request, Vehicle $vehicle): RedirectResponse
    {
        if ($vehicle->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'descricao'           => ['required', 'string', 'max:150'],
            'km_ultimo_servico'   => ['required', 'integer', 'min:0'],
            'intervalo_km'        => ['required', 'integer', 'min:100'],
            'data_ultimo_servico' => ['nullable', 'date'],
            'observacao'          => ['nullable', 'string', 'max:255'],
        ]);

        $validated['user_id']    = Auth::id();
        $validated['vehicle_id'] = $vehicle->id;

        MaintenanceReminder::create($validated);

        return redirect()
            ->route('vehicles.show', $vehicle)
            ->with('success', 'Lembrete de manutenção criado!')
            ->withFragment('lembretes');
    }

    public function feito(Request $request, Vehicle $vehicle, MaintenanceReminder $reminder): RedirectResponse
    {
        if ($reminder->vehicle_id !== $vehicle->id || $vehicle->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'km_feito' => ['required', 'integer', 'min:0'],
            'data_feito' => ['nullable', 'date'],
        ]);

        $reminder->marcarFeito($validated['km_feito'], $validated['data_feito'] ?? null);

        return redirect()
            ->route('vehicles.show', $vehicle)
            ->with('success', 'Serviço registrado! Próximo em ' . number_format($validated['km_feito'] + $reminder->intervalo_km, 0, ',', '.') . ' km.')
            ->withFragment('lembretes');
    }

    public function destroy(Vehicle $vehicle, MaintenanceReminder $reminder): RedirectResponse
    {
        if ($reminder->vehicle_id !== $vehicle->id || $vehicle->user_id !== Auth::id()) {
            abort(403);
        }

        $reminder->delete();

        return redirect()
            ->route('vehicles.show', $vehicle)
            ->with('success', 'Lembrete removido.')
            ->withFragment('lembretes');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\FuelEntry;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FuelEntryController extends Controller
{
    public function store(Request $request, Vehicle $vehicle): RedirectResponse
    {
        if ($vehicle->user_id !== Auth::id()) {
            abort(403);
        }

        $data = $request->validate([
            'data'             => ['required', 'date'],
            'valor'            => ['required', 'numeric', 'min:0'],
            'litros'           => ['nullable', 'numeric', 'min:0'],
            'km_abastecimento' => ['nullable', 'integer', 'min:0'],
            'tipo_combustivel' => ['nullable', 'string', 'max:30'],
            'posto'            => ['nullable', 'string', 'max:150'],
            'tanque_cheio'     => ['nullable', 'boolean'],
            'descricao'        => ['nullable', 'string', 'max:255'],
        ]);

        $data['user_id']    = Auth::id();
        $data['vehicle_id'] = $vehicle->id;
        $data['tanque_cheio'] = $request->boolean('tanque_cheio');

        FuelEntry::create($data);

        // Atualiza km_atual do veículo se o km informado for maior
        $km = $data['km_abastecimento'] ?? null;
        if ($km && $km > $vehicle->km_atual) {
            $vehicle->update(['km_atual' => $km]);
        }

        return redirect()
            ->route('vehicles.show', $vehicle)
            ->with('success', 'Abastecimento registrado!')
            ->withFragment('fuel');
    }

    /**
     * Atualiza apenas o KM de um abastecimento existente.
     */
    public function updateKm(Request $request, Vehicle $vehicle, FuelEntry $fuelEntry): RedirectResponse
    {
        if ($vehicle->user_id !== Auth::id() || $fuelEntry->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'km_abastecimento' => ['nullable', 'integer', 'min:0'],
        ]);

        $km = $validated['km_abastecimento'] ?? null;

        $fuelEntry->update(['km_abastecimento' => $km]);

        // Atualiza km_atual do veículo se necessário
        if ($km && $km > $vehicle->km_atual) {
            $vehicle->update(['km_atual' => $km]);
        }

        return redirect()
            ->route('vehicles.show', $vehicle)
            ->with('success', 'KM atualizado!')
            ->withFragment('fuel');
    }

    public function destroy(Vehicle $vehicle, FuelEntry $fuelEntry): RedirectResponse
    {
        if ($vehicle->user_id !== Auth::id()
            || $fuelEntry->user_id !== Auth::id()
            || $fuelEntry->vehicle_id !== $vehicle->id) {
            abort(403);
        }

        $fuelEntry->delete();

        return redirect()
            ->route('vehicles.show', $vehicle)
            ->with('success', 'Abastecimento removido.')
            ->withFragment('fuel');
    }
}

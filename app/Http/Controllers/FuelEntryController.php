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

        $validated = $request->validate([
            'data'             => ['required', 'date'],
            'valor'            => ['required', 'numeric', 'min:0'],
            'litros'           => ['nullable', 'numeric', 'min:0'],
            'km_abastecimento' => ['nullable', 'integer', 'min:0'],
            'tipo_combustivel' => ['nullable', 'string', 'max:30'],
            'posto'            => ['nullable', 'string', 'max:100'],
            'tanque_cheio'     => ['nullable', 'boolean'],
            'descricao'        => ['nullable', 'string', 'max:255'],
        ]);

        $entry = FuelEntry::create([
            'user_id'          => Auth::id(),
            'vehicle_id'       => $vehicle->id,
            'data'             => $validated['data'],
            'valor'            => $validated['valor'],
            'litros'           => $validated['litros'] ?? null,
            'km_abastecimento' => $validated['km_abastecimento'] ?? null,
            'tipo_combustivel' => $validated['tipo_combustivel'] ?? null,
            'posto'            => $validated['posto'] ?? null,
            'tanque_cheio'     => isset($validated['tanque_cheio']) ? (bool) $validated['tanque_cheio'] : false,
            'descricao'        => $validated['descricao'] ?? null,
        ]);

        // Atualiza km_atual do veículo se o abastecimento registrou km maior
        if ($validated['km_abastecimento'] && $validated['km_abastecimento'] > $vehicle->km_atual) {
            $vehicle->update(['km_atual' => $validated['km_abastecimento']]);
        }

        return redirect()
            ->route('vehicles.show', $vehicle)
            ->with('success', 'Abastecimento registrado com sucesso!');
    }

    public function destroy(Vehicle $vehicle, FuelEntry $fuelEntry): RedirectResponse
    {
        if ($vehicle->user_id !== Auth::id() || $fuelEntry->user_id !== Auth::id() || $fuelEntry->vehicle_id !== $vehicle->id) {
            abort(403);
        }

        $fuelEntry->delete();

        return redirect()
            ->route('vehicles.show', $vehicle)
            ->with('success', 'Abastecimento removido com sucesso!');
    }
}

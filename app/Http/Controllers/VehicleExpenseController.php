<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleExpense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VehicleExpenseController extends Controller
{
    public function store(Request $request, Vehicle $vehicle): RedirectResponse
    {
        if ($vehicle->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'data' => ['required', 'date'],
            'tipo' => ['required', 'string', 'max:30'],
            'valor' => ['required', 'numeric', 'min:0'],
            'descricao' => ['nullable', 'string', 'max:255'],
        ]);

        VehicleExpense::create([
            'user_id' => Auth::id(),
            'vehicle_id' => $vehicle->id,
            'data' => $validated['data'],
            'tipo' => $validated['tipo'],
            'valor' => $validated['valor'],
            'descricao' => $validated['descricao'] ?? null,
        ]);

        return redirect()
            ->route('vehicles.show', $vehicle)
            ->with('success', 'Despesa cadastrada com sucesso!');
    }

    public function destroy(Vehicle $vehicle, VehicleExpense $expense): RedirectResponse
    {
        if ($vehicle->user_id !== Auth::id() || $expense->user_id !== Auth::id() || $expense->vehicle_id !== $vehicle->id) {
            abort(403);
        }

        $expense->delete();

        return redirect()
            ->route('vehicles.show', $vehicle)
            ->with('success', 'Despesa removida com sucesso!');
    }
}

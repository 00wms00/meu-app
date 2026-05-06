<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\VehicleExpense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(Request $request): View
    {
        $vehicles = Vehicle::where('user_id', Auth::id())
            ->orderBy('apelido')
            ->get();

        return view('vehicles.index', compact('vehicles'));
    }

    public function show(Vehicle $vehicle): View
    {
        if ($vehicle->user_id !== Auth::id()) {
            abort(403);
        }

        $expenses = VehicleExpense::where('vehicle_id', $vehicle->id)
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->get();

        return view('vehicles.show', compact('vehicle', 'expenses'));
    }

    public function create(): View
    {
        return view('vehicles.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'apelido' => ['required', 'string', 'max:100'],
            'marca' => ['nullable', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'ano' => ['nullable', 'integer', 'min:1950', 'max:' . now()->year],
            'placa' => ['nullable', 'string', 'max:10'],
            'tipo_combustivel' => ['nullable', 'string', 'max:20'],
            'km_atual' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['user_id'] = Auth::id();

        Vehicle::create($validated);

        return redirect()
            ->route('vehicles.index')
            ->with('success', 'Veículo cadastrado com sucesso!');
    }

    public function edit(Vehicle $vehicle): View
    {
        $this->authorize('update', $vehicle);

        return view('vehicles.edit', compact('vehicle'));
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('update', $vehicle);

        $validated = $request->validate([
            'apelido' => ['required', 'string', 'max:100'],
            'marca' => ['nullable', 'string', 'max:100'],
            'modelo' => ['nullable', 'string', 'max:100'],
            'ano' => ['nullable', 'integer', 'min:1950', 'max:' . now()->year],
            'placa' => ['nullable', 'string', 'max:10'],
            'tipo_combustivel' => ['nullable', 'string', 'max:20'],
            'km_atual' => ['nullable', 'integer', 'min:0'],
        ]);

        $vehicle->update($validated);

        return redirect()
            ->route('vehicles.index')
            ->with('success', 'Veículo atualizado com sucesso!');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $this->authorize('delete', $vehicle);

        $vehicle->delete();

        return redirect()
            ->route('vehicles.index')
            ->with('success', 'Veículo removido com sucesso!');
    }
}

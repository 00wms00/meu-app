<?php

namespace App\Http\Controllers;

use App\Models\FuelEntry;
use App\Models\VehicleExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Gerencia PATCH de status (pendente | pago | pgoCC) para
 * itens individuais do bloco 🚗 Veículos na tela de Despesas.
 *
 * PATCH /financas/veiculo-itens/expense/{vehicleExpense}/status
 * PATCH /financas/veiculo-itens/fuel/{fuelEntry}/status
 */
class VehicleItemStatusController extends Controller
{
    private function validarStatus(Request $request): string
    {
        $data = $request->validate([
            'status' => 'required|in:pendente,pago,pgoCC',
        ]);
        return $data['status'];
    }

    public function updateExpenseStatus(Request $request, VehicleExpense $vehicleExpense)
    {
        // Garante que o item pertence ao usuário autenticado
        abort_unless($vehicleExpense->vehicle->user_id === Auth::id(), 403);

        $vehicleExpense->update(['status' => $this->validarStatus($request)]);

        return response()->json(['ok' => true, 'status' => $vehicleExpense->status]);
    }

    public function updateFuelStatus(Request $request, FuelEntry $fuelEntry)
    {
        abort_unless($fuelEntry->user_id === Auth::id(), 403);

        $fuelEntry->update(['status' => $this->validarStatus($request)]);

        return response()->json(['ok' => true, 'status' => $fuelEntry->status]);
    }
}

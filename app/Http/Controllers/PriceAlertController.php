<?php

namespace App\Http\Controllers;

use App\Models\PriceAlert;
use App\Models\Product;
use App\Services\PriceAlertService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PriceAlertController extends Controller
{
    public function __construct(
        protected PriceAlertService $alertService,
    ) {}

    public function index(): View
    {
        $alertas = PriceAlert::where('user_id', Auth::id())
            ->with('product')
            ->orderBy('variacao_percentual', 'desc')
            ->get();

        $disparados = $this->alertService->verificarTodos(Auth::id());

        return view('products.alertas', compact('alertas', 'disparados'));
    }

    public function criar(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('view', $product);

        $this->alertService->criarOuAtualizar(Auth::id(), $product->id, $request->limite_alerta);

        return redirect()
            ->route('products.show', $product)
            ->with('alerta_criado', true)
            ->with('success', 'Alerta de preço salvo com sucesso!');
    }

    public function remover(PriceAlert $alerta): RedirectResponse
    {
        $this->authorize('delete', $alerta);
        $alerta->delete();

        return back()->with('success', 'Alerta removido!');
    }

    public function toggle(PriceAlert $alerta): RedirectResponse
    {
        $this->authorize('update', $alerta);
        $alerta->update(['ativo' => ! $alerta->ativo]);

        return back()->with('success', 'Alerta alternado!');
    }
}

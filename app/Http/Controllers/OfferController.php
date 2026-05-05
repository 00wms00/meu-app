<?php

namespace App\Http\Controllers;

use App\Models\InvoiceItem;
use App\Models\Offer;
use App\Services\GeminiService;
use App\Traits\ParsesFloatInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class OfferController extends Controller
{
    use ParsesFloatInput;

    public function index(Request $request): View
    {
        $userId = Auth::id();

        $ofertas = Offer::where('user_id', $userId)
            ->where('ativa', true)
            ->when(
                $request->estabelecimento,
                fn ($q) => $q->where('estabelecimento', 'ilike', "%{$request->estabelecimento}%")
            )
            ->orderBy('validade_fim', 'asc')
            ->orderBy('estabelecimento')
            ->get()
            ->groupBy('estabelecimento');

        $estabelecimentos = Offer::where('user_id', $userId)
            ->distinct('estabelecimento')
            ->pluck('estabelecimento');

        return view('offers.index', compact('ofertas', 'estabelecimentos'));
    }

    public function create(): View
    {
        return view('offers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        // Converte vírgulas antes de validar
        $input = $request->all();
        foreach ($input['ofertas'] ?? [] as $key => $oferta) {
            $input['ofertas'][$key]['preco_oferta'] = $this->parseFloat($oferta['preco_oferta'] ?? '0');
            $input['ofertas'][$key]['quantidade']   = $this->parseFloat($oferta['quantidade']   ?? '1');
        }

        $validated = validator($input, [
            'estabelecimento'              => 'required|string|max:255',
            'ofertas'                      => 'required|array|min:1',
            'ofertas.*.nome_produto'       => 'required|string|max:255',
            'ofertas.*.preco_oferta'       => 'required|numeric|min:0',
            'ofertas.*.unidade'            => 'required|string|max:5',
            'ofertas.*.quantidade'         => 'nullable|numeric|min:0',
            'validade_inicio'              => 'nullable|date',
            'validade_fim'                 => 'nullable|date',
            'fonte'                        => 'nullable|string|max:255',
        ])->validate();

        $count = 0;
        foreach ($validated['ofertas'] as $oferta) {
            Offer::create([
                'user_id'          => Auth::id(),
                'estabelecimento'  => $validated['estabelecimento'],
                'nome_produto'     => $oferta['nome_produto'],
                'preco_oferta'     => $oferta['preco_oferta'],
                'unidade'          => $oferta['unidade'] ?? 'UN',
                'quantidade'       => $oferta['quantidade'] ?? 1,
                'validade_inicio'  => $validated['validade_inicio'] ?? null,
                'validade_fim'     => $validated['validade_fim']    ?? null,
                'fonte'            => $validated['fonte']           ?? null,
            ]);
            $count++;
        }

        return redirect()->route('offers.index')
            ->with('success', "{$count} oferta(s) cadastrada(s)!");
    }

    /**
     * Upload de encarte e extração com IA Gemini.
     */
    public function uploadEncarte(Request $request, GeminiService $gemini): RedirectResponse
    {
        $request->validate([
            'encarte' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        if (! $gemini->isConfigured()) {
            return back()->withErrors(['encarte' => 'API Gemini não configurada. Adicione GEMINI_API_KEY no .env']);
        }

        $path     = $request->file('encarte')->store('temp/encartes', 'public');
        $fullPath = storage_path('app/public/' . $path);

        try {
            $resultado = $gemini->analisarEncarte($fullPath);
            Storage::disk('public')->delete($path);

            session(['encarte_data' => [
                'estabelecimento' => $resultado['estabelecimento'] ?? 'Encarte sem nome',
                'produtos'        => $resultado['produtos']        ?? [],
                'validade_texto'  => $resultado['validade_texto']  ?? '',
                'total_produtos'  => count($resultado['produtos']  ?? []),
            ]]);

            return redirect()->route('offers.preview');
        } catch (\Exception $e) {
            Storage::disk('public')->delete($path);
            return back()->withErrors(['encarte' => 'Erro ao analisar imagem: ' . $e->getMessage()]);
        }
    }

    /**
     * Preview dos dados extraídos pelo Gemini.
     */
    public function preview(): View|RedirectResponse
    {
        $data = session('encarte_data');

        if (! $data) {
            return redirect()->route('offers.index')
                ->withErrors(['msg' => 'Nenhum dado de encarte para revisar.']);
        }

        return view('offers.preview', compact('data'));
    }

    /**
     * Salvar ofertas do encarte analisado.
     */
    public function savePreview(Request $request): RedirectResponse
    {
        $data = session('encarte_data');

        if (! $data) {
            return redirect()->route('offers.index')
                ->withErrors(['msg' => 'Sessão expirada.']);
        }

        $count = 0;
        foreach ($data['produtos'] as $produto) {
            if (empty($produto['nome']) || empty($produto['preco'])) continue;

            Offer::create([
                'user_id'         => Auth::id(),
                'estabelecimento' => $data['estabelecimento'],
                'nome_produto'    => $produto['nome'],
                'preco_oferta'    => (float) $produto['preco'],
                'unidade'         => $produto['unidade']    ?? 'UN',
                'quantidade'      => $produto['quantidade'] ?? 1,
                'fonte'           => 'Encarte IA',
                'observacao'      => $produto['observacao'] ?? null,
            ]);
            $count++;
        }

        session()->forget('encarte_data');

        return redirect()->route('offers.index')
            ->with('success', "✅ {$count} ofertas salvas de '{$data['estabelecimento']}'!");
    }

    public function comparar(Request $request): View
    {
        $userId = Auth::id();

        $ofertas = Offer::where('user_id', $userId)
            ->where('ativa', true)
            ->with('product')
            ->orderBy('nome_produto')
            ->get();

        $comparacoes = $ofertas->map(function (Offer $oferta) use ($userId) {
            $historico = null;

            if ($oferta->product_id) {
                $historico = InvoiceItem::where('product_id', $oferta->product_id)
                    ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
                    ->where('invoices.user_id', $userId)
                    ->orderBy('invoices.data_emissao', 'desc')
                    ->take(5)
                    ->get(['invoices.data_emissao', 'invoice_items.valor_unitario']);
            }

            $precoMedio = $historico?->avg('valor_unitario');
            $economia   = $precoMedio ? round($precoMedio - $oferta->preco_oferta, 2) : null;

            return [
                'oferta'                  => $oferta,
                'preco_medio_historico'   => $precoMedio ? round($precoMedio, 2) : null,
                'economia'                => $economia,
                'vale_a_pena'             => $economia !== null && $economia > 0,
            ];
        })->all();

        return view('offers.comparar', compact('comparacoes'));
    }

    public function destroy(Offer $offer): RedirectResponse
    {
        $this->authorize('delete', $offer);
        $offer->delete();

        return back()->with('success', 'Oferta removida!');
    }

    public function toggle(Offer $offer): RedirectResponse
    {
        $this->authorize('update', $offer);
        $offer->update(['ativa' => ! $offer->ativa]);

        return back();
    }
}

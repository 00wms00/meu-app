<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Product;
use App\Models\InvoiceItem;
use App\Services\GeminiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OfferController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        $ofertas = Offer::where('user_id', $userId)->where('ativa', true)
            ->when($request->estabelecimento, fn($q) => $q->where('estabelecimento', 'ilike', "%{$request->estabelecimento}%"))
            ->orderBy('validade_fim', 'asc')->orderBy('estabelecimento')->get()->groupBy('estabelecimento');
        $estabelecimentos = Offer::where('user_id', $userId)->distinct('estabelecimento')->pluck('estabelecimento');
        return view('offers.index', compact('ofertas', 'estabelecimentos'));
    }
    public function create()
    {
        return view("offers.create");
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'estabelecimento' => 'required|string|max:255',
            'ofertas' => 'required|array|min:1',
            'ofertas.*.nome_produto' => 'required|string|max:255',
            'ofertas.*.preco_oferta' => 'required|numeric|min:0',
            'ofertas.*.unidade' => 'required|string|max:5',
            'validade_inicio' => 'nullable|date',
            'validade_fim' => 'nullable|date',
            'fonte' => 'nullable|string|max:255',
        ]);

        $count = 0;
        foreach ($validated['ofertas'] as $oferta) {
            Offer::create([
                'user_id' => Auth::id(),
                'estabelecimento' => $validated['estabelecimento'],
                'nome_produto' => $oferta['nome_produto'],
                'preco_oferta' => $oferta['preco_oferta'],
                'unidade' => $oferta['unidade'] ?? 'UN',
                'quantidade' => $oferta['quantidade'] ?? 1,
                'validade_inicio' => $validated['validade_inicio'] ?? null,
                'validade_fim' => $validated['validade_fim'] ?? null,
                'fonte' => $validated['fonte'] ?? null,
            ]);
            $count++;
        }

        return redirect()->route('offers.index')->with('success', "{$count} oferta(s) cadastrada(s)!");
    }

    /**
     * Upload de encarte e extração com IA Gemini
     */
    public function uploadEncarte(Request $request, GeminiService $gemini)
    {
        $request->validate([
            'encarte' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        if (!$gemini->isConfigured()) {
            return back()->withErrors(['encarte' => 'API Gemini não configurada. Adicione GEMINI_API_KEY no arquivo .env']);
        }

        // Salvar imagem temporariamente
        $path = $request->file('encarte')->store('temp/encartes', 'public');
        $fullPath = storage_path('app/public/' . $path);

        try {
            // Enviar para o Gemini analisar
            $resultado = $gemini->analisarEncarte($fullPath);

            // Limpar imagem temporária
            Storage::disk('public')->delete($path);

            // Salvar como sessão para preview
            $estabelecimento = $resultado['estabelecimento'] ?? 'Encarte sem nome';
            $produtos = $resultado['produtos'] ?? [];
            $validadeTexto = $resultado['validade_texto'] ?? '';

            session(['encarte_data' => [
                'estabelecimento' => $estabelecimento,
                'produtos' => $produtos,
                'validade_texto' => $validadeTexto,
                'total_produtos' => count($produtos),
            ]]);

            return redirect()->route('offers.preview');

        } catch (\Exception $e) {
            Storage::disk('public')->delete($path);
            return back()->withErrors(['encarte' => 'Erro ao analisar imagem: ' . $e->getMessage()]);
        }
    }

    /**
     * Preview dos dados extraídos pelo Gemini
     */
    public function preview()
    {
        $data = session('encarte_data');
        if (!$data) {
            return redirect()->route('offers.index')->withErrors(['msg' => 'Nenhum dado de encarte para revisar.']);
        }
        return view('offers.preview', compact('data'));
    }

    /**
     * Salvar ofertas do encarte analisado
     */
    public function savePreview(Request $request)
    {
        $data = session('encarte_data');
        if (!$data) {
            return redirect()->route('offers.index')->withErrors(['msg' => 'Sessão expirada.']);
        }

        $count = 0;
        foreach ($data['produtos'] as $produto) {
            if (empty($produto['nome']) || empty($produto['preco'])) continue;

            Offer::create([
                'user_id' => Auth::id(),
                'estabelecimento' => $data['estabelecimento'],
                'nome_produto' => $produto['nome'],
                'preco_oferta' => (float) $produto['preco'],
                'unidade' => $produto['unidade'] ?? 'UN',
                'quantidade' => $produto['quantidade'] ?? 1,
                'fonte' => 'Encarte IA',
                'observacao' => $produto['observacao'] ?? null,
            ]);
            $count++;
        }

        session()->forget('encarte_data');

        return redirect()->route('offers.index')
            ->with('success', "✅ {$count} ofertas salvas de '{$data['estabelecimento']}'!");
    }

    public function comparar(Request $request)
{
    $userId = Auth::id();
    $ofertas = Offer::where('user_id', $userId)->where('ativa', true)
        ->with('product')
        ->orderBy('nome_produto')->get();

    $comparacoes = [];
    foreach ($ofertas as $oferta) {
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
        $economia = $precoMedio ? $precoMedio - $oferta->preco_oferta : null;
        
        $comparacoes[] = [
            'oferta' => $oferta,
            'preco_medio_historico' => $precoMedio ? round($precoMedio, 2) : null,
            'economia' => $economia ? round($economia, 2) : null,
            'vale_a_pena' => $economia && $economia > 0,
        ];
    }

    return view('offers.comparar', compact('comparacoes'));
}

    public function destroy(Offer $offer)
    { if ($offer->user_id !== Auth::id()) abort(403); $offer->delete(); return back()->with('success', 'Oferta removida!'); }

    public function toggle(Offer $offer)
    { if ($offer->user_id !== Auth::id()) abort(403); $offer->update(['ativa' => !$offer->ativa]); return back(); }

}

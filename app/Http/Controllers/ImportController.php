<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Services\InvoiceParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ImportController extends Controller
{
    public function create(): View
    {
        return view('import.create');
    }

    public function parse(Request $request, InvoiceParser $parser): RedirectResponse
    {
        $request->validate([
            'html'    => 'nullable|string|required_without:arquivo',
            'arquivo' => 'nullable|file|mimes:html,htm',
        ]);

        $html = $request->html ?? file_get_contents($request->file('arquivo')->path());

        try {
            $data = $parser->parse($html);
        } catch (\Exception $e) {
            return back()->withErrors(['html' => 'Erro ao processar o HTML: ' . $e->getMessage()]);
        }

        session(['parsed_invoice' => $data]);

        return redirect()->route('import.preview');
    }

    public function preview(): View|RedirectResponse
    {
        $data = $this->sessionData();

        if (! $data) {
            return redirect()->route('import.create')
                ->withErrors(['html' => 'Nenhum dado para exibir.']);
        }

        return view('import.preview', ['data' => $data]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->sessionData();

        if (! $data) {
            return redirect()->route('import.create')
                ->withErrors(['html' => 'Sessão expirada.']);
        }

        $exists = Invoice::where('user_id', Auth::id())
            ->where('chave', $data['chave'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['chave' => 'Esta nota já foi importada anteriormente.']);
        }

        DB::transaction(function () use ($data) {
            $invoice = Invoice::create([
                'user_id'                  => Auth::id(),
                'chave'                    => $data['chave'],
                'numero'                   => $data['numero'],
                'serie'                    => $data['serie'],
                'data_emissao'             => $data['data_emissao'],
                'cnpj'                     => $data['cnpj'],
                'nome_estabelecimento'     => $data['nome_estabelecimento'],
                'endereco_estabelecimento' => $data['endereco_estabelecimento'],
                'total_itens'              => $data['total_itens'],
                'valor_total'              => $data['valor_total'],
                'descontos'                => $data['descontos'],
                'valor_pago'               => $data['valor_pago'],
                'forma_pagamento'          => $data['forma_pagamento'],
                'consumidor_cpf'           => $data['consumidor']['cpf']  ?? null,
                'consumidor_nome'          => $data['consumidor']['nome'] ?? null,
            ]);

            foreach ($data['itens'] as $item) {
                $product = Product::firstOrCreate(
                    ['user_id' => Auth::id(), 'nome' => trim($item['nome'])],
                    ['unidade_padrao' => $item['unidade']]
                );


// Gerar sugestão de normalização automaticamente
if (!$product->nome_normalizado || $product->normalizacao_status === 'pendente') {
    $normalizationService = app(ProductNormalizationService::class);
    $normalizationService->markForReview($product);
}

                InvoiceItem::create([
                    'invoice_id'     => $invoice->id,
                    'product_id'     => $product->id,
                    'quantidade'     => $item['quantidade'],
                    'unidade'        => $item['unidade'],
                    'valor_unitario' => $item['valor_unitario'],
                    'valor_total'    => $item['valor_total'],
                    'codigo_produto' => $item['codigo'],
                ]);
            }
        });

        // Limpa o cache APÓS o commit da transaction
        Cache::forget('planejamento-' . Auth::id());
        session()->forget('parsed_invoice');

        return redirect()->route('dashboard')
            ->with('success', 'Nota fiscal importada com sucesso!');
    }

    // ==================== PRIVATE ====================

    /**
     * Retorna os dados da sessão de import, ou null se expirada.
     */
    private function sessionData(): ?array
    {
        return session('parsed_invoice');
    }
}

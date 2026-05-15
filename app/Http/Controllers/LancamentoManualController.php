<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Traits\ParsesFloatInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class LancamentoManualController extends Controller
{
    use ParsesFloatInput;

    public function create(): View
    {
        $proximaChave = $this->gerarChaveManual();
        return view('lancamento.create', compact('proximaChave'));
    }

    public function store(Request $request): RedirectResponse
    {
        $input = $request->all();

        if (isset($input['descontos']) && $input['descontos'] !== '') {
            $input['descontos'] = $this->parseFloat($input['descontos']);
        }

        if (isset($input['itens'])) {
            foreach ($input['itens'] as $key => $item) {
                $input['itens'][$key]['quantidade']     = $this->parseFloat($item['quantidade']     ?? '0');
                $input['itens'][$key]['valor_unitario'] = $this->parseFloat($item['valor_unitario'] ?? '0');
                $input['itens'][$key]['valor_total']    = $this->parseFloat($item['valor_total']    ?? '0');
            }
        }

        $validator = Validator::make($input, [
            'data_emissao'             => 'required|date',
            'nome_estabelecimento'     => 'required|string|max:255',
            'cnpj'                     => 'nullable|string|max:18',
            'chave'                    => 'required|string|max:50|unique:invoices,chave',
            'forma_pagamento'          => 'nullable|string|max:255',
            'descontos'                => 'nullable|numeric|min:0',
            'itens'                    => 'required|array|min:1',
            'itens.*.nome'             => 'required|string|max:255',
            'itens.*.quantidade'       => 'required|numeric|min:0.001',
            'itens.*.unidade'          => 'required|string|max:5',
            'itens.*.valor_unitario'   => 'required|numeric|min:0',
            'itens.*.valor_total'      => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        DB::transaction(function () use ($validated) {
            $valorTotal = collect($validated['itens'])->sum('valor_total');
            $descontos  = $validated['descontos'] ?? 0;

            $invoice = Invoice::create([
                'user_id'                  => Auth::id(),
                'chave'                    => $validated['chave'],
                'numero'                   => 'M' . date('ymdHis'),
                'serie'                    => '999',
                'data_emissao'             => $validated['data_emissao'],
                'cnpj'                     => $validated['cnpj'] ?? '',
                'nome_estabelecimento'     => $validated['nome_estabelecimento'],
                'endereco_estabelecimento' => null,
                'total_itens'              => count($validated['itens']),
                'valor_total'              => $valorTotal,
                'descontos'                => $descontos,
                'valor_pago'               => max(0, $valorTotal - $descontos),
                'forma_pagamento'          => $validated['forma_pagamento'] ?? null,
                'consumidor_cpf'           => null,
                'consumidor_nome'          => null,
            ]);

            foreach ($validated['itens'] as $item) {
                $product = Product::firstOrCreate(
                    ['user_id' => Auth::id(), 'nome' => trim($item['nome'])],
                    ['unidade_padrao' => $item['unidade']]
                );

                InvoiceItem::create([
                    'invoice_id'      => $invoice->id,
                    'product_id'      => $product->id,
                    'quantidade'      => $item['quantidade'],
                    'unidade'         => $item['unidade'],
                    'valor_unitario'  => $item['valor_unitario'],
                    'valor_total'     => $item['valor_total'],
                    'codigo_produto'  => null,
                ]);
            }
        });

        return redirect()->route('invoices.index')
            ->with('success', 'Compra manual registrada com sucesso!');
    }

    /**
     * Gera chave manual: prefixo '9999' + sequencial com 40 dígitos = 44 chars.
     */
    private function gerarChaveManual(): string
    {
        $prefixo = '9999';

        $ultima = Invoice::where('user_id', Auth::id())
            ->where('chave', 'like', $prefixo . '%')
            ->orderBy('chave', 'desc')
            ->first();

        $numero = $ultima
            ? (int) ltrim(substr($ultima->chave, strlen($prefixo)), '0') + 1
            : 1;

        return $prefixo . str_pad($numero, 40, '0', STR_PAD_LEFT);
    }
}

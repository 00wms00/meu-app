<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LancamentoManualController extends Controller
{
    public function create()
    {
        $proximaChave = $this->gerarChaveManual();
        return view('lancamento.create', compact('proximaChave'));
    }

    public function store(Request $request)
    {
        $input = $request->all();
        
        // Converter descontos
        if (isset($input['descontos']) && $input['descontos'] !== '') {
            $input['descontos'] = $this->parseFloat($input['descontos']);
        }
        
        // Converter itens
        if (isset($input['itens'])) {
            foreach ($input['itens'] as $key => $item) {
                $input['itens'][$key]['quantidade'] = $this->parseFloat($item['quantidade'] ?? '0');
                $input['itens'][$key]['valor_unitario'] = $this->parseFloat($item['valor_unitario'] ?? '0');
                $input['itens'][$key]['valor_total'] = $this->parseFloat($item['valor_total'] ?? '0');
            }
        }
        
        $validator = Validator::make($input, [
            'data_emissao' => 'required|date',
            'nome_estabelecimento' => 'required|string|max:255',
            'cnpj' => 'nullable|string|max:18',
            'chave' => 'required|string|max:50|unique:invoices,chave',
            'forma_pagamento' => 'nullable|string|max:255',
            'descontos' => 'nullable|numeric|min:0',
            'itens' => 'required|array|min:1',
            'itens.*.nome' => 'required|string|max:255',
            'itens.*.quantidade' => 'required|numeric|min:0.001',
            'itens.*.unidade' => 'required|string|max:5',
            'itens.*.valor_unitario' => 'required|numeric|min:0',
            'itens.*.valor_total' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        DB::transaction(function () use ($validated) {
            $totalItens = count($validated['itens']);
            $valorTotal = collect($validated['itens'])->sum('valor_total');
            $descontos = $validated['descontos'] ?? 0;
            $valorPago = max(0, $valorTotal - $descontos);

            // Número da nota manual (tamanho controlado)
            $numero = 'M' . date('ymdHi'); // Ex: M2504301828 (11 caracteres)

            $invoice = Invoice::create([
                'user_id' => Auth::id(),
                'chave' => $validated['chave'],
                'numero' => $numero,
                'serie' => '999',
                'data_emissao' => $validated['data_emissao'],
                'cnpj' => $validated['cnpj'] ?? '',
                'nome_estabelecimento' => $validated['nome_estabelecimento'],
                'endereco_estabelecimento' => null,
                'total_itens' => $totalItens,
                'valor_total' => $valorTotal,
                'descontos' => $descontos,
                'valor_pago' => $valorPago,
                'forma_pagamento' => $validated['forma_pagamento'] ?? 'Dinheiro',
                'consumidor_cpf' => null,
                'consumidor_nome' => null,
            ]);

            foreach ($validated['itens'] as $item) {
                $product = Product::firstOrCreate(
                    ['user_id' => Auth::id(), 'nome' => trim($item['nome'])],
                    ['unidade_padrao' => $item['unidade']]
                );

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'quantidade' => $item['quantidade'],
                    'unidade' => $item['unidade'],
                    'valor_unitario' => $item['valor_unitario'],
                    'valor_total' => $item['valor_total'],
                    'codigo_produto' => null,
                ]);
            }
        });

        return redirect()->route('invoices.index')
            ->with('success', 'Compra manual registrada com sucesso!');
    }

    /**
     * Gera chave: 9999 + 40 zeros + sequencial (total = 44 dígitos)
     * Formato: 9999 + 0000... + 0001 = 44 caracteres
     */
    private function gerarChaveManual(): string
    {
        $prefixo = '9999'; // 4 dígitos
        
        $ultima = Invoice::where('user_id', Auth::id())
            ->where('chave', 'like', $prefixo . '%')
            ->orderBy('chave', 'desc')
            ->first();

        if ($ultima) {
            // Extrair o número dos últimos dígitos (após o prefixo)
            $resto = substr($ultima->chave, strlen($prefixo));
            $numero = (int) ltrim($resto, '0') + 1;
        } else {
            $numero = 1;
        }

        // Total deve ser 44 dígitos: 4 (prefixo) + 40 (sequencial)
        return $prefixo . str_pad($numero, 40, '0', STR_PAD_LEFT);
    }

    private function parseFloat($value): float
    {
        if (is_numeric($value)) return (float) $value;
        if (empty($value)) return 0;
        
        $value = preg_replace('/[^\d,.-]/', '', (string) $value);
        
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
        }
        
        $value = str_replace(',', '.', $value);
        
        return (float) $value;
    }
}

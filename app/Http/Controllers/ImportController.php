<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Services\InvoiceParser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ImportController extends Controller
{
    public function create()
    {
        return view('import.create');
    }

    public function parse(Request $request, InvoiceParser $parser)
    {
        $request->validate([
            'html' => 'nullable|string|required_without:arquivo',
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

    public function preview()
    {
        $data = session('parsed_invoice');
        if (!$data) {
            return redirect()->route('import.create')->withErrors(['html' => 'Nenhum dado para exibir.']);
        }
        return view('import.preview', ['data' => $data]);
    }

    public function store(Request $request)
    {
        $data = session('parsed_invoice');
        if (!$data) {
            return redirect()->route('import.create')->withErrors(['html' => 'Sessão expirada.']);
        }

        // Verificar duplicata
        $exists = Invoice::where('user_id', Auth::id())
            ->where('chave', $data['chave'])
            ->exists();
            
        if ($exists) {
            return back()->withErrors(['chave' => 'Esta nota já foi importada anteriormente.']);
        }

        DB::transaction(function () use ($data) {
            // Criar a nota fiscal
            $invoice = Invoice::create([
                'user_id' => Auth::id(),
                'chave' => $data['chave'],
                'numero' => $data['numero'],
                'serie' => $data['serie'],
                'data_emissao' => $data['data_emissao'],
                'cnpj' => $data['cnpj'],
                'nome_estabelecimento' => $data['nome_estabelecimento'],
                'endereco_estabelecimento' => $data['endereco_estabelecimento'],
                'total_itens' => $data['total_itens'],
                'valor_total' => $data['valor_total'],
                'descontos' => $data['descontos'],
                'valor_pago' => $data['valor_pago'],
                'forma_pagamento' => $data['forma_pagamento'],
                'consumidor_cpf' => $data['consumidor']['cpf'] ?? null,
                'consumidor_nome' => $data['consumidor']['nome'] ?? null,
            ]);

            Cache::forget("planejamento-" . Auth::id());


            foreach ($data['itens'] as $item) {
                // SIMPLES: apenas criar o produto com o nome original, sem agrupar
                $product = Product::firstOrCreate(
                    [
                        'user_id' => Auth::id(),
                        'nome' => trim($item['nome']),
                    ],
                    ['unidade_padrao' => $item['unidade']]
                );

                // Criar o item da nota
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'quantidade' => $item['quantidade'],
                    'unidade' => $item['unidade'],
                    'valor_unitario' => $item['valor_unitario'],
                    'valor_total' => $item['valor_total'],
                    'codigo_produto' => $item['codigo'],
                ]);
            }
        });

        session()->forget('parsed_invoice');
        return redirect()->route('dashboard')->with('success', 'Nota fiscal importada com sucesso!');
    }
}

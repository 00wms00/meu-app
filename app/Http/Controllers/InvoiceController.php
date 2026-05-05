<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::where('user_id', Auth::id());

        if ($request->filled('de')) {
            $query->where('data_emissao', '>=', $request->de);
        }
        if ($request->filled('ate')) {
            $query->where('data_emissao', '<=', $request->ate . ' 23:59:59');
        }
        if ($request->filled('estabelecimento')) {
            $query->where('nome_estabelecimento', 'ilike', '%'.$request->estabelecimento.'%');
        }

        $invoices = $query->orderBy('data_emissao', 'desc')
                          ->orderBy('id', 'desc')
                          ->paginate(20);
                          
        return view('invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);
        $invoice->load('items.product');
        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        $this->authorize('update', $invoice);
        $invoice->load('items.product');
        return view('invoices.edit', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);


        DB::transaction(function () use ($request, $invoice) {
            $invoice->update([
                'nome_estabelecimento' => $request->nome_estabelecimento,
                'cnpj' => $request->cnpj,
                'numero' => $request->numero,
                'serie' => $request->serie,
                'data_emissao' => $request->data_emissao,
                'forma_pagamento' => $request->forma_pagamento,
                'descontos' => $this->parseFloat($request->descontos ?? '0'),
            ]);

            if ($request->has('itens_removidos')) {
                InvoiceItem::whereIn('id', $request->itens_removidos)
                    ->where('invoice_id', $invoice->id)
                    ->delete();
            }

            if ($request->has('itens')) {
                foreach ($request->itens as $itemId => $itemData) {
                    $nome = trim($itemData['nome'] ?? '');
                    if (empty($nome)) continue;
                    
                    $quantidade = $this->parseFloat($itemData['quantidade'] ?? '0');
                    $valorUnitario = $this->parseFloat($itemData['valor_unitario'] ?? '0');
                    $valorTotal = $this->parseFloat($itemData['valor_total'] ?? '0');
                    
                    $product = Product::firstOrCreate(
                        ['user_id' => Auth::id(), 'nome' => $nome],
                        ['unidade_padrao' => $itemData['unidade'] ?? 'UN']
                    );

                    if (str_starts_with($itemId, 'novo_')) {
                        InvoiceItem::create([
                            'invoice_id' => $invoice->id,
                            'product_id' => $product->id,
                            'quantidade' => $quantidade,
                            'unidade' => $itemData['unidade'] ?? 'UN',
                            'valor_unitario' => $valorUnitario,
                            'valor_total' => $valorTotal,
                        ]);
                    } else {
                        InvoiceItem::where('id', $itemId)
                            ->where('invoice_id', $invoice->id)
                            ->update([
                                'product_id' => $product->id,
                                'quantidade' => $quantidade,
                                'unidade' => $itemData['unidade'] ?? 'UN',
                                'valor_unitario' => $valorUnitario,
                                'valor_total' => $valorTotal,
                            ]);
                    }
                }
            }

            $items = $invoice->items()->get();
            $invoice->update([
                'total_itens' => $items->count(),
                'valor_total' => $items->sum('valor_total'),
                'valor_pago' => $items->sum('valor_total') - $invoice->descontos,
            ]);
        });

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Nota fiscal atualizada com sucesso!');
    }
/*
    public function destroy(Invoice $invoice)
    {
        if ($invoice->user_id !== Auth::id()) {
            abort(403);
        }

        // Primeiro excluir todos os itens (isso vai limpar produtos órfãos)
        InvoiceItem::where('invoice_id', $invoice->id)->delete();
        
        // Depois excluir a nota
        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Nota fiscal excluída com sucesso!');
    }
*/
     public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);
        $invoice->delete();   // dispara o booted() que cuida dos itens

        return redirect()->route('invoices.index')
            ->with('success', 'Nota fiscal excluída com sucesso!');
    }

    public function editItem(Invoice $invoice, InvoiceItem $item)
    {
        $this->authorize('update', $invoice);

        return view('invoices.items.edit', compact('invoice', 'item'));
    }

    public function updateItem(Request $request, Invoice $invoice, InvoiceItem $item)
    {
        $this->authorize('update', $invoice);

        $validated = $request->validate([
            'nome_produto' => 'required|string|max:255',
            'quantidade' => 'required|numeric|min:0',
            'unidade' => 'required|string|max:5',
            'valor_unitario' => 'required|numeric|min:0',
            'valor_total' => 'required|numeric|min:0',
        ]);

        $product = Product::firstOrCreate(
            ['user_id' => Auth::id(), 'nome' => trim($validated['nome_produto'])]
        );

        $item->update([
            'product_id' => $product->id,
            'quantidade' => $validated['quantidade'],
            'unidade' => $validated['unidade'],
            'valor_unitario' => $validated['valor_unitario'],
            'valor_total' => $validated['valor_total'],
        ]);

        $this->recalcularTotais($invoice);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Item atualizado com sucesso!');
    }
/*
    public function destroyItem(Invoice $invoice, InvoiceItem $item)
    {
        if ($invoice->user_id !== Auth::id()) {
            abort(403);
        }

        $item->delete();
        $this->recalcularTotais($invoice);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Item removido com sucesso!');
    }
*/
     public function destroyItem(Invoice $invoice, InvoiceItem $item)
    {
        $this->authorize('delete', $invoice);

        // delete() no model aciona o booted() do InvoiceItem corretamente
        $item->delete();

        $this->recalcularTotais($invoice->fresh());  // fresh() garante totais atualizados

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Item removido com sucesso!');
    }

    private function recalcularTotais(Invoice $invoice)
    {
        $items = $invoice->items;
        $invoice->update([
            'total_itens' => $items->count(),
            'valor_total' => $items->sum('valor_total'),
            'valor_pago' => $items->sum('valor_total') - ($invoice->descontos ?? 0),
        ]);
    }

    private function parseFloat($value): float
    {
        if (is_numeric($value)) return (float) $value;
        $value = preg_replace('/[^\d,.-]/', '', (string) $value);
        if (str_contains($value, ',') && str_contains($value, '.')) {
            $value = str_replace('.', '', $value);
        }
        $value = str_replace(',', '.', $value);
        return (float) $value;
    }
}

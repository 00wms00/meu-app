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
use Illuminate\View\View;

class InvoiceController extends Controller
{
    use ParsesFloatInput;

    public function index(Request $request): View
    {
        $query = Invoice::where('user_id', Auth::id());

        if ($request->filled('de')) {
            $query->where('data_emissao', '>=', $request->de);
        }
        if ($request->filled('ate')) {
            $query->where('data_emissao', '<=', $request->ate . ' 23:59:59');
        }
        if ($request->filled('estabelecimento')) {
            $query->where('nome_estabelecimento', 'ilike', '%' . $request->estabelecimento . '%');
        }

        $invoices = $query
            ->orderBy('data_emissao', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);
        $invoice->load('items.product');
        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice): View
    {
        $this->authorize('update', $invoice);
        $invoice->load('items.product');
        return view('invoices.edit', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        DB::transaction(function () use ($request, $invoice) {
            $invoice->update([
                'nome_estabelecimento' => $request->nome_estabelecimento,
                'cnpj'                 => $request->cnpj,
                'numero'               => $request->numero,
                'serie'                => $request->serie,
                'data_emissao'         => $request->data_emissao,
                'forma_pagamento'      => $request->forma_pagamento,
                'descontos'            => $this->parseFloat($request->descontos ?? '0'),
            ]);

            if ($request->has('itens_removidos')) {
                InvoiceItem::whereIn('id', $request->itens_removidos)
                    ->where('invoice_id', $invoice->id)
                    ->delete();
            }

            foreach ($request->itens ?? [] as $itemId => $itemData) {
                $nome = trim($itemData['nome'] ?? '');
                if (empty($nome)) continue;

                $product = Product::firstOrCreate(
                    ['user_id' => Auth::id(), 'nome' => $nome],
                    ['unidade_padrao' => $itemData['unidade'] ?? 'UN']
                );

                $campos = [
                    'product_id'     => $product->id,
                    'quantidade'     => $this->parseFloat($itemData['quantidade']    ?? '0'),
                    'unidade'        => $itemData['unidade'] ?? 'UN',
                    'valor_unitario' => $this->parseFloat($itemData['valor_unitario'] ?? '0'),
                    'valor_total'    => $this->parseFloat($itemData['valor_total']    ?? '0'),
                ];

                if (str_starts_with((string) $itemId, 'novo_')) {
                    InvoiceItem::create(array_merge($campos, ['invoice_id' => $invoice->id]));
                } else {
                    InvoiceItem::where('id', $itemId)
                        ->where('invoice_id', $invoice->id)
                        ->update($campos);
                }
            }

            $invoice->recalcularTotais();
        });

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Nota fiscal atualizada com sucesso!');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('delete', $invoice);
        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Nota fiscal excluída com sucesso!');
    }

    public function editItem(Invoice $invoice, InvoiceItem $item): View
    {
        $this->authorize('update', $invoice);
        return view('invoices.items.edit', compact('invoice', 'item'));
    }

    public function updateItem(Request $request, Invoice $invoice, InvoiceItem $item): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $validated = $request->validate([
            'nome_produto'   => 'required|string|max:255',
            'quantidade'     => 'required|numeric|min:0',
            'unidade'        => 'required|string|max:5',
            'valor_unitario' => 'required|numeric|min:0',
            'valor_total'    => 'required|numeric|min:0',
        ]);

        $product = Product::firstOrCreate(
            ['user_id' => Auth::id(), 'nome' => trim($validated['nome_produto'])]
        );

        $item->update([
            'product_id'     => $product->id,
            'quantidade'     => $validated['quantidade'],
            'unidade'        => $validated['unidade'],
            'valor_unitario' => $validated['valor_unitario'],
            'valor_total'    => $validated['valor_total'],
        ]);

        $invoice->fresh()->recalcularTotais();

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Item atualizado com sucesso!');
    }

    public function destroyItem(Invoice $invoice, InvoiceItem $item): RedirectResponse
    {
        $this->authorize('delete', $invoice);
        $item->delete();
        $invoice->fresh()->recalcularTotais();

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Item removido com sucesso!');
    }
}

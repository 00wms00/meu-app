<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::where('user_id', Auth::id())
            ->orderBy('data_emissao', 'desc');

        if ($request->filled('de')) {
            $query->whereDate('data_emissao', '>=', $request->de);
        }
        if ($request->filled('ate')) {
            $query->whereDate('data_emissao', '<=', $request->ate);
        }
        if ($request->filled('estabelecimento')) {
            $query->where('nome_estabelecimento', 'like', '%' . $request->estabelecimento . '%');
        }

        $invoices = $query->paginate(20)->withQueryString();

        return view('invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        $this->authorize('view', $invoice);
        $invoice->load('items');
        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        $this->authorize('update', $invoice);
        $invoice->load('items');
        return view('invoices.edit', compact('invoice'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        // Normaliza campos monetários: aceita vírgula (BR) ou ponto (EN)
        $request->merge([
            'valor_pago' => $this->normalizarDecimal($request->input('valor_pago')),
            'descontos'  => $this->normalizarDecimal($request->input('descontos_raw', '0')),
        ]);

        $data = $request->validate([
            'nome_estabelecimento' => 'required|string|max:255',
            'data_emissao'         => 'required|date',
            'valor_pago'           => 'required|numeric|min:0',
            'descontos'            => 'nullable|numeric|min:0',
            'forma_pagamento'      => 'nullable|string|max:100',
            'status'               => 'nullable|in:pendente,pago,pgoCC',
        ]);

        // 1. Atualiza campos principais da nota
        $invoice->update($data);

        // 2. Remove itens marcados para exclusão
        $itensRemovidos = $request->input('itens_removidos', []);
        if (!empty($itensRemovidos)) {
            InvoiceItem::whereIn('id', $itensRemovidos)
                ->where('invoice_id', $invoice->id)
                ->delete();
        }

        // 3. Atualiza / cria itens enviados pelo form
        $itens = $request->input('itens', []);
        foreach ($itens as $key => $itemData) {
            $isNovo    = str_starts_with((string) $key, 'novo_');
            $qtd       = $this->normalizarDecimal($itemData['quantidade']   ?? '0');
            $unitario  = $this->normalizarDecimal($itemData['valor_unitario'] ?? '0');
            $total     = $this->normalizarDecimal($itemData['valor_total']   ?? '0');

            if ($isNovo) {
                // Cria produto genérico e item novo
                $produto = Product::firstOrCreate(
                    ['user_id' => Auth::id(), 'nome' => trim($itemData['nome'] ?? 'Produto')],
                    ['unidade' => $itemData['unidade'] ?? 'UN']
                );

                InvoiceItem::create([
                    'invoice_id'    => $invoice->id,
                    'product_id'    => $produto->id,
                    'descricao'     => $produto->nome,
                    'quantidade'    => $qtd,
                    'unidade'       => $itemData['unidade'] ?? 'UN',
                    'valor_unitario'=> $unitario,
                    'valor_total'   => $total,
                ]);
            } else {
                // Atualiza item existente
                InvoiceItem::where('id', $key)
                    ->where('invoice_id', $invoice->id)
                    ->update([
                        'quantidade'     => $qtd,
                        'unidade'        => $itemData['unidade'] ?? 'UN',
                        'valor_unitario' => $unitario,
                        'valor_total'    => $total,
                    ]);
            }
        }

        // 4. Recalcula valor_total e valor_pago a partir dos itens
        $invoice->recalcularTotais();

        return redirect()->route('invoices.show', $invoice)->with('success', 'Nota atualizada!');
    }

    /**
     * Converte string monetária BR ("1.234,56") ou EN ("1234.56") para float string ("1234.56").
     */
    private function normalizarDecimal(?string $value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }
        $value = trim($value);
        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }
        return $value;
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);
        $invoice->delete();
        return redirect()->route('invoices.index')->with('success', 'Nota excluída.');
    }

    // ---- Itens individuais ----

    public function editItem(Invoice $invoice, \App\Models\InvoiceItem $item)
    {
        $this->authorize('update', $invoice);
        return view('invoices.items.edit', compact('invoice', 'item'));
    }

    public function updateItem(Request $request, Invoice $invoice, \App\Models\InvoiceItem $item)
    {
        $this->authorize('update', $invoice);

        $data = $request->validate([
            'descricao'    => 'required|string|max:255',
            'quantidade'   => 'required|numeric|min:0',
            'unidade'      => 'nullable|string|max:20',
            'valor_unit'   => 'required|numeric|min:0',
            'valor_total'  => 'required|numeric|min:0',
        ]);

        $item->update($data);
        $invoice->recalcularTotais();

        return redirect()->route('invoices.show', $invoice)->with('success', 'Item atualizado!');
    }

    public function destroyItem(Invoice $invoice, \App\Models\InvoiceItem $item)
    {
        $this->authorize('update', $invoice);
        $item->delete();
        $invoice->recalcularTotais();
        return redirect()->route('invoices.show', $invoice)->with('success', 'Item removido.');
    }

    /**
     * Atualiza apenas o status de pagamento de uma nota (chamado via PATCH ajax/form).
     */
    public function updateStatus(Request $request, Invoice $invoice)
    {
        $this->authorize('update', $invoice);

        $request->validate([
            'status' => 'required|in:pendente,pago,pgoCC',
        ]);

        $invoice->update(['status' => $request->status]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'status' => $invoice->status]);
        }

        return back()->with('success', 'Status da nota atualizado!');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
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

        $data = $request->validate([
            'nome_estabelecimento' => 'required|string|max:255',
            'data_emissao'         => 'required|date',
            'valor_pago'           => 'required|numeric|min:0',
            'descontos'            => 'nullable|numeric|min:0',
            'forma_pagamento'      => 'nullable|string|max:100',
            'status'               => 'nullable|in:pendente,pago,pgoCC',
        ]);

        $invoice->update($data);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Nota atualizada!');
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorize('delete', $invoice);
        $invoice->delete();
        return redirect()->route('invoices.index')->with('success', 'Nota exclu\u00edda.');
    }

    // ---- Itens ----

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
     * Usado pelo acordeon de Mercado em /financas/despesas.
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

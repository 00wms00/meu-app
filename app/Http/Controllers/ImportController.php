<?php

namespace App\Http\Controllers;

use App\Models\FuelEntry;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Vehicle;
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
            'arquivo' => 'required|file|mimes:html,htm',
        ]);

        $html = file_get_contents($request->file('arquivo')->path());

        try {
            $data = $parser->parse($html);
        } catch (\Exception $e) {
            return back()->withErrors(['arquivo' => 'Erro ao processar o HTML: ' . $e->getMessage()]);
        }

        session(['parsed_invoice' => $data]);

        return redirect()->route('import.preview');
    }

    public function preview(): View|RedirectResponse
    {
        $data = $this->sessionData();

        if (! $data) {
            return redirect()->route('import.create')
                ->withErrors(['arquivo' => 'Nenhum dado para exibir.']);
        }

        $vehicles = Vehicle::where('user_id', Auth::id())
            ->orderBy('apelido')
            ->get(['id', 'apelido', 'marca', 'modelo']);

        return view('import.preview', [
            'data'     => $data,
            'vehicles' => $vehicles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->sessionData();

        if (! $data) {
            return redirect()->route('import.create')
                ->withErrors(['arquivo' => 'Sessão expirada.']);
        }

        $destino = $request->input('destino', 'mercado');

        // ============================================================
        // DESTINO: VEÍCULO  —  cria FuelEntry
        // ============================================================
        if ($destino === 'veiculo' && ! empty($data['is_combustivel'])) {
            $request->validate([
                'vehicle_id'       => ['required', 'integer', 'exists:vehicles,id'],
                'km_abastecimento' => ['nullable', 'integer', 'min:0'],
            ]);

            $vehicle = Vehicle::findOrFail($request->vehicle_id);

            if ($vehicle->user_id !== Auth::id()) {
                abort(403);
            }

            $fuel = $data['fuel'];

            $km = $request->filled('km_abastecimento')
                ? (int) $request->km_abastecimento
                : null;

            FuelEntry::create([
                'user_id'          => Auth::id(),
                'vehicle_id'       => $vehicle->id,
                'data'             => $fuel['data'] ?? now()->toDateString(),
                'valor'            => $fuel['valor'],
                'litros'           => $fuel['litros'],
                'km_abastecimento' => $km,
                'tipo_combustivel' => $fuel['tipo_combustivel'],
                'posto'            => $fuel['posto'],
                'tanque_cheio'     => false,
                'descricao'        => 'Importado da NFC-e ' . ($data['numero'] ?? ''),
            ]);

            if ($km && $km > $vehicle->km_atual) {
                $vehicle->update(['km_atual' => $km]);
            }

            session()->forget('parsed_invoice');

            return redirect()->route('vehicles.show', $vehicle)
                ->with('success', 'Abastecimento importado da NFC-e com sucesso!');
        }

        // ============================================================
        // DESTINO: MERCADO  —  fluxo original de Invoice
        // ============================================================
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

        Cache::forget('planejamento-' . Auth::id());
        session()->forget('parsed_invoice');

        return redirect()->route('dashboard')
            ->with('success', 'Nota fiscal importada com sucesso!');
    }

    private function sessionData(): ?array
    {
        return session('parsed_invoice');
    }
}

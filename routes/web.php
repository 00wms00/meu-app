<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FuelEntryController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ShoppingListController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\LancamentoManualController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleExpenseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Importação
    Route::get('/import/create', [ImportController::class, 'create'])->name('import.create');
    Route::post('/import/parse', [ImportController::class, 'parse'])->name('import.parse');
    Route::get('/import/preview', [ImportController::class, 'preview'])->name('import.preview');
    Route::post('/import/store', [ImportController::class, 'store'])->name('import.store');
    
    // Notas
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
    Route::get('/invoices/{invoice}/items/{item}/edit', [InvoiceController::class, 'editItem'])->name('invoices.items.edit');
    Route::put('/invoices/{invoice}/items/{item}', [InvoiceController::class, 'updateItem'])->name('invoices.items.update');
    Route::delete('/invoices/{invoice}/items/{item}', [InvoiceController::class, 'destroyItem'])->name('invoices.items.destroy');
    
    // ==================== PRODUTOS ====================
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/normalizacao', [ProductController::class, 'normalizacao'])->name('normalizacao');
        Route::post('/{product}/normalizar', [ProductController::class, 'aprovarNormalizacao'])->name('normalizar');
        Route::post('/normalizar-todos', [ProductController::class, 'aprovarTodasNormalizacoes'])->name('normalizar-todos');
        Route::get('/categorias', [ProductController::class, 'categorias'])->name('categorias');
        Route::post('/categorizar-lote', [ProductController::class, 'categorizarLote'])->name('categorizar-lote');
        Route::get('/agrupamentos', [ProductController::class, 'agrupamentos'])->name('agrupamentos');
        Route::post('/agrupar-automatico', [ProductController::class, 'agruparAutomatico'])->name('agrupar-automatico');
        Route::post('/criar-grupo', [ProductController::class, 'criarGrupo'])->name('criar-grupo');
        Route::get('/ml-sugestoes', [ProductController::class, 'mlSugestoes'])->name('ml-sugestoes');
        Route::post('/ml-agrupar', [ProductController::class, 'mlAgrupar'])->name('ml-agrupar');
        Route::post('/{product}/agrupar', [ProductController::class, 'agrupar'])->name('agrupar');
        Route::post('/{product}/desagrupar', [ProductController::class, 'desagrupar'])->name('desagrupar');
        Route::post('/{product}/tornar-canonico', [ProductController::class, 'tornarCanonico'])->name('tornar-canonico');
        Route::post('/{product}/renomear-grupo', [ProductController::class, 'renomearGrupo'])->name('renomear-grupo');
        Route::post('/{product}/desfazer-grupo', [ProductController::class, 'desfazerGrupo'])->name('desfazer-grupo');
        Route::post('/{product}/adicionar-ao-grupo', [ProductController::class, 'adicionarAoGrupo'])->name('adicionar-ao-grupo');
        Route::post('/{product}/categoria', [ProductController::class, 'atualizarCategoria'])->name('atualizar-categoria');
        Route::post('/{product}/foto', [ProductController::class, 'uploadFoto'])->name('foto');
        Route::delete('/{product}/foto', [ProductController::class, 'removerFoto'])->name('foto.remover');
        Route::post('/{product}/alerta', [ProductController::class, 'criarAlerta'])->name('alerta.criar');
        Route::get('/{product}/similares', [ProductController::class, 'similares'])->name('similares');
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/{product}', [ProductController::class, 'show'])->name('show');
        Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
        Route::put('/{product}', [ProductController::class, 'update'])->name('update');
    });
    
    // Alertas
    Route::get('/alertas', [ProductController::class, 'alertas'])->name('alertas.index');
    Route::delete('/alertas/{alerta}', [ProductController::class, 'removerAlerta'])->name('alertas.remover');
    Route::post('/alertas/{alerta}/toggle', [ProductController::class, 'toggleAlerta'])->name('alertas.toggle');
    
    // ML Interativo
    Route::get('/ml-interativo', [ProductController::class, 'mlSugestoesInterativo'])->name('products.ml-interativo');
    Route::post('/ml-confirmar', [ProductController::class, 'mlConfirmarAgrupamento'])->name('products.ml-confirmar');
    
    // Relatórios
    Route::get('/relatorio-mensal', [RelatorioController::class, 'mensal'])->name('relatorio.mensal');
    Route::get('/relatorio-periodo', [RelatorioController::class, 'periodo'])->name('relatorio.periodo');
    
    // Orçamento
    Route::get('/orcamento', [BudgetController::class, 'index'])->name('budgets.index');
    Route::post('/orcamento', [BudgetController::class, 'store'])->name('budgets.store');
    
    // Categorias
    Route::resource('categories', CategoryController::class)->except(['show', 'create']);
    
    // Listas de Compras
    Route::get('/planejamento-compras', [ShoppingListController::class, 'planejamento'])->name('shopping-lists.planejamento');
    Route::resource('shopping-lists', ShoppingListController::class);
    Route::post('shopping-lists/{shopping_list}/finalizar', [ShoppingListController::class, 'finalizar'])->name('shopping-lists.finalizar');
    Route::post('shopping-lists/{shopping_list}/reabrir', [ShoppingListController::class, 'reabrir'])->name('shopping-lists.reabrir');
    Route::post('shopping-lists/{shopping_list}/items', [ShoppingListController::class, 'addItem'])->name('shopping-lists.items.add');
    Route::post('shopping-lists/{shopping_list}/frequentes', [ShoppingListController::class, 'addFrequentes'])->name('shopping-lists.frequentes');
    Route::post('shopping-lists/{shopping_list}/sugerir', [ShoppingListController::class, 'sugerirItens'])->name('shopping-lists.sugerir');
    Route::post('shopping-lists/{shopping_list}/limpar-comprados', [ShoppingListController::class, 'limparComprados'])->name('shopping-lists.limpar');
    Route::post('items/{item}/toggle', [ShoppingListController::class, 'toggleItem'])->name('items.toggle');
    Route::put('items/{item}', [ShoppingListController::class, 'updateItem'])->name('items.update');
    Route::delete('items/{item}', [ShoppingListController::class, 'removeItem'])->name('items.remove');
    Route::post('/lista-rapida', [ShoppingListController::class, 'criarListaRapida'])->name('shopping-lists.rapida');
    
    // Ofertas
    Route::get('/offers', [OfferController::class, 'index'])->name('offers.index');
    Route::get('/offers/create', [OfferController::class, 'create'])->name('offers.create');
    Route::post('/offers', [OfferController::class, 'store'])->name('offers.store');
    Route::post('/offers/upload-encarte', [OfferController::class, 'uploadEncarte'])->name('offers.upload-encarte');
    Route::get('/offers/preview', [OfferController::class, 'preview'])->name('offers.preview');
    Route::post('/offers/save-preview', [OfferController::class, 'savePreview'])->name('offers.save-preview');
    Route::delete('/offers/{offer}', [OfferController::class, 'destroy'])->name('offers.destroy');
    Route::post('/offers/{offer}/toggle', [OfferController::class, 'toggle'])->name('offers.toggle');
    
    // Lançamento Manual
    Route::get('/lancamento-manual', [LancamentoManualController::class, 'create'])->name('lancamento.create');
    Route::post('/lancamento-manual', [LancamentoManualController::class, 'store'])->name('lancamento.store');

    // Veículos
    Route::resource('vehicles', VehicleController::class);
    // Despesas gerais do veículo
    Route::post('/vehicles/{vehicle}/expenses', [VehicleExpenseController::class, 'store'])->name('vehicles.expenses.store');
    Route::delete('/vehicles/{vehicle}/expenses/{expense}', [VehicleExpenseController::class, 'destroy'])->name('vehicles.expenses.destroy');
    // Abastecimentos do veículo
    Route::post('/vehicles/{vehicle}/fuel', [FuelEntryController::class, 'store'])->name('vehicles.fuel.store');
    Route::delete('/vehicles/{vehicle}/fuel/{fuelEntry}', [FuelEntryController::class, 'destroy'])->name('vehicles.fuel.destroy');
});

require __DIR__.'/auth.php';

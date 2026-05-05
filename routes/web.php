<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RelatorioController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::middleware(['auth'])->group(function () {
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
    
    // Produtos - Categorias (rota fixa primeiro)
    Route::get('/products/categorias', [ProductController::class, 'categorias'])->name('products.categorias');
    Route::post('/products/categorizar-lote', [ProductController::class, 'categorizarLote'])->name('products.categorizar-lote');
    Route::post('/products/{product}/categoria', [ProductController::class, 'atualizarCategoria'])->name('products.atualizar-categoria');
    
    // Produtos - Agrupamentos (rotas fixas)
    Route::get('/products/agrupamentos', [ProductController::class, 'agrupamentos'])->name('products.agrupamentos');
    Route::post('/products/agrupar-automatico', [ProductController::class, 'agruparAutomatico'])->name('products.agrupar-automatico');
    Route::post('/products/criar-grupo', [ProductController::class, 'criarGrupo'])->name('products.criar-grupo');
    Route::post('/products/{product}/renomear-grupo', [ProductController::class, 'renomearGrupo'])->name('products.renomear-grupo');
    Route::post('/products/{product}/desfazer-grupo', [ProductController::class, 'desfazerGrupo'])->name('products.desfazer-grupo');
    Route::post('/products/{product}/adicionar-ao-grupo', [ProductController::class, 'adicionarAoGrupo'])->name('products.adicionar-ao-grupo');
    Route::post('/products/{product}/agrupar', [ProductController::class, 'agrupar'])->name('products.agrupar');
    Route::post('/products/{product}/desagrupar', [ProductController::class, 'desagrupar'])->name('products.desagrupar');
    Route::post('/products/{product}/tornar-canonico', [ProductController::class, 'tornarCanonico'])->name('products.tornar-canonico');
    
    // Produtos (rotas com parâmetro por último)
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    
    // Relatório
    Route::get('/relatorio-mensal', [RelatorioController::class, 'mensal'])->name('relatorio.mensal');
});

require __DIR__.'/auth.php';

// Categorias
Route::resource('categories', App\Http\Controllers\CategoryController::class)->except(['show', 'create']);
Route::get('/api/categories', [App\Http\Controllers\CategoryController::class, 'apiIndex'])->name('categories.api');

// Lançamento Manual
Route::get('/lancamento-manual', [App\Http\Controllers\LancamentoManualController::class, 'create'])->name('lancamento.create');
Route::post('/lancamento-manual', [App\Http\Controllers\LancamentoManualController::class, 'store'])->name('lancamento.store');

// Relatório por Período
Route::get('/relatorio-periodo', [App\Http\Controllers\RelatorioController::class, 'periodo'])->name('relatorio.periodo');

// Orçamento
Route::get('/orcamento', [App\Http\Controllers\BudgetController::class, 'index'])->name('budgets.index');
Route::post('/orcamento', [App\Http\Controllers\BudgetController::class, 'store'])->name('budgets.store');

// Alertas de Preço
Route::get('/alertas', [App\Http\Controllers\ProductController::class, 'alertas'])->name('alertas.index');
Route::post('/products/{product}/alerta', [App\Http\Controllers\ProductController::class, 'criarAlerta'])->name('alertas.criar');
Route::delete('/alertas/{alerta}', [App\Http\Controllers\ProductController::class, 'removerAlerta'])->name('alertas.remover');
Route::post('/alertas/{alerta}/toggle', [App\Http\Controllers\ProductController::class, 'toggleAlerta'])->name('alertas.toggle');

// Listas de Compras
Route::resource('shopping-lists', App\Http\Controllers\ShoppingListController::class);
Route::post('shopping-lists/{shopping_list}/finalizar', [App\Http\Controllers\ShoppingListController::class, 'finalizar'])->name('shopping-lists.finalizar');
Route::post('shopping-lists/{shopping_list}/reabrir', [App\Http\Controllers\ShoppingListController::class, 'reabrir'])->name('shopping-lists.reabrir');
Route::post('shopping-lists/{shopping_list}/items', [App\Http\Controllers\ShoppingListController::class, 'addItem'])->name('shopping-lists.items.add');
Route::post('shopping-lists/{shopping_list}/frequentes', [App\Http\Controllers\ShoppingListController::class, 'addFrequentes'])->name('shopping-lists.frequentes');
Route::post('shopping-lists/{shopping_list}/sugerir', [App\Http\Controllers\ShoppingListController::class, 'sugerirItens'])->name('shopping-lists.sugerir');
Route::post('shopping-lists/{shopping_list}/limpar-comprados', [App\Http\Controllers\ShoppingListController::class, 'limparComprados'])->name('shopping-lists.limpar');
Route::post('items/{item}/toggle', [App\Http\Controllers\ShoppingListController::class, 'toggleItem'])->name('items.toggle');
Route::put('items/{item}', [App\Http\Controllers\ShoppingListController::class, 'updateItem'])->name('items.update');
Route::delete('items/{item}', [App\Http\Controllers\ShoppingListController::class, 'removeItem'])->name('items.remove');

// Planejamento de Compras
Route::get('/planejamento-compras', [App\Http\Controllers\ShoppingListController::class, 'planejamento'])->name('shopping-lists.planejamento');
Route::post('/lista-rapida', [App\Http\Controllers\ShoppingListController::class, 'criarListaRapida'])->name('shopping-lists.rapida');

// Fotos dos Produtos
Route::post('/products/{product}/foto', [App\Http\Controllers\ProductController::class, 'uploadFoto'])->name('products.foto');
Route::delete('/products/{product}/foto', [App\Http\Controllers\ProductController::class, 'removerFoto'])->name('products.foto.remover');

// Machine Learning
Route::get('/products/{product}/similares', [App\Http\Controllers\ProductController::class, 'similares'])->name('products.similares');
Route::post('/products/ml-agrupar', [App\Http\Controllers\ProductController::class, 'mlAgrupar'])->name('products.ml-agrupar');
Route::get('/products/ml-sugestoes', [App\Http\Controllers\ProductController::class, 'mlSugestoes'])->name('products.ml-sugestoes');

// ML Interativo
Route::get('/ml-interativo', [App\Http\Controllers\ProductController::class, 'mlSugestoesInterativo'])->name('products.ml-interativo');
Route::post('/ml-confirmar', [App\Http\Controllers\ProductController::class, 'mlConfirmarAgrupamento'])->name('products.ml-confirmar');

// Upload de Encarte com IA
Route::post('/offers/upload-encarte', [App\Http\Controllers\OfferController::class, 'uploadEncarte'])->name('offers.upload-encarte');

// Preview e salvamento de encarte IA
Route::get('/offers/preview', [App\Http\Controllers\OfferController::class, 'preview'])->name('offers.preview');
Route::post('/offers/save-preview', [App\Http\Controllers\OfferController::class, 'savePreview'])->name('offers.save-preview');

// Preview e salvamento de encarte IA
Route::get('/offers/preview', [App\Http\Controllers\OfferController::class, 'preview'])->name('offers.preview');
Route::post('/offers/save-preview', [App\Http\Controllers\OfferController::class, 'savePreview'])->name('offers.save-preview');
Route::get('/offers/create', [App\Http\Controllers\OfferController::class, 'create'])->name('offers.create');

// Ofertas e Encartes
Route::get('/offers', [App\Http\Controllers\OfferController::class, 'index'])->name('offers.index');
Route::get('/offers/create', [App\Http\Controllers\OfferController::class, 'create'])->name('offers.create');
Route::post('/offers', [App\Http\Controllers\OfferController::class, 'store'])->name('offers.store');
Route::post('/offers/upload-encarte', [App\Http\Controllers\OfferController::class, 'uploadEncarte'])->name('offers.upload-encarte');
Route::get('/offers/preview', [App\Http\Controllers\OfferController::class, 'preview'])->name('offers.preview');
Route::post('/offers/save-preview', [App\Http\Controllers\OfferController::class, 'savePreview'])->name('offers.save-preview');
Route::delete('/offers/{offer}', [App\Http\Controllers\OfferController::class, 'destroy'])->name('offers.destroy');
Route::post('/offers/{offer}/toggle', [App\Http\Controllers\OfferController::class, 'toggle'])->name('offers.toggle');

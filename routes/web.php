<?php

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LancamentoManualController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\ShoppingListController;
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

    // Notas Fiscais
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
    Route::get('/invoices/{invoice}/items/{item}/edit', [InvoiceController::class, 'editItem'])->name('invoices.items.edit');
    Route::put('/invoices/{invoice}/items/{item}', [InvoiceController::class, 'updateItem'])->name('invoices.items.update');
    Route::delete('/invoices/{invoice}/items/{item}', [InvoiceController::class, 'destroyItem'])->name('invoices.items.destroy');

    // Produtos - Categorias (rotas fixas antes das de parâmetro)
    Route::get('/products/categorias', [ProductController::class, 'categorias'])->name('products.categorias');
    Route::post('/products/categorizar-lote', [ProductController::class, 'categorizarLote'])->name('products.categorizar-lote');

    // Produtos - Agrupamentos (rotas fixas antes das de parâmetro)
    Route::get('/products/agrupamentos', [ProductController::class, 'agrupamentos'])->name('products.agrupamentos');
    Route::post('/products/agrupar-automatico', [ProductController::class, 'agruparAutomatico'])->name('products.agrupar-automatico');
    Route::post('/products/criar-grupo', [ProductController::class, 'criarGrupo'])->name('products.criar-grupo');

    // Machine Learning (rotas fixas antes das de parâmetro)
    Route::post('/products/ml-agrupar', [ProductController::class, 'mlAgrupar'])->name('products.ml-agrupar');
    Route::get('/products/ml-sugestoes', [ProductController::class, 'mlSugestoes'])->name('products.ml-sugestoes');

    // Produtos (rotas com parâmetro por último)
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::post('/products/{product}/categoria', [ProductController::class, 'atualizarCategoria'])->name('products.atualizar-categoria');
    Route::post('/products/{product}/renomear-grupo', [ProductController::class, 'renomearGrupo'])->name('products.renomear-grupo');
    Route::post('/products/{product}/desfazer-grupo', [ProductController::class, 'desfazerGrupo'])->name('products.desfazer-grupo');
    Route::post('/products/{product}/adicionar-ao-grupo', [ProductController::class, 'adicionarAoGrupo'])->name('products.adicionar-ao-grupo');
    Route::post('/products/{product}/agrupar', [ProductController::class, 'agrupar'])->name('products.agrupar');
    Route::post('/products/{product}/desagrupar', [ProductController::class, 'desagrupar'])->name('products.desagrupar');
    Route::post('/products/{product}/tornar-canonico', [ProductController::class, 'tornarCanonico'])->name('products.tornar-canonico');
    Route::get('/products/{product}/similares', [ProductController::class, 'similares'])->name('products.similares');
    Route::post('/products/{product}/alerta', [ProductController::class, 'criarAlerta'])->name('alertas.criar');
    Route::post('/products/{product}/foto', [ProductController::class, 'uploadFoto'])->name('products.foto');
    Route::delete('/products/{product}/foto', [ProductController::class, 'removerFoto'])->name('products.foto.remover');

    // Categorias
    Route::resource('categories', CategoryController::class)->except(['show', 'create']);
    Route::get('/api/categories', [CategoryController::class, 'apiIndex'])->name('categories.api');

    // Lançamento Manual
    Route::get('/lancamento-manual', [LancamentoManualController::class, 'create'])->name('lancamento.create');
    Route::post('/lancamento-manual', [LancamentoManualController::class, 'store'])->name('lancamento.store');

    // Relatórios
    Route::get('/relatorio-mensal', [RelatorioController::class, 'mensal'])->name('relatorio.mensal');
    Route::get('/relatorio-periodo', [RelatorioController::class, 'periodo'])->name('relatorio.periodo');

    // Orçamento
    Route::get('/orcamento', [BudgetController::class, 'index'])->name('budgets.index');
    Route::post('/orcamento', [BudgetController::class, 'store'])->name('budgets.store');

    // Alertas de Preço
    Route::get('/alertas', [ProductController::class, 'alertas'])->name('alertas.index');
    Route::delete('/alertas/{alerta}', [ProductController::class, 'removerAlerta'])->name('alertas.remover');
    Route::post('/alertas/{alerta}/toggle', [ProductController::class, 'toggleAlerta'])->name('alertas.toggle');

    // Listas de Compras
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

    // Planejamento de Compras
    Route::get('/planejamento-compras', [ShoppingListController::class, 'planejamento'])->name('shopping-lists.planejamento');
    Route::post('/lista-rapida', [ShoppingListController::class, 'criarListaRapida'])->name('shopping-lists.rapida');

    // Machine Learning Interativo
    Route::get('/ml-interativo', [ProductController::class, 'mlSugestoesInterativo'])->name('products.ml-interativo');
    Route::post('/ml-confirmar', [ProductController::class, 'mlConfirmarAgrupamento'])->name('products.ml-confirmar');

    // Ofertas e Encartes
    Route::get('/offers', [OfferController::class, 'index'])->name('offers.index');
    Route::get('/offers/create', [OfferController::class, 'create'])->name('offers.create');
    Route::post('/offers', [OfferController::class, 'store'])->name('offers.store');
    Route::post('/offers/upload-encarte', [OfferController::class, 'uploadEncarte'])->name('offers.upload-encarte');
    Route::get('/offers/preview', [OfferController::class, 'preview'])->name('offers.preview');
    Route::post('/offers/save-preview', [OfferController::class, 'savePreview'])->name('offers.save-preview');
    Route::delete('/offers/{offer}', [OfferController::class, 'destroy'])->name('offers.destroy');
    Route::post('/offers/{offer}/toggle', [OfferController::class, 'toggle'])->name('offers.toggle');
});

require __DIR__.'/auth.php';

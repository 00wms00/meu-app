<?php

use App\Http\Controllers\CreditCardController;
use App\Http\Controllers\CreditPurchaseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\FaturaController;
use App\Http\Controllers\FinanceExpenseController;
use App\Http\Controllers\FinanceIncomeController;
use App\Http\Controllers\FinanceReportController;
use App\Http\Controllers\FuelEntryController;
use App\Http\Controllers\FuelStationReportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MaintenanceReminderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductAgrupamentoController;
use App\Http\Controllers\ProductFotoController;
use App\Http\Controllers\ProductMlController;
use App\Http\Controllers\PriceAlertController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ShoppingListController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\LancamentoManualController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\VehicleExpenseController;
use App\Http\Controllers\VehicleReportController;
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
        Route::post('/{product}/categoria', [ProductController::class, 'atualizarCategoria'])->name('atualizar-categoria');

        Route::get('/agrupamentos', [ProductAgrupamentoController::class, 'agrupamentos'])->name('agrupamentos');
        Route::post('/criar-grupo', [ProductAgrupamentoController::class, 'criarGrupo'])->name('criar-grupo');
        Route::post('/{product}/agrupar', [ProductAgrupamentoController::class, 'agrupar'])->name('agrupar');
        Route::post('/{product}/desagrupar', [ProductAgrupamentoController::class, 'desagrupar'])->name('desagrupar');
        Route::post('/{product}/tornar-canonico', [ProductAgrupamentoController::class, 'tornarCanonico'])->name('tornar-canonico');
        Route::post('/{product}/renomear-grupo', [ProductAgrupamentoController::class, 'renomearGrupo'])->name('renomear-grupo');
        Route::post('/{product}/desfazer-grupo', [ProductAgrupamentoController::class, 'desfazerGrupo'])->name('desfazer-grupo');
        Route::post('/{product}/adicionar-ao-grupo', [ProductAgrupamentoController::class, 'adicionarAoGrupo'])->name('adicionar-ao-grupo');

        Route::get('/ml-sugestoes', [ProductMlController::class, 'sugestoesInterativo'])->name('ml-sugestoes');
        Route::post('/ml-agrupar', [ProductMlController::class, 'confirmarAgrupamento'])->name('ml-agrupar');
        Route::get('/ml-interativo', [ProductMlController::class, 'sugestoesInterativo'])->name('ml-interativo');
        Route::post('/ml-confirmar', [ProductMlController::class, 'confirmarAgrupamento'])->name('ml-confirmar');
        Route::get('/{product}/similares', [ProductMlController::class, 'similares'])->name('similares');

        Route::post('/{product}/foto', [ProductController::class, 'uploadFoto'])->name('foto');
        Route::delete('/{product}/foto', [ProductController::class, 'removerFoto'])->name('foto.remover');

        Route::post('/{product}/alerta', [PriceAlertController::class, 'criar'])->name('alerta.criar');

        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/{product}', [ProductController::class, 'show'])->name('show');
        Route::get('/{product}/edit', [ProductController::class, 'edit'])->name('edit');
        Route::put('/{product}', [ProductController::class, 'update'])->name('update');
    });

    // Alertas de preço
    Route::get('/alertas', [PriceAlertController::class, 'index'])->name('alertas.index');
    Route::delete('/alertas/{alerta}', [PriceAlertController::class, 'remover'])->name('alertas.remover');
    Route::post('/alertas/{alerta}/toggle', [PriceAlertController::class, 'toggle'])->name('alertas.toggle');

    // Relatórios (compras)
    Route::get('/relatorio-mensal', [RelatorioController::class, 'mensal'])->name('relatorio.mensal');
    Route::get('/relatorio-periodo', [RelatorioController::class, 'periodo'])->name('relatorio.periodo');

    // Orçamento
    Route::get('/orcamento', [BudgetController::class, 'index'])->name('budgets.index');
    Route::post('/orcamento', [BudgetController::class, 'store'])->name('budgets.store');

    // Categorias (produtos)
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
    Route::post('/lista-rapida', [ShoppingListController::class, 'criarListaRapida'])->name('shopping-lists.rapida');
    Route::post('items/{item}/toggle', [ShoppingListController::class, 'toggleItem'])->name('items.toggle');
    Route::put('items/{item}', [ShoppingListController::class, 'updateItem'])->name('items.update');
    Route::delete('items/{item}', [ShoppingListController::class, 'removeItem'])->name('items.remove');

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

    // ==================== VEÍCULOS ====================
    Route::get('/vehicles/report/monthly', [VehicleReportController::class, 'monthly'])
        ->name('vehicles.report.monthly');
    Route::get('/vehicles/report/fuel-stations', [FuelStationReportController::class, 'index'])
        ->name('vehicles.report.fuel-stations');

    Route::resource('vehicles', VehicleController::class);

    Route::post('/vehicles/{vehicle}/expenses', [VehicleExpenseController::class, 'store'])->name('vehicles.expenses.store');
    Route::patch('/vehicles/{vehicle}/expenses/{expense}', [VehicleExpenseController::class, 'update'])->name('vehicles.expenses.update');
    Route::delete('/vehicles/{vehicle}/expenses/{expense}', [VehicleExpenseController::class, 'destroy'])->name('vehicles.expenses.destroy');

    Route::post('/vehicles/{vehicle}/fuel', [FuelEntryController::class, 'store'])->name('vehicles.fuel.store');
    Route::patch('/vehicles/{vehicle}/fuel/{fuelEntry}', [FuelEntryController::class, 'update'])->name('vehicles.fuel.update');
    Route::patch('/vehicles/{vehicle}/fuel/{fuelEntry}/km', [FuelEntryController::class, 'updateKm'])->name('vehicles.fuel.updateKm');
    Route::patch('/vehicles/{vehicle}/fuel/{fuelEntry}/litros', [FuelEntryController::class, 'updateLitros'])->name('vehicles.fuel.updateLitros');
    Route::delete('/vehicles/{vehicle}/fuel/{fuelEntry}', [FuelEntryController::class, 'destroy'])->name('vehicles.fuel.destroy');

    Route::post('/vehicles/{vehicle}/reminders', [MaintenanceReminderController::class, 'store'])->name('vehicles.reminders.store');
    Route::post('/vehicles/{vehicle}/reminders/{reminder}/feito', [MaintenanceReminderController::class, 'feito'])->name('vehicles.reminders.feito');
    Route::delete('/vehicles/{vehicle}/reminders/{reminder}', [MaintenanceReminderController::class, 'destroy'])->name('vehicles.reminders.destroy');

    // ==================== FINANÇAS ====================
    Route::prefix('financas')->name('finance.')->group(function () {

        // Receitas
        Route::get('/receitas', [FinanceIncomeController::class, 'index'])->name('incomes.index');
        Route::post('/receitas', [FinanceIncomeController::class, 'store'])->name('incomes.store');
        Route::put('/receitas/{income}', [FinanceIncomeController::class, 'update'])->name('incomes.update');
        Route::delete('/receitas/{income}', [FinanceIncomeController::class, 'destroy'])->name('incomes.destroy');
        Route::post('/receitas/duplicar-recorrentes', [FinanceIncomeController::class, 'duplicarRecorrentes'])->name('incomes.duplicar');

        // Despesas
        Route::get('/despesas', [FinanceExpenseController::class, 'index'])->name('expenses.index');
        Route::post('/despesas', [FinanceExpenseController::class, 'store'])->name('expenses.store');
        Route::put('/despesas/{expense}', [FinanceExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('/despesas/{expense}', [FinanceExpenseController::class, 'destroy'])->name('expenses.destroy');
        Route::patch('/despesas/{expense}/toggle-pago', [FinanceExpenseController::class, 'togglePago'])->name('expenses.toggle');
        Route::post('/despesas/duplicar-fixas', [FinanceExpenseController::class, 'duplicarFixas'])->name('expenses.duplicar');

        // Categorias de despesas (CRUD via JSON/Ajax)
        Route::get('/expense-categories',                             [ExpenseCategoryController::class, 'index'])  ->name('expense_categories.index');
        Route::post('/expense-categories',                            [ExpenseCategoryController::class, 'store'])  ->name('expense_categories.store');
        Route::put('/expense-categories/{expenseCategory}',           [ExpenseCategoryController::class, 'update']) ->name('expense_categories.update');
        Route::delete('/expense-categories/{expenseCategory}',        [ExpenseCategoryController::class, 'destroy'])->name('expense_categories.destroy');
        Route::post('/expense-categories/reorder',                    [ExpenseCategoryController::class, 'reorder'])->name('expense_categories.reorder');

        // Relatório financeiro
        Route::get('/relatorio', [FinanceReportController::class, 'index'])->name('finance.report.index');

        // Cartões de Crédito
        Route::get('/cartoes', [CreditCardController::class, 'index'])->name('credit_cards.index');
        Route::post('/cartoes', [CreditCardController::class, 'store'])->name('credit_cards.store');
        Route::put('/cartoes/{creditCard}', [CreditCardController::class, 'update'])->name('credit_cards.update');
        Route::patch('/cartoes/{creditCard}/toggle', [CreditCardController::class, 'toggleAtivo'])->name('credit_cards.toggle');
        Route::delete('/cartoes/{creditCard}', [CreditCardController::class, 'destroy'])->name('credit_cards.destroy');

        // Faturas
        Route::get('/faturas', [FaturaController::class, 'index'])->name('faturas.index');

        // Compras no Crédito + Parcelas
        Route::get('/compras-credito', [CreditPurchaseController::class, 'index'])->name('credit_purchases.index');
        Route::post('/compras-credito', [CreditPurchaseController::class, 'store'])->name('credit_purchases.store');
        Route::delete('/compras-credito/{creditPurchase}', [CreditPurchaseController::class, 'destroy'])->name('credit_purchases.destroy');
        Route::patch('/compras-credito/parcela/{installment}/toggle', [CreditPurchaseController::class, 'toggleInstallment'])->name('credit_purchases.toggle_installment');

    });

});

require __DIR__.'/auth.php';

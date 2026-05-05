<?php

use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Prefixo /api já aplicado pelo RouteServiceProvider.
| Versionamento explícito via prefix('v1'): adicionar v2 no futuro
| é só criar um novo grupo sem quebrar consumidores de v1.
|
| URLs: /api/v1/categories
|
*/

Route::prefix('v1')->group(function () {

    Route::middleware('auth:sanctum')->group(function () {

        // Retorna lista de categorias do usuário autenticado em JSON.
        // Consumido via fetch() nos selects das views de produto/import.
        // Atualizar chamadas JS de /api/categories para /api/v1/categories.
        Route::get('/categories', [CategoryController::class, 'apiIndex'])
            ->name('categories.api');

    });

});

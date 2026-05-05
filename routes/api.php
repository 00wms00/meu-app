<?php

use App\Http\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rotas registradas aqui recebem automaticamente o middleware 'api'
| (throttle:api + bindings) e o prefixo /api via RouteServiceProvider.
|
| Autenticação: middleware 'auth:sanctum' para endpoints que exigem
| sessão autenticada.
|
*/

Route::middleware('auth:sanctum')->group(function () {

    // Retorna lista de categorias do usuário autenticado em JSON.
    // Consumido via fetch() nos selects das views de produto/import.
    Route::get('/categories', [CategoryController::class, 'apiIndex'])
        ->name('categories.api');
});

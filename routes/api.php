<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\TipoImagemController;
use App\Http\Controllers\Api\ImagemController;
use App\Http\Controllers\Api\ProdutoController;
use App\Http\Controllers\Api\ProdutoHistoricoController;
use App\Http\Controllers\Api\ReceitaController;
use App\Http\Controllers\Api\ReceitaTagController;
use App\Http\Controllers\Api\OpenFoodFactsController;
use App\Http\Controllers\Api\ListaCompraStatusController;
use App\Http\Controllers\Api\ListaCompraController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- Rotas Públicas ---
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

// --- Rotas Autenticadas ---
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware(['throttle:6,1'])
        ->name('verification.send');

    // --- API Resource Routes ---
    Route::apiResource('tipo-imagens', TipoImagemController::class)->only(['index', 'show']);
    Route::apiResource('imagens', ImagemController::class);
    Route::get('/imagens/{imagem}/view', [ImagemController::class, 'view'])->name('imagens.view');
    Route::apiResource('produtos', ProdutoController::class)->only(['index', 'show']);
    Route::apiResource('receita-tags', ReceitaTagController::class)->only(['index', 'show']);;
    Route::apiResource('receitas', ReceitaController::class);
    Route::post('/receitas/{receita}/clone', [ReceitaController::class, 'clone'])->name('receitas.clone');
    Route::apiResource('categorias', CategoriaController::class)->only(['index', 'show']);


    // Produto Historicos: Everyone can view/create, only admins can update/delete
    Route::apiResource('produto-historicos', ProdutoHistoricoController::class)->except(['update', 'destroy']);

    Route::apiResource('lista-compra-status', ListaCompraStatusController::class)->only(['index', 'show']);
    Route::apiResource('listas-compra', ListaCompraController::class);
    Route::post('compras/registrar-evento', [ListaCompraController::class, 'registrarEventoDeCompra'])->name('compras.registrar-evento');

    // --- Admin Routes ---
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {

        Route::apiResource('tipo-imagens', TipoImagemController::class)->except(['index', 'show']);
        Route::apiResource('produtos', ProdutoController::class);
        Route::apiResource('categorias', CategoriaController::class);
        Route::apiResource('receita-tags', ReceitaTagController::class);
        Route::put('/produto-historicos/{produto_historico}', [ProdutoHistoricoController::class, 'update'])->name('produto-historicos.update');
        Route::delete('/produto-historicos/{produto_historico}', [ProdutoHistoricoController::class, 'destroy'])->name('produto-historicos.destroy');
        Route::apiResource('lista-compra-status', ListaCompraStatusController::class)->except(['index', 'show']);
        Route::prefix('openfoodfacts')->name('openfoodfacts.')->group(function () {
            Route::get('/product/{barcode}', [OpenFoodFactsController::class, 'getProductByBarcode'])->name('product.barcode');
            Route::get('/search', [OpenFoodFactsController::class, 'searchProducts'])->name('search');
        });
    }); // End admin middleware group

}); // End auth:sanctum group
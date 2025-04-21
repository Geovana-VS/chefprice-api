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
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware(['throttle:6,1'])
        ->name('verification.send');

    // --- API Resource Routes ---
    Route::apiResource('categorias', CategoriaController::class);
    Route::apiResource('tipo-imagens', TipoImagemController::class);
    Route::apiResource('imagens', ImagemController::class);
    Route::apiResource('produtos', ProdutoController::class);
    Route::apiResource('receitas', ReceitaController::class);
    Route::apiResource('receita-tags', ReceitaTagController::class);
    Route::apiResource('produto-historicos', ProdutoHistoricoController::class)->except(['update', 'destroy']);
});
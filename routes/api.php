<?php

use App\Http\Controllers\Api\V1\ApiAuthController;
use App\Http\Controllers\Api\V1\ApiCatalogController;
use App\Http\Controllers\Api\V1\ApiCustomerController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Endpoints públicos de catálogo y autenticación móvil
    Route::post('/auth/login', [ApiAuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/auth/register', [ApiAuthController::class, 'register'])->middleware('throttle:6,1');

    Route::get('/categories', [ApiCatalogController::class, 'categories']);
    Route::get('/books', [ApiCatalogController::class, 'books']);
    Route::get('/books/{book}', [ApiCatalogController::class, 'show']);

    // Endpoints protegidos con Bearer Token SHA-256 (Sanctum-compatible)
    Route::middleware('api.token')->group(function () {
        Route::post('/auth/logout', [ApiAuthController::class, 'logout']);
        Route::get('/me', [ApiCustomerController::class, 'me']);

        Route::get('/favorites', [ApiCustomerController::class, 'favorites']);
        Route::post('/books/{book}/favorite', [ApiCustomerController::class, 'toggleFavorite']);

        Route::get('/loans', [ApiCustomerController::class, 'loans']);
        Route::post('/loans', [ApiCustomerController::class, 'storeLoan']);

        Route::get('/orders', [ApiCustomerController::class, 'orders']);
        Route::post('/orders', [ApiCustomerController::class, 'storeOrder']);
    });
});

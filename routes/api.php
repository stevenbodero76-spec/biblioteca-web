<?php

use App\Http\Controllers\Api\V1\ApiAuthController;
use App\Http\Controllers\Api\V1\ApiCatalogController;
use App\Http\Controllers\Api\V1\ApiCustomerController;
use App\Http\Controllers\BookController;
use Illuminate\Support\Facades\Route;

Route::get('/books/external-search', [BookController::class, 'externalSearch'])
    ->middleware('throttle:30,1')
    ->name('api.books.external-search');

Route::prefix('v1')->group(function () {
    // Endpoints públicos de catálogo y autenticación móvil
    Route::post('/auth/login', [ApiAuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/auth/register', [ApiAuthController::class, 'register'])->middleware('throttle:6,1');

    Route::get('/categories', [ApiCatalogController::class, 'categories']);
    Route::get('/books', [ApiCatalogController::class, 'books']);
    Route::get('/books/{book}', [ApiCatalogController::class, 'show']);

    // Endpoints protegidos con Bearer Token SHA-256
    Route::middleware('api.token')->group(function () {
        Route::post('/auth/logout', [ApiAuthController::class, 'logout']);
        Route::get('/me', [ApiCustomerController::class, 'me']);
        Route::put('/me', [ApiCustomerController::class, 'updateProfile']);

        Route::get('/favorites', [ApiCustomerController::class, 'favorites']);
        Route::post('/books/{book}/favorite', [ApiCustomerController::class, 'toggleFavorite']);
        Route::post('/books/{book}/waitlist', [ApiCustomerController::class, 'joinWaitlist']);
        Route::delete('/waitlists/{waitlist}', [ApiCustomerController::class, 'cancelWaitlist']);
        Route::post('/books/{book}/reviews', [ApiCustomerController::class, 'storeReview']);

        Route::get('/loans', [ApiCustomerController::class, 'loans']);
        Route::post('/loans', [ApiCustomerController::class, 'storeLoan']);

        Route::get('/orders', [ApiCustomerController::class, 'orders']);
        Route::get('/orders/{order}', [ApiCustomerController::class, 'showOrder']);
        Route::post('/orders', [ApiCustomerController::class, 'storeOrder']);
    });
});

<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReaderController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WaitlistController;
use Illuminate\Support\Facades\Route;

// PORTAL PÚBLICO DE NOVAETHER™
Route::get('/', [BookController::class, 'index'])->name('home');
Route::get('/books', [BookController::class, 'index'])->name('books.index');
Route::get('/books/external-search', [BookController::class, 'externalSearch'])
    ->middleware('throttle:30,1')
    ->name('books.external-search');

// Libro de Reclamaciones Virtual (Cumplimiento comercial Perú)
Route::get('/libro-de-reclamaciones', [ComplaintController::class, 'create'])->name('complaints.create');
Route::post('/libro-de-reclamaciones', [ComplaintController::class, 'store'])
    ->middleware('throttle:4,1')
    ->name('complaints.store');

// Autenticación, Registro y Recuperación de Contraseña
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('login.post');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:4,1')
        ->name('register.post');

    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])
        ->middleware('throttle:4,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// Rutas autenticadas (Roles: Admin y Cliente Activo)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Autogestión de cuenta
    Route::get('/mi-perfil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/mi-perfil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/mi-perfil/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Carrito Unificado (Compra directa + Alquileres multi-ítem) y Órdenes
    Route::get('/carrito', [CartController::class, 'index'])->name('cart.index');
    Route::post('/carrito/agregar/{book}', [CartController::class, 'add'])->name('cart.add');
    Route::patch('/carrito/item/{key}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/carrito/item/{key}', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/carrito/checkout', [CartController::class, 'checkout'])->name('cart.checkout');

    Route::get('/mis-pedidos', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/mis-pedidos/{order}', [OrderController::class, 'show'])->name('orders.show');

    // Panel Ejecutivo, Catálogo, Clientes y Postventa (Exclusivo Admin)
    Route::middleware('rol:admin')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
        Route::post('/books', [BookController::class, 'store'])->name('books.store');
        Route::get('/books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');
        Route::put('/books/{book}', [BookController::class, 'update'])->name('books.update');
        Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');
        Route::get('/api/isbn/{isbn}', [BookController::class, 'buscarPorIsbn'])->name('books.isbn');

        Route::resource('readers', ReaderController::class)->except(['show']);

        Route::patch('/loans/{loan}/confirmar-pago', [LoanController::class, 'confirmarPago'])->name('loans.confirmar-pago');
        Route::patch('/loans/{loan}/anular', [LoanController::class, 'anular'])->name('loans.anular');
        Route::patch('/loans/{loan}/devolver', [LoanController::class, 'devolver'])->name('loans.devolver');
        Route::delete('/loans/{loan}', [LoanController::class, 'destroy'])->name('loans.destroy');

        Route::patch('/orders/{order}/confirmar-pago', [OrderController::class, 'confirmPayment'])->name('orders.confirmPayment');
        Route::patch('/orders/{order}/estado', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');
        Route::patch('/orders/{order}/anular', [OrderController::class, 'cancel'])->name('orders.cancel');

        Route::get('/admin/reclamaciones', [ComplaintController::class, 'adminIndex'])->name('admin.complaints.index');
        Route::patch('/admin/reclamaciones/{complaint}', [ComplaintController::class, 'adminUpdate'])->name('admin.complaints.update');
        Route::get('/admin/listas-espera', [WaitlistController::class, 'adminIndex'])->name('admin.waitlists.index');
        Route::get('/admin/resenas', [ReviewController::class, 'adminIndex'])->name('admin.reviews.index');
        Route::patch('/reviews/{review}/visibilidad', [ReviewController::class, 'toggleVisibility'])->name('reviews.visibility');
    });

    // Préstamos, Reservas y Tickets QR (Acceso para Cliente y Admin, filtrado por Policy/Scope)
    Route::get('/loans', [LoanController::class, 'index'])->name('loans.index');
    Route::get('/loans/create', [LoanController::class, 'create'])->name('loans.create');
    Route::post('/loans', [LoanController::class, 'store'])->name('loans.store');
    Route::get('/loans/{loan}/ticket', [LoanController::class, 'ticket'])->name('loans.ticket');

    // Lista de Espera cuando stock == 0
    Route::post('/books/{book}/waitlist', [WaitlistController::class, 'store'])->name('waitlists.store');
    Route::delete('/waitlists/{waitlist}', [WaitlistController::class, 'destroy'])->name('waitlists.destroy');

    // Estantería Personal (Favoritos) y Reseñas Verificadas
    Route::get('/mi-estanteria', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/books/{book}/favorite', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');
});

// Ficha detallada del libro + Visor Interactivo
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');

<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\ReaderController;
use Illuminate\Support\Facades\Route;

// PORTAL PÚBLICO DE NOVAETHER™ (Explorable libremente por clientes y visitantes)
Route::get('/', [BookController::class, 'index'])->name('home');
Route::get('/books', [BookController::class, 'index'])->name('books.index');

// Rutas de Autenticación y Creación de Cuenta (Registro de Clientes)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

// Rutas protegidas (Clientes registrados, Staff y Administrador)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Panel de Resumen y Analytics
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Módulo 1: Gestión de Catálogo (Solo Admin crea, edita y elimina libros)
    Route::middleware('rol:admin')->group(function () {
        Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
        Route::post('/books', [BookController::class, 'store'])->name('books.store');
        Route::get('/books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');
        Route::put('/books/{book}', [BookController::class, 'update'])->name('books.update');
        Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');
    });

    // Módulo 2: Clientes / Lectores (Staff y Admin gestionan; eliminar solo Admin)
    Route::middleware('rol:admin,bibliotecario')->group(function () {
        Route::resource('readers', ReaderController::class)->except(['show', 'destroy']);
    });
    Route::delete('/readers/{reader}', [ReaderController::class, 'destroy'])
        ->middleware('rol:admin')
        ->name('readers.destroy');

    // Módulo 3: Préstamos, Reservas y Devoluciones (Disponible para Clientes, Staff y Admin)
    Route::get('/loans', [LoanController::class, 'index'])->name('loans.index');
    Route::get('/loans/create', [LoanController::class, 'create'])->name('loans.create');
    Route::post('/loans', [LoanController::class, 'store'])->name('loans.store');
    Route::patch('/loans/{loan}/devolver', [LoanController::class, 'devolver'])->name('loans.devolver');
    Route::delete('/loans/{loan}', [LoanController::class, 'destroy'])
        ->middleware('rol:admin')
        ->name('loans.destroy');
});

// Ficha detallada del libro + Visor de Lectura Cloud
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Precios comerciales en Soles (PEN) para libros, mangas y cómics
        Schema::table('books', function (Blueprint $table) {
            $table->decimal('precio_alquiler', 8, 2)->default(8.00)->after('stock_disponible');
            $table->decimal('precio_venta', 8, 2)->default(49.90)->after('precio_alquiler');
        });

        // 2. Sistema de puntos Karma Lector por devolución puntual y reseñas
        Schema::table('readers', function (Blueprint $table) {
            $table->unsignedInteger('puntos_karma')->default(50)->after('estado');
        });

        // 3. Modalidad de reserva / pago en préstamos
        Schema::table('loans', function (Blueprint $table) {
            $table->enum('metodo_pago', ['membresia', 'yape_plin', 'mostrador'])
                ->default('membresia')
                ->after('estado');
            $table->decimal('monto', 8, 2)->default(0.00)->after('metodo_pago');
        });

        // 4. Reseñas reales con protección anti-spoilers
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comentario');
            $table->boolean('es_spoiler')->default(false);
            $table->timestamps();

            $table->unique(['book_id', 'user_id'], 'uniq_reviews_book_user');
            $table->index(['book_id', 'rating'], 'idx_reviews_book_rating');
        });

        // 5. Estantería personal / Favoritos
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'book_id'], 'uniq_favorites_user_book');
        });

        // 6. Libro de Reclamaciones Virtual (Cumplimiento INDECOPI Perú)
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_reclamo', 30)->unique();
            $table->string('nombre_completo', 150);
            $table->string('dni_ce', 20);
            $table->string('email', 150);
            $table->string('telefono', 20)->nullable();
            $table->enum('tipo', ['reclamo', 'queja'])->default('reclamo');
            $table->text('detalle');
            $table->text('pedido_consumidor');
            $table->enum('estado', ['registrado', 'en_revision', 'resuelto'])->default('registrado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('reviews');

        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['metodo_pago', 'monto']);
        });

        Schema::table('readers', function (Blueprint $table) {
            $table->dropColumn('puntos_karma');
        });

        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn(['precio_alquiler', 'precio_venta']);
        });
    }
};

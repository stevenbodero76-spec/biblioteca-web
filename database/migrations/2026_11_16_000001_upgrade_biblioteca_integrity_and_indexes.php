<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Vincular formalmente readers con users mediante FK user_id
        Schema::table('readers', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('id')
                ->constrained('users')
                ->nullOnDelete();
        });

        // Backfill seguro para registros existentes que coincidan por correo
        $usersByEmail = DB::table('users')->pluck('id', 'email');
        foreach ($usersByEmail as $email => $userId) {
            DB::table('readers')
                ->where('email', $email)
                ->whereNull('user_id')
                ->update(['user_id' => $userId]);
        }

        // 2. Índices de rendimiento en books
        Schema::table('books', function (Blueprint $table) {
            $table->index(['category_id', 'stock_disponible'], 'idx_books_category_stock');
            $table->index('stock_disponible', 'idx_books_stock_disponible');

            if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
                $table->fullText(['titulo', 'autor'], 'idx_books_fulltext_search');
            }
        });

        // 3. Índices compuestos en loans para reportes y validaciones rápidas
        Schema::table('loans', function (Blueprint $table) {
            $table->index(['estado', 'fecha_devolucion_esperada'], 'idx_loans_estado_fecha');
            $table->index(['reader_id', 'estado'], 'idx_loans_reader_estado');
        });

        // 4. Tabla de Lista de Espera (Backorder / Notificación cuando stock == 0)
        Schema::create('waitlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->foreignId('reader_id')->constrained('readers')->cascadeOnDelete();
            $table->enum('estado', ['en_espera', 'notificado', 'completado', 'cancelado'])->default('en_espera');
            $table->timestamp('notificado_at')->nullable();
            $table->timestamps();

            $table->index(['book_id', 'estado'], 'idx_waitlists_book_estado');
            $table->index(['reader_id', 'estado'], 'idx_waitlists_reader_estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waitlists');

        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex('idx_loans_estado_fecha');
            $table->dropIndex('idx_loans_reader_estado');
        });

        Schema::table('books', function (Blueprint $table) {
            if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
                $table->dropFullText('idx_books_fulltext_search');
            }
            $table->dropIndex('idx_books_category_stock');
            $table->dropIndex('idx_books_stock_disponible');
        });

        Schema::table('readers', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};

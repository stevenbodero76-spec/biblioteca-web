<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabla de Categorías (12 grandes categorías de Biblioteca Virtual + Tienda)
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->string('icono', 30)->default('📚');
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });

        // 2. Módulo 1: Tabla de Libros (Catálogo Web + Disponibilidad)
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->onDelete('restrict');
            $table->string('isbn', 30)->unique();
            $table->string('titulo', 200);
            $table->string('autor', 150);
            $table->string('editorial', 100)->nullable();
            $table->unsignedSmallInteger('anio_publicacion')->nullable();
            $table->enum('formato', ['fisico', 'digital', 'ambos'])->default('ambos');
            $table->text('sinopsis')->nullable();
            $table->string('portada_url')->nullable();
            $table->string('archivo_pdf')->nullable();
            $table->string('ubicacion_estante', 50)->nullable();
            $table->unsignedInteger('stock_total')->default(1);
            $table->unsignedInteger('stock_disponible')->default(1);
            $table->timestamps();
        });

        // 3. Módulo 2: Tabla de Lectores
        Schema::create('readers', function (Blueprint $table) {
            $table->id();
            $table->string('dni_codigo', 20)->unique();
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('email', 150)->unique();
            $table->string('telefono', 20)->nullable();
            $table->enum('tipo', ['estudiante', 'docente', 'publico'])->default('estudiante');
            $table->enum('estado', ['activo', 'suspendido'])->default('activo');
            $table->timestamps();
        });

        // 4. Módulo 3: Tabla de Préstamos y Devoluciones
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('books')->onDelete('restrict');
            $table->foreignId('reader_id')->constrained('readers')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->date('fecha_prestamo');
            $table->date('fecha_devolucion_esperada');
            $table->date('fecha_devolucion_real')->nullable();
            $table->enum('estado', ['prestado', 'devuelto', 'vencido'])->default('prestado');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
        Schema::dropIfExists('readers');
        Schema::dropIfExists('books');
        Schema::dropIfExists('categories');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Control de pagos y expiración de reservas impagas en loans
        Schema::table('loans', function (Blueprint $table) {
            $table->enum('estado_pago', ['pagado', 'pendiente', 'anulado'])
                ->default('pagado')
                ->after('monto');
            $table->string('codigo_operacion', 60)
                ->nullable()
                ->after('estado_pago');
            $table->timestamp('fecha_pago')
                ->nullable()
                ->after('codigo_operacion');
            $table->timestamp('expira_pago_at')
                ->nullable()
                ->after('fecha_pago');

            $table->index(['estado_pago', 'expira_pago_at'], 'idx_loans_pago_expira');
        });

        // 2. Plan de suscripción comercial real en readers
        Schema::table('readers', function (Blueprint $table) {
            $table->enum('plan_suscripcion', ['free', 'estudiante', 'pro'])
                ->default('free')
                ->after('tipo');
            $table->date('plan_vence_at')
                ->nullable()
                ->after('plan_suscripcion');
        });

        // 3. Moderación de reseñas
        Schema::table('reviews', function (Blueprint $table) {
            $table->boolean('visible')
                ->default(true)
                ->after('es_spoiler');
            $table->foreignId('moderado_por_id')
                ->nullable()
                ->after('visible')
                ->constrained('users')
                ->nullOnDelete();
        });

        // 4. Respuesta administrativa al Libro de Reclamaciones
        Schema::table('complaints', function (Blueprint $table) {
            $table->text('respuesta_admin')
                ->nullable()
                ->after('estado');
            $table->foreignId('respondido_por_id')
                ->nullable()
                ->after('respuesta_admin')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('respondido_at')
                ->nullable()
                ->after('respondido_por_id');
        });

        // 5. Órdenes de Compra / Carrito Unificado (Venta directa + Alquileres multi-ítem)
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_orden', 30)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reader_id')->nullable()->constrained('readers')->nullOnDelete();
            $table->enum('metodo_pago', ['yape_plin', 'tarjeta', 'mostrador', 'membresia']);
            $table->enum('estado_pago', ['pendiente', 'pagado', 'anulado'])->default('pendiente');
            $table->enum('estado_pedido', ['pendiente', 'confirmado', 'entregado', 'cancelado'])->default('pendiente');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('descuento_karma', 10, 2)->default(0.00);
            $table->unsignedInteger('puntos_karma_usados')->default(0);
            $table->decimal('total', 10, 2);
            $table->string('codigo_operacion', 60)->nullable();
            $table->enum('tipo_entrega', ['tienda', 'delivery'])->default('tienda');
            $table->string('direccion_entrega', 255)->nullable();
            $table->timestamp('fecha_pago')->nullable();
            $table->timestamp('expira_pago_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at'], 'idx_orders_user_fecha');
            $table->index(['estado_pago', 'expira_pago_at'], 'idx_orders_pago_expira');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('book_id')->constrained('books')->restrictOnDelete();
            $table->enum('tipo_operacion', ['venta', 'alquiler']);
            $table->unsignedInteger('cantidad')->default(1);
            $table->unsignedTinyInteger('duracion_dias')->nullable();
            $table->decimal('precio_unitario', 8, 2);
            $table->decimal('subtotal', 10, 2);
            $table->foreignId('loan_id')->nullable()->constrained('loans')->nullOnDelete();
            $table->timestamps();
        });

        // 6. Historial auditable de movimientos de Puntos Nova (Karma)
        Schema::create('karma_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reader_id')->constrained('readers')->cascadeOnDelete();
            $table->integer('puntos');
            $table->string('motivo', 180);
            $table->string('referencia_tipo', 50)->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->timestamps();

            $table->index(['reader_id', 'created_at'], 'idx_karma_reader_fecha');
        });

        // 7. Tokens API SHA-256 para App Móvil / WebView nativo
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('token', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // Backfill de fecha_pago para préstamos ya existentes marcados como pagado
        DB::table('loans')
            ->where('estado_pago', 'pagado')
            ->whereNull('fecha_pago')
            ->update(['fecha_pago' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
        Schema::dropIfExists('karma_movements');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');

        Schema::table('complaints', function (Blueprint $table) {
            $table->dropConstrainedForeignId('respondido_por_id');
            $table->dropColumn(['respuesta_admin', 'respondido_at']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('moderado_por_id');
            $table->dropColumn('visible');
        });

        Schema::table('readers', function (Blueprint $table) {
            $table->dropColumn(['plan_suscripcion', 'plan_vence_at']);
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex('idx_loans_pago_expira');
            $table->dropColumn(['estado_pago', 'codigo_operacion', 'fecha_pago', 'expira_pago_at']);
        });
    }
};

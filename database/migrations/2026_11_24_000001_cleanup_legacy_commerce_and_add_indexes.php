<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Eliminar tablas comerciales obsoletas de tienda y carrito
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');

        // 2. Limpiar precios comerciales en books, ampliar URLs y agregar índice de filtros de lectura
        Schema::table('books', function (Blueprint $table) {
            $dropCols = array_values(array_filter(
                ['precio_alquiler', 'precio_venta'],
                fn (string $col) => Schema::hasColumn('books', $col)
            ));

            if ($dropCols !== []) {
                $table->dropColumn($dropCols);
            }
        });

        Schema::table('books', function (Blueprint $table) {
            $table->string('portada_url', 500)->nullable()->change();
            $table->string('archivo_pdf', 500)->nullable()->change();

            if (! Schema::hasIndex('books', 'idx_books_idioma_licencia_formato')) {
                $table->index(['idioma', 'licencia', 'formato'], 'idx_books_idioma_licencia_formato');
            }
        });

        // 3. Limpiar columnas de planes de suscripción en readers
        Schema::table('readers', function (Blueprint $table) {
            $dropCols = array_values(array_filter(
                ['plan_suscripcion', 'plan_vence_at'],
                fn (string $col) => Schema::hasColumn('readers', $col)
            ));

            if ($dropCols !== []) {
                $table->dropColumn($dropCols);
            }
        });

        // 4. Eliminar índice y columnas de cobro/pago obsoletos en loans y agregar índice operativo
        if (Schema::hasIndex('loans', 'idx_loans_pago_expira')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->dropIndex('idx_loans_pago_expira');
            });
        }

        Schema::table('loans', function (Blueprint $table) {
            $dropCols = array_values(array_filter(
                ['metodo_pago', 'monto', 'estado_pago', 'codigo_operacion', 'fecha_pago', 'expira_pago_at'],
                fn (string $col) => Schema::hasColumn('loans', $col)
            ));

            if ($dropCols !== []) {
                $table->dropColumn($dropCols);
            }
        });

        Schema::table('loans', function (Blueprint $table) {
            if (! Schema::hasIndex('loans', 'idx_loans_book_estado')) {
                $table->index(['book_id', 'estado'], 'idx_loans_book_estado');
            }
        });

        // 5. Índices de lectura y moderación en reviews y complaints
        Schema::table('reviews', function (Blueprint $table) {
            if (! Schema::hasIndex('reviews', 'idx_reviews_book_visible_fecha')) {
                $table->index(['book_id', 'visible', 'created_at'], 'idx_reviews_book_visible_fecha');
            }
        });

        Schema::table('complaints', function (Blueprint $table) {
            if (! Schema::hasIndex('complaints', 'idx_complaints_estado_fecha')) {
                $table->index(['estado', 'created_at'], 'idx_complaints_estado_fecha');
            }
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (Schema::hasIndex('complaints', 'idx_complaints_estado_fecha')) {
                $table->dropIndex('idx_complaints_estado_fecha');
            }
        });

        Schema::table('reviews', function (Blueprint $table) {
            if (Schema::hasIndex('reviews', 'idx_reviews_book_visible_fecha')) {
                $table->dropIndex('idx_reviews_book_visible_fecha');
            }
        });

        if (Schema::hasIndex('loans', 'idx_loans_book_estado')) {
            if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
                Schema::table('loans', function (Blueprint $table) {
                    $table->dropForeign(['book_id']);
                });
                Schema::table('loans', function (Blueprint $table) {
                    $table->dropIndex('idx_loans_book_estado');
                    if (! Schema::hasIndex('loans', 'loans_book_id_foreign')) {
                        $table->index('book_id', 'loans_book_id_foreign');
                    }
                });
                Schema::table('loans', function (Blueprint $table) {
                    $table->foreign('book_id')->references('id')->on('books')->onDelete('restrict');
                });
            } else {
                Schema::table('loans', function (Blueprint $table) {
                    $table->dropIndex('idx_loans_book_estado');
                });
            }
        }

        Schema::table('loans', function (Blueprint $table) {
            if (! Schema::hasColumn('loans', 'metodo_pago')) {
                $table->enum('metodo_pago', ['membresia', 'yape_plin', 'mostrador'])->default('membresia')->after('estado');
            }
            if (! Schema::hasColumn('loans', 'monto')) {
                $table->decimal('monto', 8, 2)->default(0.00)->after('metodo_pago');
            }
            if (! Schema::hasColumn('loans', 'estado_pago')) {
                $table->enum('estado_pago', ['pagado', 'pendiente', 'anulado'])->default('pagado')->after('monto');
            }
            if (! Schema::hasColumn('loans', 'codigo_operacion')) {
                $table->string('codigo_operacion', 60)->nullable()->after('estado_pago');
            }
            if (! Schema::hasColumn('loans', 'fecha_pago')) {
                $table->timestamp('fecha_pago')->nullable()->after('codigo_operacion');
            }
            if (! Schema::hasColumn('loans', 'expira_pago_at')) {
                $table->timestamp('expira_pago_at')->nullable()->after('fecha_pago');
            }
        });

        Schema::table('loans', function (Blueprint $table) {
            if (! Schema::hasIndex('loans', 'idx_loans_pago_expira')) {
                $table->index(['estado_pago', 'expira_pago_at'], 'idx_loans_pago_expira');
            }
        });

        Schema::table('readers', function (Blueprint $table) {
            if (! Schema::hasColumn('readers', 'plan_suscripcion')) {
                $table->enum('plan_suscripcion', ['free', 'estudiante', 'pro'])->default('free')->after('tipo');
            }
            if (! Schema::hasColumn('readers', 'plan_vence_at')) {
                $table->date('plan_vence_at')->nullable()->after('plan_suscripcion');
            }
        });

        Schema::table('books', function (Blueprint $table) {
            if (Schema::hasIndex('books', 'idx_books_idioma_licencia_formato')) {
                $table->dropIndex('idx_books_idioma_licencia_formato');
            }
            if (! Schema::hasColumn('books', 'precio_alquiler')) {
                $table->decimal('precio_alquiler', 8, 2)->default(8.00)->after('stock_disponible');
            }
            if (! Schema::hasColumn('books', 'precio_venta')) {
                $table->decimal('precio_venta', 8, 2)->default(49.90)->after('precio_alquiler');
            }
        });
    }
};

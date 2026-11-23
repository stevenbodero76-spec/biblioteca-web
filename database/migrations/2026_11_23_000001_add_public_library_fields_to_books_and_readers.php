<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('readers', function (Blueprint $table) {
            $table->string('dni_codigo', 20)->nullable()->change();
        });

        Schema::table('books', function (Blueprint $table) {
            if (! Schema::hasColumn('books', 'idioma')) {
                $table->string('idioma', 10)->default('es')->after('anio_publicacion');
            }
            if (! Schema::hasColumn('books', 'licencia')) {
                $table->enum('licencia', ['dominio_publico', 'creative_commons', 'acceso_abierto'])
                    ->default('acceso_abierto')
                    ->after('formato');
            }
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $cols = array_filter(['idioma', 'licencia'], fn (string $c) => Schema::hasColumn('books', $c));
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('readers', function (Blueprint $table) {
            $table->string('dni_codigo', 20)->nullable(false)->change();
        });
    }
};

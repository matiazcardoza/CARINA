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
        // Schema::create('obras', function (Blueprint $table) {
        //     $table->id();
        //     $table->string('nombre', 200);
        //     $table->string('codigo', 50)->unique();
        //     $table->timestamps();
        // });
        Schema::create('obras', function (Blueprint $table) {
            $table->id();

            // Identificador ÚNICO de Silucia (no se repite en origen)
            $table->string('idmeta_silucia', 100)->unique();

            // Campos clave para búsqueda/filtrado
            $table->string('anio', 10)->index();           // suelen buscar por año
            $table->string('codmeta', 50)->index();        // "código de meta" (puede repetirse con poca frecuencia)
            $table->string('nombre', 255)->nullable();     // puedes mapear desde nombre_corto o armarlo
            $table->text('desmeta')->nullable();           // descripción larga

            // Extras útiles del JSON de metas (opcionales pero prácticos)
            $table->string('nombre_corto', 255)->nullable()->index();
            $table->string('cadena', 50)->nullable()->index();
            $table->string('prod_proy', 50)->nullable()->index();

            // Índices compuestos para tus búsquedas típicas
            $table->index(['anio', 'codmeta']);   // búsqueda por año + código
            // Búsqueda por texto en desmeta/nombre (MySQL 5.7+/8 / PG soportado por Laravel):
            $table->fullText(['desmeta', 'nombre']);

            // Metadatos de sincronización con origen (lineage)
            $table->timestamp('external_last_seen_at')->nullable();
            $table->string('external_hash', 64)->nullable();   // hash del snapshot externo, util para no comparar dato por dato para actulaizar el registro. con este valor solo es necesario verificar el hash guardado con el hash del registros entrante, si ambos valores son diferentes entonces se actualiza el registro
            $table->longText('raw_snapshot')->nullable();      // JSON del origen (opcional)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obras');
    }
};

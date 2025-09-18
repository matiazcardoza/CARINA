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
        // Schema::create('item_pecosas', function (Blueprint $table) {
        //     $table->id(); // equivale a bigIncrements('id')

        //     // scoping por obra
        //     $table->foreignId('obra_id')->constrained('obras')->cascadeOnDelete();
        //     // relación con OC (misma obra)
        //     $table->foreignId('orden_id')->constrained('ordenes_compra')->cascadeOnDelete();

        //     // campos típicos (ajústalos si tu API trae otros nombres)
        //     $table->string('id_item_pecosa_silucia', 120)->nullable();  // id externo
        //     $table->string('descripcion', 500)->nullable();             // "item" en la tabla de tu UI
        //     $table->string('unidad', 50)->nullable();                   // "desmedida"
        //     $table->decimal('cantidad_compra', 14, 4)->default(0);
        //     $table->decimal('precio_unit', 14, 4)->default(0);
        //     $table->decimal('total', 14, 2)->default(0);

        //     // campos orientados a tu UI (opcionales)
        //     $table->string('numero', 80)->nullable();                   // N° Pecosa
        //     $table->year('anio')->nullable();
        //     $table->date('fecha')->nullable();
        //     $table->string('idsalidadet', 80)->nullable();
        //     $table->string('prod_proy', 150)->nullable();
        //     $table->string('cod_meta', 50)->nullable();
        //     $table->string('desmeta', 250)->nullable();
        //     $table->string('desuoper', 250)->nullable();
        //     $table->string('destipodestino', 250)->nullable();

        //     $table->timestamps();

        //     $table->index(['obra_id', 'orden_id']);
        //     $table->unique(['orden_id', 'id_item_pecosa_silucia']); // evita duplicad

        // });

        Schema::create('item_pecosas', function (Blueprint $table) {
            $table->id();

            // Relación: una obra tiene muchos item_pecosas
            $table->foreignId('obra_id')->constrained('obras')->cascadeOnDelete();
            
            // relación con OC (misma obra)
            // $table->foreignId('orden_id')->constrained('ordenes_compra')->cascadeOnDelete();
            $table->foreignId('orden_id')
                ->nullable() // permite que quede en NULL
                ->constrained('ordenes_compra')
                ->nullOnDelete(); // si se borra la orden, deja el campo en NULL

            // Identificadores y campos de Silucia
            $table->string('idsalidadet_silucia', 50)->unique(); // ÚNICO en origen (clave principal externa)
            $table->string('idcompradet_silucia', 50)->nullable()->index(); // opcional, útil para referencias cruzadas

            // Búsquedas típicas en tu UI
            $table->string('anio', 10)->index();
            $table->string('numero', 50)->index();           // N° PECOSA (string por seguridad)
            $table->index(['anio', 'numero']);               // compuesto: (anio,numero)

            // Otros campos del JSON de pecosa (mapea según necesites)
            $table->date('fecha')->nullable();
            $table->string('prod_proy', 50)->nullable()->index();
            $table->string('cod_meta', 50)->nullable()->index();  // para doble chequeo con obra
            $table->string('desmeta', 255)->nullable();
            $table->string('desuoper', 255)->nullable();
            $table->string('destipodestino', 100)->nullable();
            $table->text('item')->nullable();                // descripción del ítem
            $table->string('desmedida', 50)->nullable();

            $table->unsignedInteger('cantidad')->nullable();
            $table->decimal('precio', 12, 2)->nullable();
            $table->integer('saldo')->nullable();
            $table->decimal('total', 14, 2)->nullable();

            $table->string('numero_origen', 50)->nullable()->index(); // ej. número de OC

            // Metadatos de sincronización
            $table->timestamp('external_last_seen_at')->nullable();
            $table->string('external_hash', 64)->nullable();
            $table->longText('raw_snapshot')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::disableForeignKeyConstraints();
        // Schema::dropIfExists('item_pecosas');
        // Schema::enableForeignKeyConstraints();
        Schema::dropIfExists('item_pecosas');
    }
};

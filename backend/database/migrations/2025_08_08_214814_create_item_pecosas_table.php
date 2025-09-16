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
        Schema::create('item_pecosas', function (Blueprint $table) {
            $table->id(); // equivale a bigIncrements('id')

            // scoping por obra
            $table->foreignId('obra_id')->constrained('obras')->cascadeOnDelete();
            // relación con OC (misma obra)
            $table->foreignId('orden_id')->constrained('ordenes_compra')->cascadeOnDelete();

            // campos típicos (ajústalos si tu API trae otros nombres)
            $table->string('id_item_pecosa_silucia', 120)->nullable();  // id externo
            $table->string('descripcion', 500)->nullable();             // "item" en la tabla de tu UI
            $table->string('unidad', 50)->nullable();                   // "desmedida"
            $table->decimal('cantidad_compra', 14, 4)->default(0);
            $table->decimal('precio_unit', 14, 4)->default(0);
            $table->decimal('total', 14, 2)->default(0);

            // campos orientados a tu UI (opcionales)
            $table->string('numero', 80)->nullable();                   // N° Pecosa
            $table->year('anio')->nullable();
            $table->date('fecha')->nullable();
            $table->string('idsalidadet', 80)->nullable();
            $table->string('prod_proy', 150)->nullable();
            $table->string('cod_meta', 50)->nullable();
            $table->string('desmeta', 250)->nullable();
            $table->string('desuoper', 250)->nullable();
            $table->string('destipodestino', 250)->nullable();

            $table->timestamps();

            $table->index(['obra_id', 'orden_id']);
            $table->unique(['orden_id', 'id_item_pecosa_silucia']); // evita duplicad




            // $table->string('id_pecosa_silucia', 10);     // contenedor (nota de entrada / inventario / orden)
            // $table->unsignedBigInteger('id_item_pecosa_silucia');   // ID del ítem en Silucia

            // // Datos administrativos y logísticos (datos que vienen de silucia)
            // $table->year('anio')->nullable();
            // $table->string('numero', 20);           // nro de PECOSA (o del contenedor si aplica)
            // $table->date('fecha')->nullable();
            // $table->string('prod_proy', 20)->nullable();
            // $table->string('cod_meta', 10)->nullable();
            // $table->string('desmeta')->nullable();
            // $table->string('desuoper')->nullable();
            // $table->string('destipodestino')->nullable();

            // // Detalle del ítem (datos que vienen de silucia)
            // $table->text('item')->nullable();
            // $table->string('desmedida', 20)->nullable();
            // $table->unsignedBigInteger('idsalidadet'); // detalle de salida en Silucia (si aplica)
            // $table->decimal('cantidad', 10, 2)->default(0);
            // $table->decimal('precio', 10, 2)->default(0);
            // $table->string('tipo', 20)->nullable();          // 'orden', 'nota_entrada', etc.
            // $table->decimal('saldo', 10, 2)->default(0);
            // $table->decimal('total', 10, 2)->default(0);
            // $table->string('numero_origen', 20)->nullable();

            // // Datos que deben llenarse con cada movimiento del kardex
            // $table->decimal('quantity_received', 20, 4)->default(0);
            // $table->decimal('quantity_issued', 20, 4)->default(0);
            // $table->decimal('quantity_on_hand', 20, 4)->default(0);

            // // Seguramente necesitare el nombre del pdf
            // // $table->string('pdf_filename', 255)->nullable()->after('saldo');

            // // Info útil para auditoría/UI
            // $table->timestamp('last_movement_at')->nullable();

            // $table->timestamps();

            // $table->index('id_pecosa_silucia', 'idx_itempecosa_pecosa');
            // $table->index('id_item_pecosa_silucia', 'idx_itempecosa_item');
            // $table->unique(['id_pecosa_silucia', 'id_item_pecosa_silucia'], 'uq_pecosa_item');
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

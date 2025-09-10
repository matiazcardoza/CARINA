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

            // Referencias externas al sistema Silucia
            $table->unsignedBigInteger('id_container_silucia');     // contenedor (nota de entrada / inventario / orden)
            $table->unsignedBigInteger('id_item_pecosa_silucia');   // ID del ítem en Silucia

            // Datos administrativos y logísticos (datos que vienen de silucia)
            $table->year('anio')->nullable();
            $table->string('numero', 20);           // nro de PECOSA (o del contenedor si aplica)
            $table->date('fecha')->nullable();
            $table->string('prod_proy', 20)->nullable();
            $table->string('cod_meta', 10)->nullable();
            $table->string('desmeta')->nullable();
            $table->string('desuoper')->nullable();
            $table->string('destipodestino')->nullable();

            // Detalle del ítem (datos que vienen de silucia)
            $table->text('item')->nullable();
            $table->string('desmedida', 20)->nullable();
            $table->unsignedBigInteger('idsalidadet'); // detalle de salida en Silucia (si aplica)
            $table->decimal('cantidad', 10, 2)->default(0);
            $table->decimal('precio', 10, 2)->default(0);
            $table->string('tipo', 20)->nullable();          // 'orden', 'nota_entrada', etc.
            $table->decimal('saldo', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('numero_origen', 20)->nullable();

            // Datos que deben llenarse con cada movimiento del kardex
            $table->decimal('quantity_received', 20, 4)->default(0);
            $table->decimal('quantity_issued', 20, 4)->default(0);
            $table->decimal('quantity_on_hand', 20, 4)->default(0);

            // Seguramente necesitare el nombre del pdf
            // $table->string('pdf_filename', 255)->nullable()->after('saldo');

            // Info útil para auditoría/UI
            $table->timestamp('last_movement_at')->nullable();

            $table->timestamps();

            // Índices útiles para búsquedas frecuentes
            $table->index('id_container_silucia', 'idx_id_container_silucia');
            $table->index(['anio', 'numero'], 'idx_anio_numero');
            $table->index('idsalidadet', 'idx_idsalidadet');

            // Unicidad: misma combinación en el sistema externo no debe repetirse
            $table->unique(
                ['id_container_silucia', 'id_item_pecosa_silucia'],
                'unique_container_item_silucia'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('item_pecosas');
        Schema::enableForeignKeyConstraints();
    }
};

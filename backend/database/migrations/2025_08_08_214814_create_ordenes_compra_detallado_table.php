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

        Schema::create('ordenes_compra_detallado', function (Blueprint $table) {
            $table->id();

            // Relación: una obra tiene muchos item_pecosas, cascadeOnDelete: "se eliminara todo las ordenes_compra_detallado,  si se elimina una orden
            $table->foreignId('obra_id')->constrained('obras')->cascadeOnDelete();
            
            // relación con OC (misma obra)
            // $table->foreignId('orden_id')->constrained('ordenes_compra')->cascadeOnDelete();
            $table->foreignId('orden_id')
                ->nullable() // permite que quede en NULL
                ->constrained('ordenes_compra')
                ->nullOnDelete(); // si se borra la orden, deja el campo en NULL

            // Identificadores y campos de Silucia
            $table->string('idcompradet', 50)->unique(); // ÚNICO en origen (clave principal externa)

            // Búsquedas típicas en tu UI
            $table->string('anio', 10)->index();
            $table->string('numero', 50)->index();                  // N° PECOSA (string por seguridad)
                $table->index(['anio', 'numero']);                      // compuesto: (anio,numero)

            $table->string('siaf', 50)->nullable();                 // No se que es siaf, pero parece un valor importnate 
            $table->string('prod_proy', 50)->nullable()->index();   // reamente esto alamcena el CUI de una obra (util para anexar con obras de base de datos de leo)


            // Otros campos del JSON de pecosa (mapea según necesites)
            $table->date('fecha')->nullable();
            $table->date('fecha_aceptacion')->nullable();

            
            $table->text('item')->nullable();                // descripción del ítem
            $table->string('desmedida', 50)->nullable();

            $table->unsignedInteger('cantidad')->nullable();
            $table->decimal('precio', 12, 2)->nullable();
            $table->unsignedInteger('saldo')->nullable();


            
            $table->decimal('total_internado', 14, 2)->nullable();
            $table->string('internado', 50)->nullable(); 

            $table->string('idmeta', 50)->nullable()->index(); // ej. número de OC

            // columnas usadas para guardar los movimientos tanto de entradas como de salida (los totales)
            $table->decimal('quantity_received', 18, 3)->default(0); // total ENTRADAS
            $table->decimal('quantity_issued',   18, 3)->default(0); // total SALIDAS
            $table->decimal('quantity_on_hand',  18, 3)->default(0); // STOCK FINAL = received - issued

            // Metadatos de sincronización
            $table->timestamp('external_last_seen_at')->nullable();
            $table->string('external_hash', 64)->nullable();

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
        Schema::dropIfExists('ordenes_compra_detallado');
    }
};

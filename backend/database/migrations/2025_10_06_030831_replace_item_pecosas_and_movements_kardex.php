<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Asegura orden de borrado por FKs
        Schema::disableForeignKeyConstraints();

        // 1) Dropear tablas viejas si existen
        if (Schema::hasTable('movements_kardex')) {
            Schema::drop('movements_kardex');
        }
        if (Schema::hasTable('item_pecosas')) {
            Schema::drop('item_pecosas');
        }

        // 2) Crear NUEVA ordenes_compra_detallado (tabla 002)
        Schema::create('ordenes_compra_detallado', function (Blueprint $table) {
            $table->id();

            // Obra
            $table->foreignId('obra_id')->constrained('obras')->cascadeOnDelete();

            // Relación con ordenes_compra (nullable, null on delete)
            $table->foreignId('orden_id')
                ->nullable()
                ->constrained('ordenes_compra')
                ->nullOnDelete();

            // Identificador externo (único)
            $table->string('idcompradet', 50)->unique();

            // Búsquedas típicas
            $table->string('anio', 10)->index();
            $table->string('numero', 50)->index();
            $table->index(['anio', 'numero']);

            $table->string('siaf', 50)->nullable();
            $table->string('prod_proy', 50)->nullable()->index(); // CUI de la obra (para enlazar)
            $table->date('fecha')->nullable();
            $table->date('fecha_aceptacion')->nullable();

            $table->text('item')->nullable();
            $table->string('desmedida', 50)->nullable();

            $table->unsignedInteger('cantidad')->nullable();
            $table->decimal('precio', 12, 2)->nullable();
            $table->unsignedInteger('saldo')->nullable();

            $table->decimal('total_internado', 14, 2)->nullable();
            $table->string('internado', 50)->nullable();

            $table->string('idmeta', 50)->nullable()->index();

            // Totales de movimientos
            $table->decimal('quantity_received', 18, 3)->default(0);
            $table->decimal('quantity_issued',   18, 3)->default(0);
            $table->decimal('quantity_on_hand',  18, 3)->default(0);

            // Metadatos de sync
            $table->timestamp('external_last_seen_at')->nullable();
            $table->string('external_hash', 64)->nullable();

            $table->timestamps();
        });

        // 3) Crear NUEVA movements_kardex (tabla 004)
        Schema::create('movements_kardex', function (Blueprint $table) {
            $table->id();

            // Ahora referencia a ordenes_compra_detallado (no a item_pecosas)
            $table->foreignId('ordenes_compra_detallado_id')
                ->constrained('ordenes_compra_detallado')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Autor del movimiento
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('movement_type');
            $table->date('movement_date')->nullable();
            $table->decimal('amount', 12, 2);
            $table->text('observations')->nullable();

            // Índices
            $table->index('movement_date', 'idx_mk_movement_date');
            $table->index(['ordenes_compra_detallado_id', 'movement_date'], 'idx_mk_item_date');

            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        // Revertir: dropear nuevas tablas y (opcional) recrear las antiguas
        if (Schema::hasTable('movements_kardex')) {
            Schema::drop('movements_kardex');
        }
        if (Schema::hasTable('ordenes_compra_detallado')) {
            Schema::drop('ordenes_compra_detallado');
        }

        // Si quieres, aquí podrías recrear las viejas estructuras (item_pecosas + movements_kardex antiguo)
        // pero como estaban vacías, normalmente no es necesario.

        Schema::enableForeignKeyConstraints();
    }
};

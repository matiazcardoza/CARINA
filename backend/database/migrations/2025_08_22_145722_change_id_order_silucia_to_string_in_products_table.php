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
        Schema::table('products', function (Blueprint $table) {
            // 1) Soltar índices que usan la columna (requerido por MySQL para cambiar tipo)
            $table->dropUnique('products_silucia_pair_unique');    // ['id_order_silucia','id_product_silucia']
            $table->dropIndex('products_id_order_silucia_idx');     // index simple

            // 2) Cambiar tipo: string corto para no chocar con longitudes de índice (utf8mb4)
            $table->string('id_order_silucia', 20)->change();

            // 3) Re-crear índices
            $table->index('id_order_silucia', 'products_id_order_silucia_idx');
            $table->unique(['id_order_silucia','id_product_silucia'], 'products_silucia_pair_unique');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_silucia_pair_unique');
            $table->dropIndex('products_id_order_silucia_idx');

            $table->unsignedBigInteger('id_order_silucia')->change();

            $table->index('id_order_silucia', 'products_id_order_silucia_idx');
            $table->unique(['id_order_silucia','id_product_silucia'], 'products_silucia_pair_unique');
        });
    }
};

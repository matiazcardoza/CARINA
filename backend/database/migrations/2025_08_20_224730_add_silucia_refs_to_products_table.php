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
            // IDs provenientes de SILUCIA (API externa)
            $table->unsignedBigInteger('id_order_silucia')->after('order_id');
            $table->unsignedBigInteger('id_product_silucia')->after('id_order_silucia');

            // Índicesp
            $table->index('id_order_silucia', 'products_id_order_silucia_idx');

            // Unicidad: un mismo detalle de pedido no puede repetirse dentro de una orden
            $table->unique(
                ['id_order_silucia', 'id_product_silucia'],
                'products_silucia_pair_unique'
            );
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
            $table->dropColumn(['id_order_silucia', 'id_product_silucia']);
        });
    }
};

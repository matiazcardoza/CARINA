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
        // Primero se elimina la FK y luego la columna
        // Schema::table('products', function (Blueprint $table) {
        //     // Nombre por convención de Laravel: products_order_id_foreign
        //     $table->dropForeign('products_order_id_foreign');
        //     $table->dropColumn('order_id');
        // });

        // Desvincula la tabla products de la tabla orders
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign('products_order_id_foreign');
            // Para change() necesitas doctrine/dbal
            $table->unsignedBigInteger('order_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Volver a crear la columna y su FK (por si haces rollback)
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable(false)->change();

            // Restauramos la FK original
            $table->foreign('order_id')
                ->references('id')
                ->on('orders_silucia')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }
};

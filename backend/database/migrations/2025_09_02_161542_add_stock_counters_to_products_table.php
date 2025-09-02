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
            // Contadores acumulados
            $table->decimal('in_qty', 20, 4)->default(0)->after('unit_price');
            $table->decimal('out_qty', 20, 4)->default(0)->after('in_qty');
            $table->decimal('stock_qty', 20, 4)->default(0)->after('out_qty');

            // Info útil para auditoría/UI
            $table->timestamp('last_movement_at')->nullable()->after('stock_qty');

            // Opcional (si aún no lo tienes): par SILUCIA único
            // $table->unique(['id_order_silucia','id_product_silucia'], 'uniq_silucia_pair');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['in_qty','out_qty','stock_qty','last_movement_at']);
            // $table->dropUnique('uniq_silucia_pair');
        });
    }
};

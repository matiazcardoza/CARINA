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
        Schema::create('movements_kardex', function (Blueprint $table) {
            // $table->id('id');
            $table->id();

            // $table->foreignId('item_pecosa_id')->constrained('item_pecosas')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('ordenes_compra_detallado_id')->constrained('ordenes_compra_detallado')->cascadeOnUpdate()->cascadeOnDelete();

            // autor del movimiento
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // si borran al usuario, se conserva el movimiento con created_by = null

            $table->string('movement_type');
            $table->date('movement_date')->nullable();
            $table->decimal('amount', 12, 2);
            $table->text('observations')->nullable();
            // $table->decimal('final_balance')->nullable();
            $table->index('movement_date', 'idx_mk_movement_date');
            // $table->index(['item_pecosa_id', 'movement_date'], 'idx_mk_item_date');
            $table->index(['ordenes_compra_detallado_id', 'movement_date'], 'idx_mk_item_date');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('movements_kardex');
        Schema::enableForeignKeyConstraints();
    }
};


            // $table->id('id');
            // $table->unsignedBigInteger('product_id');
            // $table->string('movement_type')->nullable();
            // $table->date('movement_date')->nullable();
            // $table->decimal('amount')->nullable();
            // $table->decimal('final_balance')->nullable();
            // $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade')->onUpdate('cascade');
            // $table->timestamps();
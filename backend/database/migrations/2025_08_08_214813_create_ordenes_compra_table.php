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
        Schema::create('ordenes_compra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obra_id')->constrained('obras')->cascadeOnDelete();
            $table->string('ext_order_id', 120);            // id externo de tu API
            $table->date('fecha')->nullable();
            $table->string('proveedor', 180)->nullable();
            $table->decimal('monto_total', 14, 2)->nullable();
            $table->timestamps();
            $table->unique(['obra_id', 'ext_order_id']);   // evita duplicados por obra
            $table->index(['obra_id', 'fecha']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ordenes_compra');
    }
};

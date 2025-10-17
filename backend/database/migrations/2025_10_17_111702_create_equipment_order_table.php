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
        Schema::create('equipment_order', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('order_silucia_id')->nullable();
            $table->string('machinery_equipment')->nullable();
            $table->string('ability')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('year')->nullable();
            $table->string('plate')->nullable();
            $table->foreign('order_silucia_id')->references('id')->on('orders_silucia')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipment_order');
    }
};

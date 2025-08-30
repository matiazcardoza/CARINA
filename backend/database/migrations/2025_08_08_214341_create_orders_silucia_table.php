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
        Schema::create('orders_silucia', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('silucia_id')->unique();
            $table->string('order_type')->nullable();
            $table->string('supplier')->nullable();
            $table->string('ruc_supplier')->nullable();
            $table->string('machinery_equipment')->nullable();
            $table->string('ability')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('year')->nullable();
            $table->string('plate')->nullable();
            $table->date('delivery_date')->nullable();
            $table->integer('deadline_day')->nullable();
            $table->integer('state')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders_silucia');
    }
};

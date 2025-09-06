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
        Schema::create('services', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('order_id')->unique()->nullable();
            $table->unsignedBigInteger('goal_id');
            $table->string('operator')->nullable();
            $table->string('description')->nullable();
            $table->string('goal_project')->nullable();
            $table->longText('goal_detail')->nullable();
            $table->decimal('fuel_consumed', 8, 2)->nullable();
            $table->integer('state')->default(1);
            $table->integer('state_closure')->default(1);
            $table->foreign('order_id')->references('id')->on('orders_silucia')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};

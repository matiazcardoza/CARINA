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
        Schema::create('machinery_consumables', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('daily_part_id')->nullable();
            $table->string('name')->nullable();
            $table->string('unit_measure')->nullable();
            $table->foreign('daily_part_id')->references('id')->on('daily_parts')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('machinery_consumables');
    }
};

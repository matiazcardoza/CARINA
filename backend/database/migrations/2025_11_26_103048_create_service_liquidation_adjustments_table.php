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
        Schema::create('service_liquidation_adjustments', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('service_id');
            $table->json('adjusted_data');
            $table->unsignedInteger('num_reg')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->integer('state_valorized')->default(1);
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_liquidation_adjustments');
    }
};

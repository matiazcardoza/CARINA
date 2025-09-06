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
        Schema::create('daily_parts', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('movement_kardex_id')->nullable();
            $table->string('description')->nullable();
            $table->string('occurrences')->nullable();
            $table->date('work_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('initial_fuel', 8, 2)->nullable();
            $table->time('time_worked')->default('00:00:00')->nullable();
            $table->integer('state')->default(1);
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_parts');
    }
};

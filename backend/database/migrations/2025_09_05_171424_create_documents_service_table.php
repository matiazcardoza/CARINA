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
        Schema::create('documents_service', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('service_id')->nullable();
            $table->string('path_request')->nullable();
            $table->string('path_report_auth')->nullable();
            $table->string('path_liquidation')->nullable();
            $table->string('path_valorizacion')->nullable();
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
        Schema::dropIfExists('documents_service');
    }
};

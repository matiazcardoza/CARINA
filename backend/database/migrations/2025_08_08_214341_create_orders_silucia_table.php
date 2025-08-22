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
            $table->date('issue_date')->nullable();
            $table->string('goal_project')->nullable();
            $table->longText('goal_detail')->nullable();
            $table->longText('api_date')->nullable();
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

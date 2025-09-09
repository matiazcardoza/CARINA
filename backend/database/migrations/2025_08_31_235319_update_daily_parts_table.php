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
        // Schema::table('daily_parts', function (Blueprint $table) {
        //     $table->unsignedBigInteger('products_id')->after('service_id')->nullable();
        //     $table->foreign('products_id')->references('id')->on('products')->onDelete('cascade')->onUpdate('cascade');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('daily_parts', function (Blueprint $table) {
        //     $table->dropForeign(['products_id']);
        //     $table->dropColumn('products_id');
        // });
    }
};

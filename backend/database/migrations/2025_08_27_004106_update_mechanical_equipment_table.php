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
        Schema::table('services', function (Blueprint $table) {
            $table->unsignedBigInteger('mechanical_equipment_id')->after('order_id')->unique()->nullable();
            $table->foreign('mechanical_equipment_id')->references('id')->on('mechanical_equipment')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['mechanical_equipment_id']);
            $table->dropColumn('mechanical_equipment_id');
        });
    }
};

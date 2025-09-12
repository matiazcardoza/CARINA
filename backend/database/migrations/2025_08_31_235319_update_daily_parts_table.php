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
        Schema::table('daily_parts', function (Blueprint $table) {
            $table->unsignedBigInteger('itemPecosa_id')->after('service_id')->nullable();
            $table->foreign('itemPecosa_id')->references('id')->on('item_pecosas')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_parts', function (Blueprint $table) {
            $table->dropForeign(['itemPecosa_id']);
            $table->dropColumn('itemPecosa_id');
        });
    }
};

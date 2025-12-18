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
            $table->decimal('gasolina', 10, 2)->nullable()->after('initial_fuel');
            $table->unsignedBigInteger('shift_id')->nullable()->after('movement_kardex_id');
            $table->unsignedBigInteger('operator_id')->nullable()->after('shift_id');
            $table->foreign('operator_id')->references('id')->on('operators')->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_parts', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropColumn('shift_id');
            $table->dropForeign(['operator_id']);
            $table->dropColumn('operator_id');
        });
    }
};

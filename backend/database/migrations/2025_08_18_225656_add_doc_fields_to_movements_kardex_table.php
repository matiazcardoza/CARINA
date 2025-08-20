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
        Schema::table('movements_kardex', function (Blueprint $table) {
                        // Orden deseado:
            // id, product_id, movement_date, class, number, movement_type, amount, observations, final_balance, timestamps

            $table->string('class', 10)->nullable()->after('movement_date');  // ej: 'FC', 'BV', 'NC'
            $table->string('number', 50)->nullable()->after('class');         // string para conservar ceros a la izquierda (ej: '0001')
            $table->text('observations')->nullable()->after('amount');        // comentarios largos
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movements_kardex', function (Blueprint $table) {
            $table->dropColumn(['class', 'number', 'observations']);
        });
    }
};

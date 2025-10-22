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
        Schema::table('orders_silucia', function (Blueprint $table) {
            $table->dropColumn('operator');
            $table->dropColumn('machinery_equipment');
            $table->dropColumn('ability');
            $table->dropColumn('brand');
            $table->dropColumn('model');
            $table->dropColumn('serial_number');
            $table->dropColumn('year');
            $table->dropColumn('plate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders_silucia', function (Blueprint $table) {
            $table->string('operator')->nullable()->after('medida_id');
            $table->string('machinery_equipment')->nullable()->after('ruc_supplier');
            $table->string('ability')->nullable()->after('machinery_equipment');
            $table->string('brand')->nullable()->after('ability');
            $table->string('model')->nullable()->after('brand');
            $table->string('serial_number')->nullable()->after('model');
            $table->string('year')->nullable()->after('serial_number');
            $table->string('plate')->nullable()->after('year');
        });
    }
};

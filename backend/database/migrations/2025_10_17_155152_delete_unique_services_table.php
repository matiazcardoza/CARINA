<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropUnique('order_id');
            $table->dropUnique('mechanical_equipment_id');
            $table->index('order_id');
            $table->index('mechanical_equipment_id');
        });
    }

    public function down()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->unique('order_id', 'services_order_id_unique');
            $table->unique('mechanical_equipment_id', 'services_mechanical_equipment_id_unique');
            $table->dropIndex(['order_id']);
            $table->dropIndex(['mechanical_equipment_id']);
        });
    }
};

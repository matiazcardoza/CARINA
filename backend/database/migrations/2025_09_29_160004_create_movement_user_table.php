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
        Schema::create('movement_user', function (Blueprint $table) {
            $table->unsignedBigInteger('movement_kardex_id');
            $table->unsignedBigInteger('user_id');

            // metadatos del vínculo (igual que antes) (no usaresmo estas dos columans a mi no me sirven de nada)
            // $table->string('role')->nullable();      // p.ej. "entrega", "recepción"
            // $table->string('note')->nullable();
            $table->timestamp('attached_at')->useCurrent();

            $table->foreign('movement_kardex_id')
                ->references('id')->on('movements_kardex')
                ->onDelete('cascade')->onUpdate('cascade');

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade')->onUpdate('cascade');

            // evita duplicados del mismo user en el mismo movimiento
            $table->primary(['movement_kardex_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movement_user');
    }
};

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
        // Schema::create('movement_person', function (Blueprint $table) {
        //     $table->unsignedBigInteger('movement_kardex_id');
        //     $table->string('person_dni', 8);

        //     // Campos opcionales en el vínculo
        //     $table->string('role')->nullable();      // p.ej. "entrega", "recepción"
        //     $table->string('note')->nullable();
        //     $table->timestamp('attached_at')->useCurrent();

        //     $table->foreign('movement_kardex_id')
        //         ->references('id')->on('movements_kardex')
        //         ->onDelete('cascade')->onUpdate('cascade');

        //     $table->foreign('person_dni')
        //         ->references('dni')->on('people')
        //         ->onDelete('cascade')->onUpdate('cascade');

        //     $table->primary(['movement_kardex_id', 'person_dni']); // evita duplicados
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::disableForeignKeyConstraints();
        // Schema::dropIfExists('movement_person');
        // Schema::enableForeignKeyConstraints();
    }
};

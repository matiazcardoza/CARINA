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
        Schema::create('people', function (Blueprint $table) {
            $table->string('dni', 8)->primary();      // id = DNI (string para preservar ceros)
            $table->string('first_lastname')->nullable();
            $table->string('second_lastname')->nullable();
            $table->string('names')->nullable();
            $table->string('full_name')->nullable();
            $table->string('civil_status')->nullable();
            $table->string('address')->nullable();
            $table->string('ubigeo')->nullable();     // "PUNO/PUNO/PUNO"
            $table->string('ubg_department')->nullable();
            $table->string('ubg_province')->nullable();
            $table->string('ubg_district')->nullable();
            $table->longText('photo_base64')->nullable(); // foto en base64 (opcional)
            // $table->json('raw')->nullable();          // respuesta completa RENIEC
            $table->timestamp('reniec_consulted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};

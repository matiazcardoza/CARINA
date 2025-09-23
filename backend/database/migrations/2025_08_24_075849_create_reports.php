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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            // Polimórfico: cualquier origen (Product, FuelOrder, ...)
            // esta linea crea dos columnas "reportable_id" y "reportable_type"
            $table->morphs('reportable');            

            // aqui se guarda la direccion fisica donde el pdf esta almacenaod
            $table->string('pdf_path');
            
            // Columnas usadas para guarda informacion exclusiva para la firma digital
            // --------------------------------------------------------------------------
            // cantidad de paginas que contiene el pdf (es util para saber donde insertar la firma)
            $table->unsignedInteger('pdf_page_number')->nullable();
            // estado del pdf en el flujo de firmas
            $table->enum('status', ['in_progress','completed','cancelled', 'failed'])->default('in_progress');

            // Indica a que paso le toca firmar
            $table->unsignedInteger('current_step')->default(1);

            // guarda informacion de los datos que se usaron para generar el pdf, como rangos de fecha, filtros aplicados etc
            $table->json('generation_params')->nullable();

            // plazo valido para que se pueda firmar, si el plazo esta fuera de estos periodos, entonces ya no se podrá firmar
            $table->timestamp('signing_starts_at')->nullable();
            $table->timestamp('signing_ends_at')->nullable();

            // Indica quien fue el que creo el pdf
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Indices
            $table->index(['signing_starts_at','signing_ends_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};

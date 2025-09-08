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
            $table->morphs('reportable');            // reportable_id, reportable_type

            // Archivo PDF
            $table->string('pdf_path');              // ej: reports/2025/09/08/abc123.pdf (ruta relativa en disk=local)
            $table->unsignedInteger('pdf_page_number')->nullable();
            $table->string('latest_pdf_path')->nullable(); // si guardas “versión firmada”
            $table->enum('status', ['in_progress','completed','cancelled'])->default('in_progress');

            // Metadatos útiles
            $table->string('category')->nullable();  // 'kardex' | 'fuel_order' | ...
            $table->string('subtype')->nullable();   // opcional (filtros/variantes)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
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

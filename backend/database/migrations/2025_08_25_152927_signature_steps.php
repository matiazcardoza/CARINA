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
        Schema::create('signature_steps', function (Blueprint $table) {
            $table->id();
            // $table->unsignedBigInteger('signature_flow_id');
            // Steps cuelgan del reporte
            $table->foreignId('report_id')->constrained('reports')->cascadeOnDelete();

            // el oden en el que debe ser firmado el pdf
            $table->unsignedInteger('order');              // 1..N

            // Firmante: usuario específico o rol
            $table->unsignedBigInteger('user_id')->nullable(); // opcional: asignado directo
            $table->string('role')->nullable();                // almacenero|administrador|residente de obra|supervisor

            // Sello visual (si lo usas)
            $table->unsignedInteger('page')->nullable();
            $table->unsignedInteger('pos_x')->nullable();
            $table->unsignedInteger('pos_y')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            // $table->string('status', 20)->default('pending'); // pending|signed|rejected|skipped
            $table->enum('status', ['pending','signed','rejected','skipped'])
                  ->default('pending')->index();

            // Motivo/observaciones (rechazo, etc.)
            $table->text('comment')->nullable();

            // fecha en la que fue firmado
            $table->timestamp('signed_at')->nullable();

            // esta columna indica quien fue el que firmo realmente el pdf
            // $table->unsignedBigInteger('signed_by')->nullable();
            $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('provider')->nullable();           // manual (ses) |firma_peru|reniec
            
            // Seguridad para callbacks
            $table->string('callback_token', 64)->nullable()->unique();

            // SIN signed_pdf_path -> NO almacenamos versiones
            // antes de firmar verificamos si el pdf que queremos firmar es el pdf que vamos a firmar. cuando el pdf firmado 
            // (opcional) hash para auditoría sin guardar el archivo aparte
            $table->string('sha256', 64)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signature_steps');
    }
};

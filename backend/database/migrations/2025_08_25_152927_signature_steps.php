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
            $table->unsignedBigInteger('signature_flow_id');
            $table->unsignedInteger('order');              // 1..N
            $table->string('role');                        // almacenero|administrador|residente de obra|supervisor
            $table->unsignedBigInteger('user_id')->nullable(); // opcional: asignado directo
            // Sello visual (si lo usas)
            $table->unsignedInteger('page')->nullable();
            $table->unsignedInteger('pos_x')->nullable();
            $table->unsignedInteger('pos_y')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();

            $table->string('status', 20)->default('pending'); // pending|signed|rejected|skipped
            $table->timestamp('signed_at')->nullable();
            $table->unsignedBigInteger('signed_by')->nullable();
            $table->string('provider')->nullable();           // manual|firma_peru|reniec
            $table->string('provider_tx_id')->nullable();
            $table->string('certificate_cn')->nullable();
            $table->string('certificate_serial')->nullable();
            $table->string('callback_token', 64)->nullable()->index();

            // SIN signed_pdf_path -> NO almacenamos versiones
            // (opcional) hash para auditoría sin guardar el archivo aparte
            $table->string('sha256', 64)->nullable();

            $table->timestamps();
            $table->foreign('signature_flow_id')->references('id')->on('signature_flows')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

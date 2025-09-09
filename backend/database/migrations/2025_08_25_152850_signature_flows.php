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
        Schema::create('signature_flows', function (Blueprint $table) {
            $table->id();
            // esta columna debe eliminarse depues, pues quedará obsoleto
            // $table->unsignedBigInteger('kardex_report_id');
            // $table->foreignId('report_id')->after('id');

            // Relación nueva: cada flow pertenece a un Report genérico
            $table->foreignId('report_id')
                  ->constrained('reports')        // FK -> reports.id
                  ->cascadeOnDelete();            // si borras el Report, se elimina el flow

            $table->unsignedInteger('current_step')->default(1);
            $table->string('status', 20)->default('in_progress'); // in_progress|completed|cancelled
            $table->timestamps();
            $table->unique('report_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signature_flows');
    }
};

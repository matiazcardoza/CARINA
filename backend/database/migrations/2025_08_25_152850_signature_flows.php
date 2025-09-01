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
            $table->unsignedBigInteger('kardex_report_id');
            $table->unsignedInteger('current_step')->default(1);
            $table->string('status', 20)->default('in_progress'); // in_progress|completed|cancelled
            $table->timestamps();
            $table->foreign('kardex_report_id')->references('id')->on('kardex_reports')->cascadeOnDelete();
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

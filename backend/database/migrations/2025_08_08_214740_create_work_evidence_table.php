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
        Schema::create('work_evidence', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('daily_part_id');
            $table->string('evidence_path')->nullable();
            $table->foreign('daily_part_id')->references('id')->on('daily_parts')->onDelete('cascade')->onUpdate('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_evidence');
    }
};

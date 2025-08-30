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
        Schema::create('signature_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('signature_flow_id');
            $table->unsignedBigInteger('signature_step_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event'); // flow_created|step_signed|step_rejected|callback_received|file_replaced...
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->foreign('signature_flow_id')->references('id')->on('signature_flows')->cascadeOnDelete();
            $table->foreign('signature_step_id')->references('id')->on('signature_steps')->nullOnDelete();
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

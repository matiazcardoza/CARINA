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
        Schema::create('obras', function (Blueprint $t) {
            $t->id();
            $t->string('nombre', 200);
            $t->string('codigo', 50)->unique();
            $t->timestamps();
        });

        Schema::create('obra_user', function (Blueprint $t) {
            $t->id();
            $t->foreignId('obra_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['obra_id','user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obra_user');
        Schema::dropIfExists('obras');
    }
};

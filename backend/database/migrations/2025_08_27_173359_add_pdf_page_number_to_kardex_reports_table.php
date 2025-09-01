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
        Schema::table('kardex_reports', function (Blueprint $table) {
            $table->integer('pdf_page_number')->nullable();       
        });

        // Schema::create('kardex_reports', function (Blueprint $table) {
        //     $table->id();
        //     $table->unsignedBigInteger('product_id');
        //     $table->string('pdf_path');                 // ej: silucia_product_reports/kardex_...pdf
        //     $table->date('from_date')->nullable();
        //     $table->date('to_date')->nullable();
        //     $table->string('type')->nullable();         // entrada|salida|todos
        //     $table->string('status', 20)->default('in_progress'); // in_progress|completed|cancelled
        //     $table->unsignedBigInteger('created_by')->nullable();
        //     $table->timestamps();
        //     $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('kardex_reports') && Schema::hasColumn('kardex_reports', 'pdf_page_number')) {
            Schema::table('kardex_reports', function (Blueprint $table) {
                $table->dropColumn('pdf_page_number');
            });
        }
    }
};

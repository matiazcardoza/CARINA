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
        Schema::table('products', function (Blueprint $table) {
                       // Campos copiados desde la API SILUCIA (objeto silucia_product)
            $table->string('numero', 30)->nullable()->after('id_product_silucia');
            $table->date('fecha')->nullable()->after('numero');

            // OJO: en tu payload la clave es "detalles_orden" (plural).
            // Usamos ese nombre para evitar confusiones.
            $table->text('detalles_orden')->nullable()->after('fecha');

            $table->string('rsocial', 255)->nullable()->after('detalles_orden');
            $table->string('ruc', 11)->nullable()->after('rsocial'); // RUC Perú = 11 dígitos
            $table->string('item', 255)->nullable()->after('ruc');
            $table->text('detalle')->nullable()->after('item');

            $table->decimal('cantidad', 12, 2)->nullable()->after('detalle');
            $table->string('desmedida', 50)->nullable()->after('cantidad');
            $table->decimal('precio', 12, 2)->nullable()->after('desmedida');
            $table->decimal('total_internado', 12, 2)->nullable()->after('precio');
            $table->decimal('saldo', 12, 2)->nullable()->after('total_internado');

            // Nombre del PDF generado (por ejemplo: kardex_03721_254049_2025-08-08.pdf)
            $table->string('pdf_filename', 255)->nullable()->after('saldo');

            // Índice útil si consultas por RUC
            $table->index('ruc', 'products_ruc_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_ruc_idx');
            $table->dropColumn([
                'numero',
                'fecha',
                'detalles_orden',
                'rsocial',
                'ruc',
                'item',
                'detalle',
                'cantidad',
                'desmedida',
                'precio',
                'total_internado',
                'saldo',
                'pdf_filename',
            ]);
        });
    }
};

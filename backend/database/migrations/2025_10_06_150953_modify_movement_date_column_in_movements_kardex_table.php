<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        // 1) Eliminar la columna DATE (también elimina sus índices)
        /**
         * la eliminacion lo hacemos en un bloque separado pues no se puede eliminar y crear una 
         * columna en una sola ejecutcion
         */
        // Schema::table('movements_kardex', function (Blueprint $table) {
        //     $table->dropColumn('movement_date');
        // });
        // if (Schema::hasColumn('movements_kardex', 'movement_date')) {
        //     Schema::table('movements_kardex', function (Blueprint $table) {
        //         $table->dropColumn('movement_date');
        //     });
        // }
        
        // Schema::table('movements_kardex', function (Blueprint $table) {
                    
        //     // quitar índices que usan movement_date (si existen)
        //     // $table->dropIndex('idx_mk_movement_date');
        //     if (DB::select("SHOW INDEX FROM movements_kardex WHERE Key_name = 'idx_mk_movement_date'")) {
        //         Schema::table('movements_kardex', function (Blueprint $table) {
        //             $table->dropIndex('idx_mk_movement_date');
        //         });
        //     }
        //     // $table->dropIndex('idx_mk_item_date');
        //     if (DB::select("SHOW INDEX FROM movements_kardex WHERE Key_name = 'idx_mk_item_date'")) {
        //         Schema::table('movements_kardex', function (Blueprint $table) {
        //             $table->dropIndex('idx_mk_item_date');
        //         });
        //     }

        //     // eliminar la columna tipo DATE
        //     // $table->dropColumn('movement_date');

        //     // volver a crearla como DATETIME (sin ms)
        //     $table->dateTime('movement_date')->nullable()->after('movement_type');

        //     // recrear índices
        //     $table->index('movement_date', 'idx_mk_movement_date');
        //     $table->index(['ordenes_compra_detallado_id', 'movement_date'], 'idx_mk_item_date');
        
        // });
        Schema::disableForeignKeyConstraints();
        if (Schema::hasTable('movements_kardex')) {
            Schema::drop('movements_kardex');
        }

        Schema::create('movements_kardex', function (Blueprint $table) {
            $table->id();

            // Ahora referencia a ordenes_compra_detallado (no a item_pecosas)
            $table->foreignId('ordenes_compra_detallado_id')
                ->constrained('ordenes_compra_detallado')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Autor del movimiento
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('movement_type');
            $table->dateTime('movement_date')->nullable();
            $table->decimal('amount', 12, 2);
            $table->text('observations')->nullable();

            // Índices
            $table->index('movement_date', 'idx_mk_movement_date');
            $table->index(['ordenes_compra_detallado_id', 'movement_date'], 'idx_mk_item_date');

            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // if (Schema::hasColumn('movements_kardex', 'movement_date')) {
        //     Schema::table('movements_kardex', function (Blueprint $table) {
        //         $table->dropColumn('movement_date');
        //     });
        // }
        // // Schema::table('movements_kardex', function (Blueprint $table) {
        // //     $table->dropColumn('movement_date');
        // // });

        // Schema::table('movements_kardex', function (Blueprint $table) {
        //     $table->dropIndex('idx_mk_movement_date');
        //     $table->dropIndex('idx_mk_item_date');

        //     $table->date('movement_date')->nullable()->after('movement_type');

        //     $table->index('movement_date', 'idx_mk_movement_date');
        //     $table->index(['ordenes_compra_detallado_id', 'movement_date'], 'idx_mk_item_date');
        // });
        Schema::disableForeignKeyConstraints();
        if (Schema::hasTable('movements_kardex')) {
            Schema::drop('movements_kardex');
        }
        Schema::enableForeignKeyConstraints();
    }
};

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
        Schema::create('movements_kardex', function (Blueprint $table) {
            // $table->id('id');
            $table->id();
            // $table->unsignedBigInteger('product_id');
            $table->foreignId('item_pecosa_id')->constrained('item_pecosas')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('movement_type');
            $table->date('movement_date')->nullable();
            $table->decimal('amount', 12, 2);
            $table->text('observations')->nullable();
            // $table->decimal('final_balance')->nullable();
            $table->index('movement_date', 'idx_mk_movement_date');
            $table->index(['item_pecosa_id', 'movement_date'], 'idx_mk_item_date');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('movements_kardex');
        Schema::enableForeignKeyConstraints();
    }
};


// $table->decimal('received_quantity', 20, 4)
//           ->default(0)
//           ->after('precio')
//           ->comment('Total quantity received (entries) for this item');

//     $table->decimal('issued_quantity', 20, 4)
//           ->default(0)
//           ->after('received_quantity')
//           ->comment('Total quantity issued (outputs) for this item');

//     $table->decimal('on_hand_quantity', 20, 4)
//           ->default(0)
//           ->after('issued_quantity')
//           ->comment('Current available stock for this item');
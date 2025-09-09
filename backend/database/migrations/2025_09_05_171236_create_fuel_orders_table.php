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
        Schema::create('fuel_orders', function (Blueprint $table) {
            $table->id();

            // Identificación de la orden
            $table->date('fecha');
            $table->string('numero', 20)->nullable();          // N° que muestras en el formulario
            $table->string('orden_compra', 50)->nullable();
            $table->string('componente', 150)->nullable();

            // Grifo y chofer
            $table->string('grifo', 150)->nullable();
            // Chofer: user_id del autenticado que crea la orden
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();

            // Vehículo (FK + snapshot para auditoría)
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->string('vehiculo_marca', 100)->nullable();       // snapshot del formulario
            $table->string('vehiculo_placa', 20)->nullable();        // snapshot del formulario
            $table->string('vehiculo_dependencia', 150)->nullable(); // snapshot del formulario
            $table->string('hoja_viaje', 50)->nullable();
            $table->text('motivo')->nullable();

            // Combustible (UN SOLO registro, como pediste)
            $table->enum('fuel_type', ['gasolina', 'diesel', 'glp']);
            $table->decimal('quantity_gal', 10, 3);   // Glns
            $table->decimal('amount_soles', 12, 2);   // Importe S/

            // Aprobaciones (Supervisor/Inspector y Jefe de Gerencia)
            // NULL = pendiente, 'approved' = aprobado, 'rejected' = rechazado
            // $table->enum('supervisor_status', ['approved', 'rejected'])->nullable();
            // $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            // $table->timestamp('supervisor_at')->nullable();
            // $table->text('supervisor_note')->nullable();

            // $table->enum('manager_status', ['approved', 'rejected'])->nullable();
            // $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            // $table->timestamp('manager_at')->nullable();
            // $table->text('manager_note')->nullable();

            // Índices útiles
            $table->index(['fecha']);
            $table->index(['numero']);
            $table->index(['driver_id']);
            $table->index(['vehicle_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuel_orders');
    }
};

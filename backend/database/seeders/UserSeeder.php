<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // User::create([
        //     'name' => 'admin',
        //     'email' => 'admin@domain.com',
        //     'password' => Hash::make('admin123'),
        //     'email_verified_at' => now(),
        // ]);
        $user_admin = User::updateOrCreate(
            ['email' => 'admin@domain.com'],
            [
                'name' => 'admin',
                'email_verified_at' => now(),      // útil si usas verificación de email (Breeze)
                'password' => Hash::make('admin123'),
                // 'remember_token' => Str::random(10),
            ]
        );
        $user_admin->syncRoles(['almacen_almacenero']);
        
        $almacenero = User::updateOrCreate(
            ['email' => 'almacenero@gmail.com'],
            [
                'name' => 'JULIA MAMANI YAMPASI',
                'email_verified_at' => now(),      // útil si usas verificación de email (Breeze)
                'password' => Hash::make('12345678'),
                // 'remember_token' => Str::random(10),
            ]
        );
        $almacenero->syncRoles(['almacen_almacenero']);

        $administrador_almacen = User::updateOrCreate(
            ['email' => 'administrador@gmail.com'],
            [
                'name' => 'NOHELIA LUZ QUISPE SUNI',
                'email_verified_at' => now(),      // útil si usas verificación de email (Breeze)
                'password' => Hash::make('12345678'),
                // 'remember_token' => Str::random(10),
            ]
        );
        $administrador_almacen->syncRoles(['almacen_administrador']);
        
        $residente = User::updateOrCreate(
            ['email' => 'residente@gmail.com'],
            [
                'name' => 'JORGE ANTONY QUISPE YUCRA',
                'email_verified_at' => now(),      // útil si usas verificación de email (Breeze)
                'password' => Hash::make('12345678'),
                // 'remember_token' => Str::random(10),
            ]
        );
        $residente->syncRoles(['almacen_residente']);

        $supervisor = User::updateOrCreate(
            ['email' => 'supervisor@gmail.com'],
            [
                'name' => 'GUSTAVO CUTIPA GRANDA',
                'email_verified_at' => now(),      // útil si usas verificación de email (Breeze)
                'password' => Hash::make('12345678'),
                // 'remember_token' => Str::random(10),
            ]
        );
        $supervisor->syncRoles(['almacen_supervisor']);

        // usuarios para vales de transporte
                // Chofer dedicado
        $chofer = User::updateOrCreate(
            ['email' => 'chofer@gmail.com'],
            [
                'name' => 'JACKSON',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
            ]
        );
        $chofer->syncRoles(['chofer']);

        // Supervisor de obra dedicado
        $supObra = User::updateOrCreate(
            ['email' => 'supervisor-obra@gmail.com'],
            [
                'name' => 'MARÍA LUQUE',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
            ]
        );
        $supObra->syncRoles(['supervisor']); // o 'inspector' según tu preferencia

        // Jefe de gerencia regional dedicado
        $jefe = User::updateOrCreate(
            ['email' => 'jefe-gerencia@gmail.com'],
            [
                'name' => 'ELMER QUISPE',
                'email_verified_at' => now(),
                'password' => Hash::make('12345678'),
            ]
        );
        $jefe->syncRoles(['jefe']);

        if (class_exists(Vehicle::class)) {
            Vehicle::updateOrCreate(
                ['plate' => 'ABC-123'],
                [
                    'brand' => 'Toyota',
                    // 'dependencia' => 'Infraestructura',
                    'user_id' => $chofer->id,
                ]
            );

            Vehicle::updateOrCreate(
                ['plate' => 'XYZ-987'],
                [
                    'brand' => 'Nissan',
                    // 'dependencia' => 'Logística',
                    'user_id' => $chofer->id,
                ]
            );
        }
        
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

    }
}

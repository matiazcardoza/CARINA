<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

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

    }
}

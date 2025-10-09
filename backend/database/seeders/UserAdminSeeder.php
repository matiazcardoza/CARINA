<?php

namespace Database\Seeders;

use App\Models\Persona;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $super_administrador = Role::findByName('SuperAdministrador_pd', 'api');
        $controlador = Role::findByName('Controlador_pd', 'api');
        $residente = Role::findByName('Residente_pd', 'api');
        $supervisor = Role::findByName('Supervisor_pd', 'api');

        $users = User::firstOrCreate(['email' => 'admin@domain.com'], [
            'name' => 'ADMIN',
            'email' => 'admin@domain.com',
            'password' => Hash::make('admin123'),
            'state' => 1,
        ])->assignRole($super_administrador);

        Persona::create([
            'user_id' => $users->id,
            'num_doc' => '75502353',
            'name' => 'ROYER MATIAZ',
            'last_name' => 'HUANCA CARDOZA'
        ]);

        $user2 = User::firstOrCreate(['email' => 'CONTROLADOR@domain.com'], [
            'name' => 'CONTROLADOR',
            'email' => 'CONTROLADOR@domain.com',
            'password' => Hash::make('12345678'),
            'state' => 1,
        ])->assignRole($controlador);

        Persona::create([
            'user_id' => $user2->id,
            'num_doc' => '12345678',
            'name' => 'CONTROLADOR',
            'last_name' => 'CONTROLADOR'
        ]);

        $user3 = User::firstOrCreate(['email' => 'RESIDENTE@domain.com'], [
            'name' => 'RESIDENTE',
            'email' => 'RESIDENTE@domain.com',
            'password' => Hash::make('12345678'),
            'state' => 1,
        ])->assignRole($residente);

        Persona::create([
            'user_id' => $user3->id,
            'num_doc' => '23456781',
            'name' => 'RESIDENTE',
            'last_name' => 'RESIDENTE'
        ]);

        $user4 = User::firstOrCreate(['email' => 'supervisor@domain.com'], [
            'name' => 'SUPERVISOR',
            'email' => 'SUPERVISOR@domain.com',
            'password' => Hash::make('12345678'),
            'state' => 1,
        ])->assignRole($supervisor);

        Persona::create([
            'user_id' => $user4->id,
            'num_doc' => '34567812',
            'name' => 'SUPERVISOR',
            'last_name' => 'SUPERVISOR'
        ]);
    }
}

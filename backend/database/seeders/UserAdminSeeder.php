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

        $user2 = User::firstOrCreate(['email' => 'controlador@domain.com'], [
            'name' => 'controlador',
            'email' => 'controlador@domain.com',
            'password' => Hash::make('admin123'),
            'state' => 1,
        ])->assignRole($controlador);

        Persona::create([
            'user_id' => $user2->id,
            'num_doc' => '74033068',
            'name' => 'ROYER',
            'last_name' => 'CONTROLADOR'
        ]);

        $super_administrador_almacen = Role::findOrCreate('almacen.superadmin', 'api');

        $user_super_admin = User::firstOrCreate(['email' =>  'admin_almacen@domain.com'], [
            'name'      => 'ADMIN_ALMACEN',
            'email'     => 'admin_almacen@domain.com',
            'password'  => Hash::make('10442312312'),
            'state'     => 1,
        ])->assignRole($super_administrador_almacen);

        Persona::updateOrCreate(
            ['user_id' => $user_super_admin->id],  // clave de búsqueda
            [
                'num_doc'   => '71596800',
                'name'      => 'JUAN C',
                'last_name' => 'AYALA P',
            ]
        );
    }
}

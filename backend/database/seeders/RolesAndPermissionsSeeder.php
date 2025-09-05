<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // laravel guarda en cache roles y permisos para que las consultas sean rapidas, con esto, el chaché se elimina
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        Role::findOrCreate('almacen_almacenero', 'api');
        Role::findOrCreate('almacen_administrador', 'api');
        Role::findOrCreate('almacen_residente', 'api');
        Role::findOrCreate('almacen_supervisor', 'api');


        // roles para vales de transporte
        $guard = 'api'; 
        $roleChofer     = Role::firstOrCreate(['name' => 'chofer',     'guard_name' => $guard]);
        $roleSupervisor = Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => $guard]);
        $roleInspector  = Role::firstOrCreate(['name' => 'inspector',  'guard_name' => $guard]); // opcional
        $roleJefe       = Role::firstOrCreate(['name' => 'jefe',       'guard_name' => $guard]);
    }
}

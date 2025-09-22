<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesWarehouseMovementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       
        // Crear roles
        $super_admin    = Role::firstOrCreate(['name' => 'almacen.superadmin', 'guard_name' => 'api']);
        $almacenero     = Role::firstOrCreate(['name' => 'almacen.operador', 'guard_name' => 'api']);
        $administrador  = Role::firstOrCreate(['name' => 'almacen.administrador', 'guard_name' => 'api']);
        $residente      = Role::firstOrCreate(['name' => 'almacen.residente', 'guard_name' => 'api']);
        $supervisor     = Role::firstOrCreate(['name' => 'almacen.supervisor', 'guard_name' => 'api']);
        // Definir permisos por módulo
        $modules = [
            'kardex_management' => [
                ['name' => 'access_movement_kardex', 'label' => 'Acceder a Gestión del Kardex'],
                ['name' => 'create_new_movement', 'label' => 'Crear un nuevo movimiento'],
                ['name' => 'create_see_movements', 'label' => 'Ver movimientos'],
            ],
            'user_management' => [
                ['name' => 'access_user_management', 'label' => 'Acceder al módulo de gestión del usuario'],

            ],
        ];

                // Crear permisos y asignar roles
        foreach ($modules as $module => $permissions) {
            foreach ($permissions as $perm) {
                $permission = Permission::updateOrCreate(
                    ['name' => $perm['name']],
                    ['label' => $perm['label'], 'module' => ucfirst($module), 'guard_name' => 'api']
                );

                // Asignar reglas según rol
                switch ($module) {
                    case 'kardex_management':
                        $permission->syncRoles([$super_admin, $almacenero, $administrador, $residente, $supervisor]);
                        break;

                    case 'user_management':
                        $permission->syncRoles([$super_admin]);
                        break;
                }
            }
        }

    }



}

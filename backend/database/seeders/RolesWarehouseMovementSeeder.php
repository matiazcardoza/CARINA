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
    public function runx(): void
    {
       
        // Crear roles
        $super_admin    = Role::firstOrCreate(['name' => 'almacen.superadmin', 'guard_name' => 'api']);
        $almacenero     = Role::firstOrCreate(['name' => 'almacen.almacenero', 'guard_name' => 'api']);
        $administrador  = Role::firstOrCreate(['name' => 'almacen.administrador', 'guard_name' => 'api']);
        $residente      = Role::firstOrCreate(['name' => 'almacen.residente', 'guard_name' => 'api']);
        $supervisor     = Role::firstOrCreate(['name' => 'almacen.supervisor', 'guard_name' => 'api']);
        $operario       = Role::firstOrCreate(['name' => 'almacen.operario', 'guard_name' => 'api']);   /** Obrero, trabajadores, persona que reciben los productos que se extraen del almacen */
        // Definir permisos por módulo
        $modules = [
            'kardex_management' => [
                ['name' => 'almacen.access_kardex_management', 'label' => 'Acceder a Gestión del Kardex'],
                ['name' => 'almacen.create_new_movement', 'label' => 'Crear un nuevo movimiento'],
                ['name' => 'almacen.create_see_movements', 'label' => 'Ver movimientos'],
                ['name' => 'almacen.generate_report', 'label' => 'Generar reporte de movimientos almacén'],
                ['name' => 'almacen.create_operator', 'label' => 'Crear operario para movimientos almacén'],
            ],
            'user_management' => [
                ['name' => 'access_user_management', 'label' => 'Acceder al módulo de gestión del usuario'],

            ],
            'obras_management' => [
                ['name' => 'access_obras_management', 'label' =>  'Acceder al módulo de gestión de obras'],
            ]
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
                    case 'obras_management':
                        $permission->syncRoles([$super_admin]);
                        break;
                }
            }
        }

    }


    public function run(): void
    {
        // 1) Crear roles (usa SIEMPRE el mismo guard que vas a autenticar: 'api' o 'web')
        $roles = [
            'almacen.superadmin',
            'almacen.almacenero',
            'almacen.administrador',
            'almacen.residente',
            'almacen.supervisor',
            'almacen.operario',
        ];

        $roleModels = [];
        foreach ($roles as $r) {
            $roleModels[$r] = Role::firstOrCreate([
                'name' => $r,
                'guard_name' => 'api',
            ]);
        }

        // 2) Definir todos los permisos disponibles (con tus metadatos opcionales)
        $allPerms = [
            // Kardex
            ['name' => 'almacen.access_kardex_management',  'label' => 'Acceder a Gestión del Kardex', 'module' => 'Kardex_Management'],
            ['name' => 'almacen.create_new_movement',       'label' => 'Crear un nuevo movimiento',    'module' => 'Kardex_Management'],
            ['name' => 'almacen.generate_report',           'label' => 'Generar reporte',              'module' => 'Kardex_Management'],
            ['name' => 'almacen.create_operator',           'label' => 'Crear operario',               'module' => 'Kardex_Management'],
            ['name' => 'almacen.delete_report',             'label' => 'Eliminar reporte',             'module' => 'Kardex_Management'],

            // Users
            ['name' => 'almacen.access_user_management',    'label' => 'Acceder gestión de usuarios',  'module' => 'User_Management'],

            // Obras
            ['name' => 'almacen.access_obras_management',   'label' => 'Acceder gestión de obras',     'module' => 'Obras_Management'],
        ];

        foreach ($allPerms as $p) {
            Permission::updateOrCreate(
                ['name' => $p['name'], 'guard_name' => 'api'],
                ['label' => $p['label'] ?? null, 'module' => $p['module'] ?? null]
            );
        }

        // 3) MATRIZ rol → permisos específicos (aquí decides la granularidad)
        $rolePermissionMap = [
            // Tiene todo
            'almacen.superadmin' => [
                // Puedes listar todos o usar collect(Permission::pluck('name'))->all() si prefieres abajo
                'almacen.access_kardex_management',
                'almacen.create_new_movement',
                // 'almacen.create_see_movements',
                'almacen.generate_report',
                'almacen.create_operator',
                'almacen.delete_report',
                'almacen.access_user_management',
                'almacen.access_obras_management',
            ],

            // Operación diaria Kardex
            'almacen.almacenero' => [
                'almacen.access_kardex_management',
                'almacen.create_new_movement',
                // 'almacen.create_see_movements',
                'almacen.generate_report',
                'almacen.create_operator',
                'almacen.delete_report'
            ],

            // Admin (gestiona usuarios/obras + reportes, pero no necesariamente crea movimientos)
            'almacen.administrador' => [
                'almacen.access_kardex_management',
                // 'almacen.create_see_movements',
                // 'almacen.generate_report',
                // 'access_user_management',
                // 'access_obras_management',
            ],

            // Residente (consulta + aprueba/valida si agregas ese permiso en el futuro)
            'almacen.residente' => [
                'almacen.access_kardex_management',
                // 'almacen.create_see_movements',
                // 'almacen.generate_report',
            ],

            // Supervisor (consulta + reportes)
            'almacen.supervisor' => [
                'almacen.access_kardex_management',
                // 'almacen.create_see_movements',
                // 'almacen.generate_report',
            ],

            // Operario (solo ver lo que le corresponde)
            'almacen.operario' => [
                'almacen.access_kardex_management',
                // 'almacen.create_see_movements',
            ],
        ];

        // 4) Asignar permisos exactos a cada rol
        foreach ($rolePermissionMap as $roleName => $perms) {
            $role = $roleModels[$roleName];

            // Si quieres que superadmin tenga TODOS los permisos vigentes automáticamente:
            // if ($roleName === 'almacen.superadmin') {
            //     $perms = Permission::where('guard_name', 'api')->pluck('name')->all();
            // }

            $role->syncPermissions($perms); // reemplaza el conjunto; usa givePermissionTo() si quieres incremental
        }
    }


}

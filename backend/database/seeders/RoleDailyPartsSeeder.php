<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleDailyPartsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear roles
        $super_admin = Role::firstOrCreate(['name' => 'Super Administrador', 'guard_name' => 'api']);
        $controlador = Role::firstOrCreate(['name' => 'Controlador', 'guard_name' => 'api']);
        $residente   = Role::firstOrCreate(['name' => 'Residente', 'guard_name' => 'api']);
        $supervisor  = Role::firstOrCreate(['name' => 'Supervisor', 'guard_name' => 'api']);

        // Definir permisos por módulo
        $modules = [
            'dashboard' => [
                ['name' => 'access_dashboard', 'label' => 'Acceder al Dashboard'],
            ],
            'work_log' => [
                ['name' => 'access_work_log', 'label' => 'Acceder a Work Log'],
                ['name' => 'create_work_log', 'label' => 'Crear Work Log'],
                ['name' => 'edit_work_log', 'label' => 'Editar Work Log'],
                ['name' => 'delete_work_log', 'label' => 'Eliminar Work Log'],
            ],
            'equipo_mecanico' => [
                ['name' => 'access_equipo_mecanico', 'label' => 'Acceder a Equipo Mecánico'],
                ['name' => 'create_equipo_mecanico', 'label' => 'Crear Equipo Mecánico'],
                ['name' => 'edit_equipo_mecanico', 'label' => 'Editar Equipo Mecánico'],
                ['name' => 'delete_equipo_mecanico', 'label' => 'Eliminar Equipo Mecánico'],
            ],
            'reportes' => [
                ['name' => 'access_reportes', 'label' => 'Acceder a Reportes'],
                ['name' => 'generate_reportes', 'label' => 'Generar Reportes'],
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
                    case 'dashboard':
                        $permission->syncRoles([$super_admin, $controlador, $residente, $supervisor]);
                        break;

                    case 'work_log':
                        $permission->syncRoles([$super_admin, $controlador, $residente]);
                        break;

                    case 'equipo_mecanico':
                        $permission->syncRoles([$super_admin, $controlador]);
                        break;

                    case 'reportes':
                        $permission->syncRoles([$super_admin, $supervisor]);
                        break;
                }
            }
        }
    }
}

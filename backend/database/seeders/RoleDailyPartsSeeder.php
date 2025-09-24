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
        $super_admin = Role::firstOrCreate(['name' => 'SuperAdministrador_pd', 'guard_name' => 'api']);
        $admin_em = Role::firstOrCreate(['name' => 'Admin_equipoMecanico_pd', 'guard_name' => 'api']);
        $controlador = Role::firstOrCreate(['name' => 'Controlador_pd', 'guard_name' => 'api']);
        $residente   = Role::firstOrCreate(['name' => 'Residente_pd', 'guard_name' => 'api']);
        $supervisor  = Role::firstOrCreate(['name' => 'Supervisor_pd', 'guard_name' => 'api']);

        // Definir permisos por módulo
        $modules = [
            'dashboard' => [
                ['name' => 'access_dashboard', 'label' => 'Acceder al Dashboard'],
                ['name' => 'download_reports', 'label' => 'Descargar Reportes'],
            ],
            'work_log' => [
                ['name' => 'access_work_log', 'label' => 'Acceder a Registro de Partes Diarios'],
                ['name' => 'import_work_log', 'label' => 'Importar Registro de Partes Diarios'],
                ['name' => 'delete_work_log', 'label' => 'Eliminar Registro de Partes Diarios'],
            ],
            'work_log_id' => [
                ['name' => 'access_work_log_id', 'label' => 'Acceder a Trabajo Diario'],
                ['name' => 'create_work_log_id', 'label' => 'Crear Trabajo Diario'],
                ['name' => 'edit_work_log_id', 'label' => 'Editar Trabajo Diario'],
                ['name' => 'delete_work_log_id', 'label' => 'Eliminar Trabajo Diario'],
                ['name' => 'completed_work_log_id', 'label' => 'Completar Trabajo Diario'],
                ['name' => 'generate_pdf_work_log_id', 'label' => 'Generar PDF Trabajo Diario'],
                ['name' => 'signature_work_log_id', 'label' => 'Firmar Trabajo Diario'],
            ],
            'equipo_mecanico' => [
                ['name' => 'access_equipo_mecanico', 'label' => 'Acceder a Equipo Mecánico'],
                ['name' => 'create_equipo_mecanico', 'label' => 'Crear Equipo Mecánico'],
                ['name' => 'edit_equipo_mecanico', 'label' => 'Editar Equipo Mecánico'],
                ['name' => 'delete_equipo_mecanico', 'label' => 'Eliminar Equipo Mecánico'],
                ['name' => 'asigned_equipo_mecanico', 'label' => 'Asignar Obra'],
            ],
            'reportes' => [
                ['name' => 'access_reportes', 'label' => 'Acceder a Reportes'],
                ['name' => 'generate_reportes', 'label' => 'Generar Reportes'],
            ],
            'tray_signature' => [
                ['name' => 'access_tray_signature', 'label' => 'Acceder a Bandeja de firma'],
                ['name' => 'sing_tray_signature', 'label' => 'Firmar Documento'],
            ],
        ];

        foreach ($modules as $module => $permissions) {
            foreach ($permissions as $perm) {
                $permission = Permission::updateOrCreate(
                    ['name' => $perm['name']],
                    ['label' => $perm['label'], 'module' => ucfirst($module), 'guard_name' => 'api']
                );

                switch ($module) {
                    case 'dashboard':
                        $permission->syncRoles([$super_admin, $controlador, $residente, $supervisor]);
                        break;

                    case 'work_log':
                        if ($perm['name'] === 'delete_work_log' || $perm['name'] === 'import_work_log') {
                            $permission->syncRoles([$super_admin, $admin_em]);
                        } else {
                            $permission->syncRoles([$super_admin, $controlador, $residente, $supervisor]);
                        }
                        break;

                    case 'work_log_id':
                        if ($perm['name'] === 'edit_work_log_id' || $perm['name'] === 'delete_work_log_id') {
                            $permission->syncRoles([$super_admin]);
                        } else {
                            $permission->syncRoles([$super_admin, $controlador]);
                        }
                        break;

                    case 'equipo_mecanico':
                        $permission->syncRoles([$super_admin, $admin_em]);
                        break;

                    case 'reportes':
                        if ($perm['name'] === 'generate_reportes') {
                            $permission->syncRoles([$controlador, $residente, $supervisor]);
                        } else {
                            $permission->syncRoles([$super_admin, $admin_em, $controlador, $residente, $supervisor]);
                        }
                        break;
                    
                    case 'tray_signature':
                        $permission->syncRoles([$super_admin, $controlador, $residente, $supervisor]);
                        break;
                }
            }
        }
    }
}

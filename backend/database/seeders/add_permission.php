<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class add_permission extends Seeder
{
    public function run(): void
    {
        Permission::updateOrCreate(
            ['name' => 'import_work_log_order'],
            [
                'label' => 'Importar Orden de Trabajo',
                'module' => 'Work_log',
                'guard_name' => 'api',
            ]
        );

        $this->command->info('✅ Permiso "import_work_log_order" creado o actualizado correctamente.');
    }
}

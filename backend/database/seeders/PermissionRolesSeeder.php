<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class PermissionRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Roles de dominio + 1 global "editor" (para el módulo clásico de tu colega)
        $roles = [
            'almacenero_principal',
            'almacenero_auxiliar',
            'visor',
            'admin_obra',
            'editor', // global/clásico (team_id = null)
        ];

        foreach ($roles as $name) {
            Role::firstOrCreate(
                // ['name' => $name, 'guard_name' => 'web'],
                ['name' => $name, 'guard_name' => 'api'],
                // En v6, los roles pueden tener team_id; null => global/único
                // ['team_id' => null]
            );
        }
    }
}

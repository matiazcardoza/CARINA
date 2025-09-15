<?php

namespace Database\Seeders;

use App\Models\Obra;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoTenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    // public function run(): void
    // {
    //     //
    // }
    private function addMember(int $obraId, int $userId): void
    {
        DB::table('obra_user')->updateOrInsert(
            ['obra_id' => $obraId, 'user_id' => $userId],
            ['updated_at' => now(), 'created_at' => now()]
        );
    }

    private function syncRolesForObra(User $user, int $obraId, array $roles): void
    {
        // Fija el team (obra) activo para Spatie v6
        // cuando se asignan roles y permisos en los seeders, se necesita saber el "team" (obra) a la que esta asignandose el rol y el permiso
        setPermissionsTeamId($obraId);                    // <- API oficial teams
        // Limpia relaciones cacheadas antes de leer/escribir en otro team
        $user->unsetRelation('roles')->unsetRelation('permissions');
        // Sincroniza roles EN ESA obra
        $user->syncRoles($roles);
    }

    public function run(): void
    {
        // ---- OBRAS ----
        $obraA = Obra::firstOrCreate(['codigo' => 'OBR-001'], ['nombre' => 'Construcción de Escuela']);
        $obraB = Obra::firstOrCreate(['codigo' => 'OBR-002'], ['nombre' => 'Construcción de Hospital']);
        $obraC = Obra::firstOrCreate(['codigo' => 'OBR-003'], ['nombre' => 'Mantenimiento Carretera 001']);

        // ---- USUARIOS ----
        $pwd = bcrypt('password'); // simple para demo

        $admin = User::firstOrCreate(['email' => 'admin@demo.test'], [
            'name' => 'Admin Demo',
            'password' => $pwd,
        ]);

        $alma = User::firstOrCreate(['email' => 'alma@demo.test'], [
            'name' => 'Alma Almacenera',
            'password' => $pwd,
        ]);

        $aux = User::firstOrCreate(['email' => 'aux@demo.test'], [
            'name' => 'Alex Auxiliar',
            'password' => $pwd,
        ]);

        $viewer = User::firstOrCreate(['email' => 'visor@demo.test'], [
            'name' => 'Violeta Visor',
            'password' => $pwd,
        ]);

        // ---- MEMBRESÍAS (obra_user) ----
        foreach ([$admin, $alma, $aux, $viewer] as $u) {
            $this->addMember($obraA->id, $u->id);
            $this->addMember($obraB->id, $u->id);
        }
        // viewer solo A y B; dejemos C vacío para él
        $this->addMember($obraC->id, $admin->id);
        $this->addMember($obraC->id, $alma->id);
        $this->addMember($obraC->id, $aux->id);

        // ---- ASIGNACIÓN DE ROLES POR OBRA ----
        // Admin: admin_obra en todas
        $this->syncRolesForObra($admin, $obraA->id, ['admin_obra']);
        $this->syncRolesForObra($admin, $obraB->id, ['admin_obra']);
        $this->syncRolesForObra($admin, $obraC->id, ['admin_obra']);

        // Alma: principal en A, auxiliar en B, principal en C
        $this->syncRolesForObra($alma, $obraA->id, ['almacenero_principal']);
        $this->syncRolesForObra($alma, $obraB->id, ['almacenero_auxiliar']);
        $this->syncRolesForObra($alma, $obraC->id, ['almacenero_principal']);

        // Aux: auxiliar en A y C; visor en B
        $this->syncRolesForObra($aux,  $obraA->id, ['almacenero_auxiliar']);
        $this->syncRolesForObra($aux,  $obraB->id, ['visor']);
        $this->syncRolesForObra($aux,  $obraC->id, ['almacenero_auxiliar']);

        // Viewer: visor en A y B; (sin C)
        $this->syncRolesForObra($viewer, $obraA->id, ['visor']);
        $this->syncRolesForObra($viewer, $obraB->id, ['visor']);

        // ---- ROLES GLOBALES (módulo clásico de tu colega) ----
        // Asignamos "editor" sin team para probar endpoints globales
        $admin->assignRole('editor');   // team_id = null
        $viewer->assignRole('editor');  // team_id = null

        // ---- TOKENS SANCTUM para Postman ----
        // Asegúrate que tu modelo User use HasApiTokens (docs Sanctum)
        $tokens = [
            'ADMIN'  => $admin->createToken('postman-admin')->plainTextToken,
            'ALMA'   => $alma->createToken('postman-alma')->plainTextToken,
            'AUX'    => $aux->createToken('postman-aux')->plainTextToken,
            'VISOR'  => $viewer->createToken('postman-viewer')->plainTextToken,
        ];

        foreach ($tokens as $label => $value) {
            $this->command->info("SANCTUM {$label} = {$value}");
        }
    }
}

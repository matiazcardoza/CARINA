<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Models\Obra;
use App\Models\User;
use App\Models\Persona;
use App\Services\UserSiluciaClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;


class ObraImportUsersController extends Controller
{
    
    // public function getSiluciaUsers0(Request $request, Obra $obra, UserSiluciaClient $client)
    // {
    //    // Construye un Request con el idmeta que exige el cliente
    //     $siluciaRequest = new Request([
    //         'idmeta' => $obra->idmeta_silucia,
    //     ]);

    //     // Llama al cliente (SOLO Request)
    //     $externos = $client->fetchPersonalByMeta($siluciaRequest);
    //     Log::info($externos);
    //     Log::info('======================================');
    //     Log::info($client);
    //     $createdUsers = 0;
    //     $updatedPersonas = 0;
    //     $attached = 0;
    //     $skipped = [];

    //     DB::transaction(function () use ($externos, $obra, $client, &$createdUsers, &$updatedPersonas, &$attached, &$skipped) {

    //         foreach ($externos as $row) {
    //             $dni = $client::cleanDni($row['dni'] ?? null);

    //             if (!$dni) { $skipped[] = ['reason'=>'dni_vacio_o_invalido','raw'=>$row['dni'] ?? null]; continue; }

    //             // Nombre completo (usa lo que provee la API)
    //             $nombres  = trim($row['nombres'] ?? '');
    //             $paterno  = trim($row['paterno'] ?? '');
    //             $materno  = trim($row['materno'] ?? '');

    //             // Asegura un email único (si tu API no lo da)
    //             $email = "u{$dni}@silucia.local";

    //             // 1) USER (por email único). Si ya existe, no lo pisa.
    //             $user = User::firstOrCreate(
    //                 ['email' => $email],
    //                 [
    //                     'name'     => trim($nombres.' '.$paterno),
    //                     'password' => bcrypt(Str::random(24)),
    //                     'state'    => 1,
    //                 ]
    //             );
    //             if ($user->wasRecentlyCreated) $createdUsers++;

    //             // 2) PERSONA (clave única: num_doc)
    //             $persona = Persona::updateOrCreate(
    //                 ['num_doc' => $dni],
    //                 [
    //                     'user_id'     => $user->id,
    //                     'name'        => $nombres,
    //                     'last_name'   => trim($paterno.' '.$materno),
    //                     'state'       => 1,
    //                 ]
    //             );
    //             if (!$persona->wasRecentlyCreated) $updatedPersonas++;

    //             // 3) Asignar al pivot obra_user si no está
    //             if (!$obra->members()->where('users.id', $user->id)->exists()) {
    //                 $obra->members()->attach($user->id);
    //                 $attached++;
    //             }

    //             $teamObraId = $obra->id;
    //             app(PermissionRegistrar::class)->setPermissionsTeamId($teamObraId);

    //             $hasRoleInThisObra = \Illuminate\Support\Facades\DB::table('model_has_roles')
    //                 ->where('model_type', \App\Models\User::class)
    //                 ->where('model_id', $user->id)
    //                 ->where('obra_id', $teamObraId)   // <-- clave tenant en el pivot
    //                 ->exists();

    //             // *CHG: Solo si NO tiene roles en ESTA obra, aplicar la lógica
    //             if (!$hasRoleInThisObra) {
    //                 $resolvedRoleName = $this->resolverRolSegunReglas($row);

    //                 if ($resolvedRoleName) {
    //                     $guard = config('auth.defaults.guard', 'web');

    //                     $role = \Spatie\Permission\Models\Role::firstOrCreate(
    //                         [
    //                             'name'       => $resolvedRoleName,
    //                             'guard_name' => $guard,
    //                             'obra_id'    => $teamObraId, // **clave tenant en roles**
    //                         ],
    //                         []
    //                     );

    //                     // con el team seteado arriba, Spatie grabará obra_id en el pivot
    //                     $user->assignRole($role);
    //                 }
    //                 // si no se resolvió rol => lo dejas sin rol
    //             }
    //         }
    //     });

    //     return response()->json([
    //         'ok' => true,
    //         'obra_id' => $obra->id,
    //         'import_summary' => [
    //             'created_users'     => $createdUsers,
    //             'updated_personas'  => $updatedPersonas,
    //             'attached_to_obra'  => $attached,
    //             'skipped'           => $skipped,
    //         ]
    //     ]);
    // }

    public function getSiluciaUsers(Request $request, Obra $obra, UserSiluciaClient $client)
    {
        // Construye un Request con el idmeta que exige el cliente
        $siluciaRequest = new Request([
            'idmeta' => $obra->idmeta_silucia,
        ]);

        // Llama al cliente (SOLO Request)
        $externos = $client->fetchPersonalByMeta($siluciaRequest);
        // Log::info($externos);
        // Log::info('======================================');
        // Log::info($client);

        $createdUsers     = 0;
        $createdPersonas  = 0;        // +ADD (separamos created de updated y evitamos updates si ya existe)
        $attached         = 0;
        $skipped          = [];

        DB::transaction(function () use ($externos, $obra, $client, &$createdUsers, &$createdPersonas, &$attached, &$skipped) {

            foreach ($externos as $row) {

                // +ADD: 1) Verificar estado
                $estado = trim($row['estado'] ?? '');
                if (strcasecmp($estado, 'Activo') !== 0) {
                    $skipped[] = ['reason' => 'estado_no_activo', 'dni' => $row['dni'] ?? null];
                    continue;
                }

                $dni = $client::cleanDni($row['dni'] ?? null);
                if (!$dni) {
                    $skipped[] = ['reason' => 'dni_vacio_o_invalido', 'raw' => $row['dni'] ?? null];
                    continue;
                }

                $nombres = trim($row['nombres'] ?? '');
                $paterno = trim($row['paterno'] ?? '');
                $materno = trim($row['materno'] ?? '');
                $email   = "{$dni}@domain.com";

                // *CHG: 2) USER: si existe, NO modificar datos personales ni password
                $user = User::where('email', $email)->first();

                if (!$user) {
                    // +ADD: crear con password por defecto "karina" + dni
                    $user = User::create([
                        'name'     => trim($nombres.' '.$paterno),
                        'email'    => $email,
                        'password' => bcrypt($dni),   // +ADD (regla)
                        'state'    => 1,
                    ]);
                    $createdUsers++;
                }

                // *CHG: 3) PERSONA: si ya existe, NO actualizar (no tocar datos). Si no existe, crear.
                $persona = Persona::where('num_doc', $dni)->first();
                if (!$persona) {
                    $persona = Persona::create([
                        'user_id'   => $user->id,
                        'num_doc'   => $dni,
                        'name'      => $nombres,
                        'last_name' => trim($paterno.' '.$materno),
                        'state'     => 1,
                    ]);
                    $createdPersonas++;  // +ADD
                }

                // 4) Vincular al pivot obra_user si no está
                if (!$obra->members()->where('users.id', $user->id)->exists()) {
                    $obra->members()->attach($user->id);
                    $attached++;
                }

                $teamObraId = $obra->id;
                app(PermissionRegistrar::class)->setPermissionsTeamId($teamObraId);

                $hasRoleInThisObra = \Illuminate\Support\Facades\DB::table('model_has_roles')
                    ->where('model_type', \App\Models\User::class)
                    ->where('model_id', $user->id)
                    ->where('obra_id', $teamObraId)   // <-- clave tenant en el pivot
                    ->exists();

                // *CHG: Solo si NO tiene roles en ESTA obra, aplicar la lógica
                if (!$hasRoleInThisObra) {
                    $resolvedRoleName = $this->resolverRolSegunReglas($row);

                    if ($resolvedRoleName) {
                        $user->assignRole($resolvedRoleName);
                    }

                }


            }
        });

        return response()->json([
            'ok' => true,
            'obra_id' => $obra->id,
            'import_summary' => [
                'created_users'     => $createdUsers,
                'created_personas'  => $createdPersonas, // +ADD
                'attached_to_obra'  => $attached,
                'skipped'           => $skipped,
            ],
            'usuario' => $externos
        ]);
    }

    /**
     * +ADD: Resuelve el rol a asignar según tus reglas:
     * 1) Si cargo contiene "supervisor" (case-insensitive) => almacen.supervisor
     * 2) Si cargo vacío/null => evaluar 'rol' (array):
     *    - length > 1 => SIN rol (null)
     *    - length == 1 => mirar 'desrol':
     *       * contiene "almacenero" => almacen.almacenero
     *       * contiene "UNIDADES USUARIAS" => almacen.residente
     *       * otro => SIN rol (null)
     */
    private function resolverRolSegunReglas(array $row): ?string
    {
        $cargo = $row['cargo'] ?? null;

        // Regla 2: cargo contiene "supervisor" (case-insensitive)
        if (is_string($cargo) && $cargo !== '') {
            $cargoNorm = mb_strtolower($cargo, 'UTF-8');
            if (str_contains($cargoNorm, 'supervisor')) {
                return 'almacen.supervisor';
            }
        }

        // Regla 3: cargo vacío o null => evaluar rol
        if (!isset($row['rol']) || !is_array($row['rol'])) {
            return null; // no hay info para decidir
        }

        $roles = $row['rol'];

        // length > 1 => registrar sin rol
        if (count($roles) > 1) {
            return null;
        }

        // length == 1 => mirar desrol
        if (count($roles) === 1) {
            $desrol = trim($roles[0]['desrol'] ?? '');
            $desrolNorm = mb_strtolower($desrol, 'UTF-8');

            if ($desrol !== '') {
                if (str_contains($desrolNorm, 'almacenero')) {
                    return 'almacen.almacenero';
                }
                if (str_contains($desrolNorm, 'unidades usuarias')) {
                    return 'almacen.residente';
                }
            }
        }

        // Cualquier otro caso => sin rol
        return null;
    }
}

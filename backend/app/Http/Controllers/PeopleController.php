<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Persona;
use App\Services\DecolectaClient;
use App\Services\PersonFinder;
use App\Services\ReniecClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\User;            // <-- ADD

class PeopleController extends Controller
{
    // public function __construct(private ReniecClient $reniec) {}
    public function __construct(private PersonFinder $finder) {}
    // public function __construct(private DecolectaClient $decolecta) {}
    // Esta funcion sirve para recuperar un dni de nuestra propia base de datos, y si esta no existe, busca el registro del
    // dni en la base de datos de la reniec, guarda el registro en nuestra propia base de datos y nos entrega el registro

    public function show(string $dni)
    {
        $dni = str_pad(preg_replace('/\D/', '', $dni), 8, '0', STR_PAD_LEFT);
        
        $persona = Persona::with(['user:id,name,email'])
            ->where('num_doc', $dni)
            ->first();   

        if ($persona) {
            // (opcional) homogeneiza forma de salida
            return response()->json([
                'from' => 'db.personas',
                'data' => [
                    'dni'             => $persona->num_doc,
                    'first_lastname'  => $persona->last_name,   // ajusta si separas apellidos
                    'second_lastname' => null,                  // si no lo manejas, deja null
                    'names'           => $persona->name,
                    'full_name'       => trim(($persona->name ?? '').' '.($persona->last_name ?? '')) ?: null,
                    'user'            => $persona->user ? [
                        'id'    => $persona->user->id,
                        'name'  => $persona->user->name,
                        'email' => $persona->user->email,
                    ] : null,
                ],
            ], 200);
        }


        // ← AHORA usamos el agregador correctamente
        $data = $this->finder->find($dni);
        if (!$data) {
            return response()->json([
                'ok' => false,
                'message' => 'No encontrado en proveedores externos',
            ], 404);
        }

        $ubg = explode('/', (string)$data->ubigeo);

        // $person = Person::create([
        //     'dni'             => $data->dni,
        //     'first_lastname'  => $data->first_lastname,
        //     'second_lastname' => $data->second_lastname,
        //     'names'           => $data->names,
        //     'full_name'       => $data->full_name,
        //     'civil_status'    => $data->civil_status,
        //     'address'         => $data->address,
        //     'ubigeo'          => $data->ubigeo,
        //     'ubg_department'  => $ubg[0] ?? null,
        //     'ubg_province'    => $ubg[1] ?? null,
        //     'ubg_district'    => $ubg[2] ?? null,
        //     'photo_base64'    => $data->photo_base64,
        //     // OJO: tu tabla no tiene 'raw', lo dejé fuera para que no falle.
        //     'reniec_consulted_at' => now(), // si quieres, luego generalízalo
        // ]);

        // return response()->json(['from' => $data->source, 'data' => $person], 201);
        return response()->json([
            'from' => $data->source,  // 'reniec' | 'decolecta' | etc.
            'data' => [
                'dni'             => $data->dni,
                'first_lastname'  => $data->first_lastname,
                'second_lastname' => $data->second_lastname,
                'names'           => $data->names,
                'full_name'       => $data->full_name,
                'civil_status'    => $data->civil_status,
                'address'         => $data->address,
                'ubigeo'          => $data->ubigeo,
                'photo_base64'    => $data->photo_base64,
            ],
        ], 200);
    }

    public function savev0(Request $request, string $dni)
    {
        // 1) Normaliza y valida DNI
        $dni = preg_replace('/\D/', '', $dni);
        if (strlen($dni) !== 8) {
            return response()->json(['ok'=>false,'message'=>'DNI inválido (8 dígitos)'], 422);
        }

        // 2) Obra actual desde el middleware resolve.obra
        $obraId = app('currentObraId');

        // 3) Buscar Persona -> User (local)
        $persona = Persona::with('user:id,name,email')
            ->where('num_doc', $dni)
            ->first();

        // 4) Si NO existe en BD: usar finder y GUARDAR en BD (User + Persona)
        if (!$persona) {
            $ext = $this->finder->find($dni);
            if (!$ext) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No existe en BD y no se encontró en proveedores externos',
                ], 404);
            }

            // Armar nombre(s)
            $names   = trim((string)$ext->names);
            $ap1     = trim((string)$ext->first_lastname);
            $ap2     = trim((string)$ext->second_lastname);
            $full    = trim($ext->full_name ?? ($names.' '.$ap1.' '.$ap2));

            // 4.1) USER (crea si no existe)
            $email = "{$dni}@domain.com"; // ajusta dominio si corresponde
            $user  = User::where('email', $email)->first();
            // return $user;
            if (!$user) {
                $user = User::create([
                    'name'     => $full !== '' ? $full : "Usuario $dni",
                    'email'    => $email,
                    'password' => bcrypt($dni), // regla que usas
                    'state'    => 1,
                ]);
            }

            // 4.2) PERSONA (crea y enlaza al user)
            $persona = Persona::create([
                'user_id'   => $user->id,
                'num_doc'   => $dni,
                'name'      => $names ?: $full,           // si no tienes nombres separados
                'last_name' => trim($ap1.' '.$ap2) ?: null,
                'state'     => 1,
            ]);

            $user->assignRole('almacen.operario');
            $user->obras()->syncWithoutDetaching([
                $obraId => [
                    'role'        => 'almacen.operario',
                    // 'assigned_by' => auth()->id(),
                ],
            ]);
        }



        return response()->json([
            'ok' => true,
            // 'message' => $exists
            //     ? 'El usuario ya tenía ese rol en esta obra.'
            //     : 'Rol asignado correctamente.',
            'data' => [
                // 'user_id' => $persona->$user()->id,
                'dni'     => $dni,
                'obra_id' => $obraId,
                'role'    => 'almacenero.operador',
                'created' => [
                    // 'user'   => isset($ext) && $ext && $user->wasRecentlyCreated ?? false,
                    'person' => isset($ext) && $ext && $persona->wasRecentlyCreated ?? false,
                ],
            ],
        // ], $exists ? 200 : 201);
        ], 201);
    }
    public function save(Request $request, string $dni)
    {
        // 1) Normaliza y valida DNI
        $dni = preg_replace('/\D/', '', $dni);
        if (strlen($dni) !== 8) {
            return response()->json(['ok'=>false,'message'=>'DNI inválido (8 dígitos)'], 422);
        }

        // 2) Obra actual desde el middleware resolve.obra
        $obraId   = app('currentObraId');
        $roleName = 'almacen.operario';

        $created = ['user' => false, 'person' => false];

        [$user, $persona] = DB::transaction(function () use ($dni, $obraId, $roleName, &$created) {
            // 3) Buscar Persona -> User (local)
            $persona = Persona::with('user:id,name,email')
                ->where('num_doc', $dni)
                ->first();

            if (!$persona) {
                // 4) Si NO existe en BD: usar finder y GUARDAR en BD (User + Persona)
                $ext = $this->finder->find($dni);
                if (!$ext) {
                    abort(response()->json([
                        'ok' => false,
                        'message' => 'No existe en BD y no se encontró en proveedores externos',
                    ], 404));
                }

                // Armar nombre(s)
                $names = trim((string)($ext->names ?? ''));
                $ap1   = trim((string)($ext->first_lastname ?? ''));
                $ap2   = trim((string)($ext->second_lastname ?? ''));
                $full  = trim($ext->full_name ?? ($names.' '.$ap1.' '.$ap2));

                // 4.1) USER (crea si no existe)
                $email = "{$dni}@domain.com";
                $user  = User::where('email', $email)->first();
                if (!$user) {
                    $user = User::create([
                        'name'     => $full !== '' ? $full : "Usuario $dni",
                        'email'    => $email,
                        'password' => bcrypt($dni),
                        'state'    => 1,
                    ]);
                    $created['user'] = true;
                }

                // 4.2) PERSONA (crea y enlaza al user)
                $persona = Persona::create([
                    'user_id'   => $user->id,
                    'num_doc'   => $dni,
                    'name'      => $names ?: ($full !== '' ? $full : "Usuario $dni"),
                    'last_name' => trim($ap1.' '.$ap2) ?: null,
                    'state'     => 1,
                ]);
                $created['person'] = true;

            } else {
                // Persona ya existía
                $user = $persona->user;

                // Si Persona existe pero NO tiene user asociado: crearlo y vincularlo
                if (!$user) {
                    $email = "{$dni}@domain.com";
                    $user  = User::where('email', $email)->first();
                    if (!$user) {
                        $user = User::create([
                            'name'     => $persona->name ?: "Usuario $dni",
                            'email'    => $email,
                            'password' => bcrypt($dni),
                            'state'    => 1,
                        ]);
                        $created['user'] = true;
                    }
                    $persona->user_id = $user->id;
                    $persona->save();
                }
            }

            // (A) Pivot obra_user: vincula SIEMPRE a la obra (idempotente)
            $user->obras()->syncWithoutDetaching([
                $obraId => [
                    // Descomenta si tienes estas columnas en el pivot (ver migración más abajo)
                    // 'role'        => $roleName,
                    // 'assigned_by' => auth()->id(),
                ],
            ]);

            // (B) Si usas Spatie Permission con teams por obra, descomenta:
            // app(PermissionRegistrar::class)->setPermissionsTeamId($obraId);
            if (!$user->hasRole($roleName)) {
                $user->assignRole($roleName);
            }

            return [$user, $persona];
        });

        // 201 si creaste algo; 200 si todo ya existía
        $status = ($created['user'] || $created['person']) ? 201 : 200;

        return response()->json([
            'ok'   => true,
            'data' => [
                'user_id' => $user->id,
                'dni'     => $dni,
                'obra_id' => $obraId,
                'role'    => $roleName,
                'created' => $created,
            ],
        ], $status);
    }
}

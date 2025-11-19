<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::select(
            'users.*',
            'roles.id as role_id',
            'roles.name as role_name',
            'personas.num_doc',
            'personas.name as persona_name',
            'personas.last_name'
        )
        ->leftJoin('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
        ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
        ->leftJoin('personas', 'users.id', '=', 'personas.user_id')
        ->get();

        $transformedUsers = [];
        foreach ($users as $user) {
            $userId = $user->id;
            if (!isset($transformedUsers[$userId])) {
                $transformedUsers[$userId] = [
                    'id' => $user->id,
                    'num_doc' => $user->num_doc ?? 'N/A',
                    'name' => $user->name,
                    'persona_name' => $user->persona_name ?? 'N/A',
                    'last_name' => $user->last_name ?? '',
                    'email' => $user->email,
                    'roles' => [], // aquí meteremos id y name
                    'role_names' => '',
                    'state' => $user->state ?? 1,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ];
            }

            if ($user->role_id && $user->role_name) {
                $transformedUsers[$userId]['roles'][] = [
                    'id' => $user->role_id,
                    'name' => $user->role_name,
                ];
            }
        }

        $finalUsers = array_values($transformedUsers);
        foreach ($finalUsers as &$user) {
            $user['role_names'] = implode(', ', array_column($user['roles'], 'name'));
        }

        return response()->json([
            'message' => 'Users retrieved successfully',
            'data' => $finalUsers
        ], 200);
    }

    public function incidencia()
    {
        $users = User::select(
            'users.*',
            'personas.num_doc',
            'personas.name as persona_name',
            'personas.last_name'
        )
        ->leftJoin('personas', 'users.id', '=', 'personas.user_id')
        ->where('users.id', Auth::id())
        ->get();

        return response()->json([
            'message' => 'Users retrieved successfully',
            'data' => $users
        ], 200);
    }

    public function operarios(Request $request){

        $q = trim((string) $request->query('q', ''));

        $query = User::query()
            // Si tus roles son guard 'api', especifícalo:
            ->role('almacen.operario', 'api')
            ->select('id','name','email')
            // Eager load de SOLO la primera persona:
            ->with(['persona:id,user_id,num_doc,name,last_name,state']);

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhereHas('persona', function ($p) use ($q) {
                      $p->where('num_doc', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%")
                        ->orWhere('last_name', 'like', "%{$q}%");
                  });
            });
        }

        $users = $query->get(); // SIN paginación

        // (Opcional) Formatear respuesta combinada
        $data = $users->map(function ($u) {
            return [
                'id'        => $u->id,
                'name'      => $u->name,
                'email'     => $u->email,
                'persona'   => $u->persona ? [
                    'id'        => $u->persona->id,
                    'num_doc'   => $u->persona->num_doc,
                    'name'      => $u->persona->name,
                    'last_name' => $u->persona->last_name,
                    'state'     => $u->persona->state,
                ] : null,
            ];
        });

        return response()->json([
            'ok'    => true,
            'role'  => 'almacen.operario',
            'count' => $data->count(),
            'data'  => $data,
        ]);
    }

    function getRoles()
    {
        $roles = Role::get();
        return response()->json([
            'message' => 'Roles retrieved successfully',
            'data' => $roles
        ], 200);
    }

    function consultUsers($numDoc)
    {
        $data = null;
        try {
            $urlReniec = "https://ws2.pide.gob.pe/Rest/RENIEC/Consultar";
            $responseReniec = Http::timeout(10)->get($urlReniec, [
                'nuDniConsulta' => $numDoc,
                'nuDniUsuario' => '70977157',
                'nuRucUsuario' => '20406325815',
                'password' => '70977157',
                'out' => 'json',
            ]);

            if ($responseReniec->successful() && !empty($responseReniec->json()['consultarResponse']['return']['datosPersona'])) {
                $data = $responseReniec->json()['consultarResponse']['return']['datosPersona'];
                $source = 'RENIEC-PIDE';
                $name = $data['prenombres'];
                $last_name = $data['apPrimer'] . ' ' . $data['apSegundo'];
                $nombreCompleto = $name . ' ' . $last_name;
            }
        } catch (\Exception $e) {
        }

        if (!$data) {
            try {
                $tokenApiPeru = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJlbWFpbCI6InJvaHVhbmNhY2FAZXN0LnVuYXAuZWR1LnBlIn0.5vsbSt9uYHpYTTEHoc9ttwoT4p2kc0DxL1zCrK2NVWo';
                $urlApiPeru = "https://dniruc.apisperu.com/api/v1/dni/$numDoc?token=$tokenApiPeru";

                $responseApiPeru = Http::timeout(15)->get($urlApiPeru);

                if ($responseApiPeru->successful() && isset($responseApiPeru['nombres'])) {
                    $data = $responseApiPeru->json();
                    $source = 'APISPERU';
                    $name = $data['nombres'];
                    $last_name = $data['apellidoPaterno'] . ' ' . $data['apellidoMaterno'];
                    $nombreCompleto = $name . ' ' . $last_name;
                }
            } catch (\Exception $e) {
                Log::error("Error en APISPERU: ".$e->getMessage());
            }
        }

        if($nombreCompleto){
            return response()->json([
                'success' => true,
                'clausule' => 1,
                'data' => [
                    'dni' => $numDoc,
                    'name' => $name,
                    'last_name' => $last_name
                ]
            ], 201);
        } else {
            return response()->json([
                'success' => true,
                'clausule' => 2,
                'data' => [
                    'dni' => $numDoc,
                    'name' => '',
                    'last_name' => ''
                ]
            ], 201);
        }
    }

    function createUser(Request $request)
    {
        $user = User::create([
            'name' => $request->username,
            'email'=> $request->email,
            'password' => bcrypt($request->password),
            'state' => $request->state,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        Persona::create([
            'user_id' => $user->id,
            'num_doc' => $request->num_doc,
            'name' => $request->persona_name,
            'last_name' => $request->last_name,
            'state' => 1,
        ]);

        if ($request->has('roles')) {
            $user->roles()->sync($request->roles);
        }

        return response()->json([
            'message' => 'Usuario creado correctamente',
            'data' => $user,
        ], 201);
    }

    function updateUser(Request $request)
    {
        $user = User::find($request->id);
        $user->update([
            'name' => $request->username,
            'email'=> $request->email,
            'state' => $request->state,
            'password' => $request->password ? bcrypt($request->password) : $user->password,
            'updated_at' => now()
        ]);

        $persona = Persona::where('user_id', $user->id)->first();
        $persona->update([
            'num_doc' => $request->num_doc,
            'name' => $request->persona_name,
            'last_name' => $request->last_name
        ]);

        if ($request->has('roles')) {
            $user->roles()->sync($request->roles);
        }

        return response()->json([
            'message' => 'Usuario y roles actualizados correctamente',
            'data' => $user->load('roles')
        ], 201);
    }

    function updateUserRoles(Request $request)
    {
        $user = User::find($request->userId);
        $user->roles()->sync($request->roles);

        return response()->json([
            'message' => 'Roles de usuario actualizados correctamente',
            'data' => $user->load('roles')
        ], 200);
    }

    function destroy($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado',
            ], 404);
        }

        $user->roles()->detach();

        $persona = Persona::where('user_id', $user->id)->first();
        if ($persona) {
            $persona->delete();
        }

        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente',
        ], 200);
    }

    public function importUsersSilucia()
    {
        set_time_limit(0);
        
        $url = 'https://sistemas.regionpuno.gob.pe/siluciav2-api/api/personal/lista?rowsPerPage=0&flag=T&idrol=17';
        $response = Http::get($url);
        $responseData = $response->json();
        $personalData = $responseData['data'] ?? [];
        
        $stats = [
            'created' => 0,
            'updated' => 0,
            'errors' => []
        ];

        foreach ($personalData as $persona) {
            try {
                $dni = $persona['dni'];
                $cargo = $persona['cargo']['idcargo'] ?? null;
                $uoperativas = $persona['uoperativas'] ?? [];
                
                $roles = $this->determineRoles($cargo, $uoperativas);
                
                if (empty($roles)) {
                    continue;
                }

                $existingPersona = Persona::where('num_doc', $dni)->first();
                
                if ($existingPersona) {
                    $user = $existingPersona->user;
                    $user->syncRoles($roles);
                    
                    $stats['updated']++;
                } else {
                    $user = User::create([
                        'name' => $persona['nombres'],
                        'email' => $dni . '@domain.com',
                        'password' => Hash::make($dni),
                        'state' => 1
                    ]);

                    Persona::create([
                        'user_id' => $user->id,
                        'num_doc' => $dni,
                        'name' => $persona['nombres'],
                        'last_name' => $persona['paterno'] . ' ' . $persona['materno']
                    ]);

                    $user->assignRole($roles);
                    
                    $stats['created']++;
                }
                $this->syncUserMetas($user->id, $persona['metas'] ?? []);
                
            } catch (\Exception $e) {
                $stats['errors'][] = [
                    'dni' => $dni ?? 'unknown',
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'message' => 'Importación completada',
            'stats' => $stats
        ], 200);
    }

    private function determineRoles($idCargo, $uoperativas)
    {
        $roles = [];
        $uoperIds = collect($uoperativas)->pluck('iduoper')->toArray();
        if (in_array($idCargo, [5, 6])) {
            if (in_array('00106', $uoperIds) || in_array('00107', $uoperIds)) {
                $roles[] = 4;
            }
        }
        
        if ($idCargo == 7) {
            if (in_array('00108', $uoperIds)) {
                $roles[] = 5;
            }
        }
        
        $hasResidenteUnits = in_array('00106', $uoperIds) || in_array('00107', $uoperIds);
        $hasSupervisorUnits = in_array('00108', $uoperIds);
        
        if ($hasResidenteUnits && $hasSupervisorUnits) {
            $roles = [4, 5];
        }

        if (!empty($rolesArray)) {
            foreach ($rolesArray as $rol) {
                if ($rol['idrol'] == '34') {
                    $roles[] = 3;
                    break;
                }
            }
        }
        
        return array_unique($roles);
    }

    private function syncUserMetas($userId, $metas)
    {
        if (empty($metas)) {
            Project::where('user_id', $userId)->delete();
            return;
        }

        $currentYear = date('Y');
        $incomingMetasIds = [];
        foreach ($metas as $meta) {
            $idMeta = $meta['idmeta'];
            $anio = $meta['anio'] ?? $currentYear;
            
            $incomingMetasIds[] = [
                'goal_id' => $idMeta,
                'year' => $anio
            ];
        }
        $existingProjects = Project::where('user_id', $userId)->get();
        $projectsToDelete = $existingProjects->filter(function ($project) use ($incomingMetasIds) {
            return !collect($incomingMetasIds)->contains(function ($incoming) use ($project) {
                return $incoming['goal_id'] == $project->goal_id && 
                    $incoming['year'] == $project->year;
            });
        });
        Project::whereIn('id', $projectsToDelete->pluck('id'))->delete();
        foreach ($incomingMetasIds as $metaData) {
            $exists = Project::where('user_id', $userId)
                ->where('goal_id', $metaData['goal_id'])
                ->where('year', $metaData['year'])
                ->exists();

            if (!$exists) {
                Project::create([
                    'user_id' => $userId,
                    'goal_id' => $metaData['goal_id'],
                    'year' => $metaData['year']
                ]);
            }
        }
    }

    public function importControladorSilucia(){
        set_time_limit(0);
        $url = 'https://sistemas.regionpuno.gob.pe/siluciav2-api/api/personal/lista?rowsPerPage=0&flag=T&idrol=34';
        $response = Http::get($url);
        $responseData = $response->json();
        $personalData = $responseData['data'] ?? [];
        foreach ($personalData as $persona) {
            $dni = $persona['dni'];
            $existingPersona = Persona::where('num_doc', $dni)->first();
            if ($existingPersona) {
                $user = $existingPersona->user;
                $user->assignRole([3]);
                $this->syncUserMetas($user->id, $persona['metas'] ?? []);
            } else {
                $user = User::create([
                    'name' => $persona['nombres'],
                    'email' => $dni . '@domain.com',
                    'password' => Hash::make($dni),
                    'state' => 1
                ]);
                Persona::create([
                    'user_id' => $user->id,
                    'num_doc' => $dni,
                    'name' => $persona['nombres'],
                    'last_name' => $persona['paterno'] . ' ' . $persona['materno']
                ]);
                $user->assignRole([3]);
                $this->syncUserMetas($user->id, $persona['metas'] ?? []);
            }
        }
        return response()->json([
            'message' => 'usuario importado correctamente',
        ], 200);
    }

    public function changePassword(Request $request){
        $user = User::find(Auth::id());
        $user->update([
            'password' => bcrypt($request->password),
            'updated_at' => now()
        ]);

        return response()->json([
            'message' => 'Contraseña actualizada correctamente',
            'data' => $user
        ], 200);
    }
}

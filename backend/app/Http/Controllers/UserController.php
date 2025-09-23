<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\User;
use Illuminate\Http\Request;
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
        setPermissionsTeamId(1);
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
        Log::info('Actualizando usuario con datos: ' . json_encode($request->all()));
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
}

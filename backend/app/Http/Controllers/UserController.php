<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    function index()
    {
        $users = User::select(
                'users.*',
                'roles.name as role_name',
                'personas.num_doc',
                'personas.name as persona_name',
                'personas.last_name'
            )
            ->leftJoin('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->leftJoin('personas', 'users.id', '=', 'personas.user_id')
            ->get();

        $permissions = Permission::get();

        $users->each(function ($user) use ($permissions) {
            $userModel = User::find($user->id);
            $user->permissions = $userModel->getAllPermissions()->pluck('name');
        });

        return response()->json([
            'message' => 'Users retrieved successfully',
            'data' => $users
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

                $responseApiPeru = Http::timeout(7)->get($urlApiPeru);

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
}

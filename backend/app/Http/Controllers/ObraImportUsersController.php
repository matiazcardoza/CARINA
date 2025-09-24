<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Models\Obra;
use App\Models\User;
use App\Models\Persona;
use App\Services\UserSiluciaClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ObraImportUsersController extends Controller
{
    
    public function __invoke(Request $request, Obra $obra, UserSiluciaClient $client)
    {
       // Construye un Request con el idmeta que exige el cliente
        $siluciaRequest = new Request([
            'idmeta' => $obra->idmeta_silucia,
        ]);

        // Llama al cliente (SOLO Request)
        $externos = $client->fetchPersonalByMeta($siluciaRequest);

        $createdUsers = 0;
        $updatedPersonas = 0;
        $attached = 0;
        $skipped = [];

        DB::transaction(function () use ($externos, $obra, $client, &$createdUsers, &$updatedPersonas, &$attached, &$skipped) {

            foreach ($externos as $row) {
                $dni = $client::cleanDni($row['dni'] ?? null);

                if (!$dni) { $skipped[] = ['reason'=>'dni_vacio_o_invalido','raw'=>$row['dni'] ?? null]; continue; }

                // Nombre completo (usa lo que provee la API)
                $nombres  = trim($row['nombres'] ?? '');
                $paterno  = trim($row['paterno'] ?? '');
                $materno  = trim($row['materno'] ?? '');

                // Asegura un email único (si tu API no lo da)
                $email = "u{$dni}@silucia.local";

                // 1) USER (por email único). Si ya existe, no lo pisa.
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name'     => trim($nombres.' '.$paterno),
                        'password' => bcrypt(Str::random(24)),
                        'state'    => 1,
                    ]
                );
                if ($user->wasRecentlyCreated) $createdUsers++;

                // 2) PERSONA (clave única: num_doc)
                $persona = Persona::updateOrCreate(
                    ['num_doc' => $dni],
                    [
                        'user_id'     => $user->id,
                        'name'        => $nombres,
                        'last_name'   => trim($paterno.' '.$materno),
                        'state'       => 1,
                    ]
                );
                if (!$persona->wasRecentlyCreated) $updatedPersonas++;

                // 3) Asignar al pivot obra_user si no está
                if (!$obra->members()->where('users.id', $user->id)->exists()) {
                    $obra->members()->attach($user->id);
                    $attached++;
                }
            }
        });

        return response()->json([
            'ok' => true,
            'obra_id' => $obra->id,
            'import_summary' => [
                'created_users'     => $createdUsers,
                'updated_personas'  => $updatedPersonas,
                'attached_to_obra'  => $attached,
                'skipped'           => $skipped,
            ]
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ItemPecosa;
use App\Models\Obra;
use App\Models\User;
use App\Services\PecosaClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;


class UserObrasController extends Controller
{
    public function __construct(private PecosaClient $pecosas) {}
    // GET /admin/users/{user}/obras
    public function index(User $user)
    {
        // return "hola mundo";
        // obras del usuario + roles por obra
        $obras = DB::table('obra_user')
            ->join('obras','obras.id','=','obra_user.obra_id')
            ->where('obra_user.user_id', $user->id)
            ->orderBy('obras.nombre')
            // ->get(['obras.id','obras.nombre','obras.codmeta']);
            ->get([
                'obras.id',
                'obras.idmeta_silucia',
                'obras.anio',
                'obras.codmeta',
                'obras.nombre',
                'obras.desmeta',
                'obras.nombre_corto',
                // 'obras.codigo', // <- esta es la clave
            ]);
        
        // por eficiencia, cargamos roles por obra en bloque
        $rolesPorObra = [];
        foreach ($obras as $o) {
            setPermissionsTeamId($o->id);
            $user->unsetRelation('roles')->unsetRelation('permissions'); // refrescar cache
            $rolesPorObra[$o->id] = $user->roles->pluck('name')->values();
        }
        // return $rolesPorObra;
        $result =  $obras->map(function ($o) use ($rolesPorObra) {
            return [
                'obra'  => [
                    'id'=>$o->id, 
                    'idmeta_silucia'=>$o->idmeta_silucia, 
                    'anio'=>$o->anio, 
                    'codmeta'=>$o->codmeta, 
                    'nombre'=>$o->nombre, 
                    'desmeta'=>$o->desmeta, 
                    'nombre_corto'=>$o->nombre_corto, 
                    'codigo'=>null
                ],
                'roles' => $rolesPorObra[$o->id] ?? collect(),
            ];
        });
        // use Illuminate\Support\Facades\Log;

        Log::info($result);

        return $result;
    }

    // POST /admin/users/{user}/obras  { obra_id: number, roles?: string[] }
    public function store(Request $request, User $user)
    {
        $data = $request->validate([
            'obra_id' => ['required','integer','exists:obras,id'],
            'roles'   => ['nullable','array'],
            'roles.*' => ['string'],
        ]);

        $obra = Obra::findOrFail($data['obra_id']);

        // vincular al pivot
        DB::table('obra_user')->updateOrInsert(
            ['obra_id' => $obra->id, 'user_id' => $user->id],
            ['created_at'=>now(), 'updated_at'=>now()]
        );

        // si envían roles iniciales
        if (!empty($data['roles'])) {
            setPermissionsTeamId($obra->id);
            $user->unsetRelation('roles')->unsetRelation('permissions');
            // valida que existan
            $validos = Role::whereIn('name', $data['roles'])->pluck('name')->all();
            $user->syncRoles($validos);
        }

        return response()->json(['ok'=>true]);
    }

    // DELETE /admin/users/{user}/obras/{obra}
    public function destroy(User $user, Obra $obra)
    {
        // limpiar roles en esa obra (opcional pero recomendado)
        setPermissionsTeamId($obra->id);
        $user->unsetRelation('roles')->unsetRelation('permissions');
        $user->syncRoles([]); // deja sin roles en esa obra

        // desvincular pivot
        DB::table('obra_user')->where(['obra_id'=>$obra->id,'user_id'=>$user->id])->delete();

        return response()->json(['ok'=>true]);
    }

    // PUT /admin/users/{user}/obras/{obra}/roles  { roles: string[] }  (reemplaza)
    public function syncRoles(Request $request, User $user, Obra $obra)
    {
        $data = $request->validate([
            'roles'   => ['required','array'],
            'roles.*' => ['string'],
        ]);

        setPermissionsTeamId($obra->id);
        $user->unsetRelation('roles')->unsetRelation('permissions');

        $validos = Role::whereIn('name', $data['roles'])->pluck('name')->all();
        $user->syncRoles($validos);

        return response()->json(['ok'=>true, 'roles'=>$validos]);
        
        // -------------------------
            // $data = $request->validate([
            //     'roles'   => ['required','array'],
            //     'roles.*' => ['string'],
            // ]);

            // // Team scope (Spatie v6 con teams)
            // setPermissionsTeamId($obra->id);

            // // Evita relaciones cacheadas
            // $user->unsetRelation('roles')->unsetRelation('permissions');

            // // Guard a usar (por tu User::$guard_name será 'api')
            // $guard = 'api';

            // // Solo roles que existan con ese guard (y team actual por el scope de arriba)
            // $validos = Role::query()
            //     ->where('guard_name', $guard)
            //     ->whereIn('name', $data['roles'])
            //     ->pluck('name')
            //     ->all();

            // // (Opcional) reporta faltantes en vez de lanzar excepción
            // $faltantes = array_values(array_diff($data['roles'], $validos));
            // if ($faltantes) {
            //     return response()->json([
            //         'ok'      => false,
            //         'message' => 'Algunos roles no existen para este guard/obra',
            //         'missing' => $faltantes,
            //     ], 422);
            // }

            // $user->syncRoles($validos);

            // return response()->json(['ok'=>true, 'roles'=>$validos]);
    }

    // POST /admin/users/{user}/obras/{obra}/roles  { roles: string[] }  (agrega)
    public function attachRoles(Request $request, User $user, Obra $obra)
    {
        $data = $request->validate([
            'roles'   => ['required','array'],
            'roles.*' => ['string'],
        ]);

        setPermissionsTeamId($obra->id);
        $user->unsetRelation('roles')->unsetRelation('permissions');

        $validos = Role::whereIn('name', $data['roles'])->pluck('name')->all();
        foreach ($validos as $r) $user->assignRole($r);

        return response()->json(['ok'=>true]);
    }

    // DELETE /admin/users/{user}/obras/{obra}/roles  { roles: string[] }  (quita)
    public function detachRoles(Request $request, User $user, Obra $obra)
    {
        $data = $request->validate([
            'roles'   => ['required','array'],
            'roles.*' => ['string'],
        ]);

        setPermissionsTeamId($obra->id);
        $user->unsetRelation('roles')->unsetRelation('permissions');

        $validos = Role::whereIn('name', $data['roles'])->pluck('name')->all();
        foreach ($validos as $r) $user->removeRole($r);

        return response()->json(['ok'=>true]);
    }

    /**
     * POST /admin/users/{user}/obras/import
     * Body:
     * {
     *   "meta": {
     *     "idmeta": "0801", "anio": "2025", "codmeta": "0001",
     *     "nombre_corto": "HOSPITAL...", "desmeta": "..."
     *   },
     *   "roles": ["almacenero_principal","visor"]
     * }
     */
    public function importAttachFromExternalx(Request $request, User $user)
    {
        // return $request; 
        $data = $request->validate([
            'meta' => ['required','array'],
            'meta.idmeta' => ['required','string','max:100'],
            'meta.anio'   => ['required','string','max:10'],
            'meta.codmeta'=> ['required','string','max:50'],
            'meta.nombre_corto' => ['nullable','string','max:255'],
            'meta.desmeta'      => ['nullable','string'],
            'roles'   => ['nullable','array'],
            'roles.*' => ['string'],
        ]);

        $m = $data['meta'];
        $roles = $data['roles'] ?? [];


        $obra = Obra::updateOrCreate(
            ['idmeta_silucia' => $m['idmeta']],
            [
                'anio'    => $m['anio'],
                'codmeta' => $m['codmeta'],
                'nombre'  => $m['nombre_corto'] ?? $m['desmeta'] ?? ('Meta '.$m['codmeta']),
                'desmeta' => $m['desmeta'] ?? null,
                'external_last_seen_at' => now(),
                'raw_snapshot' => json_encode($m),
                'external_hash' => hash('sha1', json_encode($m)),
            ]
        );
        
        // 2) Importar/actualizar PECOSAs de esa meta (filtra por anio+cod_meta)
        //    Paginamos hasta terminar (si el API lo devuelve paginado)
        $imported = 0;
        $page = 1;
        do {
            $batch = $this->pecosas->index([
                'idmeta' => $m['idmeta']
                // 'page' => $page,
                // 'per_page' => 100,
                // 'anio' => $m['anio'],
                // 'cod_meta' => $m['codmeta'], // el API entrega cod_meta en la data; si no filtra server-side, filtraremos client-side
            ]);

            $rows = $batch['data'] ?? [];
            foreach ($rows as $r) {
                // Sanity: si por alguna razón vinieron otras metas, filtramos aquí:
                if (!empty($r['cod_meta']) && $r['cod_meta'] !== $m['codmeta']) continue;

                ItemPecosa::updateOrCreate(
                ['idsalidadet_silucia' => $r['idsalidadet']],
                [
                    'obra_id'   => $obra->id,
                    'idcompradet_silucia' => $r['idcompradet'] ?? null,
                    'anio'      => $r['anio'] ?? null,
                    'numero'    => $r['numero'] ?? null,
                    'fecha'        => $r['fecha'] ?? null,
                    'prod_proy'    => $r['prod_proy'] ?? null,
                    'cod_meta'     => $r['cod_meta'] ?? null,
                    'desmeta'      => $r['desmeta'] ?? null,
                    'desuoper'     => $r['desuoper'] ?? null,
                    'destipodestino'=> $r['destipodestino'] ?? null,
                    'item'         => $r['item'] ?? null,
                    'desmedida'    => $r['desmedida'] ?? null,
                    'idsalidadet'  => $r['idsalidadet'] ?? null,
                    'cantidad'     => $r['cantidad'] ?? null,
                    'precio'       => $r['precio'] ?? null,
                    'saldo'        => $r['saldo'] ?? null,
                    'total'        => $r['total'] ?? null,
                    // ... (mapear resto igual)
                    'raw_snapshot' => json_encode($r),
                    'external_hash' => hash('sha1', json_encode($r)),
                    'external_last_seen_at' => now(),
                ]
            );

                $imported++;
            }

            $next = $batch['next_page_url'] ?? null;
            $page++;
        } while ($next);
        // return "hola mundo 004";    
        // 3) Vincular usuario a obra (pivot)
        DB::table('obra_user')->updateOrInsert(
            ['obra_id' => $obra->id, 'user_id' => $user->id],
            ['created_at'=>now(), 'updated_at'=>now()]
        );
        
        // 4) Asignar roles en el TEAM = obra
        if (!empty($roles)) {
            setPermissionsTeamId($obra->id);
            $validos = Role::whereIn('name', $roles)->pluck('name')->all();
            $user->syncRoles($validos);
        }

        return response()->json([
            'ok' => true,
            'obra' => [
                'id' => $obra->id,
                'nombre' => $obra->nombre,
                'codigo' => $obra->codigo,
                'ext_idmeta' => $m['idmeta'],
                'anio' => $m['anio'],
                'codmeta' => $m['codmeta'],
            ],
            'imported_items' => $imported,
            'roles_assigned' => $roles ?? [],
        ]);
    }
    public function importAttachFromExternal(Request $request, User $user)
    {
        $data = $request->validate([
            'meta' => ['required','array'],
            'meta.idmeta' => ['required','string','max:100'],
            'meta.anio'   => ['required','string','max:10'],
            'meta.codmeta'=> ['required','string','max:50'],
            'meta.nombre_corto' => ['nullable','string','max:255'],
            'meta.desmeta'      => ['nullable','string'],
            'roles'   => ['nullable','array'],
            'roles.*' => ['string'],
        ]);

        $m = $data['meta'];
        $roles = $data['roles'] ?? [];

        // 1) Upsert de la OBRA local por idmeta (único en Silucia)
        $obra = Obra::updateOrCreate(
            ['idmeta_silucia' => $m['idmeta']],
            [
                'anio'    => $m['anio'],
                'codmeta' => $m['codmeta'],
                'nombre'  => $m['nombre_corto'] ?? $m['desmeta'] ?? ('Meta '.$m['codmeta']),
                'desmeta' => $m['desmeta'] ?? null,
                'external_last_seen_at' => now(),
                'raw_snapshot' => json_encode($m, JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION),
                'external_hash' => hash('sha1', json_encode($m, JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION)),
            ]
        );

        // 2) Traer TODAS las PECOSAs de esa obra en UNA sola llamada (idmeta + per_page=1000)
        $batch = $this->pecosas->index([
            'idmeta'   => $m['idmeta'],
            'per_page' => 1000, // no hay más de 1000, según tu regla de negocio
        ]);

        $rows = $batch['data'] ?? [];

        Log::info(['info de pecosas' => $rows]);
        $imported = 0;

        foreach ($rows as $r) {
            ItemPecosa::updateOrCreate(
                ['idsalidadet_silucia' => $r['idsalidadet']],
                [
                    'obra_id'               => $obra->id,
                    'idcompradet_silucia'   => $r['idcompradet'] ?? null,

                    // Búsquedas típicas
                    'anio'      => $r['anio'] ?? null,
                    'numero'    => $r['numero'] ?? null,

                    // Otros campos
                    'fecha'           => $r['fecha'] ?? null,
                    'prod_proy'       => $r['prod_proy'] ?? null,
                    'cod_meta'        => $r['cod_meta'] ?? null,
                    'desmeta'         => $r['desmeta'] ?? null,
                    'desuoper'        => $r['desuoper'] ?? null,
                    'destipodestino'  => $r['destipodestino'] ?? null,
                    'item'            => $r['item'] ?? null,
                    'desmedida'       => $r['desmedida'] ?? null,
                    'cantidad'        => $r['cantidad'] ?? null,
                    'precio'          => $r['precio'] ?? null,
                    'saldo'           => $r['saldo'] ?? null,
                    'total'           => $r['total'] ?? null,
                    'numero_origen'   => $r['numero_origen'] ?? null,

                    // Metadatos de sincronización
                    'raw_snapshot'         => json_encode($r, JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION),
                    'external_hash'        => hash('sha1', json_encode($r, JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION)),
                    'external_last_seen_at'=> now(),
                ]
            );
            $imported++;
        }

        // 3) Vincular usuario a obra (pivot)
        DB::table('obra_user')->updateOrInsert(
            ['obra_id' => $obra->id, 'user_id' => $user->id],
            ['created_at'=>now(), 'updated_at'=>now()]
        );

        // 4) Asignar roles en el TEAM = obra
        if (!empty($roles)) {
            setPermissionsTeamId($obra->id);
            $validos = Role::whereIn('name', $roles)->pluck('name')->all();
            $user->syncRoles($validos);
        }

        return response()->json([
            'ok' => true,
            'obra' => [
                'id'        => $obra->id,
                'nombre'    => $obra->nombre,
                'codmeta'   => $obra->codmeta,        // <- usamos codmeta (no 'codigo')
                'idmeta'    => $m['idmeta'],
                'anio'      => $m['anio'],
            ],
            'imported_items' => $imported,
            'roles_assigned' => $roles ?? [],
        ]);
    }

    public function importWork(Request $request)
    {
        $data = $request->validate([
            'meta' => ['required','array'],
            'meta.idmeta' => ['required','string','max:100'],
            'meta.anio'   => ['required','string','max:10'],
            'meta.codmeta'=> ['required','string','max:50'],
            'meta.nombre_corto' => ['nullable','string','max:255'],
            'meta.desmeta'      => ['nullable','string'],
        ]);

        $m = $data['meta'];

        return DB::transaction(function () use ($m) {

            // 1) Upsert de OBRA por idmeta_silucia (sin raw_snapshot para ahorrar espacio)
            $obra = Obra::updateOrCreate(
                ['idmeta_silucia' => $m['idmeta']],
                [
                    'anio'    => $m['anio'],
                    'codmeta' => $m['codmeta'],
                    'nombre'  => $m['nombre_corto'] ?? $m['desmeta'] ?? ('Meta '.$m['codmeta']),
                    'desmeta' => $m['desmeta'] ?? null,
                    'external_last_seen_at' => now(),
                    'external_hash' => sha1(json_encode($m, JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION)),
                ]
            );

            // 2) Traer TODOS los itempecosas en UNA llamada (sin iterar)
            //    Usa el tope que garantice "todo de golpe" según tu API (1000 o 10000).
            $perPage = 10000; // o 1000 si ese es el límite real garantizado por la API
            $batch = $this->pecosas->index([
                'idmeta'   => $m['idmeta'],
                'per_page' => $perPage,
            ]);

            $rows = $batch['data'] ?? [];
            $fetched = count($rows);

            // (Opcional) si tu API devuelve "total" para verificar que realmente vinieron todos:
            if (isset($batch['total']) && $batch['total'] > $fetched) {
                // No iteramos: solo dejamos constancia en logs para monitoreo
                Log::warning("API devolvió {$fetched} de {$batch['total']} itempecosas (sin iterar por diseño). idmeta={$m['idmeta']}");
            }

            $created = 0;
            $updated = 0;

            foreach ($rows as $r) {
                $incomingHash = sha1(json_encode($r, JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION));

                $model = ItemPecosa::firstOrNew([
                    'idsalidadet_silucia' => $r['idsalidadet'],
                ]);

                // Mapea/convierte tipos (fecha a date; numéricos a float/decimal si aplica)
                $payload = [
                    'obra_id'               => $obra->id,
                    'idcompradet_silucia'   => $r['idcompradet'] ?? null,
                    'anio'                  => isset($r['anio']) ? (string)$r['anio'] : null,
                    'numero'                => isset($r['numero']) ? (string)$r['numero'] : null,
                    'fecha'                 => isset($r['fecha']) ? Carbon::parse($r['fecha'])->toDateString() : null,
                    'prod_proy'             => $r['prod_proy'] ?? null,
                    'cod_meta'              => $r['cod_meta'] ?? null,
                    'desmeta'               => $r['desmeta'] ?? null,
                    'desuoper'              => $r['desuoper'] ?? null,
                    'destipodestino'        => $r['destipodestino'] ?? null,
                    'item'                  => $r['item'] ?? null,
                    'desmedida'             => $r['desmedida'] ?? null,
                    'cantidad'              => isset($r['cantidad']) ? (float)$r['cantidad'] : null,
                    'precio'                => isset($r['precio']) ? (float)$r['precio'] : null,
                    'saldo'                 => isset($r['saldo']) ? (float)$r['saldo'] : null,
                    'total'                 => isset($r['total']) ? (float)$r['total'] : null,
                    'numero_origen'         => $r['numero_origen'] ?? null,
                    'external_last_seen_at' => now(),
                ];

                if ($model->exists) {
                    if ($model->external_hash !== $incomingHash) {
                        $model->fill($payload);
                        // Quita esta línea si tampoco quieres snapshot en itempecosa:
                        // $model->raw_snapshot = json_encode($r, JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION);
                        $model->external_hash = $incomingHash;
                        $model->save();
                        $updated++;
                    }
                } else {
                    $model->fill($payload);
                    // Quita esta línea si no quieres snapshot:
                    // $model->raw_snapshot = json_encode($r, JSON_UNESCAPED_UNICODE|JSON_PRESERVE_ZERO_FRACTION);
                    $model->external_hash = $incomingHash;
                    $model->save();
                    $created++;
                }
            }

            Log::info("Import meta {$m['idmeta']} (sin iterar): fetched={$fetched}, created={$created}, updated={$updated}");

            return response()->json([
                'ok' => true,
                'obra' => [
                    'id'        => $obra->id,
                    'nombre'    => $obra->nombre,
                    'codmeta'   => $obra->codmeta,
                    'idmeta'    => $m['idmeta'],
                    'anio'      => $m['anio'],
                ],
                'fetched_items'  => $fetched,
                'created_items'  => $created,
                'updated_items'  => $updated,
                // (Opcional) 'warning' => 'La API reportó más que lo recibido, revisar logs' 
            ]);
        });
    }

    public function userRolesByObra(Request $request){
        $user = $request->user();
        return response()->json([
            'roles' => $user->getRoleNames() // Devuelve ['admin', 'editor', ...]
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ItemPecosa;
use App\Models\Obra;
use App\Models\User;
use App\Services\PecosaClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserObrasController extends Controller
{
    public function __construct(private PecosaClient $pecosas) {}
    // GET /admin/users/{user}/obras
    public function index(User $user)
    {
        // obras del usuario + roles por obra
        $obras = DB::table('obra_user')
            ->join('obras','obras.id','=','obra_user.obra_id')
            ->where('obra_user.user_id', $user->id)
            ->orderBy('obras.nombre')
            ->get(['obras.id','obras.nombre','obras.codigo']);

        // por eficiencia, cargamos roles por obra en bloque
        $rolesPorObra = [];
        foreach ($obras as $o) {
            setPermissionsTeamId($o->id);
            $user->unsetRelation('roles')->unsetRelation('permissions'); // refrescar cache
            $rolesPorObra[$o->id] = $user->roles->pluck('name')->values();
        }

        return $obras->map(function ($o) use ($rolesPorObra) {
            return [
                'obra'  => ['id'=>$o->id, 'nombre'=>$o->nombre, 'codigo'=>$o->codigo],
                'roles' => $rolesPorObra[$o->id] ?? collect(),
            ];
        });
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
    public function importAttachFromExternal(Request $request, User $user)
    {
        return $request;
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

        // 1) Upsert de la OBRA local
        $obra = Obra::updateOrCreate(
            ['ext_idmeta' => $m['idmeta'], 'anio' => $m['anio']],  // clave externa
            [
                'codigo'       => $m['codmeta'],                     // usa codmeta como “código”
                'nombre'       => $m['nombre_corto'] ?? $m['desmeta'] ?? ('Meta '.$m['codmeta']),
                'desmeta'      => $m['desmeta'] ?? null,
                'external_last_seen_at' => now(),
            ]
        );

        // 2) Importar/actualizar PECOSAs de esa meta (filtra por anio+cod_meta)
        //    Paginamos hasta terminar (si el API lo devuelve paginado)
        $imported = 0;
        $page = 1;
        do {
            $batch = $this->pecosas->index([
                'page' => $page,
                'per_page' => 100,
                'anio' => $m['anio'],
                'cod_meta' => $m['codmeta'], // el API entrega cod_meta en la data; si no filtra server-side, filtraremos client-side
            ]);

            $rows = $batch['data'] ?? [];
            foreach ($rows as $r) {
                // Sanity: si por alguna razón vinieron otras metas, filtramos aquí:
                if (!empty($r['cod_meta']) && $r['cod_meta'] !== $m['codmeta']) continue;

                ItemPecosa::updateOrCreate(
                    ['ext_idcompradet' => $r['idcompradet']], // clave externa
                    [
                        'obra_id'      => $obra->id,
                        'anio'         => $r['anio'] ?? null,
                        'numero'       => $r['numero'] ?? null,
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
                        'numero_origen'=> $r['numero_origen'] ?? null,
                        'external_last_seen_at' => now(),
                    ]
                );
                $imported++;
            }

            $next = $batch['next_page_url'] ?? null;
            $page++;
        } while ($next);

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
}

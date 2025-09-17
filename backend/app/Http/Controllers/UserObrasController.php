<?php

namespace App\Http\Controllers;

use App\Models\Obra;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserObrasController extends Controller
{
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
}

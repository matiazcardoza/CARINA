<?php

namespace App\Http\Controllers;

// use Illuminate\Http\Request;

// namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Obra;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MembersController extends Controller
{
    // GET /admin/obras/{obra}/miembros
  public function index(Obra $obra, Request $request) {
    // asegurar team activo=obra para leer roles de esa obra
    setPermissionsTeamId($obra->id);
    $users = DB::table('obra_user')
      ->where('obra_id',$obra->id)
      ->join('users','users.id','=','obra_user.user_id')
      ->select('users.id','users.name','users.email')
      ->get();

    $data = $users->map(function($u){
      $user = User::find($u->id);
      // limpiar relaciones antes de leer roles con team activo
      $user->unsetRelation('roles')->unsetRelation('permissions');
      return [
        'id'    => $u->id,
        'name'  => $u->name,
        'email' => $u->email,
        'roles' => $user->roles->pluck('name')->values(), // roles en ESTA obra
      ];
    });

    return response()->json($data);
  }

  // POST /admin/obras/{obra}/miembros  body: { user_id:int, roles:string[] }
  public function upsert(Obra $obra, Request $request) {
    $validated = $request->validate([
      'user_id' => ['required','exists:users,id'],
      'roles'   => ['array'], // ej ['almacenero_principal','visor']
      'roles.*' => ['string'],
    ]);

    // 1) asegurar membresía
    DB::table('obra_user')->updateOrInsert(
      ['obra_id'=>$obra->id,'user_id'=>$validated['user_id']],
      ['updated_at'=>now(),'created_at'=>now()]
    );

    // 2) asignar roles EN ESA OBRA (usar team activo + unset relations)
    $user = User::findOrFail($validated['user_id']);
    setPermissionsTeamId($obra->id);
    $user->unsetRelation('roles')->unsetRelation('permissions');

    // si el admin te manda roles, sincronízalos; si viene vacío, deja los que existan
    if (isset($validated['roles'])) {
      $user->syncRoles($validated['roles']); // team = obra actual
    }

    return response()->json(['ok'=>true]);
  }

  // DELETE /admin/obras/{obra}/miembros/{user}
  public function destroy(Obra $obra, User $user) {
    DB::table('obra_user')->where([
      'obra_id'=>$obra->id,'user_id'=>$user->id
    ])->delete();

    // Opcional: también quitar roles en esa obra
    setPermissionsTeamId($obra->id);
    $user->unsetRelation('roles')->unsetRelation('permissions');
    $user->syncRoles([]); // limpia roles de esa obra

    return response()->json(['ok'=>true]);
  }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('permissions')
            ->addSelect([
                'users_count' => DB::table('model_has_roles')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('model_has_roles.role_id', 'roles.id')
                    ->where('model_type', '=', \App\Models\User::class)
            ])
            ->get();

        return response()->json([
            'message' => 'Roles retrieved successfully',
            'data' => $roles
        ], 200);
    }

    public function createRole(Request $request)
    {
        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'api'
        ]);

        return response()->json([
            'message' => 'Role created successfully',
            'data' => $role
        ], 201);
    }

    public function updateRole(Request $request)
    {
        $role = Role::find($request->id);
        $role->update([
            'name' => $request->name,
        ]);
        return response()->json([
            'message' => 'Role updated successfully',
            'data' => $role
        ], 200);
    }

    public function destroy($id)
    {
        $role = Role::find($id);
        if (!$role) {
            return response()->json([
                'message' => 'Role not found'
            ], 404);
        }
        DB::table('model_has_roles')
            ->where('role_id', $role->id)
            ->where('model_type', \App\Models\User::class)
            ->delete();
        Role::withoutEvents(function () use ($role) {
            $role->delete();
        });

        return response()->json([
            'message' => 'Role deleted successfully'
        ], 200);
    }
    
    public function getRolePermissions(Request $request){
        $role = Role::with('permissions')->find($request->role_id);
            
        $allPermissions = Permission::where('guard_name', 'api')->get();
        $rolePermissions = $role->permissions->pluck('name')->toArray();
            
        $groupedPermissions = $allPermissions->groupBy('module')->map(function ($modulePermissions, $module) use ($rolePermissions) {
            return [
                'module' => strtolower(str_replace(' ', '_', $module)),
                'moduleLabel' => $module,
                'permissions' => $modulePermissions->map(function ($permission) use ($rolePermissions) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'label' => $permission->label ?? $permission->name,
                        'module' => $permission->module,
                        'guard_name' => $permission->guard_name,
                        'assigned' => in_array($permission->name, $rolePermissions)
                    ];
                })->values()->all()
            ];
        })->values()->all();

        return response()->json([
            'message' => 'Permisos del rol obtenidos correctamente',
            'data' => [
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                ],
                'modulePermissions' => $groupedPermissions,
                'currentPermissions' => $rolePermissions
            ]
        ], 200);
    }

    public function updateRolePermissions(Request $request){
        $role = Role::findOrFail($request->role_id);
        $role->syncPermissions($request->permissions);
        $updatedRole = Role::with('permissions')->find($role->id);

        return response()->json([
            'message' => 'Permisos del rol actualizados correctamente',
            'data' => [
                'role' => [
                    'id' => $updatedRole->id,
                    'name' => $updatedRole->name,
                    'guard_name' => $updatedRole->guard_name,
                ],
                'permissions' => $updatedRole->permissions->pluck('name')->toArray()
            ]
        ], 200);
    }
}

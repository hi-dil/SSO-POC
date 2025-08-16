<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RoleManagementController extends Controller
{
    public function index()
    {
        // Load all data for the UI
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();
        $users = User::with(['roles.permissions', 'tenants'])->get();

        return view('admin.roles.index', compact('roles', 'permissions', 'users'));
    }

    public function getRoles(): JsonResponse
    {
        $roles = Role::with('permissions')->get();
        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }

    public function getPermissions(): JsonResponse
    {
        $permissions = Permission::all();
        return response()->json([
            'success' => true,
            'data' => $permissions
        ]);
    }

    public function getUsers(): JsonResponse
    {
        $users = User::with(['roles.permissions', 'tenants'])->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'roles' => $user->roles->map(function ($role) {
                    return [
                        'role' => [
                            'id' => $role->id,
                            'name' => $role->name,
                            'slug' => $role->slug,
                            'is_system' => $role->is_system
                        ],
                        'tenant_id' => $role->pivot->tenant_id
                    ];
                })
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }
}
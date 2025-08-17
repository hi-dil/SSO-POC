<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Tenant;
use App\DTOs\Request\CreateRoleRequestDTO;
use App\DTOs\Response\RoleResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RoleManagementController extends Controller
{
    public function index()
    {
        // Load all data for the UI
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all();
        $tenants = Tenant::where('is_active', true)->get();
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
                        'tenant_id' => $role->pivot->tenant_id ?? null
                    ];
                })
            ];
        });

        return view('admin.roles.index', compact('roles', 'permissions', 'users', 'tenants'));
    }

    public function create()
    {
        $permissions = Permission::all();
        return view('admin.roles.create', compact('permissions'));
    }

    public function edit(Role $role)
    {
        $role->load('permissions');
        $permissions = Permission::all();
        return view('admin.roles.edit', compact('role', 'permissions'));
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
                        'tenant_id' => $role->pivot->tenant_id ?? null
                    ];
                })
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:roles,name',
                'slug' => 'nullable|string|max:255|unique:roles,slug',
                'description' => 'nullable|string|max:500',
                'permissions' => 'nullable|array',
                'permissions.*' => 'string|exists:permissions,slug'
            ]);

            $dto = CreateRoleRequestDTO::fromArray($validatedData);

            $role = Role::create([
                'name' => $dto->name,
                'slug' => $dto->slug ?: \Str::slug($dto->name),
                'description' => $dto->description,
                'guard_name' => 'web',
                'is_system' => false
            ]);

            if (!empty($dto->permissions)) {
                $permissions = Permission::whereIn('slug', $dto->permissions)->get();
                $role->permissions()->sync($permissions->pluck('id'));
            }

            $role->load('permissions');

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role created successfully',
                    'data' => RoleResponseDTO::fromModel($role, true)->toArray()
                ], 201);
            }

            return redirect()->route('admin.roles.index')
                ->with('success', 'Role created successfully');

        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }

            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    public function update(Request $request, Role $role)
    {
        try {
            if ($role->is_system) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'System roles cannot be updated'
                    ], 403);
                }

                return redirect()->back()
                    ->with('error', 'System roles cannot be updated');
            }

            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
                'slug' => 'nullable|string|max:255|unique:roles,slug,' . $role->id,
                'description' => 'nullable|string|max:500',
                'permissions' => 'nullable|array',
                'permissions.*' => 'string|exists:permissions,slug'
            ]);

            $dto = CreateRoleRequestDTO::fromArray($validatedData);

            $role->update([
                'name' => $dto->name,
                'slug' => $dto->slug ?: \Str::slug($dto->name),
                'description' => $dto->description
            ]);

            if (!empty($dto->permissions)) {
                $permissions = Permission::whereIn('slug', $dto->permissions)->get();
                $role->permissions()->sync($permissions->pluck('id'));
            } else {
                $role->permissions()->sync([]);
            }

            $role->load('permissions');

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Role updated successfully',
                    'data' => RoleResponseDTO::fromModel($role, true)->toArray()
                ]);
            }

            return redirect()->route('admin.roles.index')
                ->with('success', 'Role updated successfully');

        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }

            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $role = Role::findOrFail($id);

            if ($role->is_system) {
                return response()->json([
                    'success' => false,
                    'message' => 'System roles cannot be deleted'
                ], 403);
            }

            $role->delete();

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        }
    }

    public function assignUserRole(Request $request, string $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            
            $validated = $request->validate([
                'role_slug' => 'required|string|exists:roles,slug',
                'tenant_id' => 'nullable|string|exists:tenants,id'
            ]);

            $role = Role::where('slug', $validated['role_slug'])->firstOrFail();
            
            // Check if user already has this role in this scope
            if ($user->hasRole($role->name, $validated['tenant_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'User already has this role in the specified scope'
                ], 400);
            }
            
            // Use the assignRole method which should handle tenant-specific assignments
            $user->assignRole($role->name, $validated['tenant_id']);

            return response()->json([
                'success' => true,
                'message' => 'Role assigned successfully'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User or role not found'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function removeUserRole(Request $request, string $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            
            $validated = $request->validate([
                'role_slug' => 'required|string|exists:roles,slug',
                'tenant_id' => 'nullable|string|exists:tenants,id'
            ]);

            $role = Role::where('slug', $validated['role_slug'])->firstOrFail();
            
            // Use the removeRole method which should handle tenant-specific assignments
            $user->removeRole($role->name, $validated['tenant_id']);

            return response()->json([
                'success' => true,
                'message' => 'Role removed successfully'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User or role not found'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
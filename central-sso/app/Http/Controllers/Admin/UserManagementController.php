<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    public function index()
    {
        $users = User::with(['roles.permissions', 'tenants'])->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'tenants' => $user->tenants->map(function ($tenant) {
                    return [
                        'id' => $tenant->id,
                        'name' => $tenant->name,
                        'slug' => $tenant->slug
                    ];
                }),
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

        $tenants = Tenant::where('is_active', true)->get();

        return view('admin.users.index', compact('users', 'tenants'));
    }

    public function getUsers(): JsonResponse
    {
        $users = User::with(['roles.permissions', 'tenants'])->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'tenants' => $user->tenants->map(function ($tenant) {
                    return [
                        'id' => $tenant->id,
                        'name' => $tenant->name,
                        'slug' => $tenant->slug
                    ];
                }),
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

    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'is_admin' => 'boolean',
                'tenant_ids' => 'nullable|array',
                'tenant_ids.*' => 'string|exists:tenants,id'
            ]);

            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'is_admin' => $validatedData['is_admin'] ?? false,
            ]);

            // Attach tenants if provided
            if (!empty($validatedData['tenant_ids'])) {
                $user->tenants()->sync($validatedData['tenant_ids']);
            }

            $user->load(['tenants', 'roles']);

            return response()->json([
                'success' => true,
                'message' => 'User created successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_admin' => $user->is_admin,
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                    'tenants' => $user->tenants,
                    'roles' => []
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $id,
                'password' => 'nullable|string|min:8|confirmed',
                'is_admin' => 'boolean',
                'tenant_ids' => 'nullable|array',
                'tenant_ids.*' => 'string|exists:tenants,id'
            ]);

            $updateData = [
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'is_admin' => $validatedData['is_admin'] ?? false,
            ];

            // Only update password if provided
            if (!empty($validatedData['password'])) {
                $updateData['password'] = Hash::make($validatedData['password']);
            }

            $user->update($updateData);

            // Update tenant associations
            if (isset($validatedData['tenant_ids'])) {
                $user->tenants()->sync($validatedData['tenant_ids']);
            }

            $user->load(['tenants', 'roles']);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_admin' => $user->is_admin,
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                    'tenants' => $user->tenants,
                    'roles' => $user->roles
                ]
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);

            // Prevent deletion of current user
            if ($user->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own account'
                ], 403);
            }

            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function assignTenant(Request $request, string $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            
            $validated = $request->validate([
                'tenant_id' => 'required|string|exists:tenants,id'
            ]);

            if (!$user->tenants->contains('id', $validated['tenant_id'])) {
                $user->tenants()->attach($validated['tenant_id']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Tenant assigned successfully'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function removeTenant(Request $request, string $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            
            $validated = $request->validate([
                'tenant_id' => 'required|string|exists:tenants,id'
            ]);

            $user->tenants()->detach($validated['tenant_id']);

            return response()->json([
                'success' => true,
                'message' => 'Tenant access removed successfully'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }
}
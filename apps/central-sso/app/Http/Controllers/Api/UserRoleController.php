<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Tenant;
use App\DTOs\Request\AssignRoleRequestDTO;
use App\DTOs\Response\RoleResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @OA\Tag(
 *     name="User Roles",
 *     description="User role assignment and management endpoints"
 * )
 */
class UserRoleController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/users/{userId}/roles",
     *     summary="Get all roles for a user",
     *     tags={"User Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="tenant_id",
     *         in="query",
     *         description="Filter by tenant ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="include_global",
     *         in="query",
     *         description="Include global roles",
     *         required=false,
     *         @OA\Schema(type="boolean", default=true)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="role", ref="#/components/schemas/RoleResponseDTO"),
     *                     @OA\Property(property="tenant_id", type="integer", example=1)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function getUserRoles(Request $request, string $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            $tenantId = $request->query('tenant_id');
            $includeGlobal = $request->boolean('include_global', true);

            $query = $user->roles()->with('permissions');

            if ($tenantId !== null) {
                $query->wherePivot('tenant_id', $tenantId);
            } elseif (!$includeGlobal) {
                $query->whereNotNull('tenant_id');
            }

            $userRoles = $query->get();

            $data = $userRoles->map(function ($role) {
                return [
                    'role' => RoleResponseDTO::fromModel($role, true)->toArray(),
                    'tenant_id' => $role->pivot->tenant_id
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/users/{userId}/roles",
     *     summary="Assign a role to a user",
     *     tags={"User Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AssignRoleRequestDTO")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role assigned successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Role assigned successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Role already assigned"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User or role not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function assignRole(Request $request, string $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);

            $validatedData = $request->validate([
                'role_slug' => 'required|string|exists:roles,slug',
                'tenant_id' => 'nullable|integer|exists:tenants,id'
            ]);

            $dto = AssignRoleRequestDTO::fromArray($validatedData);
            $role = Role::where('slug', $dto->role_slug)->firstOrFail();

            if ($dto->tenant_id && !Tenant::find($dto->tenant_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant not found'
                ], 404);
            }

            if ($user->hasRole($role->name, $dto->tenant_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role already assigned to user'
                ], 400);
            }

            $user->assignRole($role, $dto->tenant_id);

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
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{userId}/roles",
     *     summary="Remove a role from a user",
     *     tags={"User Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/AssignRoleRequestDTO")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role removed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Role removed successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Role not assigned to user"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User or role not found"
     *     )
     * )
     */
    public function removeRole(Request $request, string $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);

            $validatedData = $request->validate([
                'role_slug' => 'required|string|exists:roles,slug',
                'tenant_id' => 'nullable|integer|exists:tenants,id'
            ]);

            $dto = AssignRoleRequestDTO::fromArray($validatedData);
            $role = Role::where('slug', $dto->role_slug)->firstOrFail();

            if (!$user->hasRole($role->name, $dto->tenant_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not assigned to user'
                ], 400);
            }

            $user->removeRole($role, $dto->tenant_id);

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
        }
    }

    /**
     * @OA\Put(
     *     path="/api/users/{userId}/roles/sync",
     *     summary="Sync roles for a user in a specific tenant",
     *     tags={"User Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="roles",
     *                 type="array",
     *                 description="Array of role slugs to assign",
     *                 @OA\Items(type="string"),
     *                 example={"admin", "manager"}
     *             ),
     *             @OA\Property(
     *                 property="tenant_id",
     *                 type="integer",
     *                 description="Tenant ID (null for global roles)",
     *                 example=1
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Roles synced successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Roles synced successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function syncRoles(Request $request, string $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);

            $validatedData = $request->validate([
                'roles' => 'required|array',
                'roles.*' => 'string|exists:roles,slug',
                'tenant_id' => 'nullable|integer|exists:tenants,id'
            ]);

            $tenantId = $validatedData['tenant_id'] ?? null;
            $roles = $validatedData['roles'];

            if ($tenantId && !Tenant::find($tenantId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tenant not found'
                ], 404);
            }

            $user->syncRoles($roles, $tenantId);

            return response()->json([
                'success' => true,
                'message' => 'Roles synced successfully'
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

    /**
     * @OA\Get(
     *     path="/api/users/{userId}/permissions",
     *     summary="Get all permissions for a user through roles",
     *     tags={"User Roles"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="userId",
     *         in="path",
     *         description="User ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="tenant_id",
     *         in="query",
     *         description="Filter by tenant ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/PermissionResponseDTO")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function getUserPermissions(Request $request, string $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            $tenantId = $request->query('tenant_id');

            $permissions = $user->getAllPermissions($tenantId);

            $data = $permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'slug' => $permission->slug,
                    'description' => $permission->description,
                    'category' => $permission->category,
                    'is_system' => $permission->is_system
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
    }
}

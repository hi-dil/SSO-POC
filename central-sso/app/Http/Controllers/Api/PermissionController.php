<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\DTOs\Response\PermissionResponseDTO;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @OA\Tag(
 *     name="Permissions",
 *     description="Permission management endpoints"
 * )
 */
class PermissionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/permissions",
     *     summary="Get all permissions",
     *     tags={"Permissions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by category",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="include_system",
     *         in="query",
     *         description="Include system permissions",
     *         required=false,
     *         @OA\Schema(type="boolean", default=true)
     *     ),
     *     @OA\Parameter(
     *         name="include_roles",
     *         in="query",
     *         description="Include roles count",
     *         required=false,
     *         @OA\Schema(type="boolean", default=false)
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
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $category = $request->query('category');
        $includeSystem = $request->boolean('include_system', true);
        $includeRoles = $request->boolean('include_roles', false);

        $query = Permission::query();

        if ($category) {
            $query->byCategory($category);
        }

        if (!$includeSystem) {
            $query->custom();
        }

        if ($includeRoles) {
            $query->withCount('roles');
        }

        $permissions = $query->get();

        $data = $permissions->map(function ($permission) use ($includeRoles) {
            return PermissionResponseDTO::fromModel($permission, $includeRoles)->toArray();
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/permissions",
     *     summary="Create a new permission",
     *     tags={"Permissions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Create Reports"),
     *             @OA\Property(property="slug", type="string", example="reports.create"),
     *             @OA\Property(property="description", type="string", example="Can create new reports"),
     *             @OA\Property(property="category", type="string", example="reports")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Permission created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/PermissionResponseDTO")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:permissions,name',
                'slug' => 'nullable|string|max:255|unique:permissions,slug',
                'description' => 'nullable|string|max:500',
                'category' => 'nullable|string|max:100'
            ]);

            $permission = Permission::create([
                'name' => $validatedData['name'],
                'slug' => $validatedData['slug'] ?: \Str::slug($validatedData['name']),
                'description' => $validatedData['description'] ?? null,
                'category' => $validatedData['category'] ?? 'custom',
                'guard_name' => 'web',
                'is_system' => false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission created successfully',
                'data' => PermissionResponseDTO::fromModel($permission)->toArray()
            ], 201);

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
     *     path="/api/permissions/{id}",
     *     summary="Get a specific permission",
     *     tags={"Permissions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Permission ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/PermissionResponseDTO")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Permission not found"
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $permission = Permission::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => PermissionResponseDTO::fromModel($permission, true)->toArray()
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Permission not found'
            ], 404);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/permissions/{id}",
     *     summary="Update a permission",
     *     tags={"Permissions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Permission ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Create Reports"),
     *             @OA\Property(property="slug", type="string", example="reports.create"),
     *             @OA\Property(property="description", type="string", example="Can create new reports"),
     *             @OA\Property(property="category", type="string", example="reports")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/PermissionResponseDTO")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Cannot update system permission"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Permission not found"
     *     )
     * )
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $permission = Permission::findOrFail($id);

            if ($permission->is_system) {
                return response()->json([
                    'success' => false,
                    'message' => 'System permissions cannot be updated'
                ], 403);
            }

            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:permissions,name,' . $id,
                'slug' => 'nullable|string|max:255|unique:permissions,slug,' . $id,
                'description' => 'nullable|string|max:500',
                'category' => 'nullable|string|max:100'
            ]);

            $permission->update([
                'name' => $validatedData['name'],
                'slug' => $validatedData['slug'] ?: \Str::slug($validatedData['name']),
                'description' => $validatedData['description'],
                'category' => $validatedData['category']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission updated successfully',
                'data' => PermissionResponseDTO::fromModel($permission, true)->toArray()
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Permission not found'
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
     *     path="/api/permissions/{id}",
     *     summary="Delete a permission",
     *     tags={"Permissions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Permission ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Permission deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Permission deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Cannot delete system permission"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Permission not found"
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $permission = Permission::findOrFail($id);

            if ($permission->is_system) {
                return response()->json([
                    'success' => false,
                    'message' => 'System permissions cannot be deleted'
                ], 403);
            }

            $permission->delete();

            return response()->json([
                'success' => true,
                'message' => 'Permission deleted successfully'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Permission not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/permissions/categories",
     *     summary="Get all permission categories",
     *     tags={"Permissions"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(type="string"),
     *                 example={"users", "roles", "tenants", "system", "api"}
     *             )
     *         )
     *     )
     * )
     */
    public function categories(): JsonResponse
    {
        $categories = Permission::query()
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }
}

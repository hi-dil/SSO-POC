<?php

namespace App\DTOs\Response;

/**
 * @OA\Schema(
 *     schema="PermissionResponseDTO",
 *     type="object",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="Permission ID",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Permission name",
 *         example="View Users"
 *     ),
 *     @OA\Property(
 *         property="slug",
 *         type="string",
 *         description="Permission slug",
 *         example="users.view"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         description="Permission description",
 *         example="Allows viewing user list and details"
 *     ),
 *     @OA\Property(
 *         property="category",
 *         type="string",
 *         description="Permission category",
 *         example="users"
 *     ),
 *     @OA\Property(
 *         property="is_system",
 *         type="boolean",
 *         description="Whether the permission is a system permission",
 *         example=false
 *     ),
 *     @OA\Property(
 *         property="roles_count",
 *         type="integer",
 *         description="Number of roles with this permission",
 *         example=3
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Creation timestamp",
 *         example="2025-08-16T12:00:00Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Last update timestamp",
 *         example="2025-08-16T12:00:00Z"
 *     )
 * )
 */
class PermissionResponseDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly ?string $category,
        public readonly bool $is_system,
        public readonly ?int $roles_count = null,
        public readonly ?string $created_at = null,
        public readonly ?string $updated_at = null
    ) {}

    public static function fromModel($permission, bool $includeRelations = false): self
    {
        $rolesCount = null;

        if ($includeRelations) {
            $rolesCount = $permission->roles()->count();
        }

        return new self(
            id: $permission->id,
            name: $permission->name,
            slug: $permission->slug,
            description: $permission->description,
            category: $permission->category,
            is_system: $permission->is_system,
            roles_count: $rolesCount,
            created_at: $permission->created_at?->toISOString(),
            updated_at: $permission->updated_at?->toISOString()
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'category' => $this->category,
            'is_system' => $this->is_system,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        if ($this->roles_count !== null) {
            $data['roles_count'] = $this->roles_count;
        }

        return $data;
    }
}
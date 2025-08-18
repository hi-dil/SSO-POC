<?php

namespace App\DTOs\Response;

/**
 * @OA\Schema(
 *     schema="RoleResponseDTO",
 *     type="object",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="Role ID",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Role name",
 *         example="Manager"
 *     ),
 *     @OA\Property(
 *         property="slug",
 *         type="string",
 *         description="Role slug",
 *         example="manager"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         description="Role description",
 *         example="Can manage users and view reports"
 *     ),
 *     @OA\Property(
 *         property="is_system",
 *         type="boolean",
 *         description="Whether the role is a system role",
 *         example=false
 *     ),
 *     @OA\Property(
 *         property="permissions",
 *         type="array",
 *         description="List of permissions assigned to this role",
 *         @OA\Items(ref="#/components/schemas/PermissionResponseDTO")
 *     ),
 *     @OA\Property(
 *         property="users_count",
 *         type="integer",
 *         description="Number of users with this role",
 *         example=5
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
class RoleResponseDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly ?string $description,
        public readonly bool $is_system,
        public readonly array $permissions = [],
        public readonly ?int $users_count = null,
        public readonly ?string $created_at = null,
        public readonly ?string $updated_at = null
    ) {}

    public static function fromModel($role, bool $includeRelations = false): self
    {
        $permissions = [];
        $usersCount = null;

        if ($includeRelations) {
            $permissions = $role->permissions->map(function ($permission) {
                return PermissionResponseDTO::fromModel($permission)->toArray();
            })->toArray();
            
            $usersCount = $role->users()->count();
        }

        return new self(
            id: $role->id,
            name: $role->name,
            slug: $role->slug,
            description: $role->description,
            is_system: $role->is_system,
            permissions: $permissions,
            users_count: $usersCount,
            created_at: $role->created_at?->toISOString(),
            updated_at: $role->updated_at?->toISOString()
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'is_system' => $this->is_system,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        if (!empty($this->permissions)) {
            $data['permissions'] = $this->permissions;
        }

        if ($this->users_count !== null) {
            $data['users_count'] = $this->users_count;
        }

        return $data;
    }
}
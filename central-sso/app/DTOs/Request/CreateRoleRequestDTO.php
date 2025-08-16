<?php

namespace App\DTOs\Request;

/**
 * @OA\Schema(
 *     schema="CreateRoleRequestDTO",
 *     type="object",
 *     required={"name"},
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Role name",
 *         example="Manager"
 *     ),
 *     @OA\Property(
 *         property="slug",
 *         type="string",
 *         description="Role slug (auto-generated if not provided)",
 *         example="manager"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         description="Role description",
 *         example="Can manage users and view reports"
 *     ),
 *     @OA\Property(
 *         property="permissions",
 *         type="array",
 *         description="Array of permission slugs to assign to this role",
 *         @OA\Items(type="string"),
 *         example={"users.view", "users.create", "reports.view"}
 *     )
 * )
 */
class CreateRoleRequestDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $slug = null,
        public readonly ?string $description = null,
        public readonly array $permissions = []
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'] ?? null,
            description: $data['description'] ?? null,
            permissions: $data['permissions'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'permissions' => $this->permissions,
        ];
    }
}
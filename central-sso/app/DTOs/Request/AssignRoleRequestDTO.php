<?php

namespace App\DTOs\Request;

/**
 * @OA\Schema(
 *     schema="AssignRoleRequestDTO",
 *     type="object",
 *     required={"role_slug"},
 *     @OA\Property(
 *         property="role_slug",
 *         type="string",
 *         description="Role slug to assign",
 *         example="manager"
 *     ),
 *     @OA\Property(
 *         property="tenant_id",
 *         type="integer",
 *         description="Tenant ID for tenant-specific role assignment (optional for global roles)",
 *         example=1
 *     )
 * )
 */
class AssignRoleRequestDTO
{
    public function __construct(
        public readonly string $role_slug,
        public readonly ?int $tenant_id = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            role_slug: $data['role_slug'],
            tenant_id: $data['tenant_id'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'role_slug' => $this->role_slug,
            'tenant_id' => $this->tenant_id,
        ];
    }
}
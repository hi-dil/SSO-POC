<?php

namespace App\DTOs\Response;

/**
 * @OA\Schema(
 *     schema="UserResponseDTO",
 *     type="object",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         description="User ID",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="User's full name",
 *         example="John Doe"
 *     ),
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         format="email",
 *         description="User's email address",
 *         example="john.doe@example.com"
 *     ),
 *     @OA\Property(
 *         property="tenants",
 *         type="array",
 *         description="List of tenant slugs the user has access to",
 *         @OA\Items(type="string"),
 *         example={"tenant1", "tenant2"}
 *     ),
 *     @OA\Property(
 *         property="current_tenant",
 *         type="string",
 *         description="Current active tenant slug",
 *         example="tenant1"
 *     ),
 *     @OA\Property(
 *         property="is_admin",
 *         type="boolean",
 *         description="Whether the user is an admin",
 *         example=false
 *     )
 * )
 */
class UserResponseDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $email,
        public readonly array $tenants,
        public readonly ?string $current_tenant = null,
        public readonly bool $is_admin = false
    ) {}

    public static function fromUser($user, ?string $current_tenant = null): self
    {
        return new self(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            tenants: $user->tenants->pluck('slug')->toArray(),
            current_tenant: $current_tenant,
            is_admin: $user->is_admin ?? false
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'tenants' => $this->tenants,
            'current_tenant' => $this->current_tenant,
            'is_admin' => $this->is_admin,
        ];
    }
}
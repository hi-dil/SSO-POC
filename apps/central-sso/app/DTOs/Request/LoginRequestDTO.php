<?php

namespace App\DTOs\Request;

/**
 * @OA\Schema(
 *     schema="LoginRequestDTO",
 *     type="object",
 *     required={"email", "password", "tenant_slug"},
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         format="email",
 *         description="User's email address",
 *         example="superadmin@sso.com"
 *     ),
 *     @OA\Property(
 *         property="password",
 *         type="string",
 *         format="password",
 *         description="User's password",
 *         example="password"
 *     ),
 *     @OA\Property(
 *         property="tenant_slug",
 *         type="string",
 *         description="Tenant slug",
 *         example="tenant1"
 *     )
 * )
 */
class LoginRequestDTO
{
    public function __construct(
        public readonly string $email,
        public readonly string $password,
        public readonly ?string $tenant = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            password: $data['password'],
            tenant: $data['tenant'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'password' => $this->password,
            'tenant' => $this->tenant,
        ];
    }
}
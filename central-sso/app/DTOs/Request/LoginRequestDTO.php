<?php

namespace App\DTOs\Request;

/**
 * @OA\Schema(
 *     schema="LoginRequestDTO",
 *     type="object",
 *     required={"email", "password"},
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         format="email",
 *         description="User's email address",
 *         example="user@example.com"
 *     ),
 *     @OA\Property(
 *         property="password",
 *         type="string",
 *         format="password",
 *         description="User's password",
 *         example="password123"
 *     ),
 *     @OA\Property(
 *         property="tenant",
 *         type="string",
 *         description="Tenant slug (optional)",
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
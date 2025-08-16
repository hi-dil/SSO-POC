<?php

namespace App\DTOs\Request;

/**
 * @OA\Schema(
 *     schema="RegisterRequestDTO",
 *     type="object",
 *     required={"name", "email", "password", "password_confirmation"},
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
 *         property="password",
 *         type="string",
 *         format="password",
 *         description="User's password (minimum 8 characters)",
 *         example="password123"
 *     ),
 *     @OA\Property(
 *         property="password_confirmation",
 *         type="string",
 *         format="password",
 *         description="Password confirmation",
 *         example="password123"
 *     ),
 *     @OA\Property(
 *         property="tenant_slug",
 *         type="string",
 *         description="Tenant slug for registration",
 *         example="tenant1"
 *     )
 * )
 */
class RegisterRequestDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly string $password_confirmation,
        public readonly ?string $tenant_slug = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            password_confirmation: $data['password_confirmation'],
            tenant_slug: $data['tenant_slug'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
            'tenant_slug' => $this->tenant_slug,
        ];
    }
}
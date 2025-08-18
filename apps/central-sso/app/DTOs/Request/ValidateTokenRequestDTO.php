<?php

namespace App\DTOs\Request;

/**
 * @OA\Schema(
 *     schema="ValidateTokenRequestDTO",
 *     type="object",
 *     required={"token"},
 *     @OA\Property(
 *         property="token",
 *         type="string",
 *         description="JWT token to validate",
 *         example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
 *     )
 * )
 */
class ValidateTokenRequestDTO
{
    public function __construct(
        public readonly string $token
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            token: $data['token']
        );
    }

    public function toArray(): array
    {
        return [
            'token' => $this->token,
        ];
    }
}
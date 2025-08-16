<?php

namespace App\DTOs\Response;

/**
 * @OA\Schema(
 *     schema="ValidateTokenResponseDTO",
 *     type="object",
 *     @OA\Property(
 *         property="valid",
 *         type="boolean",
 *         description="Whether the token is valid",
 *         example=true
 *     ),
 *     @OA\Property(
 *         property="user",
 *         ref="#/components/schemas/UserResponseDTO"
 *     ),
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         description="Validation message",
 *         example="Token is valid"
 *     ),
 *     @OA\Property(
 *         property="expires_at",
 *         type="string",
 *         format="date-time",
 *         description="Token expiration date",
 *         example="2025-08-16T12:00:00Z"
 *     )
 * )
 */
class ValidateTokenResponseDTO
{
    public function __construct(
        public readonly bool $valid,
        public readonly ?UserResponseDTO $user = null,
        public readonly ?string $message = null,
        public readonly ?string $expires_at = null
    ) {}

    public static function valid(UserResponseDTO $user, ?string $expires_at = null): self
    {
        return new self(
            valid: true,
            user: $user,
            message: 'Token is valid',
            expires_at: $expires_at
        );
    }

    public static function invalid(string $message = 'Token is invalid'): self
    {
        return new self(
            valid: false,
            message: $message
        );
    }

    public function toArray(): array
    {
        $data = [
            'valid' => $this->valid,
            'message' => $this->message,
        ];

        if ($this->user) {
            $data['user'] = $this->user->toArray();
        }

        if ($this->expires_at) {
            $data['expires_at'] = $this->expires_at;
        }

        return $data;
    }
}
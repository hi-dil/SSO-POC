<?php

namespace App\DTOs\Response;

/**
 * @OA\Schema(
 *     schema="LoginResponseDTO",
 *     type="object",
 *     @OA\Property(
 *         property="success",
 *         type="boolean",
 *         description="Whether the login was successful",
 *         example=true
 *     ),
 *     @OA\Property(
 *         property="token",
 *         type="string",
 *         description="JWT authentication token",
 *         example="eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
 *     ),
 *     @OA\Property(
 *         property="user",
 *         ref="#/components/schemas/UserResponseDTO"
 *     ),
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         description="Success or error message",
 *         example="Login successful"
 *     ),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         description="Validation errors (if any)",
 *         additionalProperties={"type": "array", "items": {"type": "string"}},
 *         example={"email": {"The email field is required."}}
 *     )
 * )
 */
class LoginResponseDTO
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $token = null,
        public readonly ?UserResponseDTO $user = null,
        public readonly ?string $message = null,
        public readonly ?array $errors = null
    ) {}

    public static function success(string $token, UserResponseDTO $user, string $message = 'Login successful'): self
    {
        return new self(
            success: true,
            token: $token,
            user: $user,
            message: $message
        );
    }

    public static function error(string $message, ?array $errors = null): self
    {
        return new self(
            success: false,
            message: $message,
            errors: $errors
        );
    }

    public function toArray(): array
    {
        $data = [
            'success' => $this->success,
            'message' => $this->message,
        ];

        if ($this->token) {
            $data['token'] = $this->token;
        }

        if ($this->user) {
            $data['user'] = $this->user->toArray();
        }

        if ($this->errors) {
            $data['errors'] = $this->errors;
        }

        return $data;
    }
}
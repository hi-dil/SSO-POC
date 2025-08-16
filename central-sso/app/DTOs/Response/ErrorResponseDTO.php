<?php

namespace App\DTOs\Response;

/**
 * @OA\Schema(
 *     schema="ErrorResponseDTO",
 *     type="object",
 *     @OA\Property(
 *         property="success",
 *         type="boolean",
 *         description="Always false for error responses",
 *         example=false
 *     ),
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         description="Error message",
 *         example="Something went wrong"
 *     ),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         description="Validation errors",
 *         additionalProperties={"type": "array", "items": {"type": "string"}},
 *         example={"field": {"Field is required"}}
 *     ),
 *     @OA\Property(
 *         property="code",
 *         type="integer",
 *         description="HTTP status code",
 *         example=400
 *     )
 * )
 */
class ErrorResponseDTO
{
    public function __construct(
        public readonly string $message,
        public readonly ?array $errors = null,
        public readonly int $code = 400
    ) {}

    public static function validation(array $errors, string $message = 'Validation failed'): self
    {
        return new self(
            message: $message,
            errors: $errors,
            code: 422
        );
    }

    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return new self(
            message: $message,
            code: 401
        );
    }

    public static function forbidden(string $message = 'Forbidden'): self
    {
        return new self(
            message: $message,
            code: 403
        );
    }

    public static function notFound(string $message = 'Not found'): self
    {
        return new self(
            message: $message,
            code: 404
        );
    }

    public function toArray(): array
    {
        $data = [
            'success' => false,
            'message' => $this->message,
            'code' => $this->code,
        ];

        if ($this->errors) {
            $data['errors'] = $this->errors;
        }

        return $data;
    }
}
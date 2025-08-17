<?php

namespace App\DTOs\Response;

/**
 * @OA\Schema(
 *     schema="AuditResponseDTO",
 *     type="object",
 *     title="Audit Response DTO",
 *     description="Response DTO for audit operations",
 *     @OA\Property(property="success", type="boolean", description="Operation success status", example=true),
 *     @OA\Property(property="audit_id", type="integer", description="Audit record ID", example=123),
 *     @OA\Property(property="message", type="string", description="Response message", example="Audit recorded successfully")
 * )
 */
class AuditResponseDTO
{
    public function __construct(
        public readonly bool $success,
        public readonly ?int $audit_id = null,
        public readonly ?string $message = null
    ) {}

    public static function success(?int $audit_id = null, ?string $message = null): self
    {
        return new self(
            success: true,
            audit_id: $audit_id,
            message: $message
        );
    }

    public static function error(string $message): self
    {
        return new self(
            success: false,
            message: $message
        );
    }

    public function toArray(): array
    {
        $data = ['success' => $this->success];
        
        if ($this->audit_id !== null) {
            $data['audit_id'] = $this->audit_id;
        }
        
        if ($this->message !== null) {
            $data['message'] = $this->message;
        }
        
        return $data;
    }
}
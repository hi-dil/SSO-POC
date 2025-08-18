<?php

namespace App\DTOs\Request;

/**
 * @OA\Schema(
 *     schema="LogoutAuditRequestDTO",
 *     type="object",
 *     title="Logout Audit Request DTO",
 *     description="Request DTO for recording logout audit events",
 *     required={"session_id", "tenant_id"},
 *     @OA\Property(property="session_id", type="string", description="Session identifier", example="sess_abc123"),
 *     @OA\Property(property="tenant_id", type="string", description="Tenant identifier", example="tenant1")
 * )
 */
class LogoutAuditRequestDTO
{
    public function __construct(
        public readonly string $session_id,
        public readonly string $tenant_id
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            session_id: $data['session_id'],
            tenant_id: $data['tenant_id']
        );
    }

    public function toArray(): array
    {
        return [
            'session_id' => $this->session_id,
            'tenant_id' => $this->tenant_id,
        ];
    }
}
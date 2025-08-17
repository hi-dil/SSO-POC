<?php

namespace App\DTOs\Request;

/**
 * @OA\Schema(
 *     schema="LoginAuditRequestDTO",
 *     type="object",
 *     title="Login Audit Request DTO",
 *     description="Request DTO for recording login audit events",
 *     required={"email", "tenant_id", "login_method", "is_successful"},
 *     @OA\Property(property="user_id", type="integer", description="User ID (optional)", example=123),
 *     @OA\Property(property="email", type="string", format="email", description="User email address", example="user@example.com"),
 *     @OA\Property(property="tenant_id", type="string", description="Tenant identifier", example="tenant1"),
 *     @OA\Property(property="login_method", type="string", enum={"sso", "direct", "api"}, description="Login method used", example="direct"),
 *     @OA\Property(property="is_successful", type="boolean", description="Whether the login was successful", example=true),
 *     @OA\Property(property="failure_reason", type="string", description="Reason for login failure (if applicable)", example="Invalid credentials"),
 *     @OA\Property(property="ip_address", type="string", format="ip", description="Client IP address", example="192.168.1.1"),
 *     @OA\Property(property="user_agent", type="string", description="Client user agent", example="Mozilla/5.0..."),
 *     @OA\Property(property="session_id", type="string", description="Session identifier", example="sess_abc123")
 * )
 */
class LoginAuditRequestDTO
{
    public function __construct(
        public readonly ?int $user_id,
        public readonly string $email,
        public readonly string $tenant_id,
        public readonly string $login_method,
        public readonly bool $is_successful,
        public readonly ?string $failure_reason = null,
        public readonly ?string $ip_address = null,
        public readonly ?string $user_agent = null,
        public readonly ?string $session_id = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            user_id: $data['user_id'] ?? null,
            email: $data['email'],
            tenant_id: $data['tenant_id'],
            login_method: $data['login_method'],
            is_successful: $data['is_successful'],
            failure_reason: $data['failure_reason'] ?? null,
            ip_address: $data['ip_address'] ?? null,
            user_agent: $data['user_agent'] ?? null,
            session_id: $data['session_id'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'email' => $this->email,
            'tenant_id' => $this->tenant_id,
            'login_method' => $this->login_method,
            'is_successful' => $this->is_successful,
            'failure_reason' => $this->failure_reason,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'session_id' => $this->session_id,
        ];
    }
}
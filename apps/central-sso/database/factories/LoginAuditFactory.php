<?php

namespace Database\Factories;

use App\Models\LoginAudit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LoginAuditFactory extends Factory
{
    protected $model = LoginAudit::class;

    public function definition(): array
    {
        $loginMethods = ['direct', 'sso', 'api'];
        $tenants = ['tenant1', 'tenant2', null];
        
        return [
            'user_id' => User::factory(),
            'tenant_id' => $this->faker->randomElement($tenants),
            'login_method' => $this->faker->randomElement($loginMethods),
            'ip_address' => $this->faker->ipv4,
            'user_agent' => $this->faker->userAgent,
            'session_id' => 'session_' . $this->faker->unique()->uuid,
            'login_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'logout_at' => null,
            'session_duration' => null,
            'is_successful' => true,
            'failure_reason' => null,
        ];
    }

    public function failed(string $reason = 'Invalid credentials'): Factory
    {
        return $this->state(function (array $attributes) use ($reason) {
            return [
                'is_successful' => false,
                'failure_reason' => $reason,
                'user_id' => null, // Failed logins might not have a user_id
            ];
        });
    }

    public function withLogout(): Factory
    {
        return $this->state(function (array $attributes) {
            $loginAt = $attributes['login_at'];
            $logoutAt = $this->faker->dateTimeBetween($loginAt, 'now');
            $duration = $logoutAt->getTimestamp() - $loginAt->getTimestamp();
            
            return [
                'logout_at' => $logoutAt,
                'session_duration' => $duration,
            ];
        });
    }

    public function forTenant(string $tenantId): Factory
    {
        return $this->state(function (array $attributes) use ($tenantId) {
            return [
                'tenant_id' => $tenantId,
            ];
        });
    }

    public function withMethod(string $method): Factory
    {
        return $this->state(function (array $attributes) use ($method) {
            return [
                'login_method' => $method,
            ];
        });
    }

    public function recent(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'login_at' => $this->faker->dateTimeBetween('-1 day', 'now'),
            ];
        });
    }

    public function old(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'login_at' => $this->faker->dateTimeBetween('-90 days', '-30 days'),
            ];
        });
    }
}
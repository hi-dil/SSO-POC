<?php

namespace Database\Factories;

use App\Models\ActiveSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActiveSessionFactory extends Factory
{
    protected $model = ActiveSession::class;

    public function definition(): array
    {
        $loginMethods = ['direct', 'sso', 'api'];
        $tenants = ['tenant1', 'tenant2', null];
        
        return [
            'user_id' => User::factory(),
            'tenant_id' => $this->faker->randomElement($tenants),
            'login_method' => $this->faker->randomElement($loginMethods),
            'session_id' => 'session_' . $this->faker->unique()->uuid,
            'last_activity' => now(),
            'metadata' => json_encode([
                'ip_address' => $this->faker->ipv4,
                'user_agent' => $this->faker->userAgent,
                'login_time' => now()->toISOString(),
            ]),
        ];
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

    public function expired(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'last_activity' => now()->subHours(3), // Expired after 2 hours
            ];
        });
    }

    public function active(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'last_activity' => now()->subMinutes($this->faker->numberBetween(1, 30)),
            ];
        });
    }
}
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use App\Models\LoginAudit;
use App\Models\ActiveSession;
use App\Services\LoginAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class LoginAuditTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private LoginAuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auditService = app(LoginAuditService::class);
    }

    /** @test */
    public function it_can_record_successful_login()
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        $audit = $this->auditService->recordLogin(
            $user,
            $tenant->id,
            'direct',
            'test_session_123'
        );

        $this->assertDatabaseHas('login_audits', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'login_method' => 'direct',
            'session_id' => 'test_session_123',
            'is_successful' => true,
        ]);

        $this->assertDatabaseHas('active_sessions', [
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'login_method' => 'direct',
            'session_id' => 'test_session_123',
        ]);
    }

    /** @test */
    public function it_can_record_failed_login()
    {
        $audit = $this->auditService->recordFailedLogin(
            'test@example.com',
            'tenant1',
            'direct',
            'Invalid credentials'
        );

        $this->assertDatabaseHas('login_audits', [
            'tenant_id' => 'tenant1',
            'login_method' => 'direct',
            'is_successful' => false,
            'failure_reason' => 'Invalid credentials',
        ]);
    }

    /** @test */
    public function it_can_record_logout()
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        // First record a login
        $audit = $this->auditService->recordLogin(
            $user,
            $tenant->id,
            'direct',
            'test_session_123'
        );

        // Then record logout
        $this->auditService->recordLogout('test_session_123');

        $audit->refresh();
        $this->assertNotNull($audit->logout_at);
        $this->assertNotNull($audit->session_duration);

        $this->assertDatabaseMissing('active_sessions', [
            'session_id' => 'test_session_123',
        ]);
    }

    /** @test */
    public function it_generates_api_session_id_when_not_provided()
    {
        $user = User::factory()->create();

        $audit = $this->auditService->recordLogin(
            $user,
            'tenant1',
            'api'
        );

        $this->assertStringStartsWith('api_', $audit->session_id);
        $this->assertStringContainsString($user->id, $audit->session_id);
    }

    /** @test */
    public function it_can_get_dashboard_statistics()
    {
        // Create test data
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        // Create some audit records
        $this->auditService->recordLogin($user, $tenant->id, 'direct', 'session1');
        $this->auditService->recordLogin($user, $tenant->id, 'sso', 'session2');
        $this->auditService->recordFailedLogin('test@example.com', $tenant->id, 'api', 'Invalid credentials');

        $stats = $this->auditService->getDashboardStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('active_users', $stats);
        $this->assertArrayHasKey('total_sessions', $stats);
        $this->assertArrayHasKey('today_logins', $stats);
        $this->assertArrayHasKey('total_logins_30_days', $stats);
        $this->assertArrayHasKey('unique_users_30_days', $stats);
        $this->assertArrayHasKey('login_trend', $stats);
        $this->assertArrayHasKey('active_by_tenant', $stats);
        $this->assertArrayHasKey('active_by_method', $stats);
        $this->assertArrayHasKey('logins_by_tenant', $stats);
        $this->assertArrayHasKey('logins_by_method', $stats);
        $this->assertArrayHasKey('recent_logins', $stats);
        $this->assertArrayHasKey('active_sessions', $stats);
    }

    /** @test */
    public function it_can_get_user_activity()
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        // Create some audit records for the user
        $this->auditService->recordLogin($user, $tenant->id, 'direct', 'session1');
        $this->auditService->recordLogin($user, $tenant->id, 'sso', 'session2');

        $activity = $this->auditService->getUserActivity($user->id);

        $this->assertIsArray($activity);
        $this->assertArrayHasKey('user', $activity);
        $this->assertArrayHasKey('total_logins', $activity);
        $this->assertArrayHasKey('last_login', $activity);
        $this->assertArrayHasKey('active_sessions', $activity);
        $this->assertArrayHasKey('recent_logins', $activity);
        $this->assertArrayHasKey('is_currently_active', $activity);

        $this->assertEquals($user->id, $activity['user']->id);
        $this->assertEquals(2, $activity['total_logins']);
    }

    /** @test */
    public function it_can_get_tenant_activity()
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        // Create some audit records for the tenant
        $this->auditService->recordLogin($user, $tenant->id, 'direct', 'session1');
        $this->auditService->recordLogin($user, $tenant->id, 'sso', 'session2');

        $activity = $this->auditService->getTenantActivity($tenant->id);

        $this->assertIsArray($activity);
        $this->assertArrayHasKey('tenant_id', $activity);
        $this->assertArrayHasKey('total_logins', $activity);
        $this->assertArrayHasKey('active_users', $activity);
        $this->assertArrayHasKey('recent_logins', $activity);
        $this->assertArrayHasKey('top_users', $activity);

        $this->assertEquals($tenant->id, $activity['tenant_id']);
        $this->assertEquals(2, $activity['total_logins']);
    }

    /** @test */
    public function it_can_cleanup_old_records()
    {
        $user = User::factory()->create();

        // Create an old audit record
        LoginAudit::factory()->create([
            'user_id' => $user->id,
            'login_at' => now()->subDays(100),
        ]);

        // Create a recent audit record
        LoginAudit::factory()->create([
            'user_id' => $user->id,
            'login_at' => now()->subDays(10),
        ]);

        $result = $this->auditService->cleanup(30);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('audit_records_deleted', $result);
        $this->assertArrayHasKey('expired_sessions_deleted', $result);
        $this->assertEquals(1, $result['audit_records_deleted']);
    }

    /** @test */
    public function audit_api_endpoint_can_record_login()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $tenant = Tenant::factory()->create(['id' => 'tenant1']);

        $response = $this->postJson('/api/audit/login', [
            'email' => 'test@example.com',
            'tenant_id' => 'tenant1',
            'login_method' => 'sso',
            'is_successful' => true,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Browser',
            'session_id' => 'test_session_123',
        ]);

        $response->assertStatus(200)
                ->assertJson(['success' => true])
                ->assertJsonStructure(['audit_id']);

        $this->assertDatabaseHas('login_audits', [
            'user_id' => $user->id,
            'tenant_id' => 'tenant1',
            'login_method' => 'sso',
            'is_successful' => true,
        ]);
    }

    /** @test */
    public function audit_api_endpoint_can_record_logout()
    {
        $user = User::factory()->create();

        // First create a login record
        $audit = LoginAudit::factory()->create([
            'user_id' => $user->id,
            'session_id' => 'test_session_123',
            'logout_at' => null,
        ]);

        $response = $this->postJson('/api/audit/logout', [
            'session_id' => 'test_session_123',
            'tenant_id' => 'tenant1',
        ]);

        $response->assertStatus(200)
                ->assertJson(['success' => true]);

        $audit->refresh();
        $this->assertNotNull($audit->logout_at);
    }

    /** @test */
    public function audit_api_validates_required_fields()
    {
        $response = $this->postJson('/api/audit/login', [
            'login_method' => 'sso',
            // Missing required fields
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['email', 'tenant_id']);
    }

    /** @test */
    public function audit_api_handles_nonexistent_user()
    {
        $response = $this->postJson('/api/audit/login', [
            'email' => 'nonexistent@example.com',
            'tenant_id' => 'tenant1',
            'login_method' => 'sso',
            'is_successful' => true,
        ]);

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'User not found in central SSO'
                ]);
    }

    /** @test */
    public function login_audit_model_can_get_recent_activity()
    {
        $user = User::factory()->create();
        LoginAudit::factory()->count(5)->create(['user_id' => $user->id]);

        $recentActivity = LoginAudit::getRecentActivity(3);

        $this->assertCount(3, $recentActivity);
        $this->assertTrue($recentActivity->first()->login_at >= $recentActivity->last()->login_at);
    }

    /** @test */
    public function login_audit_model_can_get_statistics()
    {
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();

        LoginAudit::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'login_method' => 'direct',
            'is_successful' => true,
            'login_at' => now()->subDays(5),
        ]);

        LoginAudit::factory()->create([
            'user_id' => $user->id,
            'tenant_id' => $tenant->id,
            'login_method' => 'sso',
            'is_successful' => true,
            'login_at' => now()->subDays(3),
        ]);

        $stats = LoginAudit::getStatistics(now()->subDays(10), now());

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_logins', $stats);
        $this->assertArrayHasKey('unique_users', $stats);
        $this->assertArrayHasKey('by_tenant', $stats);
        $this->assertArrayHasKey('by_method', $stats);

        $this->assertEquals(2, $stats['total_logins']);
        $this->assertEquals(1, $stats['unique_users']);
    }

    /** @test */
    public function active_session_model_can_track_sessions()
    {
        $user = User::factory()->create();

        ActiveSession::createOrUpdate(
            $user->id,
            'tenant1',
            'direct',
            'test_session',
            ['test_data' => 'value']
        );

        $this->assertDatabaseHas('active_sessions', [
            'user_id' => $user->id,
            'tenant_id' => 'tenant1',
            'login_method' => 'direct',
            'session_id' => 'test_session',
        ]);

        $sessions = ActiveSession::getUserSessions($user->id);
        $this->assertCount(1, $sessions);

        $isActive = ActiveSession::userHasActiveSession($user->id);
        $this->assertTrue($isActive);

        ActiveSession::removeSession('test_session');
        $this->assertDatabaseMissing('active_sessions', [
            'session_id' => 'test_session',
        ]);
    }
}
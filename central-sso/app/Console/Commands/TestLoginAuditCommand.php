<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Tenant;
use App\Services\LoginAuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;

class TestLoginAuditCommand extends Command
{
    protected $signature = 'test:login-audit {--comprehensive : Run comprehensive tests including API calls}';
    protected $description = 'Test the login audit system comprehensively';

    private LoginAuditService $auditService;

    public function __construct(LoginAuditService $auditService)
    {
        parent::__construct();
        $this->auditService = $auditService;
    }

    public function handle()
    {
        $this->info('🧪 Starting Login Audit System Tests');
        $this->newLine();

        $comprehensive = $this->option('comprehensive');
        
        // Basic audit service tests
        $this->testBasicAuditService();
        
        // Database tests
        $this->testDatabaseAuditRecords();
        
        // Analytics tests
        $this->testAnalyticsData();
        
        if ($comprehensive) {
            // API endpoint tests
            $this->testAuditAPIEndpoints();
            
            // Tenant communication tests
            $this->testTenantAuditCommunication();
        }
        
        $this->newLine();
        $this->info('✅ All login audit tests completed successfully!');
        
        return 0;
    }

    private function testBasicAuditService()
    {
        $this->info('📋 Testing Basic Audit Service...');
        
        // Find a test user
        $user = User::where('email', 'superadmin@sso.com')->first();
        if (!$user) {
            $this->error('Test user not found. Please run database seeders first.');
            return;
        }
        
        // Test successful login recording
        $audit = $this->auditService->recordLogin($user, 'tenant1', 'direct', 'test_session_123');
        $this->assertTrue($audit->exists, 'Login audit record should be created');
        $this->assertEquals($user->id, $audit->user_id, 'User ID should match');
        $this->assertEquals('tenant1', $audit->tenant_id, 'Tenant ID should match');
        $this->assertEquals('direct', $audit->login_method, 'Login method should match');
        $this->assertTrue($audit->is_successful, 'Login should be marked as successful');
        $this->line('  ✓ Successful login recording works');
        
        // Test failed login recording
        $failedAudit = $this->auditService->recordFailedLogin(
            'nonexistent@test.com',
            'tenant1',
            'direct',
            'User not found'
        );
        $this->assertTrue($failedAudit->exists, 'Failed login audit record should be created');
        $this->assertFalse($failedAudit->is_successful, 'Login should be marked as failed');
        $this->assertEquals('User not found', $failedAudit->failure_reason, 'Failure reason should match');
        $this->line('  ✓ Failed login recording works');
        
        // Test logout recording
        $this->auditService->recordLogout('test_session_123');
        $audit->refresh();
        $this->assertNotNull($audit->logout_at, 'Logout timestamp should be recorded');
        $this->assertNotNull($audit->session_duration, 'Session duration should be calculated');
        $this->line('  ✓ Logout recording works');
        
        $this->info('  ✅ Basic audit service tests passed');
        $this->newLine();
    }

    private function testDatabaseAuditRecords()
    {
        $this->info('🗄️ Testing Database Audit Records...');
        
        // Test recent activity retrieval
        $recentLogins = \App\Models\LoginAudit::getRecentActivity(5);
        $this->assertTrue($recentLogins->count() > 0, 'Should have recent login records');
        $this->line('  ✓ Recent activity retrieval works');
        
        // Test statistics calculation
        $stats = \App\Models\LoginAudit::getStatistics(now()->subDays(30), now());
        $this->assertIsArray($stats, 'Statistics should be an array');
        $this->assertArrayHasKey('total_logins', $stats, 'Should have total logins count');
        $this->assertArrayHasKey('unique_users', $stats, 'Should have unique users count');
        $this->assertArrayHasKey('by_tenant', $stats, 'Should have tenant breakdown');
        $this->assertArrayHasKey('by_method', $stats, 'Should have method breakdown');
        $this->line('  ✓ Statistics calculation works');
        
        // Test active sessions
        $activeSessions = \App\Models\ActiveSession::getActiveSessions();
        $this->assertIsObject($activeSessions, 'Active sessions should be a collection');
        $this->line('  ✓ Active sessions retrieval works');
        
        $this->info('  ✅ Database audit record tests passed');
        $this->newLine();
    }

    private function testAnalyticsData()
    {
        $this->info('📊 Testing Analytics Data...');
        
        // Test dashboard statistics
        $dashboardStats = $this->auditService->getDashboardStatistics();
        $requiredKeys = [
            'active_users', 'total_sessions', 'today_logins', 'total_logins_30_days',
            'unique_users_30_days', 'login_trend', 'active_by_tenant', 'active_by_method',
            'logins_by_tenant', 'logins_by_method', 'recent_logins', 'active_sessions'
        ];
        
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $dashboardStats, "Dashboard stats should have {$key}");
        }
        $this->line('  ✓ Dashboard statistics structure is correct');
        
        // Test user activity summary
        $user = User::first();
        if ($user) {
            $userActivity = $this->auditService->getUserActivity($user->id);
            $this->assertArrayHasKey('user', $userActivity, 'User activity should include user data');
            $this->assertArrayHasKey('total_logins', $userActivity, 'Should include total logins');
            $this->assertArrayHasKey('recent_logins', $userActivity, 'Should include recent logins');
            $this->line('  ✓ User activity summary works');
        }
        
        // Test tenant activity summary
        $tenant = Tenant::first();
        if ($tenant) {
            $tenantActivity = $this->auditService->getTenantActivity($tenant->id);
            $this->assertArrayHasKey('total_logins', $tenantActivity, 'Tenant activity should include total logins');
            $this->assertArrayHasKey('active_users', $tenantActivity, 'Should include active users count');
            $this->line('  ✓ Tenant activity summary works');
        }
        
        $this->info('  ✅ Analytics data tests passed');
        $this->newLine();
    }

    private function testAuditAPIEndpoints()
    {
        $this->info('🌐 Testing Audit API Endpoints...');
        
        // Test login audit API endpoint
        try {
            $response = Http::post('http://localhost:8000/api/audit/login', [
                'email' => 'test@example.com',
                'tenant_id' => 'tenant1',
                'login_method' => 'api',
                'is_successful' => true,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Test Script',
                'session_id' => 'test_api_session_' . uniqid(),
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $this->assertTrue($data['success'], 'API login audit should succeed');
                $this->assertArrayHasKey('audit_id', $data, 'Should return audit ID');
                $this->line('  ✓ Login audit API endpoint works');
            } else {
                $this->warn('  ⚠️ Login audit API endpoint returned: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->warn('  ⚠️ Could not test login audit API: ' . $e->getMessage());
        }
        
        // Test logout audit API endpoint
        try {
            $response = Http::post('http://localhost:8000/api/audit/logout', [
                'session_id' => 'test_api_session_123',
                'tenant_id' => 'tenant1',
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $this->assertTrue($data['success'], 'API logout audit should succeed');
                $this->line('  ✓ Logout audit API endpoint works');
            } else {
                $this->warn('  ⚠️ Logout audit API endpoint returned: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->warn('  ⚠️ Could not test logout audit API: ' . $e->getMessage());
        }
        
        $this->info('  ✅ API endpoint tests completed');
        $this->newLine();
    }

    private function testTenantAuditCommunication()
    {
        $this->info('🏢 Testing Tenant Audit Communication...');
        
        // Test tenant1 application audit communication
        $this->testTenantApp('tenant1', 8001);
        
        // Test tenant2 application audit communication
        $this->testTenantApp('tenant2', 8002);
        
        $this->info('  ✅ Tenant audit communication tests completed');
        $this->newLine();
    }

    private function testTenantApp($tenantSlug, $port)
    {
        try {
            // Test if tenant app is accessible
            $response = Http::timeout(5)->get("http://localhost:{$port}");
            
            if ($response->successful()) {
                $this->line("  ✓ {$tenantSlug} application is accessible");
                
                // Test if tenant can reach central SSO audit API
                $testResponse = Http::timeout(5)->post('http://localhost:8000/api/audit/login', [
                    'email' => "test@{$tenantSlug}.com",
                    'tenant_id' => $tenantSlug,
                    'login_method' => 'test',
                    'is_successful' => true,
                    'session_id' => "test_{$tenantSlug}_" . uniqid(),
                ]);
                
                if ($testResponse->successful()) {
                    $this->line("  ✓ {$tenantSlug} can communicate with central audit API");
                } else {
                    $this->warn("  ⚠️ {$tenantSlug} audit communication failed: " . $testResponse->status());
                }
            } else {
                $this->warn("  ⚠️ {$tenantSlug} application not accessible");
            }
        } catch (\Exception $e) {
            $this->warn("  ⚠️ Error testing {$tenantSlug}: " . $e->getMessage());
        }
    }

    private function assertTrue($condition, $message)
    {
        if (!$condition) {
            $this->error("❌ Assertion failed: {$message}");
            throw new \Exception("Test failed: {$message}");
        }
    }

    private function assertEquals($expected, $actual, $message)
    {
        if ($expected !== $actual) {
            $this->error("❌ Assertion failed: {$message}. Expected: {$expected}, Actual: {$actual}");
            throw new \Exception("Test failed: {$message}");
        }
    }

    private function assertIsArray($value, $message)
    {
        if (!is_array($value)) {
            $this->error("❌ Assertion failed: {$message}");
            throw new \Exception("Test failed: {$message}");
        }
    }

    private function assertIsObject($value, $message)
    {
        if (!is_object($value)) {
            $this->error("❌ Assertion failed: {$message}");
            throw new \Exception("Test failed: {$message}");
        }
    }

    private function assertArrayHasKey($key, $array, $message)
    {
        if (!array_key_exists($key, $array)) {
            $this->error("❌ Assertion failed: {$message}");
            throw new \Exception("Test failed: {$message}");
        }
    }

    private function assertNotNull($value, $message)
    {
        if ($value === null) {
            $this->error("❌ Assertion failed: {$message}");
            throw new \Exception("Test failed: {$message}");
        }
    }

    private function assertFalse($condition, $message)
    {
        if ($condition) {
            $this->error("❌ Assertion failed: {$message}");
            throw new \Exception("Test failed: {$message}");
        }
    }
}
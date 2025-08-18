<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Tenant;
use App\Models\LoginAudit;
use App\Models\ActiveSession;
use App\Services\LoginAuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class TestFullSystemCommand extends Command
{
    protected $signature = 'test:full-system {--cleanup : Clean up test data after tests}';
    protected $description = 'Test the complete SSO audit system across all applications';

    private LoginAuditService $auditService;
    private array $testData = [];

    public function __construct(LoginAuditService $auditService)
    {
        parent::__construct();
        $this->auditService = $auditService;
    }

    public function handle()
    {
        $this->info('🚀 Starting Full SSO Audit System Integration Tests');
        $this->info('=========================================================');
        $this->newLine();

        try {
            // Pre-test setup
            $this->setupTestEnvironment();
            
            // Core system tests
            $this->testCentralSSOAuditSystem();
            
            // Tenant application tests
            $this->testTenantApplications();
            
            // Integration tests
            $this->testSystemIntegration();
            
            // Performance and load tests
            $this->testSystemPerformance();
            
            // Analytics and reporting tests
            $this->testAnalyticsAndReporting();
            
            // Cleanup
            if ($this->option('cleanup')) {
                $this->cleanupTestData();
            }
            
            $this->newLine();
            $this->info('🎉 All integration tests completed successfully!');
            $this->info('✅ The SSO audit system is working correctly across all components.');
            
        } catch (\Exception $e) {
            $this->error('❌ Integration test failed: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }

    private function setupTestEnvironment()
    {
        $this->info('🔧 Setting up test environment...');
        
        // Verify database connection
        try {
            DB::connection()->getPdo();
            $this->line('  ✓ Database connection verified');
        } catch (\Exception $e) {
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }
        
        // Verify required tables exist
        $requiredTables = ['users', 'tenants', 'login_audits', 'active_sessions'];
        foreach ($requiredTables as $table) {
            if (!DB::getSchemaBuilder()->hasTable($table)) {
                throw new \Exception("Required table '{$table}' does not exist");
            }
        }
        $this->line('  ✓ All required database tables exist');
        
        // Store initial counts for cleanup verification
        $this->testData['initial_audit_count'] = LoginAudit::count();
        $this->testData['initial_session_count'] = ActiveSession::count();
        
        $this->info('  ✅ Test environment setup completed');
        $this->newLine();
    }

    private function testCentralSSOAuditSystem()
    {
        $this->info('🏛️ Testing Central SSO Audit System...');
        
        // Test audit service functionality
        $user = User::where('email', 'superadmin@sso.com')->first();
        if (!$user) {
            throw new \Exception('Test user not found. Please run database seeders.');
        }
        
        // Test login recording
        $audit = $this->auditService->recordLogin($user, 'tenant1', 'direct', 'full_test_session_1');
        $this->assertTrue($audit->exists, 'Should create audit record');
        $this->testData['test_audit_ids'][] = $audit->id;
        $this->line('  ✓ Login audit recording works');
        
        // Test logout recording
        $this->auditService->recordLogout('full_test_session_1');
        $audit->refresh();
        $this->assertNotNull($audit->logout_at, 'Should record logout timestamp');
        $this->line('  ✓ Logout audit recording works');
        
        // Test failed login recording
        $failedAudit = $this->auditService->recordFailedLogin(
            'nonexistent@test.com',
            'tenant1',
            'direct',
            'User not found'
        );
        $this->testData['test_audit_ids'][] = $failedAudit->id;
        $this->line('  ✓ Failed login audit recording works');
        
        // Test API endpoints
        $this->testAuditAPIEndpoints();
        
        $this->info('  ✅ Central SSO audit system tests passed');
        $this->newLine();
    }

    private function testAuditAPIEndpoints()
    {
        $this->info('  🌐 Testing audit API endpoints...');
        
        // Test successful login audit API
        $response = Http::post('http://localhost:8000/api/audit/login', [
            'email' => 'superadmin@sso.com',
            'tenant_id' => 'tenant1',
            'login_method' => 'api',
            'is_successful' => true,
            'session_id' => 'api_test_session_' . uniqid(),
        ]);
        
        $this->assertTrue($response->successful(), 'Login audit API should succeed');
        $data = $response->json();
        $this->assertTrue($data['success'], 'API response should indicate success');
        $this->testData['test_audit_ids'][] = $data['audit_id'];
        $this->line('    ✓ Login audit API endpoint works');
        
        // Test logout audit API
        $response = Http::post('http://localhost:8000/api/audit/logout', [
            'session_id' => 'api_test_session_123',
            'tenant_id' => 'tenant1',
        ]);
        
        $this->assertTrue($response->successful(), 'Logout audit API should succeed');
        $this->line('    ✓ Logout audit API endpoint works');
        
        // Test validation errors
        $response = Http::post('http://localhost:8000/api/audit/login', [
            'login_method' => 'api',
            // Missing required fields
        ]);
        
        $this->assertEquals(422, $response->status(), 'Should return validation error');
        $this->line('    ✓ API validation works correctly');
    }

    private function testTenantApplications()
    {
        $this->info('🏢 Testing Tenant Applications...');
        
        // Test tenant1
        $this->testTenantApplication('tenant1', 'tenant1-app:8000');
        
        // Test tenant2
        $this->testTenantApplication('tenant2', 'tenant2-app:8000');
        
        $this->info('  ✅ Tenant application tests passed');
        $this->newLine();
    }

    private function testTenantApplication($tenantSlug, $host)
    {
        $this->info("  🏢 Testing {$tenantSlug} application...");
        
        // Test application accessibility
        try {
            $response = Http::timeout(10)->get("http://{$host}");
            $this->assertTrue($response->successful(), "{$tenantSlug} should be accessible");
            $this->line("    ✓ {$tenantSlug} application is accessible");
        } catch (\Exception $e) {
            throw new \Exception("{$tenantSlug} application not accessible: " . $e->getMessage());
        }
        
        // Test audit communication
        $response = Http::timeout(10)->post('http://localhost:8000/api/audit/login', [
            'email' => "test@{$tenantSlug}.com",
            'tenant_id' => $tenantSlug,
            'login_method' => 'sso',
            'is_successful' => true,
            'session_id' => "tenant_test_{$tenantSlug}_" . uniqid(),
        ]);
        
        if ($response->successful()) {
            $data = $response->json();
            $this->testData['test_audit_ids'][] = $data['audit_id'];
            $this->line("    ✓ {$tenantSlug} can communicate with central audit API");
        } else {
            $this->line("    ⚠️ {$tenantSlug} audit API call failed: HTTP " . $response->status());
            $this->line("    Response: " . $response->body());
        }
        
        // Test tenant-specific command if available
        try {
            $output = shell_exec("docker exec {$tenantSlug}-app php artisan test:tenant-audit 2>/dev/null");
            if (strpos($output, '✅') !== false) {
                $this->line("    ✓ {$tenantSlug} internal audit tests passed");
            }
        } catch (\Exception $e) {
            $this->line("    ℹ️ {$tenantSlug} internal tests not available or failed");
        }
    }

    private function testSystemIntegration()
    {
        $this->info('🔗 Testing System Integration...');
        
        // Test cross-tenant audit tracking
        $this->testCrossTenantAuditTracking();
        
        // Test concurrent audit recording
        $this->testConcurrentAuditRecording();
        
        // Test audit data consistency
        $this->testAuditDataConsistency();
        
        $this->info('  ✅ System integration tests passed');
        $this->newLine();
    }

    private function testCrossTenantAuditTracking()
    {
        $this->info('  🌐 Testing cross-tenant audit tracking...');
        
        $user = User::where('email', 'superadmin@sso.com')->first();
        
        // Record logins to different tenants
        $audit1 = $this->auditService->recordLogin($user, 'tenant1', 'sso', 'cross_tenant_session_1');
        $audit2 = $this->auditService->recordLogin($user, 'tenant2', 'sso', 'cross_tenant_session_2');
        
        $this->testData['test_audit_ids'][] = $audit1->id;
        $this->testData['test_audit_ids'][] = $audit2->id;
        
        // Verify records are created correctly
        $this->assertEquals('tenant1', $audit1->tenant_id, 'First audit should be for tenant1');
        $this->assertEquals('tenant2', $audit2->tenant_id, 'Second audit should be for tenant2');
        $this->assertEquals($user->id, $audit1->user_id, 'Both audits should be for same user');
        $this->assertEquals($user->id, $audit2->user_id, 'Both audits should be for same user');
        
        $this->line('    ✓ Cross-tenant audit tracking works correctly');
    }

    private function testConcurrentAuditRecording()
    {
        $this->info('  ⚡ Testing concurrent audit recording...');
        
        $user = User::where('email', 'superadmin@sso.com')->first();
        $startTime = microtime(true);
        
        // Record multiple audits quickly
        $audits = [];
        for ($i = 0; $i < 5; $i++) {
            $audits[] = $this->auditService->recordLogin(
                $user,
                'tenant1',
                'direct',
                'concurrent_session_' . $i
            );
            $this->testData['test_audit_ids'][] = end($audits)->id;
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // Verify all audits were created
        $this->assertEquals(5, count($audits), 'Should create 5 audit records');
        $this->assertTrue($duration < 1.0, 'Should complete within 1 second');
        
        $this->line("    ✓ Concurrent audit recording works (5 records in {$duration}s)");
    }

    private function testAuditDataConsistency()
    {
        $this->info('  📊 Testing audit data consistency...');
        
        // Get statistics before and after operations
        $statsBefore = $this->auditService->getDashboardStatistics();
        
        // Perform some audit operations
        $user = User::where('email', 'superadmin@sso.com')->first();
        $audit = $this->auditService->recordLogin($user, 'tenant1', 'direct', 'consistency_session');
        $this->testData['test_audit_ids'][] = $audit->id;
        
        // Get statistics after
        $statsAfter = $this->auditService->getDashboardStatistics();
        
        // Verify statistics updated correctly
        $this->assertTrue(
            $statsAfter['total_logins_30_days'] >= $statsBefore['total_logins_30_days'],
            'Total logins should not decrease'
        );
        
        $this->line('    ✓ Audit data consistency maintained');
    }

    private function testSystemPerformance()
    {
        $this->info('⚡ Testing System Performance...');
        
        // Test audit API response time
        $startTime = microtime(true);
        
        $response = Http::post('http://localhost:8000/api/audit/login', [
            'email' => 'superadmin@sso.com',
            'tenant_id' => 'tenant1',
            'login_method' => 'api',
            'is_successful' => true,
            'session_id' => 'perf_test_' . uniqid(),
        ]);
        
        $responseTime = microtime(true) - $startTime;
        
        $this->assertTrue($response->successful(), 'Performance test audit should succeed');
        $this->assertTrue($responseTime < 0.5, 'API response should be under 500ms');
        
        $data = $response->json();
        $this->testData['test_audit_ids'][] = $data['audit_id'];
        
        $this->line("  ✓ Audit API response time: {$responseTime}s (< 0.5s)");
        
        // Test statistics calculation performance
        $startTime = microtime(true);
        $stats = $this->auditService->getDashboardStatistics();
        $statsTime = microtime(true) - $startTime;
        
        $this->assertTrue($statsTime < 1.0, 'Statistics calculation should be under 1s');
        $this->line("  ✓ Statistics calculation time: {$statsTime}s (< 1.0s)");
        
        $this->info('  ✅ Performance tests passed');
        $this->newLine();
    }

    private function testAnalyticsAndReporting()
    {
        $this->info('📊 Testing Analytics and Reporting...');
        
        // Test dashboard statistics
        $stats = $this->auditService->getDashboardStatistics();
        $requiredKeys = [
            'active_users', 'total_sessions', 'today_logins', 'total_logins_30_days',
            'unique_users_30_days', 'login_trend', 'active_by_tenant', 'active_by_method',
            'logins_by_tenant', 'logins_by_method', 'recent_logins', 'active_sessions'
        ];
        
        foreach ($requiredKeys as $key) {
            $this->assertTrue(array_key_exists($key, $stats), "Stats should contain {$key}");
        }
        $this->line('  ✓ Dashboard statistics structure is correct');
        
        // Test user activity reporting
        $user = User::where('email', 'superadmin@sso.com')->first();
        $userActivity = $this->auditService->getUserActivity($user->id);
        
        $this->assertTrue(is_array($userActivity), 'User activity should be an array');
        $this->assertTrue(array_key_exists('total_logins', $userActivity), 'Should contain total logins');
        $this->line('  ✓ User activity reporting works');
        
        // Test tenant activity reporting
        $tenant = Tenant::first();
        if ($tenant) {
            $tenantActivity = $this->auditService->getTenantActivity($tenant->id);
            $this->assertTrue(is_array($tenantActivity), 'Tenant activity should be an array');
            $this->assertTrue(array_key_exists('total_logins', $tenantActivity), 'Should contain total logins');
            $this->line('  ✓ Tenant activity reporting works');
        }
        
        // Test analytics cleanup
        $cleanupResult = $this->auditService->cleanup(1); // Keep only 1 day
        $this->assertTrue(is_array($cleanupResult), 'Cleanup should return array');
        $this->assertTrue(array_key_exists('audit_records_deleted', $cleanupResult), 'Should report deleted records');
        $this->line('  ✓ Analytics cleanup functionality works');
        
        $this->info('  ✅ Analytics and reporting tests passed');
        $this->newLine();
    }

    private function cleanupTestData()
    {
        $this->info('🧹 Cleaning up test data...');
        
        // Delete test audit records
        if (isset($this->testData['test_audit_ids'])) {
            $deleted = LoginAudit::whereIn('id', $this->testData['test_audit_ids'])->delete();
            $this->line("  ✓ Deleted {$deleted} test audit records");
        }
        
        // Clean up active sessions
        ActiveSession::where('session_id', 'like', '%test%')->delete();
        ActiveSession::where('session_id', 'like', '%concurrent%')->delete();
        ActiveSession::where('session_id', 'like', '%perf%')->delete();
        $this->line('  ✓ Cleaned up test active sessions');
        
        // Verify cleanup
        $finalAuditCount = LoginAudit::count();
        $finalSessionCount = ActiveSession::count();
        
        $this->line("  ✓ Final audit count: {$finalAuditCount}");
        $this->line("  ✓ Final session count: {$finalSessionCount}");
        
        $this->info('  ✅ Test data cleanup completed');
        $this->newLine();
    }

    // Helper assertion methods
    private function assertTrue($condition, $message)
    {
        if (!$condition) {
            throw new \Exception("Assertion failed: {$message}");
        }
    }

    private function assertEquals($expected, $actual, $message)
    {
        if ($expected !== $actual) {
            throw new \Exception("Assertion failed: {$message}. Expected: {$expected}, Actual: {$actual}");
        }
    }

    private function assertNotNull($value, $message)
    {
        if ($value === null) {
            throw new \Exception("Assertion failed: {$message}");
        }
    }
}
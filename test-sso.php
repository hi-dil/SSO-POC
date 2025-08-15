<?php

/**
 * SSO System Test Script
 * 
 * This script tests all three login methods in the SSO system:
 * 1. Main Dashboard Login (Central SSO)
 * 2. SSO Button Login (Tenant -> Central SSO -> Tenant)
 * 3. Direct Tenant Login (Tenant API call)
 * 
 * Requirements:
 * - Docker containers must be running
 * - All services accessible on localhost
 */

class SSOTester
{
    private $baseUrls = [
        'central' => 'http://localhost:8000',
        'tenant1' => 'http://localhost:8001',
        'tenant2' => 'http://localhost:8002',
    ];

    private $testCredentials = [
        'superadmin' => ['email' => 'superadmin@sso.com', 'password' => 'password'],
        'tenant1_user' => ['email' => 'user@tenant1.com', 'password' => 'password'],
        'tenant1_admin' => ['email' => 'admin@tenant1.com', 'password' => 'password'],
        'tenant2_user' => ['email' => 'user@tenant2.com', 'password' => 'password'],
        'tenant2_admin' => ['email' => 'admin@tenant2.com', 'password' => 'password'],
    ];

    private $cookieJar;
    private $verbose = true;

    public function __construct($verbose = true)
    {
        $this->verbose = $verbose;
        $this->cookieJar = tempnam(sys_get_temp_dir(), 'sso_test_cookies');
        
        $this->log("🧪 SSO System Test Script Started");
        $this->log("📁 Cookie jar: " . $this->cookieJar);
        $this->log("");
    }

    public function __destruct()
    {
        if (file_exists($this->cookieJar)) {
            unlink($this->cookieJar);
        }
    }

    public function runAllTests()
    {
        $results = [];
        
        // Test 1: Main Dashboard Login
        $results['dashboard_login'] = $this->testDashboardLogin();
        $this->clearSession();
        
        // Test 2: SSO Button Login
        $results['sso_button_login'] = $this->testSSOButtonLogin();
        $this->clearSession();
        
        // Test 3: Direct Tenant Login
        $results['direct_tenant_login'] = $this->testDirectTenantLogin();
        $this->clearSession();
        
        // Test 4: API Endpoints
        $results['api_tests'] = $this->testAPIEndpoints();
        
        $this->printSummary($results);
        return $results;
    }

    private function testDashboardLogin()
    {
        $this->log("🎯 Testing Main Dashboard Login Flow");
        $this->log("=" . str_repeat("=", 50));
        
        try {
            // Step 1: Get login page
            $this->log("1. Getting central SSO login page...");
            $loginPage = $this->httpRequest('GET', $this->baseUrls['central'] . '/login');
            
            if (strpos($loginPage, 'csrf') === false) {
                throw new Exception("Login page doesn't contain CSRF token");
            }
            
            $csrfToken = $this->extractCSRFToken($loginPage);
            $this->log("   ✅ Login page loaded, CSRF token extracted");
            
            // Step 2: Login with superadmin
            $this->log("2. Logging in with superadmin credentials...");
            $loginData = [
                '_token' => $csrfToken,
                'email' => $this->testCredentials['superadmin']['email'],
                'password' => $this->testCredentials['superadmin']['password']
            ];
            
            $dashboardPage = $this->httpRequest('POST', $this->baseUrls['central'] . '/login', $loginData);
            
            // Should redirect to dashboard
            if (strpos($dashboardPage, 'Dashboard') === false && strpos($dashboardPage, 'Welcome') === false) {
                throw new Exception("Dashboard not reached after login");
            }
            
            $this->log("   ✅ Successfully logged into central dashboard");
            
            // Step 3: Test tenant access from dashboard
            $this->log("3. Testing tenant access from dashboard...");
            
            if (strpos($dashboardPage, 'tenant1') !== false || strpos($dashboardPage, 'Access') !== false) {
                $this->log("   ✅ Dashboard shows tenant access options");
                return ['status' => 'SUCCESS', 'message' => 'Dashboard login flow working correctly'];
            } else {
                throw new Exception("Dashboard doesn't show tenant access options");
            }
            
        } catch (Exception $e) {
            $this->log("   ❌ Error: " . $e->getMessage());
            return ['status' => 'FAILED', 'message' => $e->getMessage()];
        }
    }

    private function testSSOButtonLogin()
    {
        $this->log("");
        $this->log("🔗 Testing SSO Button Login Flow");
        $this->log("=" . str_repeat("=", 50));
        
        try {
            // Step 1: Get tenant login page
            $this->log("1. Getting tenant1 login page...");
            $tenantLoginPage = $this->httpRequest('GET', $this->baseUrls['tenant1'] . '/login');
            
            if (strpos($tenantLoginPage, 'Login with Central SSO') === false) {
                throw new Exception("Tenant login page doesn't have SSO button");
            }
            
            $this->log("   ✅ Tenant login page loaded with SSO button");
            
            // Step 2: Click SSO button (follow redirect)
            $this->log("2. Following SSO redirect...");
            $ssoRedirect = $this->httpRequest('GET', $this->baseUrls['tenant1'] . '/auth/sso');
            
            // This should redirect to central SSO
            $this->log("   ✅ SSO redirect initiated");
            
            // Step 3: Get central SSO form for tenant1
            $this->log("3. Getting central SSO login form...");
            $ssoLoginPage = $this->httpRequest('GET', $this->baseUrls['central'] . '/auth/tenant1?callback_url=' . urlencode($this->baseUrls['tenant1'] . '/sso/callback'));
            
            if (strpos($ssoLoginPage, 'Central SSO Login') === false) {
                throw new Exception("Central SSO login page not loaded");
            }
            
            $csrfToken = $this->extractCSRFToken($ssoLoginPage);
            $this->log("   ✅ Central SSO login form loaded");
            
            // Step 4: Submit credentials
            $this->log("4. Submitting tenant1 credentials...");
            $ssoLoginData = [
                '_token' => $csrfToken,
                'email' => $this->testCredentials['tenant1_user']['email'],
                'password' => $this->testCredentials['tenant1_user']['password'],
                'tenant_slug' => 'tenant1',
                'callback_url' => $this->baseUrls['tenant1'] . '/sso/callback'
            ];
            
            $callbackResponse = $this->httpRequest('POST', $this->baseUrls['central'] . '/auth/login', $ssoLoginData);
            
            // Should be redirected to tenant dashboard
            if (strpos($callbackResponse, 'dashboard') !== false || strpos($callbackResponse, 'Welcome') !== false) {
                $this->log("   ✅ Successfully authenticated via SSO button");
                return ['status' => 'SUCCESS', 'message' => 'SSO button login flow working correctly'];
            } else {
                throw new Exception("SSO callback didn't redirect to tenant dashboard");
            }
            
        } catch (Exception $e) {
            $this->log("   ❌ Error: " . $e->getMessage());
            return ['status' => 'FAILED', 'message' => $e->getMessage()];
        }
    }

    private function testDirectTenantLogin()
    {
        $this->log("");
        $this->log("🎯 Testing Direct Tenant Login Flow");
        $this->log("=" . str_repeat("=", 50));
        
        try {
            // Step 1: Get tenant login page
            $this->log("1. Getting tenant1 login page...");
            $tenantLoginPage = $this->httpRequest('GET', $this->baseUrls['tenant1'] . '/login');
            
            $csrfToken = $this->extractCSRFToken($tenantLoginPage);
            $this->log("   ✅ Tenant login page loaded");
            
            // Step 2: Submit direct login
            $this->log("2. Submitting direct login credentials...");
            $loginData = [
                '_token' => $csrfToken,
                'email' => $this->testCredentials['tenant1_user']['email'],
                'password' => $this->testCredentials['tenant1_user']['password']
            ];
            
            $dashboardResponse = $this->httpRequest('POST', $this->baseUrls['tenant1'] . '/login', $loginData);
            
            // Should redirect to dashboard
            if (strpos($dashboardResponse, 'dashboard') !== false || strpos($dashboardResponse, 'Welcome') !== false) {
                $this->log("   ✅ Successfully logged in directly to tenant");
                return ['status' => 'SUCCESS', 'message' => 'Direct tenant login working correctly'];
            } else {
                throw new Exception("Direct login didn't redirect to dashboard");
            }
            
        } catch (Exception $e) {
            $this->log("   ❌ Error: " . $e->getMessage());
            return ['status' => 'FAILED', 'message' => $e->getMessage()];
        }
    }

    private function testAPIEndpoints()
    {
        $this->log("");
        $this->log("🔌 Testing API Endpoints");
        $this->log("=" . str_repeat("=", 50));
        
        $results = [];
        
        try {
            // Test Central SSO API Login
            $this->log("1. Testing Central SSO API login...");
            $apiLoginData = [
                'email' => $this->testCredentials['tenant1_user']['email'],
                'password' => $this->testCredentials['tenant1_user']['password'],
                'tenant_slug' => 'tenant1'
            ];
            
            $apiResponse = $this->httpRequest('POST', $this->baseUrls['central'] . '/api/auth/login', 
                json_encode($apiLoginData), ['Content-Type: application/json']);
            
            $apiResult = json_decode($apiResponse, true);
            
            if ($apiResult && isset($apiResult['success']) && $apiResult['success']) {
                $this->log("   ✅ API login successful");
                $token = $apiResult['token'];
                
                // Test token validation
                $this->log("2. Testing token validation...");
                $validateData = [
                    'token' => $token,
                    'tenant_slug' => 'tenant1'
                ];
                
                $validateResponse = $this->httpRequest('POST', $this->baseUrls['central'] . '/api/auth/validate',
                    json_encode($validateData), ['Content-Type: application/json']);
                
                $validateResult = json_decode($validateResponse, true);
                
                if ($validateResult && isset($validateResult['valid']) && $validateResult['valid']) {
                    $this->log("   ✅ Token validation successful");
                    $results['api_login'] = 'SUCCESS';
                    $results['token_validation'] = 'SUCCESS';
                } else {
                    $this->log("   ❌ Token validation failed");
                    $results['token_validation'] = 'FAILED';
                }
            } else {
                $this->log("   ❌ API login failed");
                $results['api_login'] = 'FAILED';
            }
            
        } catch (Exception $e) {
            $this->log("   ❌ API Error: " . $e->getMessage());
            $results['api_error'] = $e->getMessage();
        }
        
        return $results;
    }

    private function httpRequest($method, $url, $data = null, $headers = [])
    {
        $ch = curl_init();
        
        $defaultHeaders = [
            'User-Agent: SSO-Test-Script/1.0'
        ];
        
        $allHeaders = array_merge($defaultHeaders, $headers);
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_COOKIEJAR => $this->cookieJar,
            CURLOPT_COOKIEFILE => $this->cookieJar,
            CURLOPT_HTTPHEADER => $allHeaders,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                if (is_array($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                } else {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                }
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_error($ch)) {
            throw new Exception("HTTP Request failed: " . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($httpCode >= 400) {
            throw new Exception("HTTP Error $httpCode: $url");
        }
        
        return $response;
    }

    private function extractCSRFToken($html)
    {
        if (preg_match('/name=["\']_token["\'][^>]*value=["\']([^"\']+)["\']/', $html, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/content=["\']([^"\']+)["\'][^>]*name=["\']csrf-token["\']/', $html, $matches)) {
            return $matches[1];
        }
        
        throw new Exception("CSRF token not found in HTML");
    }

    private function clearSession()
    {
        if (file_exists($this->cookieJar)) {
            unlink($this->cookieJar);
            $this->cookieJar = tempnam(sys_get_temp_dir(), 'sso_test_cookies');
        }
    }

    private function log($message)
    {
        if ($this->verbose) {
            echo $message . "\n";
        }
    }

    private function printSummary($results)
    {
        $this->log("");
        $this->log("📊 TEST RESULTS SUMMARY");
        $this->log("=" . str_repeat("=", 50));
        
        $totalTests = 0;
        $passedTests = 0;
        
        foreach ($results as $testName => $result) {
            $totalTests++;
            
            if (is_array($result)) {
                $status = $result['status'] ?? 'UNKNOWN';
                $message = $result['message'] ?? '';
            } else {
                $status = $result;
                $message = '';
            }
            
            $icon = ($status === 'SUCCESS') ? '✅' : '❌';
            if ($status === 'SUCCESS') $passedTests++;
            
            $this->log(sprintf("%-25s %s %s", ucwords(str_replace('_', ' ', $testName)), $icon, $status));
            if ($message && $status !== 'SUCCESS') {
                $this->log("   " . $message);
            }
        }
        
        $this->log("");
        $this->log("📈 Summary: $passedTests/$totalTests tests passed");
        
        if ($passedTests === $totalTests) {
            $this->log("🎉 All tests passed! SSO system is working correctly.");
        } else {
            $this->log("⚠️  Some tests failed. Please check the logs above.");
        }
        
        $this->log("");
        $this->log("🔗 Quick Test URLs:");
        $this->log("   Main Dashboard:  " . $this->baseUrls['central'] . "/login");
        $this->log("   Tenant1 Login:   " . $this->baseUrls['tenant1'] . "/login");
        $this->log("   Tenant2 Login:   " . $this->baseUrls['tenant2'] . "/login");
        $this->log("   Admin Panel:     " . $this->baseUrls['central'] . "/admin/tenants");
    }
}

// Run the tests
if (php_sapi_name() === 'cli') {
    $tester = new SSOTester(true);
    $results = $tester->runAllTests();
    
    // Exit with appropriate code
    $hasFailures = false;
    foreach ($results as $result) {
        if (is_array($result) && $result['status'] === 'FAILED') {
            $hasFailures = true;
            break;
        } elseif (is_string($result) && $result === 'FAILED') {
            $hasFailures = true;
            break;
        }
    }
    
    exit($hasFailures ? 1 : 0);
} else {
    echo "This script should be run from the command line.\n";
    echo "Usage: php test-sso.php\n";
}
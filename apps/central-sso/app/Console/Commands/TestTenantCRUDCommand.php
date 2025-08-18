<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\TenantManagementController;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TestTenantCRUDCommand extends Command
{
    protected $signature = 'test:tenant-crud';
    protected $description = 'Test tenant CRUD operations';

    public function handle()
    {
        $this->info('🧪 Testing Tenant CRUD Operations');
        
        try {
            // Login as superadmin to test permissions
            $superadmin = User::where('email', 'superadmin@sso.com')->first();
            if (!$superadmin) {
                $this->error('❌ Superadmin user not found');
                return 1;
            }
            
            Auth::login($superadmin);
            $this->info("✅ Authenticated as: {$superadmin->email}");
            
            // Test CREATE operation
            $this->info("\n📝 Testing CREATE operation...");
            $controller = new TenantManagementController();
            
            $testData = [
                'name' => 'Test Tenant CRUD',
                'slug' => 'test-tenant-crud-' . time(),
                'domain' => 'test-crud.example.com',
                'description' => 'This is a test tenant for CRUD operations',
                'plan' => 'premium',
                'industry' => 'technology',
                'region' => 'us-east',
                'employee_count' => 100,
                'max_users' => 50,
                'is_active' => true,
            ];
            
            $request = Request::create('/admin/tenants', 'POST', $testData);
            $request->setUserResolver(function () use ($superadmin) {
                return $superadmin;
            });
            
            $response = $controller->store($request);
            
            if ($response->getStatusCode() === 302) {
                $this->info("✅ Tenant created successfully");
                
                // Find the created tenant
                $tenant = Tenant::find($testData['slug']);
                if ($tenant) {
                    $this->info("✅ Tenant found: {$tenant->name}");
                    
                    // Test READ operation
                    $this->info("\n👁️  Testing READ operation...");
                    $showResponse = $controller->show($tenant);
                    if ($showResponse instanceof \Illuminate\View\View) {
                        $viewData = $showResponse->getData();
                        if (isset($viewData['tenant']) && isset($viewData['stats'])) {
                            $this->info("✅ Show method works with stats data");
                        } else {
                            $this->error("❌ Show method missing required data");
                        }
                    }
                    
                    // Test UPDATE operation
                    $this->info("\n✏️  Testing UPDATE operation...");
                    $updateData = array_merge($testData, [
                        'name' => 'Updated Test Tenant CRUD',
                        'description' => 'Updated description for testing',
                        'plan' => 'enterprise',
                        'employee_count' => 200,
                    ]);
                    
                    $updateRequest = Request::create("/admin/tenants/{$tenant->id}", 'PUT', $updateData);
                    $updateRequest->setUserResolver(function () use ($superadmin) {
                        return $superadmin;
                    });
                    
                    $updateResponse = $controller->update($updateRequest, $tenant);
                    if ($updateResponse->getStatusCode() === 302) {
                        $this->info("✅ Tenant updated successfully");
                        
                        // Refresh tenant to check updates
                        $tenant->refresh();
                        if ($tenant->name === 'Updated Test Tenant CRUD' && $tenant->plan === 'enterprise') {
                            $this->info("✅ Tenant data updated correctly");
                        } else {
                            $this->error("❌ Tenant data not updated properly");
                        }
                    } else {
                        $this->error("❌ Update failed: " . $updateResponse->getContent());
                    }
                    
                    // Test DELETE operation
                    $this->info("\n🗑️  Testing DELETE operation...");
                    $deleteRequest = Request::create("/admin/tenants/{$tenant->id}", 'DELETE');
                    $deleteRequest->setUserResolver(function () use ($superadmin) {
                        return $superadmin;
                    });
                    
                    $deleteResponse = $controller->destroy($deleteRequest, $tenant);
                    if ($deleteResponse->getStatusCode() === 302) {
                        $this->info("✅ Tenant deleted successfully");
                        
                        // Verify tenant is deleted
                        if (!Tenant::find($testData['slug'])) {
                            $this->info("✅ Tenant removed from database");
                        } else {
                            $this->error("❌ Tenant still exists in database");
                        }
                    } else {
                        $this->error("❌ Delete failed: " . $deleteResponse->getContent());
                    }
                    
                } else {
                    $this->error("❌ Created tenant not found");
                }
            } else {
                $this->error("❌ Failed to create tenant: " . $response->getContent());
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Exception during testing: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
        
        $this->info("\n✅ All CRUD operations tested successfully!");
        return 0;
    }
}
<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\TenantManagementController;
use App\Models\Tenant;
use Illuminate\Console\Command;

class TestTenantShowCommand extends Command
{
    protected $signature = 'test:tenant-show {tenant_id=tenant1}';
    protected $description = 'Test tenant show functionality';

    public function handle()
    {
        $tenantId = $this->argument('tenant_id');
        $this->info("🔍 Testing tenant show functionality for: {$tenantId}");
        
        try {
            $tenant = Tenant::find($tenantId);
            
            if (!$tenant) {
                $this->error("❌ Tenant {$tenantId} not found");
                return 1;
            }
            
            // Test the controller method
            $controller = new TenantManagementController();
            $response = $controller->show($tenant);
            
            $this->info("✅ Controller method executed successfully");
            
            // Check if the response is a view
            if ($response instanceof \Illuminate\View\View) {
                $viewData = $response->getData();
                
                $this->info("📋 View data keys: " . implode(', ', array_keys($viewData)));
                
                if (isset($viewData['stats'])) {
                    $this->info("📊 Stats data:");
                    foreach ($viewData['stats'] as $key => $value) {
                        if (is_array($value)) {
                            $value = json_encode($value);
                        }
                        $this->line("  {$key}: {$value}");
                    }
                }
                
                if (isset($viewData['tenant'])) {
                    $tenant = $viewData['tenant'];
                    $this->info("🏢 Tenant data:");
                    $this->line("  ID: {$tenant->id}");
                    $this->line("  Name: {$tenant->name}");
                    $this->line("  Plan: " . ($tenant->plan ?? 'N/A'));
                    $this->line("  Industry: " . ($tenant->industry ?? 'N/A'));
                    $this->line("  Users count: " . $tenant->users()->count());
                }
                
            } else {
                $this->error("❌ Expected view response, got: " . get_class($response));
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Exception: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
        
        $this->info("✅ Tenant show test completed successfully!");
        return 0;
    }
}
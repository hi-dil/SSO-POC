<?php

namespace App\Console\Commands;

use App\Http\Controllers\Admin\TenantManagementController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class TestTenantCreationCommand extends Command
{
    protected $signature = 'test:tenant-creation {--count=50 : Number of tenants to create}';
    protected $description = 'Test tenant creation through the TenantManagementController';

    public function handle()
    {
        $count = $this->option('count');
        $this->info("🚀 Testing bulk creation of {$count} tenants...");
        
        try {
            // Create a mock request
            $request = new Request();
            $request->merge(['count' => $count]);
            
            // Instantiate the controller
            $controller = new TenantManagementController();
            
            // Call the bulk create method
            $response = $controller->bulkCreate($request);
            
            // Get the response data
            $data = $response->getData(true);
            
            if ($data['success']) {
                $this->info("✅ Success: {$data['message']}");
                $this->info("📊 Created tenants:");
                
                foreach ($data['tenants'] as $index => $tenant) {
                    $plan = $tenant['plan'] ?? 'N/A';
                    $industry = $tenant['industry'] ?? 'N/A';
                    $this->line("  {$tenant['id']}: {$tenant['name']} ({$plan}, {$industry})");
                    
                    // Only show first 10 for brevity
                    if ($index >= 9) {
                        $remaining = count($data['tenants']) - 10;
                        if ($remaining > 0) {
                            $this->line("  ... and {$remaining} more tenants");
                        }
                        break;
                    }
                }
                
                // Show statistics
                $this->newLine();
                $this->info("📈 Statistics:");
                $plans = [];
                $industries = [];
                $activeCount = 0;
                
                foreach ($data['tenants'] as $tenant) {
                    $plan = $tenant['plan'] ?? null;
                    $industry = $tenant['industry'] ?? null;
                    
                    if ($plan) $plans[$plan] = ($plans[$plan] ?? 0) + 1;
                    if ($industry) $industries[$industry] = ($industries[$industry] ?? 0) + 1;
                    if ($tenant['is_active']) $activeCount++;
                }
                
                $this->line("  Active tenants: {$activeCount}/" . count($data['tenants']));
                $this->line("  Plan distribution: " . json_encode($plans));
                $this->line("  Top industries: " . json_encode(array_slice($industries, 0, 5, true)));
                
            } else {
                $this->error("❌ Failed: {$data['message']}");
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Exception: " . $e->getMessage());
            $this->error("Stack trace: " . $e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}
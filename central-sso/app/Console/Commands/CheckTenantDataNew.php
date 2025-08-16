<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class CheckTenantDataNew extends Command
{
    protected $signature = 'check:tenant-data {id=tenant3}';
    protected $description = 'Check tenant data structure';

    public function handle()
    {
        $tenantId = $this->argument('id');
        $tenant = Tenant::find($tenantId);
        
        if (!$tenant) {
            $this->error("Tenant {$tenantId} not found");
            return 1;
        }
        
        $this->info("=== Tenant {$tenantId} Data ===");
        $this->line("ID: {$tenant->id}");
        $this->line("Name (attribute): " . ($tenant->name ?? 'NULL'));
        $this->line("Slug (attribute): " . ($tenant->slug ?? 'NULL'));
        $this->line("Domain (attribute): " . ($tenant->domain ?? 'NULL'));
        $this->line("Is Active (attribute): " . ($tenant->is_active ? 'true' : 'false'));
        
        $this->newLine();
        $this->info("=== Raw Data Field ===");
        $this->line("Data: " . json_encode($tenant->data, JSON_PRETTY_PRINT));
        
        $this->newLine();
        $this->info("=== Accessing Custom Attributes ===");
        $this->line("Plan: " . ($tenant->plan ?? 'NOT FOUND'));
        $this->line("Industry: " . ($tenant->industry ?? 'NOT FOUND'));
        $this->line("Region: " . ($tenant->region ?? 'NOT FOUND'));
        $this->line("Employee Count: " . ($tenant->employee_count ?? 'NOT FOUND'));
        $this->line("Created Year: " . ($tenant->created_year ?? 'NOT FOUND'));
        $this->line("Billing Status: " . ($tenant->billing_status ?? 'NOT FOUND'));
        $this->line("Features: " . (is_array($tenant->features) ? json_encode($tenant->features) : 'NOT FOUND'));
        
        return 0;
    }
}
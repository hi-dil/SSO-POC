<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class CheckTenantDataCommand extends Command
{
    protected $signature = 'tenant:check {--update : Update tenant data if needed}';
    protected $description = 'Check and optionally update tenant data';

    public function handle()
    {
        $this->info('Checking tenant data...');

        $tenant1 = Tenant::find('tenant1');
        if ($tenant1) {
            $this->line('Tenant1 Name: ' . $tenant1->name);
            $this->line('Tenant1 Data: ' . json_encode($tenant1->data));

            if ($this->option('update')) {
                $tenant1->update([
                    'name' => 'Acme Corporation',
                    'data' => [
                        'plan' => 'enterprise',
                        'industry' => 'technology',
                        'region' => 'us-east',
                        'employee_count' => 500,
                        'created_year' => 2020,
                        'features' => [
                            'analytics' => true,
                            'api' => true,
                            'sso' => true,
                        ]
                    ]
                ]);
                $this->info('Updated tenant1');
                
                $tenant1->refresh();
                $this->line('After update - Name: ' . $tenant1->name);
                $this->line('After update - Data: ' . json_encode($tenant1->data));
            }
        }

        // Check a few more tenants
        for ($i = 3; $i <= 5; $i++) {
            $tenant = Tenant::find('tenant' . $i);
            if ($tenant) {
                $this->line("Tenant{$i} Name: " . ($tenant->name ?? 'NULL'));
                $this->line("Tenant{$i} Data: " . json_encode($tenant->data));
            }
        }

        return 0;
    }
}
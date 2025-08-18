<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class TenantStatsCommand extends Command
{
    protected $signature = 'tenant:stats';
    protected $description = 'Show tenant statistics';

    public function handle()
    {
        $this->info('🏢 Tenant Statistics');
        $this->info('==================');
        
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('is_active', true)->count();
        $inactiveTenants = $totalTenants - $activeTenants;
        
        $this->line("Total Tenants: {$totalTenants}");
        $this->line("Active Tenants: {$activeTenants}");
        $this->line("Inactive Tenants: {$inactiveTenants}");
        
        $this->newLine();
        $this->info('📊 Plan Distribution:');
        $planStats = Tenant::selectRaw('plan, COUNT(*) as count')
            ->whereNotNull('plan')
            ->groupBy('plan')
            ->orderBy('count', 'desc')
            ->get();
            
        foreach ($planStats as $stat) {
            $this->line("  {$stat->plan}: {$stat->count}");
        }
        
        $this->newLine();
        $this->info('🏭 Industry Distribution:');
        $industryStats = Tenant::selectRaw('industry, COUNT(*) as count')
            ->whereNotNull('industry')
            ->groupBy('industry')
            ->orderBy('count', 'desc')
            ->get();
            
        foreach ($industryStats as $stat) {
            $this->line("  {$stat->industry}: {$stat->count}");
        }
        
        $this->newLine();
        $this->info('🌍 Region Distribution:');
        $regionStats = Tenant::selectRaw('region, COUNT(*) as count')
            ->whereNotNull('region')
            ->groupBy('region')
            ->orderBy('count', 'desc')
            ->get();
            
        foreach ($regionStats as $stat) {
            $this->line("  {$stat->region}: {$stat->count}");
        }
        
        $this->newLine();
        $this->info('📈 Sample Tenants:');
        $sampleTenants = Tenant::take(10)->get();
        foreach ($sampleTenants as $tenant) {
            $status = $tenant->is_active ? '✅' : '❌';
            $this->line("  {$status} {$tenant->id}: {$tenant->name} ({$tenant->plan}, {$tenant->industry})");
        }
        
        return 0;
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SqlTenantDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Updating 50 tenants with realistic data using SQL...');
        
        // Define different plans and features for variety
        $plans = ['basic', 'premium', 'enterprise', 'starter', 'pro'];
        $industries = ['technology', 'healthcare', 'finance', 'education', 'retail', 'manufacturing', 'consulting', 'media', 'nonprofit', 'government'];
        $regions = ['us-east', 'us-west', 'eu-central', 'asia-pacific', 'canada', 'australia'];
        
        // Update tenant1 
        DB::statement("UPDATE tenants SET 
            name = 'Acme Corporation',
            data = ? 
            WHERE id = 'tenant1'", [
            json_encode([
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
            ])
        ]);
        
        // Update tenant2
        DB::statement("UPDATE tenants SET 
            name = 'Global Health Systems',
            data = ? 
            WHERE id = 'tenant2'", [
            json_encode([
                'plan' => 'premium',
                'industry' => 'healthcare',
                'region' => 'us-west',
                'employee_count' => 250,
                'created_year' => 2018,
                'features' => [
                    'analytics' => true,
                    'api' => true,
                    'compliance' => true,
                ]
            ])
        ]);
        
        // Update tenants 3-50 with realistic data
        for ($i = 3; $i <= 50; $i++) {
            $plan = $plans[array_rand($plans)];
            $industry = $industries[array_rand($industries)];
            $region = $regions[array_rand($regions)];
            
            // Generate realistic company names
            $companyNames = [
                'TechCorp', 'InnovateLab', 'DataFlow', 'CloudVision', 'FinanceHub',
                'EduTech', 'HealthPlus', 'RetailMax', 'MediaGroup', 'ConsultPro',
                'SecureTech', 'GlobalSoft', 'NextGen', 'SmartSys', 'DigitalEdge',
                'FlexiCorp', 'PowerTech', 'VitalCare', 'PrimeLab', 'EliteGroup'
            ];
            
            $suffixes = ['Inc', 'LLC', 'Corp', 'Ltd', 'Group', 'Systems', 'Solutions', 'Technologies'];
            
            $baseName = $companyNames[array_rand($companyNames)];
            $suffix = $suffixes[array_rand($suffixes)];
            $companyName = $baseName . ' ' . $suffix;
            
            // Determine features based on plan
            $features = ['analytics' => true];
            
            switch ($plan) {
                case 'starter':
                    $features['basic_support'] = true;
                    break;
                case 'basic':
                    $features['email_support'] = true;
                    break;
                case 'premium':
                    $features['api'] = true;
                    $features['priority_support'] = true;
                    break;
                case 'pro':
                    $features['api'] = true;
                    $features['advanced_analytics'] = true;
                    $features['phone_support'] = true;
                    break;
                case 'enterprise':
                    $features['api'] = true;
                    $features['sso'] = true;
                    $features['advanced_analytics'] = true;
                    $features['dedicated_support'] = true;
                    $features['compliance'] = true;
                    break;
            }
            
            // Add industry-specific features
            if ($industry === 'healthcare') {
                $features['hipaa_compliance'] = true;
            } elseif ($industry === 'finance') {
                $features['pci_compliance'] = true;
                $features['fraud_detection'] = true;
            } elseif ($industry === 'education') {
                $features['ferpa_compliance'] = true;
                $features['student_portal'] = true;
            }
            
            $isActive = rand(0, 10) > 1 ? 1 : 0; // 90% active, 10% inactive for testing
            
            DB::statement("UPDATE tenants SET 
                name = ?,
                is_active = ?,
                data = ? 
                WHERE id = ?", [
                $companyName,
                $isActive,
                json_encode([
                    'plan' => $plan,
                    'industry' => $industry,
                    'region' => $region,
                    'employee_count' => rand(10, 5000),
                    'created_year' => rand(2015, 2024),
                    'features' => $features,
                ]),
                'tenant' . $i
            ]);
            
            if ($i % 10 === 0) {
                $this->command->info("Updated {$i} tenants...");
            }
        }

        $this->command->info('All 50 tenants updated successfully with realistic data!');
        $this->command->info('✅ Database now contains 50 tenants with:');
        $this->command->info('   - Realistic company names');
        $this->command->info('   - Different plans (basic, premium, enterprise, starter, pro)');
        $this->command->info('   - Various industries (technology, healthcare, finance, etc.)');
        $this->command->info('   - Multiple regions (us-east, us-west, eu-central, etc.)');
        $this->command->info('   - Plan-specific and industry-specific features');
        $this->command->info('   - 90% active, 10% inactive status for testing');
    }
}
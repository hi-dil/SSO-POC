<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating 50 tenants...');
        
        // Define different plans and features for variety
        $plans = ['basic', 'premium', 'enterprise', 'starter', 'pro'];
        $industries = ['technology', 'healthcare', 'finance', 'education', 'retail', 'manufacturing', 'consulting', 'media', 'nonprofit', 'government'];
        $regions = ['us-east', 'us-west', 'eu-central', 'asia-pacific', 'canada', 'australia'];
        
        // Update existing tenant1 and tenant2 if they exist
        $tenant1 = Tenant::updateOrCreate(
            ['id' => 'tenant1'],
            [
                'name' => 'Acme Corporation',
                'slug' => 'tenant1',
                'domain' => 'tenant1.localhost:8001',
                'description' => 'Primary technology tenant for development and testing',
                'is_active' => true,
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
            ]
        );

        $tenant2 = Tenant::updateOrCreate(
            ['id' => 'tenant2'],
            [
                'name' => 'Global Health Systems',
                'slug' => 'tenant2',
                'domain' => 'tenant2.localhost:8002',
                'description' => 'Healthcare organization with premium features',
                'is_active' => true,
                'data' => [
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
                ]
            ]
        );
        
        // Create 48 additional tenants (for a total of 50)
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
            
            $tenant = Tenant::updateOrCreate(
                ['id' => 'tenant' . $i],
                [
                    'name' => $companyName,
                    'slug' => 'tenant' . $i,
                    'domain' => 'tenant' . $i . '.example.com',
                    'description' => 'A ' . $industry . ' organization using ' . $plan . ' plan in ' . $region . ' region',
                    'is_active' => rand(0, 10) > 1, // 90% active, 10% inactive for testing
                    'data' => [
                        'plan' => $plan,
                        'industry' => $industry,
                        'region' => $region,
                        'employee_count' => rand(10, 5000),
                        'created_year' => rand(2015, 2024),
                        'features' => $features,
                    ]
                ]
            );
            
            if ($i % 10 === 0) {
                $this->command->info("Created {$i} tenants...");
            }
        }

        // Create or find admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@sso.localhost'],
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'is_admin' => true,
            ]
        );

        // Create or find test user
        $testUser = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
            ]
        );

        // Attach users to tenants (sync to avoid duplicates)
        $admin->tenants()->syncWithoutDetaching([$tenant1->id, $tenant2->id]);
        $testUser->tenants()->syncWithoutDetaching([$tenant1->id]);

        $this->command->info('Tenants and test users created successfully!');
        $this->command->info('Admin: admin@sso.localhost / password');
        $this->command->info('Test User: test@example.com / password');
    }
}

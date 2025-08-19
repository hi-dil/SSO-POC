<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class TenantManagementController extends Controller
{
    private AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
        $this->middleware('can:tenants.view')->only(['index', 'show']);
        $this->middleware('can:tenants.create')->only(['create', 'store', 'bulkCreate']);
        $this->middleware('can:tenants.edit')->only(['edit', 'update']);
        $this->middleware('can:tenants.delete')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $query = Tenant::withCount('users');
        
        // Handle search with fuzzy matching
        if ($request->filled('search')) {
            $search = trim($request->search);
            $searchTerms = explode(' ', $search);
            
            $query->where(function($q) use ($search, $searchTerms) {
                // Exact match first (highest priority)
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('slug', 'LIKE', "%{$search}%")
                  ->orWhere('domain', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhere('plan', 'LIKE', "%{$search}%")
                  ->orWhere('industry', 'LIKE', "%{$search}%")
                  ->orWhere('region', 'LIKE', "%{$search}%");
                
                // Fuzzy search for individual terms
                foreach ($searchTerms as $term) {
                    if (strlen($term) >= 2) {
                        $q->orWhere('name', 'LIKE', "%{$term}%")
                          ->orWhere('slug', 'LIKE', "%{$term}%")
                          ->orWhere('domain', 'LIKE', "%{$term}%")
                          ->orWhere('description', 'LIKE', "%{$term}%")
                          ->orWhere('plan', 'LIKE', "%{$term}%")
                          ->orWhere('industry', 'LIKE', "%{$term}%")
                          ->orWhere('region', 'LIKE', "%{$term}%");
                    }
                }
            });
        }
        
        // Handle sorting
        $sortField = $request->get('sort', 'updated_at');
        $sortDirection = $request->get('direction', 'desc');
        
        // Debug logging
        \Log::info('Tenant sorting', [
            'sort' => $sortField,
            'direction' => $sortDirection,
            'all_params' => $request->all()
        ]);
        
        // Validate sort field
        $allowedSortFields = ['name', 'slug', 'domain', 'plan', 'industry', 'region', 'created_at', 'updated_at', 'is_active'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'updated_at';
        }
        
        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }
        
        $query->orderBy($sortField, $sortDirection);
        
        // Debug the query
        \Log::info('Tenant query SQL', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings()
        ]);
        
        $tenants = $query->paginate(15)->withQueryString();
        
        \Log::info('Tenants found', [
            'count' => $tenants->total(),
            'current_page' => $tenants->currentPage()
        ]);
        
        return view('admin.tenants.index', compact('tenants'));
    }

    public function create()
    {
        return view('admin.tenants.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tenants,id|regex:/^[a-z0-9-]+$/',
            'domain' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'plan' => 'required|in:starter,basic,premium,pro,enterprise',
            'industry' => 'required|in:technology,healthcare,finance,education,retail,manufacturing,consulting,media,nonprofit,government',
            'region' => 'required|in:us-east,us-west,eu-central,asia-pacific,canada,australia',
            'employee_count' => 'nullable|integer|min:1',
            'max_users' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Prepare features based on plan
            $features = $this->getFeaturesForPlan($request->plan);
            
            // Add industry-specific features
            $features = array_merge($features, $this->getIndustryFeatures($request->industry));

            $tenant = Tenant::create([
                'id' => $request->slug,
                'plan' => $request->plan,
                'industry' => $request->industry,
                'region' => $request->region,
                'employee_count' => $request->employee_count ?: rand(10, 1000),
                'created_year' => date('Y'),
                'features' => $features,
                'billing_status' => 'active',
                'billing_cycle' => 'monthly',
                'trial_ends_at' => now()->addDays(30)->toDateString(),
            ]);
            
            // Set tenant attributes using Stancl methods
            $tenant->name = $request->name;
            $tenant->slug = $request->slug;
            $tenant->domain = $request->domain ?: $request->slug . '.example.com';
            $tenant->description = $request->description;
            $tenant->is_active = $request->boolean('is_active', true);
            $tenant->max_users = $request->max_users;
            $tenant->save();

            // Log tenant creation
            $this->auditService->logTenantManagement(
                'tenant_created',
                "Tenant '{$tenant->name}' created",
                $tenant,
                [
                    'tenant_slug' => $tenant->slug,
                    'plan' => $tenant->plan,
                    'industry' => $tenant->industry,
                    'region' => $tenant->region,
                    'max_users' => $tenant->max_users,
                    'is_active' => $tenant->is_active,
                    'features' => $features
                ]
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tenant created successfully',
                    'tenant' => $tenant
                ]);
            }
            
            return redirect()->route('admin.tenants.show', $tenant)
                ->with('success', 'Tenant created successfully');

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create tenant: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->withErrors(['error' => 'Failed to create tenant: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function show(Tenant $tenant)
    {
        $tenant->load('users');
        
        // Calculate tenant statistics
        $totalUsers = $tenant->users()->count();
        $adminUsers = $tenant->users()->where('is_admin', true)->count();
        $regularUsers = $totalUsers - $adminUsers;
        
        // Get last login activity (this would require login audit data)
        $lastLogin = null; // We could implement this later with login audit data
        
        $stats = [
            'total_users' => $totalUsers,
            'admin_users' => $adminUsers,
            'regular_users' => $regularUsers,
            'active_users' => $totalUsers, // For now, assume all users are active
            'last_login' => $lastLogin,
            'plan' => $tenant->plan ?? 'Not set',
            'industry' => $tenant->industry ?? 'Not set',
            'region' => $tenant->region ?? 'Not set',
            'employee_count' => $tenant->employee_count ?? 'Not set',
            'created_year' => $tenant->created_year ?? 'Not set',
            'billing_status' => $tenant->billing_status ?? 'Not set',
            'billing_cycle' => $tenant->billing_cycle ?? 'Not set',
            'features' => $tenant->features ?? [],
        ];
        
        return view('admin.tenants.show', compact('tenant', 'stats'));
    }

    public function edit(Tenant $tenant)
    {
        return view('admin.tenants.edit', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'domain' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'plan' => 'required|in:starter,basic,premium,pro,enterprise',
            'industry' => 'required|in:technology,healthcare,finance,education,retail,manufacturing,consulting,media,nonprofit,government',
            'region' => 'required|in:us-east,us-west,eu-central,asia-pacific,canada,australia',
            'employee_count' => 'nullable|integer|min:1',
            'max_users' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Store original values for audit
            $originalData = [
                'name' => $tenant->name,
                'domain' => $tenant->domain,
                'description' => $tenant->description,
                'is_active' => $tenant->is_active,
                'max_users' => $tenant->max_users,
                'plan' => $tenant->plan,
                'industry' => $tenant->industry,
                'region' => $tenant->region,
                'employee_count' => $tenant->employee_count,
            ];

            // Prepare features based on plan
            $features = $this->getFeaturesForPlan($request->plan);
            
            // Add industry-specific features
            $features = array_merge($features, $this->getIndustryFeatures($request->industry));

            // Update tenant attributes using Stancl methods
            $tenant->name = $request->name;
            $tenant->domain = $request->domain;
            $tenant->description = $request->description;
            $tenant->is_active = $request->boolean('is_active', true);
            $tenant->max_users = $request->max_users;
            $tenant->plan = $request->plan;
            $tenant->industry = $request->industry;
            $tenant->region = $request->region;
            $tenant->employee_count = $request->employee_count;
            $tenant->features = $features;
            $tenant->save();

            // Log tenant update - detect changes
            $newData = [
                'name' => $tenant->name,
                'domain' => $tenant->domain,
                'description' => $tenant->description,
                'is_active' => $tenant->is_active,
                'max_users' => $tenant->max_users,
                'plan' => $tenant->plan,
                'industry' => $tenant->industry,
                'region' => $tenant->region,
                'employee_count' => $tenant->employee_count,
            ];

            $changes = [];
            foreach ($originalData as $key => $oldValue) {
                if ($newData[$key] != $oldValue) {
                    $changes[$key] = ['old' => $oldValue, 'new' => $newData[$key]];
                }
            }

            if (!empty($changes)) {
                $this->auditService->logTenantManagement(
                    'tenant_updated',
                    "Tenant '{$tenant->name}' updated",
                    $tenant,
                    [
                        'field_changes' => $changes,
                        'features_updated' => $features
                    ]
                );
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tenant updated successfully',
                    'tenant' => $tenant
                ]);
            }

            return redirect()->route('admin.tenants.show', $tenant)
                ->with('success', 'Tenant updated successfully');

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update tenant: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->withErrors(['error' => 'Failed to update tenant: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function destroy(Request $request, Tenant $tenant)
    {
        try {
            // Check if tenant has users
            if ($tenant->users()->count() > 0) {
                $message = 'Cannot delete tenant with active users. Remove users first.';
                
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message
                    ], 422);
                }
                
                return redirect()->route('admin.tenants.show', $tenant)
                    ->withErrors(['error' => $message]);
            }

            $tenantName = $tenant->name;
            $tenantSlug = $tenant->slug;
            $tenantPlan = $tenant->plan;
            $tenantUserCount = $tenant->users()->count();

            // Log tenant deletion before actual deletion
            $this->auditService->logTenantManagement(
                'tenant_deleted',
                "Tenant '{$tenantName}' deleted",
                $tenant,
                [
                    'tenant_slug' => $tenantSlug,
                    'plan' => $tenantPlan,
                    'user_count' => $tenantUserCount,
                    'deleted_by' => auth()->user()->name
                ]
            );

            $tenant->delete();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tenant deleted successfully'
                ]);
            }

            return redirect()->route('admin.tenants.index')
                ->with('success', "Tenant '{$tenantName}' deleted successfully");

        } catch (\Exception $e) {
            $message = 'Failed to delete tenant: ' . $e->getMessage();
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message
                ], 500);
            }
            
            return redirect()->route('admin.tenants.show', $tenant)
                ->withErrors(['error' => $message]);
        }
    }

    public function bulkCreate(Request $request)
    {
        $count = $request->input('count', 48); // Default to 48 additional tenants
        
        try {
            $created = [];
            $plans = ['starter', 'basic', 'premium', 'pro', 'enterprise'];
            $industries = ['technology', 'healthcare', 'finance', 'education', 'retail', 'manufacturing', 'consulting', 'media', 'nonprofit', 'government'];
            $regions = ['us-east', 'us-west', 'eu-central', 'asia-pacific', 'canada', 'australia'];
            
            // Company name components for realistic names
            $companyNames = [
                'TechCorp', 'InnovateLab', 'DataFlow', 'CloudVision', 'FinanceHub',
                'EduTech', 'HealthPlus', 'RetailMax', 'MediaGroup', 'ConsultPro',
                'SecureTech', 'GlobalSoft', 'NextGen', 'SmartSys', 'DigitalEdge',
                'FlexiCorp', 'PowerTech', 'VitalCare', 'PrimeLab', 'EliteGroup',
                'CodeWorks', 'DataCore', 'WebFlow', 'AppLab', 'TechFlow'
            ];
            
            $suffixes = ['Inc', 'LLC', 'Corp', 'Ltd', 'Group', 'Systems', 'Solutions', 'Technologies'];

            // Create tenant1 and tenant2 first if they don't exist
            if (!Tenant::find('tenant1')) {
                $tenant1 = Tenant::create([
                    'id' => 'tenant1',
                    'plan' => 'enterprise',
                    'industry' => 'technology',
                    'region' => 'us-east',
                    'employee_count' => 500,
                    'created_year' => 2020,
                    'features' => $this->getFeaturesForPlan('enterprise'),
                    'billing_status' => 'active',
                    'billing_cycle' => 'monthly',
                ]);
                
                $tenant1->name = 'Acme Corporation';
                $tenant1->slug = 'tenant1';
                $tenant1->domain = 'tenant1.localhost:8001';
                $tenant1->description = 'Primary technology tenant for development and testing';
                $tenant1->is_active = true;
                $tenant1->save();
                
                $created[] = $tenant1;
            }

            if (!Tenant::find('tenant2')) {
                $tenant2 = Tenant::create([
                    'id' => 'tenant2',
                    'plan' => 'premium',
                    'industry' => 'healthcare',
                    'region' => 'us-west',
                    'employee_count' => 250,
                    'created_year' => 2018,
                    'features' => array_merge(
                        $this->getFeaturesForPlan('premium'),
                        $this->getIndustryFeatures('healthcare')
                    ),
                    'billing_status' => 'active',
                    'billing_cycle' => 'annual',
                ]);
                
                $tenant2->name = 'Global Health Systems';
                $tenant2->slug = 'tenant2';
                $tenant2->domain = 'tenant2.localhost:8002';
                $tenant2->description = 'Healthcare organization with premium features';
                $tenant2->is_active = true;
                $tenant2->save();
                
                $created[] = $tenant2;
            }

            // Create additional tenants
            $startId = 3;
            $existingTenants = Tenant::where('id', 'like', 'tenant%')->count();
            if ($existingTenants >= 2) {
                $startId = $existingTenants + 1;
            }

            for ($i = $startId; $i <= ($startId + $count - 1); $i++) {
                $plan = $plans[array_rand($plans)];
                $industry = $industries[array_rand($industries)];
                $region = $regions[array_rand($regions)];
                
                $baseName = $companyNames[array_rand($companyNames)];
                $suffix = $suffixes[array_rand($suffixes)];
                $companyName = $baseName . ' ' . $suffix;
                
                $features = $this->getFeaturesForPlan($plan);
                $features = array_merge($features, $this->getIndustryFeatures($industry));

                $tenant = Tenant::create([
                    'id' => 'tenant' . $i,
                    'plan' => $plan,
                    'industry' => $industry,
                    'region' => $region,
                    'employee_count' => rand(10, 5000),
                    'created_year' => rand(2015, 2024),
                    'features' => $features,
                    'billing_status' => rand(0, 20) > 1 ? 'active' : 'suspended', // 95% active
                    'billing_cycle' => rand(0, 1) ? 'monthly' : 'annual',
                    'trial_ends_at' => now()->addDays(rand(-30, 60))->toDateString(),
                ]);
                
                $tenant->name = $companyName;
                $tenant->slug = 'tenant' . $i;
                $tenant->domain = 'tenant' . $i . '.example.com';
                $tenant->description = "A {$industry} organization using {$plan} plan in {$region} region";
                $tenant->is_active = rand(0, 10) > 1; // 90% active
                $tenant->save();

                $created[] = $tenant;
            }

            return response()->json([
                'success' => true,
                'message' => count($created) . ' tenants created successfully',
                'tenants' => $created
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk create tenants: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getFeaturesForPlan(string $plan): array
    {
        $features = ['analytics' => true];
        
        switch ($plan) {
            case 'starter':
                $features['basic_support'] = true;
                $features['user_limit'] = 10;
                break;
            case 'basic':
                $features['email_support'] = true;
                $features['user_limit'] = 50;
                $features['custom_branding'] = true;
                break;
            case 'premium':
                $features['api'] = true;
                $features['priority_support'] = true;
                $features['user_limit'] = 200;
                $features['custom_branding'] = true;
                $features['advanced_analytics'] = true;
                break;
            case 'pro':
                $features['api'] = true;
                $features['advanced_analytics'] = true;
                $features['phone_support'] = true;
                $features['user_limit'] = 500;
                $features['custom_branding'] = true;
                $features['integrations'] = true;
                break;
            case 'enterprise':
                $features['api'] = true;
                $features['sso'] = true;
                $features['advanced_analytics'] = true;
                $features['dedicated_support'] = true;
                $features['compliance'] = true;
                $features['user_limit'] = -1; // Unlimited
                $features['custom_branding'] = true;
                $features['integrations'] = true;
                $features['white_label'] = true;
                break;
        }
        
        return $features;
    }

    private function getIndustryFeatures(string $industry): array
    {
        $features = [];
        
        switch ($industry) {
            case 'healthcare':
                $features['hipaa_compliance'] = true;
                $features['patient_data_encryption'] = true;
                break;
            case 'finance':
                $features['pci_compliance'] = true;
                $features['fraud_detection'] = true;
                $features['financial_reporting'] = true;
                break;
            case 'education':
                $features['ferpa_compliance'] = true;
                $features['student_portal'] = true;
                $features['gradebook_integration'] = true;
                break;
            case 'government':
                $features['security_clearance'] = true;
                $features['audit_trails'] = true;
                break;
            case 'manufacturing':
                $features['inventory_tracking'] = true;
                $features['supply_chain'] = true;
                break;
        }
        
        return $features;
    }
}
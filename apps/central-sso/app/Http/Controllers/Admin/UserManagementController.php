<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Tenant;
use App\Models\UserContact;
use App\Models\UserAddress;
use App\Models\UserFamilyMember;
use App\Models\UserSocialMedia;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;

class UserManagementController extends Controller
{
    private AuditService $auditService;

    public function __construct(AuditService $auditService)
    {
        $this->auditService = $auditService;
    }

    public function index(Request $request)
    {
        $query = User::with(['roles', 'tenants', 'familyMembers']);
        
        // Handle search with fuzzy matching
        if ($request->filled('search')) {
            $search = trim($request->search);
            $searchTerms = explode(' ', $search);
            
            $query->where(function($q) use ($search, $searchTerms) {
                // Exact match first (highest priority)
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('phone', 'LIKE', "%{$search}%")
                  ->orWhere('employee_id', 'LIKE', "%{$search}%")
                  ->orWhere('job_title', 'LIKE', "%{$search}%")
                  ->orWhere('department', 'LIKE', "%{$search}%")
                  ->orWhere('city', 'LIKE', "%{$search}%")
                  ->orWhere('country', 'LIKE', "%{$search}%");
                
                // Fuzzy search for individual terms
                foreach ($searchTerms as $term) {
                    if (strlen($term) >= 2) {
                        $q->orWhere('name', 'LIKE', "%{$term}%")
                          ->orWhere('email', 'LIKE', "%{$term}%")
                          ->orWhere('phone', 'LIKE', "%{$term}%")
                          ->orWhere('job_title', 'LIKE', "%{$term}%")
                          ->orWhere('department', 'LIKE', "%{$term}%");
                    }
                }
            });
        }
        
        // Handle sorting
        $sortField = $request->get('sort', 'updated_at');
        $sortDirection = $request->get('direction', 'desc');
        
        // Validate sort field
        $allowedSortFields = ['name', 'email', 'phone', 'job_title', 'department', 'city', 'country', 'created_at', 'updated_at', 'is_admin'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'updated_at';
        }
        
        // Validate sort direction
        if (!in_array($sortDirection, ['asc', 'desc'])) {
            $sortDirection = 'desc';
        }
        
        $query->orderBy($sortField, $sortDirection);
        
        $users = $query->paginate(15)->withQueryString();
        $tenants = Tenant::where('is_active', true)->get();

        return view('admin.users.index', compact('users', 'tenants'));
    }

    public function getUsers(): JsonResponse
    {
        $users = User::with(['roles.permissions', 'tenants'])->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => $user->is_admin,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                'tenants' => $user->tenants->map(function ($tenant) {
                    return [
                        'id' => $tenant->id,
                        'name' => $tenant->name,
                        'slug' => $tenant->slug
                    ];
                }),
                'roles' => $user->roles->map(function ($role) {
                    return [
                        'role' => [
                            'id' => $role->id,
                            'name' => $role->name,
                            'slug' => $role->slug,
                            'is_system' => $role->is_system
                        ],
                        'tenant_id' => $role->pivot->tenant_id ?? null
                    ];
                })
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'is_admin' => 'boolean',
                'tenant_ids' => 'nullable|array',
                'tenant_ids.*' => 'string|exists:tenants,id',
                'phone' => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date|before:today',
                'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
                'nationality' => 'nullable|string|max:100',
                'bio' => 'nullable|string|max:1000',
                'avatar_url' => 'nullable|url|max:500',
                'address_line_1' => 'nullable|string|max:255',
                'address_line_2' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:100',
                'state_province' => 'nullable|string|max:100',
                'postal_code' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_phone' => 'nullable|string|max:20',
                'emergency_contact_relationship' => 'nullable|string|max:100',
                'job_title' => 'nullable|string|max:255',
                'department' => 'nullable|string|max:255',
                'employee_id' => 'nullable|string|max:50',
                'hire_date' => 'nullable|date',
                'timezone' => 'nullable|string|max:50',
                'language' => 'nullable|string|max:10',
            ]);

            $userData = array_merge($validatedData, [
                'password' => Hash::make($validatedData['password']),
                'is_admin' => $validatedData['is_admin'] ?? false,
                'timezone' => $validatedData['timezone'] ?? 'UTC',
                'language' => $validatedData['language'] ?? 'en',
            ]);
            
            // Remove tenant_ids from user data as it's handled separately
            unset($userData['tenant_ids'], $userData['password_confirmation']);
            
            $user = User::create($userData);

            // Attach tenants if provided
            if (!empty($validatedData['tenant_ids'])) {
                $user->tenants()->sync($validatedData['tenant_ids']);
                
                // Log tenant assignment
                $tenantNames = Tenant::whereIn('id', $validatedData['tenant_ids'])->pluck('name')->toArray();
                $this->auditService->logUserManagement(
                    'tenant_assigned',
                    "User '{$user->name}' assigned to tenants: " . implode(', ', $tenantNames),
                    $user,
                    [
                        'tenant_ids' => $validatedData['tenant_ids'],
                        'tenant_names' => $tenantNames,
                        'operation' => 'bulk_assign'
                    ]
                );
            }

            // Log user creation
            $this->auditService->logUserManagement(
                'user_created',
                "User '{$user->name}' created",
                $user,
                [
                    'user_email' => $user->email,
                    'is_admin' => $user->is_admin,
                    'has_tenant_access' => !empty($validatedData['tenant_ids']),
                    'tenant_count' => count($validatedData['tenant_ids'] ?? [])
                ]
            );

            $user->load(['tenants', 'roles']);

            // Handle different response types
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User created successfully',
                    'data' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'is_admin' => $user->is_admin,
                        'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                        'tenants' => $user->tenants,
                        'roles' => []
                    ]
                ], 201);
            }

            return redirect()->route('admin.users.show', $user)
                ->with('success', 'User created successfully');

        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    public function update(Request $request, User $user)
    {
        try {
            // Check if this is a tenant-only update (from modal)
            // We detect this by checking if we only have tenant_ids and no other user fields
            $isTenantOnlyUpdate = $request->has('tenant_ids') && 
                                !$request->has('name') && 
                                !$request->has('email') &&
                                !$request->has('password');
            
            if ($isTenantOnlyUpdate) {
                // Validate only tenant_ids for tenant-only updates
                $validatedData = $request->validate([
                    'tenant_ids' => 'nullable|array',
                    'tenant_ids.*' => 'string|exists:tenants,id',
                ]);
                
                // Get old tenant IDs for audit
                $oldTenantIds = $user->tenants->pluck('id')->toArray();
                $newTenantIds = $validatedData['tenant_ids'] ?? [];

                // Update tenant associations only
                $user->tenants()->sync($newTenantIds);
                
                // Log tenant assignment changes
                if (array_diff($oldTenantIds, $newTenantIds) || array_diff($newTenantIds, $oldTenantIds)) {
                    $removedTenants = array_diff($oldTenantIds, $newTenantIds);
                    $addedTenants = array_diff($newTenantIds, $oldTenantIds);
                    
                    $this->auditService->logUserManagement(
                        'tenant_updated',
                        "User '{$user->name}' tenant assignments updated",
                        $user,
                        [
                            'old_tenant_ids' => $oldTenantIds,
                            'new_tenant_ids' => $newTenantIds,
                            'added_tenants' => $addedTenants,
                            'removed_tenants' => $removedTenants,
                            'operation' => 'tenant_only_update'
                        ]
                    );
                }
                
                $user->load(['tenants', 'roles']);
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Tenant assignments updated successfully',
                        'data' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'tenants' => $user->tenants,
                        ]
                    ]);
                }
                
                return redirect()->route('admin.users.index')
                    ->with('success', 'Tenant assignments updated successfully');
            }
            
            // Full user update validation
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
                'password' => 'nullable|string|min:8|confirmed',
                'is_admin' => 'boolean',
                'tenant_ids' => 'nullable|array',
                'tenant_ids.*' => 'string|exists:tenants,id',
                'phone' => 'nullable|string|max:20',
                'date_of_birth' => 'nullable|date|before:today',
                'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
                'nationality' => 'nullable|string|max:100',
                'bio' => 'nullable|string|max:1000',
                'avatar_url' => 'nullable|url|max:500',
                'address_line_1' => 'nullable|string|max:255',
                'address_line_2' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:100',
                'state_province' => 'nullable|string|max:100',
                'postal_code' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:100',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_phone' => 'nullable|string|max:20',
                'emergency_contact_relationship' => 'nullable|string|max:100',
                'job_title' => 'nullable|string|max:255',
                'department' => 'nullable|string|max:255',
                'employee_id' => 'nullable|string|max:50',
                'hire_date' => 'nullable|date',
                'timezone' => 'nullable|string|max:50',
                'language' => 'nullable|string|max:10',
            ]);

            $updateData = array_merge($validatedData, [
                'is_admin' => $validatedData['is_admin'] ?? false,
            ]);

            // Only update password if provided
            if (!empty($validatedData['password'])) {
                $updateData['password'] = Hash::make($validatedData['password']);
            } else {
                unset($updateData['password']);
            }
            
            // Store original values for audit
            $originalData = $user->only(['name', 'email', 'is_admin', 'phone', 'job_title', 'department']);
            $oldTenantIds = $user->tenants->pluck('id')->toArray();

            // Remove fields that shouldn't be mass assigned
            unset($updateData['tenant_ids'], $updateData['password_confirmation']);

            $user->update($updateData);

            // Update tenant associations
            $tenantChanges = [];
            if (isset($validatedData['tenant_ids'])) {
                $newTenantIds = $validatedData['tenant_ids'];
                $user->tenants()->sync($newTenantIds);
                
                if (array_diff($oldTenantIds, $newTenantIds) || array_diff($newTenantIds, $oldTenantIds)) {
                    $tenantChanges = [
                        'old_tenant_ids' => $oldTenantIds,
                        'new_tenant_ids' => $newTenantIds,
                        'added_tenants' => array_diff($newTenantIds, $oldTenantIds),
                        'removed_tenants' => array_diff($oldTenantIds, $newTenantIds),
                    ];
                }
            }

            // Log user update
            $changes = [];
            foreach ($originalData as $key => $oldValue) {
                if (isset($updateData[$key]) && $updateData[$key] != $oldValue) {
                    $changes[$key] = ['old' => $oldValue, 'new' => $updateData[$key]];
                }
            }

            if (!empty($changes) || !empty($tenantChanges) || !empty($validatedData['password'])) {
                $auditData = array_merge(['field_changes' => $changes], $tenantChanges);
                if (!empty($validatedData['password'])) {
                    $auditData['password_changed'] = true;
                }

                $this->auditService->logUserManagement(
                    'user_updated',
                    "User '{$user->name}' updated",
                    $user,
                    $auditData
                );
            }

            $user->load(['tenants', 'roles']);

            // Handle different response types
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User updated successfully',
                    'data' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'is_admin' => $user->is_admin,
                        'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                        'tenants' => $user->tenants,
                        'roles' => $user->roles
                    ]
                ]);
            }

            return redirect()->route('admin.users.show', $user)
                ->with('success', 'User updated successfully');

        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    public function destroy(Request $request, User $user)
    {
        try {
            // Prevent deletion of current user
            if ($user->id === auth()->id()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You cannot delete your own account'
                    ], 403);
                }
                
                return redirect()->back()
                    ->with('error', 'You cannot delete your own account');
            }

            $userName = $user->name;
            $userEmail = $user->email;
            $tenantCount = $user->tenants->count();
            $isAdmin = $user->is_admin;
            
            // Log user deletion before actual deletion
            $this->auditService->logUserManagement(
                'user_deleted',
                "User '{$userName}' deleted",
                $user,
                [
                    'user_email' => $userEmail,
                    'was_admin' => $isAdmin,
                    'tenant_count' => $tenantCount,
                    'deleted_by' => auth()->user()->name
                ]
            );
            
            $user->delete();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User deleted successfully'
                ]);
            }

            return redirect()->route('admin.users.index')
                ->with('success', "User \"{$userName}\" deleted successfully");

        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }

    public function assignTenant(Request $request, string $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            
            $validated = $request->validate([
                'tenant_id' => 'required|string|exists:tenants,id'
            ]);

            if (!$user->tenants->contains('id', $validated['tenant_id'])) {
                $user->tenants()->attach($validated['tenant_id']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Tenant assigned successfully'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function removeTenant(Request $request, string $userId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            
            $validated = $request->validate([
                'tenant_id' => 'required|string|exists:tenants,id'
            ]);

            $user->tenants()->detach($validated['tenant_id']);

            return response()->json([
                'success' => true,
                'message' => 'Tenant access removed successfully'
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    public function show(User $user)
    {
        $user->load(['familyMembers', 'tenants', 'roles', 'contacts', 'addresses']);
        
        return view('admin.users.show', compact('user'));
    }

    public function create()
    {
        $tenants = Tenant::where('is_active', true)->get();
        return view('admin.users.create', compact('tenants'));
    }

    public function edit(User $user)
    {
        $user->load(['tenants']);
        $tenants = Tenant::where('is_active', true)->get();
        return view('admin.users.edit', compact('user', 'tenants'));
    }

    // ============ CONTACT MANAGEMENT METHODS ============

    public function contacts(User $user)
    {
        $this->authorize('View User Contacts');
        
        $user->load(['contacts' => function($query) {
            $query->orderBy('is_primary', 'desc')->orderBy('type');
        }]);
        
        return view('admin.users.contacts', compact('user'));
    }

    public function storeContact(Request $request, User $user)
    {
        $this->authorize('Manage User Contacts');
        
        $validatedData = $request->validate([
            'type' => 'required|string|max:50',
            'label' => 'nullable|string|max:255',
            'value' => 'required|string|max:255',
            'is_primary' => 'boolean',
            'is_public' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        // If marking as primary, unset other primary contacts of the same type
        if ($validatedData['is_primary'] ?? false) {
            $user->contacts()->where('type', $validatedData['type'])->update(['is_primary' => false]);
        }

        $contact = $user->contacts()->create($validatedData);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Contact added successfully',
                'contact' => $contact
            ]);
        }

        return redirect()->route('admin.users.contacts', $user)->with('success', 'Contact added successfully!');
    }

    public function updateContact(Request $request, User $user, UserContact $contact)
    {
        $this->authorize('Manage User Contacts');
        
        // Ensure the contact belongs to the user
        if ($contact->user_id !== $user->id) {
            abort(404);
        }

        $validatedData = $request->validate([
            'type' => 'required|string|max:50',
            'label' => 'nullable|string|max:255',
            'value' => 'required|string|max:255',
            'is_primary' => 'boolean',
            'is_public' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        // If marking as primary, unset other primary contacts of the same type
        if ($validatedData['is_primary'] ?? false) {
            $user->contacts()->where('type', $validatedData['type'])->where('id', '!=', $contact->id)->update(['is_primary' => false]);
        }

        $contact->update($validatedData);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Contact updated successfully',
                'contact' => $contact
            ]);
        }

        return redirect()->route('admin.users.contacts', $user)->with('success', 'Contact updated successfully!');
    }

    public function destroyContact(Request $request, User $user, UserContact $contact)
    {
        $this->authorize('Manage User Contacts');
        
        // Ensure the contact belongs to the user
        if ($contact->user_id !== $user->id) {
            abort(404);
        }

        $contact->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Contact deleted successfully'
            ]);
        }

        return redirect()->route('admin.users.contacts', $user)->with('success', 'Contact deleted successfully!');
    }

    // ============ ADDRESS MANAGEMENT METHODS ============

    public function addresses(User $user)
    {
        $this->authorize('View User Addresses');
        
        $user->load(['addresses' => function($query) {
            $query->orderBy('is_primary', 'desc')->orderBy('type');
        }]);
        
        return view('admin.users.addresses', compact('user'));
    }

    public function storeAddress(Request $request, User $user)
    {
        $this->authorize('Manage User Addresses');
        
        $validatedData = $request->validate([
            'type' => 'required|string|max:50',
            'label' => 'nullable|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state_province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'required|string|max:100',
            'is_primary' => 'boolean',
            'is_public' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        // If marking as primary, unset other primary addresses
        if ($validatedData['is_primary'] ?? false) {
            $user->addresses()->update(['is_primary' => false]);
        }

        $address = $user->addresses()->create($validatedData);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Address added successfully',
                'address' => $address
            ]);
        }

        return redirect()->route('admin.users.addresses', $user)->with('success', 'Address added successfully!');
    }

    public function updateAddress(Request $request, User $user, UserAddress $address)
    {
        $this->authorize('Manage User Addresses');
        
        // Ensure the address belongs to the user
        if ($address->user_id !== $user->id) {
            abort(404);
        }

        $validatedData = $request->validate([
            'type' => 'required|string|max:50',
            'label' => 'nullable|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state_province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'required|string|max:100',
            'is_primary' => 'boolean',
            'is_public' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        // If marking as primary, unset other primary addresses
        if ($validatedData['is_primary'] ?? false) {
            $user->addresses()->where('id', '!=', $address->id)->update(['is_primary' => false]);
        }

        $address->update($validatedData);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Address updated successfully',
                'address' => $address
            ]);
        }

        return redirect()->route('admin.users.addresses', $user)->with('success', 'Address updated successfully!');
    }

    public function destroyAddress(Request $request, User $user, UserAddress $address)
    {
        $this->authorize('Manage User Addresses');
        
        // Ensure the address belongs to the user
        if ($address->user_id !== $user->id) {
            abort(404);
        }

        $address->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Address deleted successfully'
            ]);
        }

        return redirect()->route('admin.users.addresses', $user)->with('success', 'Address deleted successfully!');
    }

    // ============ FAMILY MEMBER MANAGEMENT METHODS ============

    public function family(User $user)
    {
        $this->authorize('View User Family Members');
        
        $user->load(['familyMembers' => function($query) {
            $query->orderBy('relationship')->orderBy('first_name');
        }]);
        
        return view('admin.users.family', compact('user'));
    }

    public function storeFamilyMember(Request $request, User $user)
    {
        $this->authorize('Manage User Family Members');
        
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'relationship' => 'required|string|max:100',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'occupation' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'is_emergency_contact' => 'boolean',
            'is_dependent' => 'boolean',
        ]);

        $familyMember = $user->familyMembers()->create($validatedData);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Family member added successfully',
                'family_member' => $familyMember
            ]);
        }

        return redirect()->route('admin.users.family', $user)->with('success', 'Family member added successfully!');
    }

    public function updateFamilyMember(Request $request, User $user, UserFamilyMember $familyMember)
    {
        $this->authorize('Manage User Family Members');
        
        // Ensure the family member belongs to the user
        if ($familyMember->user_id !== $user->id) {
            abort(404);
        }

        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'relationship' => 'required|string|max:100',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'occupation' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'is_emergency_contact' => 'boolean',
            'is_dependent' => 'boolean',
        ]);

        $familyMember->update($validatedData);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Family member updated successfully',
                'family_member' => $familyMember
            ]);
        }

        return redirect()->route('admin.users.family', $user)->with('success', 'Family member updated successfully!');
    }

    public function destroyFamilyMember(Request $request, User $user, UserFamilyMember $familyMember)
    {
        $this->authorize('Manage User Family Members');
        
        // Ensure the family member belongs to the user
        if ($familyMember->user_id !== $user->id) {
            abort(404);
        }

        $familyMember->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Family member deleted successfully'
            ]);
        }

        return redirect()->route('admin.users.family', $user)->with('success', 'Family member deleted successfully!');
    }

    // ============ SOCIAL MEDIA MANAGEMENT METHODS ============

    public function socialMedia(User $user)
    {
        $this->authorize('View User Social Media');
        
        $user->load(['socialMedia' => function($query) {
            $query->ordered();
        }]);
        
        return view('admin.users.social-media', compact('user'));
    }

    public function storeSocialMedia(Request $request, User $user)
    {
        $this->authorize('Manage User Social Media');
        
        $validatedData = $request->validate([
            'platform' => 'required|string|max:50',
            'username' => 'nullable|string|max:255',
            'url' => 'required|url|max:500',
            'display_name' => 'nullable|string|max:255',
            'is_public' => 'boolean',
            'order' => 'integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $socialMedia = $user->socialMedia()->create($validatedData);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Social media profile added successfully',
                'social_media' => $socialMedia
            ]);
        }

        return redirect()->route('admin.users.social-media', $user)->with('success', 'Social media profile added successfully!');
    }

    public function updateSocialMedia(Request $request, User $user, UserSocialMedia $socialMedia)
    {
        $this->authorize('Manage User Social Media');
        
        // Ensure the social media profile belongs to the user
        if ($socialMedia->user_id !== $user->id) {
            abort(404);
        }

        $validatedData = $request->validate([
            'platform' => 'required|string|max:50',
            'username' => 'nullable|string|max:255',
            'url' => 'required|url|max:500',
            'display_name' => 'nullable|string|max:255',
            'is_public' => 'boolean',
            'order' => 'integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $socialMedia->update($validatedData);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Social media profile updated successfully',
                'social_media' => $socialMedia
            ]);
        }

        return redirect()->route('admin.users.social-media', $user)->with('success', 'Social media profile updated successfully!');
    }

    public function destroySocialMedia(Request $request, User $user, UserSocialMedia $socialMedia)
    {
        $this->authorize('Manage User Social Media');
        
        // Ensure the social media profile belongs to the user
        if ($socialMedia->user_id !== $user->id) {
            abort(404);
        }

        $socialMedia->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Social media profile deleted successfully'
            ]);
        }

        return redirect()->route('admin.users.social-media', $user)->with('success', 'Social media profile deleted successfully!');
    }
}
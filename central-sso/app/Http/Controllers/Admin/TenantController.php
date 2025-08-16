<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TenantController extends Controller
{

    public function index()
    {
        $tenants = Tenant::withCount('users')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.tenants.index', compact('tenants'));
    }

    public function create()
    {
        return view('admin.tenants.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tenants,slug',
            'domain' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'max_users' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        // Generate ID from slug if not provided
        $validated['id'] = $validated['slug'];

        $tenant = Tenant::create($validated);

        return redirect()
            ->route('admin.tenants.index')
            ->with('success', 'Tenant created successfully.');
    }

    public function show(Tenant $tenant)
    {
        $tenant->load(['users' => function($query) {
            $query->with('roles');
        }]);

        $stats = [
            'total_users' => $tenant->users->count(),
            'admin_users' => $tenant->users->filter(function($user) {
                return $user->hasRole(['Super Admin', 'Admin']);
            })->count(),
            'regular_users' => $tenant->users->filter(function($user) {
                return $user->hasRole('User');
            })->count(),
            'last_login' => $tenant->users->max('updated_at'),
        ];

        return view('admin.tenants.show', compact('tenant', 'stats'));
    }

    public function edit(Tenant $tenant)
    {
        return view('admin.tenants.edit', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tenants,slug,' . $tenant->id,
            'domain' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'max_users' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $tenant->update($validated);

        return redirect()
            ->route('admin.tenants.index')
            ->with('success', 'Tenant updated successfully.');
    }

    public function destroy(Tenant $tenant)
    {
        // Prevent deletion if tenant has users
        if ($tenant->users()->count() > 0) {
            return redirect()
                ->route('admin.tenants.index')
                ->with('error', 'Cannot delete tenant with existing users.');
        }

        $tenant->delete();

        return redirect()
            ->route('admin.tenants.index')
            ->with('success', 'Tenant deleted successfully.');
    }

    public function users(Tenant $tenant)
    {
        $users = $tenant->users()
            ->with('roles')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Get users not in this tenant for assignment
        $availableUsers = User::whereDoesntHave('tenants', function($query) use ($tenant) {
            $query->where('tenant_id', $tenant->id);
        })->get();

        return view('admin.tenants.users', compact('tenant', 'users', 'availableUsers'));
    }

    public function assignUser(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($validated['user_id']);
        
        if (!$user->tenants->contains($tenant->id)) {
            $user->tenants()->attach($tenant->id);
            
            return redirect()
                ->route('admin.tenants.users', $tenant)
                ->with('success', "User {$user->name} assigned to tenant successfully.");
        }

        return redirect()
            ->route('admin.tenants.users', $tenant)
            ->with('error', 'User is already assigned to this tenant.');
    }

    public function removeUser(Request $request, Tenant $tenant, User $user)
    {
        $user->tenants()->detach($tenant->id);

        return redirect()
            ->route('admin.tenants.users', $tenant)
            ->with('success', "User {$user->name} removed from tenant successfully.");
    }

    public function toggle(Tenant $tenant)
    {
        $tenant->update(['is_active' => !$tenant->is_active]);
        
        $status = $tenant->is_active ? 'activated' : 'deactivated';
        
        return redirect()
            ->route('admin.tenants.index')
            ->with('success', "Tenant {$status} successfully.");
    }
}

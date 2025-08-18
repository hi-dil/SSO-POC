@extends('layouts.admin')

@section('title', 'Roles & Permissions')

@section('header')
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Roles & Permissions</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Manage authentication roles and permissions for the central SSO system
        </p>
    </div>
@endsection

@section('actions')
    <a href="{{ route('admin.roles.create') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium  transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-teal-600 dark:bg-teal-500 text-white hover:bg-teal-700 dark:hover:bg-teal-600 h-10 px-4 py-2">
        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        New Role
    </a>
@endsection

@section('content')
<div x-data="roleManagement()" x-init="initializeData()">
    <!-- Tabs -->
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8">
            <button @click="activeTab = 'roles'" 
                    :class="activeTab === 'roles' ? 'border-teal-600 text-teal-600 dark:border-teal-400 dark:text-teal-400' : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:border-gray-600 dark:hover:border-gray-400'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                Roles (<span x-text="roles.length"></span>)
            </button>
            <button @click="activeTab = 'permissions'" 
                    :class="activeTab === 'permissions' ? 'border-teal-600 text-teal-600 dark:border-teal-400 dark:text-teal-400' : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:border-gray-600 dark:hover:border-gray-400'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                Permissions (<span x-text="permissions.length"></span>)
            </button>
            <button @click="activeTab = 'users'" 
                    :class="activeTab === 'users' ? 'border-teal-600 text-teal-600 dark:border-teal-400 dark:text-teal-400' : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hover:border-gray-600 dark:hover:border-gray-400'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                User Assignments
            </button>
        </nav>
    </div>

    <!-- Roles Tab -->
    <div x-show="activeTab === 'roles'" class="py-6">
        <div class="space-y-4">
            <template x-for="role in roles" :key="role.id">
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm">
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-lg font-semibold" x-text="role.name"></h3>
                                    <span x-show="role.is_system" class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20">
                                        System
                                    </span>
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 border-transparent bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-500" x-text="(role.permissions?.length || 0) + ' permissions'"></span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400" x-text="role.description || 'No description'"></p>
                                
                                <!-- Permission Badges -->
                                <div class="flex flex-wrap gap-1 mt-3" x-show="role.permissions?.length > 0">
                                    <template x-for="permission in role.permissions" :key="permission.slug">
                                        <span class="inline-flex items-center rounded-md bg-teal-100 dark:bg-teal-900 px-2 py-1 text-xs font-medium text-teal-800 dark:text-teal-200 ring-1 ring-inset ring-teal-200 dark:ring-teal-700" x-text="permission.slug"></span>
                                    </template>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <a :href="`/admin/roles/${role.id}/edit`" class="inline-flex items-center justify-center rounded-md text-sm font-medium  transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white h-9 px-3">
                                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Edit
                                </a>
                                <button x-show="!role.is_system" @click="deleteRole(role)" class="inline-flex items-center justify-center rounded-md text-sm font-medium  transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 hover:bg-red-600 hover:text-white dark:hover:bg-red-600 h-9 px-3">
                                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            
            <div x-show="roles.length === 0" class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M34 40h10v-4a6 6 0 00-10.712-3.714M34 40H14m20 0v-4a9.971 9.971 0 00-.712-3.714M14 40H4v-4a6 6 0 0110.713-3.714M14 40v-4c0-1.313.253-2.566.713-3.714m0 0A9.971 9.971 0 0124 30c4.21 0 7.813 2.602 9.288 6.286"></path>
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">No roles</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Get started by creating a new role.</p>
            </div>
        </div>
    </div>

    <!-- Permissions Tab -->
    <div x-show="activeTab === 'permissions'" class="py-6">
        <div class="space-y-6">
            <template x-for="category in permissionCategories" :key="category">
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 capitalize" x-text="category + ' Permissions'"></h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <template x-for="permission in getPermissionsByCategory(category)" :key="permission.id">
                                <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-4 space-y-2">
                                    <div class="flex items-start justify-between">
                                        <div class="space-y-1">
                                            <h4 class="text-sm font-medium" x-text="permission.name"></h4>
                                            <p class="text-xs font-mono text-gray-600 dark:text-gray-400" x-text="permission.slug"></p>
                                            <p class="text-xs text-gray-600 dark:text-gray-400" x-text="permission.description"></p>
                                        </div>
                                        <span x-show="permission.is_system" class="inline-flex items-center rounded-full border px-1.5 py-0.5 text-xs font-semibold border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20">
                                            System
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Users Tab -->
    <div x-show="activeTab === 'users'" class="py-6">
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">User Role Assignments</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Manage role assignments for central SSO users.</p>
                
                <div class="space-y-4">
                    <template x-for="user in users" :key="user.id">
                        <div class="flex items-center justify-between py-4 border-b border-gray-200 dark:border-gray-700 last:border-0">
                            <div class="space-y-1">
                                <h4 class="text-sm font-medium" x-text="user.name"></h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400" x-text="user.email"></p>
                                <div class="flex flex-wrap gap-1" x-show="user.roles?.length > 0">
                                    <template x-for="userRole in user.roles" :key="userRole.role.id + (userRole.tenant_id || 'global')">
                                        <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                            <span x-text="userRole.role.name"></span>
                                            <span x-show="userRole.tenant_id" x-text="' (Tenant ' + userRole.tenant_id + ')'"></span>
                                            <span x-show="!userRole.tenant_id"> (Global)</span>
                                        </span>
                                    </template>
                                </div>
                            </div>
                            <button @click="manageUserRoles(user)" class="inline-flex items-center justify-center rounded-md text-sm font-medium  transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white h-9 px-3">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                Manage Roles
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>


    <!-- User Roles Modal -->
    <div x-show="showUserRolesModal" x-cloak class="fixed inset-0 z-50 bg-black/50 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" x-transition>
        <div class="fixed left-[50%] top-[50%] z-50 grid w-full max-w-2xl translate-x-[-50%] translate-y-[-50%] gap-4 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-lg duration-200 sm:rounded-lg">
            <div class="space-y-4">
                <div class="space-y-2">
                    <h2 class="text-lg font-semibold leading-none tracking-tight">Manage User Roles</h2>
                    <div x-show="selectedUser">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            User: <span class="font-medium" x-text="selectedUser?.name + ' (' + selectedUser?.email + ')'"></span>
                        </p>
                    </div>
                </div>

                <!-- Add Role Form -->
                <div class="border rounded-lg p-4 space-y-4">
                    <h3 class="text-md font-medium">Assign New Role</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Role</label>
                            <select x-model="newRoleAssignment.roleId" class="flex h-10 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm  focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2">
                                <option value="">Select a role...</option>
                                <template x-for="role in roles" :key="role.id">
                                    <option :value="role.id" x-text="role.name"></option>
                                </template>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Scope</label>
                            <select x-model="newRoleAssignment.tenantId" class="flex h-10 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm  focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2">
                                <option value="">Global (all tenants)</option>
                                <template x-for="tenant in tenants" :key="tenant.id">
                                    <option :value="tenant.id" x-text="tenant.name + ' (' + tenant.slug + ')'"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                    <button @click="assignRoleToUser()" 
                            :disabled="!newRoleAssignment.roleId"
                            class="inline-flex items-center justify-center rounded-md text-sm font-medium  transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-teal-600 dark:bg-teal-500 text-white hover:bg-teal-700 dark:hover:bg-teal-600 h-9 px-4">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Assign Role
                    </button>
                </div>

                <!-- Current Roles -->
                <div class="space-y-3">
                    <h3 class="text-md font-medium">Current Role Assignments</h3>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        <template x-for="userRole in selectedUser?.roles || []" :key="userRole.role.id + (userRole.tenant_id || 'global')">
                            <div class="flex items-center justify-between py-2 px-3 border rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <span class="text-sm font-medium" x-text="userRole.role.name"></span>
                                    <span x-show="userRole.role.is_system" class="inline-flex items-center rounded-full border px-1.5 py-0.5 text-xs font-semibold border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20">
                                        System
                                    </span>
                                    <span class="inline-flex items-center rounded-md bg-teal-100 dark:bg-teal-900 px-2 py-1 text-xs font-medium text-teal-800 dark:text-teal-200 ring-1 ring-inset ring-teal-200 dark:ring-teal-700">
                                        <span x-show="userRole.tenant_id" x-text="getTenantName(userRole.tenant_id)"></span>
                                        <span x-show="!userRole.tenant_id">Global</span>
                                    </span>
                                </div>
                                <button @click="removeRoleFromUser(userRole)" 
                                        class="inline-flex items-center justify-center rounded-md text-xs font-medium  transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 bg-red-600 text-white hover:bg-red-700 dark:bg-red-600 dark:hover:bg-red-700 h-8 px-3">
                                    Remove
                                </button>
                            </div>
                        </template>
                        <div x-show="!selectedUser?.roles?.length" class="text-center py-4 text-sm text-gray-600 dark:text-gray-400">
                            No roles assigned
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button @click="showUserRolesModal = false" class="inline-flex items-center justify-center rounded-md text-sm font-medium  transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white h-10 px-4 py-2">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function roleManagement() {
    return {
        activeTab: 'roles',
        roles: [],
        permissions: [],
        users: [],
        tenants: [],
        showUserRolesModal: false,
        selectedUser: null,
        newRoleAssignment: {
            roleId: '',
            tenantId: ''
        },
        
        get permissionCategories() {
            return [...new Set(this.permissions.map(p => p.category))].filter(Boolean).sort();
        },
        
        initializeData() {
            this.roles = @json($roles ?? []);
            this.permissions = @json($permissions ?? []);
            this.users = @json($users ?? []);
            this.tenants = @json($tenants ?? []);
        },
        
        async loadData() {
            await Promise.all([
                this.loadRoles(),
                this.loadPermissions(),
                this.loadUsers()
            ]);
        },
        
        async loadRoles() {
            try {
                const response = await fetch('/admin/roles/data', {
                    headers: { 
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': await this.getToken()
                    },
                    credentials: 'same-origin'
                });
                const data = await response.json();
                this.roles = data.data || [];
            } catch (error) {
                console.error('Error loading roles:', error);
                this.roles = [];
            }
        },
        
        async loadPermissions() {
            try {
                const response = await fetch('/admin/permissions/data', {
                    headers: { 
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': await this.getToken()
                    },
                    credentials: 'same-origin'
                });
                const data = await response.json();
                this.permissions = data.data || [];
            } catch (error) {
                console.error('Error loading permissions:', error);
                this.permissions = [];
            }
        },
        
        async loadUsers() {
            try {
                const response = await fetch('/admin/users/data', {
                    headers: { 
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': await this.getToken()
                    },
                    credentials: 'same-origin'
                });
                const data = await response.json();
                this.users = data.data || [];
            } catch (error) {
                console.error('Error loading users:', error);
                this.users = [];
            }
        },
        
        getPermissionsByCategory(category) {
            return this.permissions.filter(p => p.category === category);
        },
        
        
        async deleteRole(role) {
            // Use a more elegant confirmation
            const confirmed = await this.confirmAction(`Are you sure you want to delete the role "${role.name}"?`, 'This action cannot be undone.');
            if (!confirmed) return;
            
            try {
                const response = await fetch(`/admin/roles/${role.id}`, {
                    method: 'DELETE',
                    headers: { 
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': await this.getToken()
                    },
                    credentials: 'same-origin'
                });
                
                if (response.ok) {
                    await this.loadRoles();
                    this.showToast('Role deleted successfully!', 'success');
                } else {
                    const error = await response.json();
                    this.showToast('Error: ' + (error.message || 'Failed to delete role'), 'error');
                }
            } catch (error) {
                console.error('Error deleting role:', error);
                this.showToast('Error deleting role', 'error');
            }
        },
        
        manageUserRoles(user) {
            this.selectedUser = user;
            this.newRoleAssignment = { roleId: '', tenantId: '' };
            this.showUserRolesModal = true;
        },
        
        userHasRole(role) {
            return this.selectedUser?.roles?.some(ur => ur.role.id === role.id);
        },

        getTenantName(tenantId) {
            const tenant = this.tenants.find(t => t.id === tenantId);
            return tenant ? tenant.name : tenantId;
        },

        async assignRoleToUser() {
            if (!this.selectedUser || !this.newRoleAssignment.roleId) return;
            
            const role = this.roles.find(r => r.id == this.newRoleAssignment.roleId);
            if (!role) return;

            try {
                const response = await fetch(`/admin/users/${this.selectedUser.id}/roles`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': await this.getToken()
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ 
                        role_slug: role.slug,
                        tenant_id: this.newRoleAssignment.tenantId || null
                    })
                });
                
                if (response.ok) {
                    await this.loadUsers();
                    this.selectedUser = this.users.find(u => u.id === this.selectedUser.id);
                    this.newRoleAssignment = { roleId: '', tenantId: '' };
                    this.showToast('Role assigned successfully!', 'success');
                } else {
                    const error = await response.json();
                    this.showToast('Error: ' + (error.message || 'Failed to assign role'), 'error');
                }
            } catch (error) {
                console.error('Error assigning role:', error);
                this.showToast('Error assigning role', 'error');
            }
        },

        async removeRoleFromUser(userRole) {
            if (!this.selectedUser) return;
            
            try {
                const response = await fetch(`/admin/users/${this.selectedUser.id}/roles`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': await this.getToken()
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ 
                        role_slug: userRole.role.slug,
                        tenant_id: userRole.tenant_id
                    })
                });
                
                if (response.ok) {
                    await this.loadUsers();
                    this.selectedUser = this.users.find(u => u.id === this.selectedUser.id);
                    this.showToast('Role removed successfully!', 'success');
                } else {
                    const error = await response.json();
                    this.showToast('Error: ' + (error.message || 'Failed to remove role'), 'error');
                }
            } catch (error) {
                console.error('Error removing role:', error);
                this.showToast('Error removing role', 'error');
            }
        },
        
        
        async getToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        },

        showToast(message, type = 'info') {
            // Use the global toast system
            if (window.showToast) {
                window.showToast(message, type);
            } else {
                // Fallback for demo
                console.log(`${type.toUpperCase()}: ${message}`);
                alert(message);
            }
        },

        confirmAction(title, message = '') {
            return new Promise((resolve) => {
                // Use browser confirm as fallback - could be enhanced with a custom modal
                const result = confirm(title + (message ? '\n\n' + message : ''));
                resolve(result);
            });
        }
    };
}

</script>
@endsection
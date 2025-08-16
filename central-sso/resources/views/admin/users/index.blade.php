@extends('layouts.admin')

@section('title', 'User Management')

@section('header')
    <div>
        <h1 class="text-2xl font-semibold text-card-foreground">User Management</h1>
        <p class="text-sm text-muted-foreground mt-1">
            Manage central SSO users and their tenant access
        </p>
    </div>
@endsection

@section('actions')
    <button onclick="window.dispatchEvent(new CustomEvent('show-create-user'))" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        New User
    </button>
@endsection

@section('content')
<div x-data="userManagement()" x-init="initializeData()">
    <!-- Users List -->
    <div class="space-y-4">
        <template x-for="user in users" :key="user.id">
            <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="space-y-3 flex-1">
                            <div class="flex items-center gap-3">
                                <div class="h-10 w-10 rounded-full bg-muted flex items-center justify-center">
                                    <span class="text-sm font-medium text-muted-foreground" x-text="user.name.charAt(0).toUpperCase()"></span>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold" x-text="user.name"></h3>
                                    <p class="text-sm text-muted-foreground" x-text="user.email"></p>
                                </div>
                                <span x-show="user.is_admin" class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-destructive/10 text-destructive">
                                    Admin
                                </span>
                            </div>
                            
                            <!-- Tenant Access -->
                            <div x-show="user.tenants?.length > 0">
                                <p class="text-sm font-medium text-card-foreground mb-2">Tenant Access:</p>
                                <div class="flex flex-wrap gap-1">
                                    <template x-for="tenant in user.tenants" :key="tenant.id">
                                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                            <span x-text="tenant.name + ' (' + tenant.slug + ')'"></span>
                                        </span>
                                    </template>
                                </div>
                            </div>

                            <!-- Roles -->
                            <div x-show="user.roles?.length > 0">
                                <p class="text-sm font-medium text-card-foreground mb-2">Roles:</p>
                                <div class="flex flex-wrap gap-1">
                                    <template x-for="userRole in user.roles" :key="userRole.role.id + (userRole.tenant_id || 'global')">
                                        <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">
                                            <span x-text="userRole.role.name"></span>
                                            <span x-show="userRole.tenant_id" x-text="' (Tenant ' + userRole.tenant_id + ')'"></span>
                                            <span x-show="!userRole.tenant_id"> (Global)</span>
                                        </span>
                                    </template>
                                </div>
                            </div>
                            
                            <p class="text-xs text-muted-foreground">
                                Created: <span x-text="user.created_at"></span>
                            </p>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <button @click="editUser(user)" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Edit
                            </button>
                            <button @click="manageTenants(user)" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m14 0v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5"></path>
                                </svg>
                                Tenants
                            </button>
                            <button x-show="user.id !== currentUserId" @click="deleteUser(user)" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-destructive/20 bg-destructive/10 text-destructive hover:bg-destructive hover:text-destructive-foreground h-9 px-3">
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
        
        <div x-show="users.length === 0" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-semibold text-foreground">No users</h3>
            <p class="mt-1 text-sm text-muted-foreground">Get started by creating a new user.</p>
        </div>
    </div>

    <!-- Create/Edit User Modal -->
    <div x-show="showUserModal" class="fixed inset-0 z-50 bg-black/50 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" x-transition>
        <div class="fixed left-[50%] top-[50%] z-50 grid w-full max-w-lg translate-x-[-50%] translate-y-[-50%] gap-4 border border-border bg-card p-6 shadow-lg duration-200 sm:rounded-lg">
            <form @submit.prevent="saveUser()">
                <div class="space-y-4">
                    <div class="space-y-2">
                        <h2 class="text-lg font-semibold leading-none tracking-tight" x-text="editingUser ? 'Edit User' : 'Create New User'"></h2>
                        <p class="text-sm text-muted-foreground">Configure user settings and tenant access.</p>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Name</label>
                            <input type="text" x-model="userForm.name" required 
                                   class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                   placeholder="Enter full name">
                        </div>
                        
                        <div class="space-y-2">
                            <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Email</label>
                            <input type="email" x-model="userForm.email" required 
                                   class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                   placeholder="Enter email address">
                        </div>
                        
                        <div class="space-y-2">
                            <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                Password <span x-show="editingUser" class="text-muted-foreground">(leave blank to keep current)</span>
                            </label>
                            <input type="password" x-model="userForm.password" :required="!editingUser"
                                   class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                   placeholder="Enter password">
                        </div>
                        
                        <div class="space-y-2">
                            <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Confirm Password</label>
                            <input type="password" x-model="userForm.password_confirmation" :required="!editingUser || userForm.password"
                                   class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                   placeholder="Confirm password">
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <input type="checkbox" x-model="userForm.is_admin" id="is_admin"
                                   class="peer h-4 w-4 shrink-0 rounded-sm border border-primary ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground">
                            <label for="is_admin" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Admin User</label>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Tenant Access</label>
                            <div class="space-y-2 max-h-32 overflow-y-auto rounded-md border border-input p-3">
                                <template x-for="tenant in tenants" :key="tenant.id">
                                    <div class="flex items-center space-x-2">
                                        <input type="checkbox" :value="tenant.id" x-model="userForm.tenant_ids" :id="'tenant-' + tenant.id"
                                               class="peer h-4 w-4 shrink-0 rounded-sm border border-primary ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground">
                                        <label :for="'tenant-' + tenant.id" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                            <span x-text="tenant.name"></span>
                                            <span class="text-muted-foreground" x-text="'(' + tenant.slug + ')'"></span>
                                        </label>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-2">
                        <button type="button" @click="showUserModal = false" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
                            Cancel
                        </button>
                        <button type="submit" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
                            <span x-text="editingUser ? 'Update' : 'Create'"></span> User
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tenant Management Modal -->
    <div x-show="showTenantModal" class="fixed inset-0 z-50 bg-black/50 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" x-transition>
        <div class="fixed left-[50%] top-[50%] z-50 grid w-full max-w-2xl translate-x-[-50%] translate-y-[-50%] gap-4 border border-border bg-card p-6 shadow-lg duration-200 sm:rounded-lg">
            <div class="space-y-4">
                <div class="space-y-2">
                    <h2 class="text-lg font-semibold leading-none tracking-tight">Manage Tenant Access</h2>
                    <div x-show="selectedUser">
                        <p class="text-sm text-muted-foreground">
                            User: <span class="font-medium" x-text="selectedUser?.name + ' (' + selectedUser?.email + ')'"></span>
                        </p>
                    </div>
                </div>

                <!-- Current Tenant Access -->
                <div class="space-y-3">
                    <h3 class="text-md font-medium">Current Tenant Access</h3>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        <template x-for="tenant in selectedUser?.tenants || []" :key="tenant.id">
                            <div class="flex items-center justify-between py-2 px-3 border rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <span class="text-sm font-medium" x-text="tenant.name"></span>
                                    <span class="text-xs text-muted-foreground" x-text="'(' + tenant.slug + ')'"></span>
                                </div>
                                <button @click="removeTenantAccess(tenant.id)" 
                                        class="inline-flex items-center justify-center rounded-md text-xs font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 bg-destructive text-destructive-foreground hover:bg-destructive/80 h-8 px-3">
                                    Remove
                                </button>
                            </div>
                        </template>
                        <div x-show="!selectedUser?.tenants?.length" class="text-center py-4 text-sm text-muted-foreground">
                            No tenant access granted
                        </div>
                    </div>
                </div>

                <!-- Add Tenant Access -->
                <div class="border rounded-lg p-4 space-y-4">
                    <h3 class="text-md font-medium">Grant Tenant Access</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium">Tenant</label>
                            <select x-model="newTenantAccess.tenantId" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2">
                                <option value="">Select a tenant...</option>
                                <template x-for="tenant in availableTenants" :key="tenant.id">
                                    <option :value="tenant.id" x-text="tenant.name + ' (' + tenant.slug + ')'"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                    <button @click="assignTenantAccess()" 
                            :disabled="!newTenantAccess.tenantId"
                            class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-9 px-4">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Grant Access
                    </button>
                </div>
                
                <div class="flex justify-end">
                    <button @click="showTenantModal = false" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function userManagement() {
    return {
        users: [],
        tenants: [],
        showUserModal: false,
        showTenantModal: false,
        editingUser: null,
        selectedUser: null,
        currentUserId: {{ auth()->id() }},
        userForm: {
            name: '',
            email: '',
            password: '',
            password_confirmation: '',
            is_admin: false,
            tenant_ids: []
        },
        newTenantAccess: {
            tenantId: ''
        },
        
        get availableTenants() {
            if (!this.selectedUser) return this.tenants;
            const userTenantIds = this.selectedUser.tenants?.map(t => t.id) || [];
            return this.tenants.filter(t => !userTenantIds.includes(t.id));
        },
        
        initializeData() {
            this.users = @json($users ?? []);
            this.tenants = @json($tenants ?? []);
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
        
        showCreateUserModal() {
            this.editingUser = null;
            this.userForm = {
                name: '',
                email: '',
                password: '',
                password_confirmation: '',
                is_admin: false,
                tenant_ids: []
            };
            this.showUserModal = true;
        },
        
        editUser(user) {
            this.editingUser = user;
            this.userForm = {
                name: user.name,
                email: user.email,
                password: '',
                password_confirmation: '',
                is_admin: user.is_admin,
                tenant_ids: user.tenants?.map(t => t.id) || []
            };
            this.showUserModal = true;
        },
        
        async saveUser() {
            try {
                const url = this.editingUser ? `/admin/users/${this.editingUser.id}` : '/admin/users';
                const method = this.editingUser ? 'PUT' : 'POST';
                
                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': await this.getToken()
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(this.userForm)
                });
                
                if (response.ok) {
                    this.showUserModal = false;
                    await this.loadUsers();
                    this.showToast(this.editingUser ? 'User updated successfully!' : 'User created successfully!', 'success');
                } else {
                    const error = await response.json();
                    this.showToast('Error: ' + (error.message || 'Failed to save user'), 'error');
                }
            } catch (error) {
                console.error('Error saving user:', error);
                this.showToast('Error saving user', 'error');
            }
        },
        
        async deleteUser(user) {
            const confirmed = await this.confirmAction(`Are you sure you want to delete "${user.name}"?`, 'This action cannot be undone.');
            if (!confirmed) return;
            
            try {
                const response = await fetch(`/admin/users/${user.id}`, {
                    method: 'DELETE',
                    headers: { 
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': await this.getToken()
                    },
                    credentials: 'same-origin'
                });
                
                if (response.ok) {
                    await this.loadUsers();
                    this.showToast('User deleted successfully!', 'success');
                } else {
                    const error = await response.json();
                    this.showToast('Error: ' + (error.message || 'Failed to delete user'), 'error');
                }
            } catch (error) {
                console.error('Error deleting user:', error);
                this.showToast('Error deleting user', 'error');
            }
        },
        
        manageTenants(user) {
            this.selectedUser = user;
            this.newTenantAccess = { tenantId: '' };
            this.showTenantModal = true;
        },

        async assignTenantAccess() {
            if (!this.selectedUser || !this.newTenantAccess.tenantId) return;

            try {
                const response = await fetch(`/admin/users/${this.selectedUser.id}/tenants`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': await this.getToken()
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ 
                        tenant_id: this.newTenantAccess.tenantId
                    })
                });
                
                if (response.ok) {
                    await this.loadUsers();
                    this.selectedUser = this.users.find(u => u.id === this.selectedUser.id);
                    this.newTenantAccess = { tenantId: '' };
                    this.showToast('Tenant access granted successfully!', 'success');
                } else {
                    const error = await response.json();
                    this.showToast('Error: ' + (error.message || 'Failed to grant tenant access'), 'error');
                }
            } catch (error) {
                console.error('Error granting tenant access:', error);
                this.showToast('Error granting tenant access', 'error');
            }
        },

        async removeTenantAccess(tenantId) {
            if (!this.selectedUser) return;
            
            try {
                const response = await fetch(`/admin/users/${this.selectedUser.id}/tenants`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': await this.getToken()
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ 
                        tenant_id: tenantId
                    })
                });
                
                if (response.ok) {
                    await this.loadUsers();
                    this.selectedUser = this.users.find(u => u.id === this.selectedUser.id);
                    this.showToast('Tenant access removed successfully!', 'success');
                } else {
                    const error = await response.json();
                    this.showToast('Error: ' + (error.message || 'Failed to remove tenant access'), 'error');
                }
            } catch (error) {
                console.error('Error removing tenant access:', error);
                this.showToast('Error removing tenant access', 'error');
            }
        },
        
        async getToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        },

        showToast(message, type = 'info') {
            if (window.showToast) {
                window.showToast(message, type);
            } else {
                console.log(`${type.toUpperCase()}: ${message}`);
                alert(message);
            }
        },

        confirmAction(title, message = '') {
            return new Promise((resolve) => {
                const result = confirm(title + (message ? '\n\n' + message : ''));
                resolve(result);
            });
        }
    };
}

// Global function for the create button
window.addEventListener('show-create-user', function() {
    const component = document.querySelector('[x-data*="userManagement"]');
    if (component && component._x_dataStack && component._x_dataStack[0]) {
        component._x_dataStack[0].showCreateUserModal();
    }
});
</script>
@endsection
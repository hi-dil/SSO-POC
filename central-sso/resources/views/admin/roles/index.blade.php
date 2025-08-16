@extends('layouts.admin')

@section('title', 'Roles & Permissions')

@section('header')
    <div>
        <h1 class="text-2xl font-semibold text-card-foreground">Roles & Permissions</h1>
        <p class="text-sm text-muted-foreground mt-1">
            Manage authentication roles and permissions for the central SSO system
        </p>
    </div>
@endsection

@section('actions')
    <button onclick="window.dispatchEvent(new CustomEvent('show-create-role'))" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        New Role
    </button>
@endsection

@section('content')
<div x-data="roleManagement()" x-init="initializeData()">
    <!-- Tabs -->
    <div class="border-b border-border">
        <nav class="-mb-px flex space-x-8">
            <button @click="activeTab = 'roles'" 
                    :class="activeTab === 'roles' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground hover:border-muted-foreground'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                Roles (<span x-text="roles.length"></span>)
            </button>
            <button @click="activeTab = 'permissions'" 
                    :class="activeTab === 'permissions' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground hover:border-muted-foreground'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                Permissions (<span x-text="permissions.length"></span>)
            </button>
            <button @click="activeTab = 'users'" 
                    :class="activeTab === 'users' ? 'border-primary text-primary' : 'border-transparent text-muted-foreground hover:text-foreground hover:border-muted-foreground'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                User Assignments
            </button>
        </nav>
    </div>

    <!-- Roles Tab -->
    <div x-show="activeTab === 'roles'" class="py-6">
        <div class="space-y-4">
            <template x-for="role in roles" :key="role.id">
                <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-lg font-semibold" x-text="role.name"></h3>
                                    <span x-show="role.is_system" class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-destructive/10 text-destructive">
                                        System
                                    </span>
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80" x-text="(role.permissions?.length || 0) + ' permissions'"></span>
                                </div>
                                <p class="text-sm text-muted-foreground" x-text="role.description || 'No description'"></p>
                                
                                <!-- Permission Badges -->
                                <div class="flex flex-wrap gap-1 mt-3" x-show="role.permissions?.length > 0">
                                    <template x-for="permission in role.permissions" :key="permission.slug">
                                        <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10" x-text="permission.slug"></span>
                                    </template>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <button @click="editRole(role)" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3">
                                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Edit
                                </button>
                                <button x-show="!role.is_system" @click="deleteRole(role)" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-destructive/20 bg-destructive/10 text-destructive hover:bg-destructive hover:text-destructive-foreground h-9 px-3">
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
                <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 48 48">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M34 40h10v-4a6 6 0 00-10.712-3.714M34 40H14m20 0v-4a9.971 9.971 0 00-.712-3.714M14 40H4v-4a6 6 0 0110.713-3.714M14 40v-4c0-1.313.253-2.566.713-3.714m0 0A9.971 9.971 0 0124 30c4.21 0 7.813 2.602 9.288 6.286"></path>
                </svg>
                <h3 class="mt-2 text-sm font-semibold text-foreground">No roles</h3>
                <p class="mt-1 text-sm text-muted-foreground">Get started by creating a new role.</p>
            </div>
        </div>
    </div>

    <!-- Permissions Tab -->
    <div x-show="activeTab === 'permissions'" class="py-6">
        <div class="space-y-6">
            <template x-for="category in permissionCategories" :key="category">
                <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold mb-4 capitalize" x-text="category + ' Permissions'"></h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <template x-for="permission in getPermissionsByCategory(category)" :key="permission.id">
                                <div class="rounded-lg border border-border p-4 space-y-2">
                                    <div class="flex items-start justify-between">
                                        <div class="space-y-1">
                                            <h4 class="text-sm font-medium" x-text="permission.name"></h4>
                                            <p class="text-xs font-mono text-muted-foreground" x-text="permission.slug"></p>
                                            <p class="text-xs text-muted-foreground" x-text="permission.description"></p>
                                        </div>
                                        <span x-show="permission.is_system" class="inline-flex items-center rounded-full border px-1.5 py-0.5 text-xs font-semibold border-destructive/10 text-destructive">
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
        <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">User Role Assignments</h3>
                <p class="text-sm text-muted-foreground mb-6">Manage role assignments for central SSO users.</p>
                
                <div class="space-y-4">
                    <template x-for="user in users" :key="user.id">
                        <div class="flex items-center justify-between py-4 border-b border-border last:border-0">
                            <div class="space-y-1">
                                <h4 class="text-sm font-medium" x-text="user.name"></h4>
                                <p class="text-sm text-muted-foreground" x-text="user.email"></p>
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
                            <button @click="manageUserRoles(user)" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                                Manage Roles
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit Role Modal -->
    <div x-show="showRoleModal" class="fixed inset-0 z-50 bg-black/50 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" x-transition>
        <div class="fixed left-[50%] top-[50%] z-50 grid w-full max-w-lg translate-x-[-50%] translate-y-[-50%] gap-4 border border-border bg-card p-6 shadow-lg duration-200 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[state=closed]:slide-out-to-left-1/2 data-[state=closed]:slide-out-to-top-[48%] data-[state=open]:slide-in-from-left-1/2 data-[state=open]:slide-in-from-top-[48%] sm:rounded-lg">
            <form @submit.prevent="saveRole()">
                <div class="space-y-4">
                    <div class="space-y-2">
                        <h2 class="text-lg font-semibold leading-none tracking-tight" x-text="editingRole ? 'Edit Role' : 'Create New Role'"></h2>
                        <p class="text-sm text-muted-foreground">Configure role settings and permissions.</p>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Name</label>
                            <input type="text" x-model="roleForm.name" required 
                                   class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                   placeholder="Enter role name">
                        </div>
                        
                        <div class="space-y-2">
                            <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Description</label>
                            <textarea x-model="roleForm.description" rows="3"
                                      class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                      placeholder="Enter role description"></textarea>
                        </div>
                        
                        <div class="space-y-2">
                            <label class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Permissions</label>
                            <div class="space-y-3 max-h-64 overflow-y-auto rounded-md border border-input p-3">
                                <template x-for="category in permissionCategories" :key="category">
                                    <div>
                                        <h4 class="text-sm font-medium capitalize mb-2" x-text="category"></h4>
                                        <div class="space-y-2 ml-4">
                                            <template x-for="permission in getPermissionsByCategory(category)" :key="permission.slug">
                                                <div class="flex items-center space-x-2">
                                                    <input type="checkbox" :value="permission.slug" x-model="roleForm.permissions" :id="'perm-' + permission.slug"
                                                           class="peer h-4 w-4 shrink-0 rounded-sm border border-primary ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground">
                                                    <label :for="'perm-' + permission.slug" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70" x-text="permission.name"></label>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-2">
                        <button type="button" @click="showRoleModal = false" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
                            Cancel
                        </button>
                        <button type="submit" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
                            <span x-text="editingRole ? 'Update' : 'Create'"></span> Role
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- User Roles Modal -->
    <div x-show="showUserRolesModal" class="fixed inset-0 z-50 bg-black/50 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0" x-transition>
        <div class="fixed left-[50%] top-[50%] z-50 grid w-full max-w-md translate-x-[-50%] translate-y-[-50%] gap-4 border border-border bg-card p-6 shadow-lg duration-200 sm:rounded-lg">
            <div class="space-y-4">
                <div class="space-y-2">
                    <h2 class="text-lg font-semibold leading-none tracking-tight">Manage User Roles</h2>
                    <div x-show="selectedUser">
                        <p class="text-sm text-muted-foreground">
                            User: <span class="font-medium" x-text="selectedUser?.name + ' (' + selectedUser?.email + ')'"></span>
                        </p>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <template x-for="role in roles" :key="role.id">
                        <div class="flex items-center justify-between py-2 border-b border-border last:border-0">
                            <div class="flex items-center space-x-2">
                                <span class="text-sm font-medium" x-text="role.name"></span>
                                <span x-show="role.is_system" class="inline-flex items-center rounded-full border px-1.5 py-0.5 text-xs font-semibold border-destructive/10 text-destructive">
                                    System
                                </span>
                            </div>
                            <button @click="toggleUserRole(role)" 
                                    :class="userHasRole(role) ? 'bg-destructive text-destructive-foreground hover:bg-destructive/80' : 'bg-primary text-primary-foreground hover:bg-primary/80'"
                                    class="inline-flex items-center justify-center rounded-md text-xs font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 h-8 px-3">
                                <span x-text="userHasRole(role) ? 'Remove' : 'Assign'"></span>
                            </button>
                        </div>
                    </template>
                </div>
                
                <div class="flex justify-end">
                    <button @click="showUserRolesModal = false" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
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
        showRoleModal: false,
        showUserRolesModal: false,
        editingRole: null,
        selectedUser: null,
        roleForm: {
            name: '',
            description: '',
            permissions: []
        },
        
        get permissionCategories() {
            return [...new Set(this.permissions.map(p => p.category))].filter(Boolean).sort();
        },
        
        initializeData() {
            this.roles = @json($roles ?? []);
            this.permissions = @json($permissions ?? []);
            this.users = @json($users ?? []);
            
            // Show a welcome toast
            setTimeout(() => {
                this.showToast('Role management interface loaded successfully', 'success');
            }, 500);
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
        
        showCreateRoleModal() {
            this.editingRole = null;
            this.roleForm = { name: '', description: '', permissions: [] };
            this.showRoleModal = true;
        },
        
        editRole(role) {
            this.editingRole = role;
            this.roleForm = {
                name: role.name,
                description: role.description || '',
                permissions: role.permissions?.map(p => p.slug) || []
            };
            this.showRoleModal = true;
        },
        
        async saveRole() {
            try {
                const url = this.editingRole ? `/api/roles/${this.editingRole.id}` : '/api/roles';
                const method = this.editingRole ? 'PUT' : 'POST';
                
                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': await this.getToken()
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(this.roleForm)
                });
                
                if (response.ok) {
                    this.showRoleModal = false;
                    await this.loadRoles();
                    this.showToast(this.editingRole ? 'Role updated successfully!' : 'Role created successfully!', 'success');
                } else {
                    const error = await response.json();
                    this.showToast('Error: ' + (error.message || 'Failed to save role'), 'error');
                }
            } catch (error) {
                console.error('Error saving role:', error);
                this.showToast('Error saving role', 'error');
            }
        },
        
        async deleteRole(role) {
            // Use a more elegant confirmation
            const confirmed = await this.confirmAction(`Are you sure you want to delete the role "${role.name}"?`, 'This action cannot be undone.');
            if (!confirmed) return;
            
            try {
                const response = await fetch(`/api/roles/${role.id}`, {
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
            this.showUserRolesModal = true;
        },
        
        userHasRole(role) {
            return this.selectedUser?.roles?.some(ur => ur.role.id === role.id);
        },
        
        async toggleUserRole(role) {
            if (!this.selectedUser) return;
            
            const hasRole = this.userHasRole(role);
            const action = hasRole ? 'DELETE' : 'POST';
            
            try {
                const response = await fetch(`/api/users/${this.selectedUser.id}/roles`, {
                    method: action,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': await this.getToken()
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ role_slug: role.slug })
                });
                
                if (response.ok) {
                    await this.loadUsers();
                    this.selectedUser = this.users.find(u => u.id === this.selectedUser.id);
                    this.showToast(hasRole ? 'Role removed successfully!' : 'Role assigned successfully!', 'success');
                } else {
                    const error = await response.json();
                    this.showToast('Error: ' + (error.message || 'Failed to update role'), 'error');
                }
            } catch (error) {
                console.error('Error toggling role:', error);
                this.showToast('Error updating role', 'error');
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

// Global function for the create button
window.addEventListener('show-create-role', function() {
    // Trigger Alpine.js event to show modal
    document.querySelector('[x-data]').__x.$data.showCreateRoleModal();
});
</script>
@endsection
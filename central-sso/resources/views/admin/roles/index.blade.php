@extends('layouts.admin')

@section('title', 'Roles & Permissions')

@section('header')
    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
        Roles & Permissions Management
    </h2>
    <p class="mt-1 text-sm text-gray-500">
        Manage central SSO authentication roles and permissions. These roles control access to the central SSO system only.
    </p>
@endsection

@section('actions')
    <button onclick="showCreateRoleModal()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Create Role
    </button>
@endsection

@section('content')
<div x-data="roleManagement()" x-init="initializeData()">
    <!-- Tabs -->
    <div class="border-b border-gray-200">
        <nav class="-mb-px flex space-x-8 px-6 pt-6">
            <button @click="activeTab = 'roles'" 
                    :class="activeTab === 'roles' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                Roles (<span x-text="roles.length"></span>)
            </button>
            <button @click="activeTab = 'permissions'" 
                    :class="activeTab === 'permissions' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                Permissions (<span x-text="permissions.length"></span>)
            </button>
            <button @click="activeTab = 'users'" 
                    :class="activeTab === 'users' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                User Assignments
            </button>
        </nav>
    </div>

    <!-- Roles Tab -->
    <div x-show="activeTab === 'roles'" class="p-6">
        <div class="space-y-6">
            <!-- Roles List -->
            <template x-for="role in roles" :key="role.id">
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3">
                                <h3 class="text-lg font-medium text-gray-900" x-text="role.name"></h3>
                                <span x-show="role.is_system" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    System Role
                                </span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800" x-text="role.permissions?.length + ' permissions'"></span>
                            </div>
                            <p class="mt-1 text-sm text-gray-500" x-text="role.description || 'No description'"></p>
                            
                            <!-- Permission Tags -->
                            <div class="mt-2 flex flex-wrap gap-1" x-show="role.permissions?.length > 0">
                                <template x-for="permission in role.permissions" :key="permission.slug">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800" x-text="permission.slug"></span>
                                </template>
                            </div>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <button @click="editRole(role)" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">Edit</button>
                            <button x-show="!role.is_system" @click="deleteRole(role)" class="text-red-600 hover:text-red-900 text-sm font-medium">Delete</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Permissions Tab -->
    <div x-show="activeTab === 'permissions'" class="p-6">
        <div class="space-y-6">
            <!-- Permission Categories -->
            <template x-for="category in permissionCategories" :key="category">
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 capitalize" x-text="category + ' Permissions'"></h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <template x-for="permission in getPermissionsByCategory(category)" :key="permission.id">
                            <div class="border border-gray-200 rounded p-3">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="font-medium text-gray-900" x-text="permission.name"></h4>
                                        <p class="text-sm text-gray-500" x-text="permission.slug"></p>
                                        <p class="text-xs text-gray-400 mt-1" x-text="permission.description"></p>
                                    </div>
                                    <span x-show="permission.is_system" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                        System
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Users Tab -->
    <div x-show="activeTab === 'users'" class="p-6">
        <div class="space-y-6">
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">User Role Assignments</h3>
                <p class="text-sm text-gray-500 mb-4">Assign roles to users for central SSO access control.</p>
                
                <!-- User Search -->
                <div class="mb-4">
                    <input type="text" x-model="userSearch" @input="loadUsers()" placeholder="Search users..." 
                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                </div>

                <!-- Users List -->
                <div class="space-y-3">
                    <template x-for="user in users" :key="user.id">
                        <div class="flex items-center justify-between py-3 border-b border-gray-200">
                            <div>
                                <h4 class="font-medium text-gray-900" x-text="user.name"></h4>
                                <p class="text-sm text-gray-500" x-text="user.email"></p>
                                <div class="mt-1" x-show="user.roles?.length > 0">
                                    <template x-for="userRole in user.roles" :key="userRole.role.id">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 mr-1" x-text="userRole.role.name + (userRole.tenant_id ? ' (Tenant ' + userRole.tenant_id + ')' : ' (Global)')"></span>
                                    </template>
                                </div>
                            </div>
                            <button @click="manageUserRoles(user)" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                Manage Roles
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <!-- Create/Edit Role Modal -->
    <div x-show="showRoleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-transition>
        <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
            <form @submit.prevent="saveRole()">
                <div class="mt-3">
                    <h3 class="text-lg font-medium text-gray-900 mb-4" x-text="editingRole ? 'Edit Role' : 'Create New Role'"></h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" x-model="roleForm.name" required 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea x-model="roleForm.description" rows="3"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Permissions</label>
                            <div class="space-y-3 max-h-64 overflow-y-auto border border-gray-200 rounded p-3">
                                <template x-for="category in permissionCategories" :key="category">
                                    <div>
                                        <h4 class="font-medium text-gray-900 capitalize mb-2" x-text="category"></h4>
                                        <div class="space-y-1 ml-4">
                                            <template x-for="permission in getPermissionsByCategory(category)" :key="permission.slug">
                                                <label class="flex items-center">
                                                    <input type="checkbox" :value="permission.slug" x-model="roleForm.permissions"
                                                           class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                                    <span class="ml-2 text-sm text-gray-700" x-text="permission.name"></span>
                                                </label>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" @click="showRoleModal = false" class="px-4 py-2 bg-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                            <span x-text="editingRole ? 'Update' : 'Create'"></span> Role
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- User Roles Modal -->
    <div x-show="showUserRolesModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-transition>
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Manage User Roles</h3>
                <div x-show="selectedUser">
                    <p class="text-sm text-gray-600 mb-4">
                        User: <span class="font-medium" x-text="selectedUser?.name + ' (' + selectedUser?.email + ')'"></span>
                    </p>
                    
                    <div class="space-y-3">
                        <template x-for="role in roles" :key="role.id">
                            <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                <div>
                                    <span class="font-medium" x-text="role.name"></span>
                                    <span x-show="role.is_system" class="ml-2 text-xs text-red-600">(System)</span>
                                </div>
                                <button @click="toggleUserRole(role)" 
                                        :class="userHasRole(role) ? 'bg-red-600 hover:bg-red-700 text-white' : 'bg-green-600 hover:bg-green-700 text-white'"
                                        class="px-3 py-1 text-xs font-medium rounded">
                                    <span x-text="userHasRole(role) ? 'Remove' : 'Assign'"></span>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button @click="showUserRolesModal = false" class="px-4 py-2 bg-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-400">
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
        userSearch: '',
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
            // Load initial data passed from the server
            this.roles = @json($roles ?? []);
            this.permissions = @json($permissions ?? []);
            this.users = @json($users ?? []);
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
                    alert(this.editingRole ? 'Role updated successfully!' : 'Role created successfully!');
                } else {
                    const error = await response.json();
                    alert('Error: ' + (error.message || 'Failed to save role'));
                }
            } catch (error) {
                console.error('Error saving role:', error);
                alert('Error saving role');
            }
        },
        
        async deleteRole(role) {
            if (!confirm(`Are you sure you want to delete the role "${role.name}"?`)) return;
            
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
                    alert('Role deleted successfully!');
                } else {
                    const error = await response.json();
                    alert('Error: ' + (error.message || 'Failed to delete role'));
                }
            } catch (error) {
                console.error('Error deleting role:', error);
                alert('Error deleting role');
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
                    // Update selected user
                    this.selectedUser = this.users.find(u => u.id === this.selectedUser.id);
                    alert(hasRole ? 'Role removed successfully!' : 'Role assigned successfully!');
                } else {
                    const error = await response.json();
                    alert('Error: ' + (error.message || 'Failed to update role'));
                }
            } catch (error) {
                console.error('Error toggling role:', error);
                alert('Error updating role');
            }
        },
        
        async getToken() {
            // Get CSRF token for web-based authentication
            // For API calls from the admin panel, we'll use session-based auth
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        }
    };
}

// Global function for the create button
function showCreateRoleModal() {
    // This will be called by Alpine's roleManagement component
    window.dispatchEvent(new CustomEvent('show-create-role'));
}
</script>
@endsection
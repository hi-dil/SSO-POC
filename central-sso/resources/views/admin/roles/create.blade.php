@extends('layouts.admin')

@section('title', 'Create Role')

@section('header')
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Create Role</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Create a new role and assign permissions for the central SSO system
        </p>
    </div>
@endsection

@section('actions')
    <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Back to Roles
    </a>
@endsection

@section('content')
<div x-data="roleForm()" x-init="initializeData()">
    <form @submit.prevent="submitForm()" class="space-y-6">
        @csrf
        
        <!-- Basic Information -->
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                        <label for="name" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Name *</label>
                        <input type="text" 
                               id="name"
                               name="name"
                               x-model="form.name" 
                               required 
                               class="flex h-10 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-gray-600 dark:placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                               placeholder="Enter role name">
                        <div x-show="errors.name" class="text-sm text-red-600 dark:text-red-400" x-text="errors.name"></div>
                    </div>
                    
                    <div class="space-y-2">
                        <label for="slug" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Slug</label>
                        <input type="text" 
                               id="slug"
                               name="slug"
                               x-model="form.slug" 
                               class="flex h-10 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-gray-600 dark:placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                               placeholder="Leave empty to auto-generate from name">
                        <div x-show="errors.slug" class="text-sm text-red-600 dark:text-red-400" x-text="errors.slug"></div>
                    </div>
                </div>
                
                <div class="space-y-2 mt-6">
                    <label for="description" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Description</label>
                    <textarea id="description"
                              name="description"
                              x-model="form.description" 
                              rows="3"
                              class="flex min-h-[80px] w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm placeholder:text-gray-600 dark:placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                              placeholder="Enter role description"></textarea>
                    <div x-show="errors.description" class="text-sm text-red-600 dark:text-red-400" x-text="errors.description"></div>
                </div>
            </div>
        </div>

        <!-- Permissions -->
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Permissions</h3>
                
                <!-- Search and Controls -->
                <div class="mb-4 p-3 border border-gray-200 dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-700">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex-1">
                            <input type="text" 
                                   x-model="permissionSearch" 
                                   @input.debounce.300ms="filterPermissions()"
                                   placeholder="Search permissions..." 
                                   class="w-full h-8 px-3 py-1 text-sm rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 placeholder:text-gray-500 dark:placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="selectAllPermissions()" class="text-xs px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                                Select All
                            </button>
                            <button type="button" @click="selectNonePermissions()" class="text-xs px-2 py-1 bg-gray-600 text-white rounded hover:bg-gray-700 transition-colors">
                                Select None
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center justify-between mt-2 text-xs text-gray-600 dark:text-gray-400">
                        <span x-text="`${selectedPermissionCount} of ${filteredPermissions.length} selected`"></span>
                        <span x-text="`Page ${currentPage} of ${totalPages}`"></span>
                    </div>
                </div>
                
                <!-- Permissions Table -->
                <div class="border border-gray-200 dark:border-gray-600 rounded-md">
                    <div class="max-h-96 overflow-y-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-muted sticky top-0 border-b border-gray-200 dark:border-gray-600">
                                <tr>
                                    <th class="w-12 px-3 py-3 text-left">
                                        <input type="checkbox" 
                                               :checked="allVisibleSelected" 
                                               :indeterminate="someVisibleSelected && !allVisibleSelected"
                                               @change="toggleAllVisiblePermissions()"
                                               class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    </th>
                                    <th class="px-3 py-3 text-left font-medium">Permission</th>
                                    <th class="px-3 py-3 text-left font-medium">Category</th>
                                    <th class="px-3 py-3 text-left font-medium">Description</th>
                                    <th class="w-16 px-3 py-3 text-left font-medium">System</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="permission in paginatedPermissions" :key="permission.slug">
                                    <tr @click="togglePermissionRow($event, permission.slug)" 
                                        class="border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors">
                                        <td class="px-3 py-3">
                                            <input type="checkbox" 
                                                   :value="permission.slug" 
                                                   x-model="form.permissions"
                                                   @click.stop
                                                   class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="font-medium" x-text="permission.name"></div>
                                            <div class="text-xs font-mono text-gray-500 dark:text-gray-400" x-text="permission.slug"></div>
                                        </td>
                                        <td class="px-3 py-3">
                                            <span class="inline-flex items-center rounded-full bg-blue-100 dark:bg-blue-900 px-2 py-1 text-xs font-medium text-blue-800 dark:text-blue-200 capitalize" x-text="permission.category"></span>
                                        </td>
                                        <td class="px-3 py-3">
                                            <span class="text-gray-700 dark:text-gray-300" x-text="permission.description || 'No description'"></span>
                                        </td>
                                        <td class="px-3 py-3">
                                            <span x-show="permission.is_system" class="inline-flex items-center rounded-full border px-1.5 py-0.5 text-xs font-semibold border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20">
                                                System
                                            </span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                        
                        <!-- No Results -->
                        <div x-show="filteredPermissions.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <div class="text-sm">No permissions found</div>
                            <div class="text-xs mt-1">Try adjusting your search criteria</div>
                        </div>
                    </div>
                    
                    <!-- Pagination -->
                    <div x-show="totalPages > 1" class="px-3 py-2 border-t border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700">
                        <div class="flex items-center justify-between">
                            <button type="button" @click="currentPage = Math.max(1, currentPage - 1)" 
                                    :disabled="currentPage === 1"
                                    class="text-xs px-2 py-1 bg-gray-600 text-white rounded hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                Previous
                            </button>
                            <div class="flex gap-1">
                                <template x-for="page in pageNumbers" :key="page">
                                    <button type="button" @click="currentPage = page" 
                                            :class="page === currentPage ? 'bg-blue-600' : 'bg-gray-600'"
                                            class="text-xs px-2 py-1 text-white rounded hover:opacity-80 transition-colors" 
                                            x-text="page"></button>
                                </template>
                            </div>
                            <button type="button" @click="currentPage = Math.min(totalPages, currentPage + 1)" 
                                    :disabled="currentPage === totalPages"
                                    class="text-xs px-2 py-1 bg-gray-600 text-white rounded hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                Next
                            </button>
                        </div>
                    </div>
                </div>
                
                <div x-show="errors.permissions" class="text-sm text-red-600 dark:text-red-400 mt-2" x-text="errors.permissions"></div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end space-x-3">
            <a href="{{ route('admin.roles.index') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white h-10 px-4 py-2">
                Cancel
            </a>
            <button type="submit" 
                    :disabled="isSubmitting"
                    class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-blue-600 dark:bg-blue-500 text-white hover:bg-blue-700 dark:hover:bg-blue-600 h-10 px-4 py-2">
                <span x-show="!isSubmitting">Create Role</span>
                <span x-show="isSubmitting" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Creating...
                </span>
            </button>
        </div>
    </form>
</div>

<script>
function roleForm() {
    return {
        form: {
            name: '',
            slug: '',
            description: '',
            permissions: []
        },
        permissions: [],
        permissionSearch: '',
        filteredPermissions: [],
        currentPage: 1,
        itemsPerPage: 15,
        errors: {},
        isSubmitting: false,
        
        get paginatedPermissions() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            const end = start + this.itemsPerPage;
            return this.filteredPermissions.slice(start, end);
        },
        
        get totalPages() {
            return Math.ceil(this.filteredPermissions.length / this.itemsPerPage);
        },
        
        get pageNumbers() {
            const total = this.totalPages;
            const current = this.currentPage;
            const delta = 2;
            const range = [];
            const rangeWithDots = [];
            
            for (let i = Math.max(2, current - delta); i <= Math.min(total - 1, current + delta); i++) {
                range.push(i);
            }
            
            if (current - delta > 2) {
                rangeWithDots.push(1, '...');
            } else {
                rangeWithDots.push(1);
            }
            
            rangeWithDots.push(...range);
            
            if (current + delta < total - 1) {
                rangeWithDots.push('...', total);
            } else if (total > 1) {
                rangeWithDots.push(total);
            }
            
            return rangeWithDots.filter(p => p !== '...' || rangeWithDots.length > 3);
        },
        
        get selectedPermissionCount() {
            return this.form.permissions ? this.form.permissions.length : 0;
        },
        
        get allVisibleSelected() {
            const visibleSlugs = this.paginatedPermissions.map(p => p.slug);
            return visibleSlugs.length > 0 && visibleSlugs.every(slug => this.form.permissions.includes(slug));
        },
        
        get someVisibleSelected() {
            const visibleSlugs = this.paginatedPermissions.map(p => p.slug);
            return visibleSlugs.some(slug => this.form.permissions.includes(slug));
        },
        
        initializeData() {
            this.permissions = @json($permissions ?? []);
            this.filteredPermissions = [...this.permissions];
        },
        
        filterPermissions() {
            const search = this.permissionSearch.toLowerCase().trim();
            
            if (!search) {
                this.filteredPermissions = [...this.permissions];
            } else {
                this.filteredPermissions = this.permissions.filter(permission => {
                    return permission.name.toLowerCase().includes(search) ||
                           permission.slug.toLowerCase().includes(search) ||
                           permission.category.toLowerCase().includes(search) ||
                           (permission.description && permission.description.toLowerCase().includes(search));
                });
            }
            
            this.currentPage = 1;
        },
        
        togglePermissionRow(event, permissionSlug) {
            if (event.target.type === 'checkbox') return;
            
            if (this.form.permissions.includes(permissionSlug)) {
                this.form.permissions = this.form.permissions.filter(slug => slug !== permissionSlug);
            } else {
                this.form.permissions.push(permissionSlug);
            }
        },
        
        toggleAllVisiblePermissions() {
            const visibleSlugs = this.paginatedPermissions.map(p => p.slug);
            const allSelected = visibleSlugs.every(slug => this.form.permissions.includes(slug));
            
            if (allSelected) {
                this.form.permissions = this.form.permissions.filter(slug => !visibleSlugs.includes(slug));
            } else {
                visibleSlugs.forEach(slug => {
                    if (!this.form.permissions.includes(slug)) {
                        this.form.permissions.push(slug);
                    }
                });
            }
        },
        
        selectAllPermissions() {
            this.form.permissions = this.filteredPermissions.map(p => p.slug);
        },
        
        selectNonePermissions() {
            this.form.permissions = [];
        },
        
        async submitForm() {
            this.isSubmitting = true;
            this.errors = {};
            
            try {
                const formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                formData.append('name', this.form.name);
                formData.append('slug', this.form.slug);
                formData.append('description', this.form.description);
                
                this.form.permissions.forEach((permission, index) => {
                    formData.append(`permissions[${index}]`, permission);
                });
                
                const response = await fetch('{{ route("admin.roles.store") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                    }
                });
                
                const data = await response.json();
                
                if (response.ok) {
                    if (window.showToast) {
                        window.showToast('Role created successfully!', 'success');
                    }
                    window.location.href = '{{ route("admin.roles.index") }}';
                } else {
                    if (data.errors) {
                        this.errors = data.errors;
                    } else {
                        if (window.showToast) {
                            window.showToast(data.message || 'An error occurred', 'error');
                        }
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                if (window.showToast) {
                    window.showToast('An error occurred while creating the role', 'error');
                }
            } finally {
                this.isSubmitting = false;
            }
        }
    };
}
</script>
@endsection
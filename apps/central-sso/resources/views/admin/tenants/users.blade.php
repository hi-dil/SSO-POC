@extends('layouts.admin')

@section('title', 'Tenant Users')

@section('header')
    <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:text-3xl sm:truncate">
        {{ $tenant->name }} - Users
    </h2>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Manage users assigned to this tenant
    </p>
@endsection

@section('actions')
    <div class="flex space-x-3">
        <a href="{{ route('admin.tenants.show', $tenant) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            View Tenant
        </a>
        <a href="{{ route('admin.tenants.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Tenants
        </a>
    </div>
@endsection

@section('content')
<div class="px-4 py-5 sm:p-6">
    <!-- Assign New User -->
    @if($availableUsers->count() > 0)
        <div class="mb-8 bg-gray-50 dark:bg-gray-800/50 p-6 rounded-lg">
            <div class="space-y-4">
                <!-- Header and Summary -->
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Assign Users to Tenant</h3>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        <span id="assign-selected-count">0</span> of {{ $availableUsers->count() }} users selected
                    </div>
                </div>
                
                <!-- Search and Controls -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Select Users to Assign</span>
                            <div class="flex items-center space-x-2">
                                <button type="button" onclick="selectAllAssignUsers()" 
                                        class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">
                                    Select All
                                </button>
                                <span class="text-gray-300 dark:text-gray-600">|</span>
                                <button type="button" onclick="selectNoneAssignUsers()" 
                                        class="text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300">
                                    Select None
                                </button>
                            </div>
                        </div>
                        <button type="button" onclick="assignSelectedUsers()" 
                                id="assign-button"
                                disabled
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 dark:bg-indigo-500 hover:bg-indigo-700 dark:hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Assign Selected Users
                        </button>
                    </div>
                    
                    <!-- Search Bar -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" 
                               id="assign-user-search" 
                               placeholder="Search users by name, email, or role..."
                               onkeyup="searchAssignUsers()"
                               class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-md text-sm placeholder:text-gray-500 dark:placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
                
                <!-- User Selection Table -->
                <div class="border border-gray-200 dark:border-gray-600 rounded-lg overflow-hidden">
                    <div class="max-h-80 overflow-y-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0">
                                <tr>
                                    <th class="w-12 px-4 py-3 text-left">
                                        <input type="checkbox" 
                                               id="select-all-assign-users" 
                                               onchange="toggleAllAssignUsers(this)"
                                               class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                    </th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-900 dark:text-white">User</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-900 dark:text-white">Email</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-900 dark:text-white">Roles</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-900 dark:text-white">Status</th>
                                </tr>
                            </thead>
                            <tbody id="assign-user-table-body" class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($availableUsers as $user)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors" 
                                        onclick="toggleAssignUserRow(this, '{{ $user->id }}')"
                                        data-user-id="{{ $user->id }}"
                                        data-user-name="{{ strtolower($user->name) }}"
                                        data-user-email="{{ strtolower($user->email) }}"
                                        data-user-roles="{{ strtolower($user->roles->pluck('name')->join(' ')) }}">
                                        <td class="px-4 py-3" onclick="event.stopPropagation()">
                                            <input type="checkbox" 
                                                   name="user_ids[]" 
                                                   value="{{ $user->id }}"
                                                   onchange="updateAssignSelectedCount()"
                                                   class="assign-user-checkbox rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500">
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-8 w-8">
                                                    <div class="h-8 w-8 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                                            {{ substr($user->name, 0, 1) }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="ml-3">
                                                    <div class="font-medium text-gray-900 dark:text-white">{{ $user->name }}</div>
                                                    @if($user->job_title)
                                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $user->job_title }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="text-gray-700 dark:text-gray-300">{{ $user->email }}</span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-1">
                                                @forelse($user->roles as $role)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium 
                                                        @if($role->name === 'super-admin') bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 
                                                        @elseif($role->name === 'admin') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 
                                                        @else bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 @endif">
                                                        {{ ucwords(str_replace('-', ' ', $role->name)) }}
                                                    </span>
                                                @empty
                                                    <span class="text-gray-400 dark:text-gray-500 text-xs">No roles</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($user->is_admin)
                                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200">
                                                    <svg class="w-1.5 h-1.5 mr-1.5" fill="currentColor" viewBox="0 0 8 8">
                                                        <circle cx="4" cy="4" r="3"/>
                                                    </svg>
                                                    Admin
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                                    <svg class="w-1.5 h-1.5 mr-1.5" fill="currentColor" viewBox="0 0 8 8">
                                                        <circle cx="4" cy="4" r="3"/>
                                                    </svg>
                                                    User
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Pagination Controls -->
                <div id="assign-user-pagination" class="hidden border-t border-gray-200 dark:border-gray-600 px-4 py-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                Showing <span id="assign-page-start">1</span> to <span id="assign-page-end">15</span> of <span id="assign-total-users">{{ $availableUsers->count() }}</span> users
                            </span>
                        </div>
                        <div class="flex items-center space-x-1">
                            <button type="button" id="assign-prev-page" onclick="changeAssignPage(-1)" 
                                    class="inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                            <span id="assign-page-numbers" class="inline-flex"></span>
                            <button type="button" id="assign-next-page" onclick="changeAssignPage(1)" 
                                    class="inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Empty State -->
                <div id="assign-no-users-message" class="hidden text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <p class="text-sm">No users found</p>
                    <p class="text-xs text-gray-400 mt-1">Try adjusting your search criteria</p>
                </div>
                
                @error('user_id')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    @endif

    <!-- Users List -->
    @if($users->count() > 0)
        <div class="overflow-hidden shadow ring-1 ring-black dark:ring-gray-600 ring-opacity-5 md:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            User
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Roles
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Assigned
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Last Login
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($users as $user)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {{ substr($user->name, 0, 1) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($user->roles as $role)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            @if($role->name === 'super-admin') bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-400 
                                            @elseif($role->name === 'tenant-admin') bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-400 
                                            @else bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-400 @endif">
                                            {{ ucwords(str_replace('-', ' ', $role->name)) }}
                                        </span>
                                    @empty
                                        <span class="text-gray-400 dark:text-gray-500 text-sm">No roles assigned</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $user->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $user->updated_at->diffForHumans() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                @can('manage-tenants')
                                    <form method="POST" action="{{ route('admin.tenants.remove-user', [$tenant, $user]) }}" class="inline" onsubmit="return confirm('Are you sure you want to remove this user from the tenant?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                                            Remove
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($users->hasPages())
            <div class="mt-6">
                {{ $users->links() }}
            </div>
        @endif
    @else
        <!-- Empty State -->
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No users assigned</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                @if($availableUsers->count() > 0)
                    Assign users to this tenant using the form above.
                @else
                    All users are already assigned to this tenant.
                @endif
            </p>
        </div>
    @endif

    <!-- Tenant Usage Summary -->
    <div class="mt-8 bg-gray-50 dark:bg-gray-800/50 p-4 rounded-lg">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Usage Summary</h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="text-center">
                <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $users->total() }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Assigned Users</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-gray-600 dark:text-gray-400">{{ $availableUsers->count() }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Available Users</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold {{ $tenant->max_users && $users->total() >= $tenant->max_users ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                    @if($tenant->max_users)
                        {{ $tenant->max_users - $users->total() }}
                    @else
                        ∞
                    @endif
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    @if($tenant->max_users)
                        Remaining Slots
                    @else
                        Unlimited
                    @endif
                </div>
            </div>
        </div>
        
        @if($tenant->max_users && $users->total() >= $tenant->max_users)
            <div class="mt-4 p-3 bg-red-100 dark:bg-red-900/30 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-400 rounded">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-400 dark:text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <span class="text-sm">
                        This tenant has reached its maximum user limit ({{ $tenant->max_users }}). 
                        Remove users or increase the limit to assign more users.
                    </span>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
// User assignment functionality
let assignCurrentPage = 1;
const assignItemsPerPage = 15;
let assignFilteredRows = [];

// Row click functionality
function toggleAssignUserRow(row, userId) {
    const checkbox = row.querySelector('input[name="user_ids[]"]');
    checkbox.checked = !checkbox.checked;
    updateAssignSelectedCount();
}

// Search functionality
let assignSearchTimer;
function searchAssignUsers() {
    clearTimeout(assignSearchTimer);
    assignSearchTimer = setTimeout(() => {
        const searchTerm = document.getElementById('assign-user-search').value.toLowerCase();
        const tableBody = document.getElementById('assign-user-table-body');
        const rows = tableBody.querySelectorAll('tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const name = row.dataset.userName || '';
            const email = row.dataset.userEmail || '';
            const roles = row.dataset.userRoles || '';
            
            const isVisible = name.includes(searchTerm) || 
                            email.includes(searchTerm) || 
                            roles.includes(searchTerm);
            
            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });
        
        // Show/hide empty state
        const noUsersMessage = document.getElementById('assign-no-users-message');
        if (visibleCount === 0 && searchTerm !== '') {
            noUsersMessage.classList.remove('hidden');
            tableBody.style.display = 'none';
        } else {
            noUsersMessage.classList.add('hidden');
            tableBody.style.display = '';
        }
        
        // Update pagination after search
        initializeAssignPagination();
    }, 300);
}

// Selection count update
function updateAssignSelectedCount() {
    const selectedCheckboxes = document.querySelectorAll('.assign-user-checkbox:checked');
    const count = selectedCheckboxes.length;
    document.getElementById('assign-selected-count').textContent = count;
    
    // Enable/disable assign button
    const assignButton = document.getElementById('assign-button');
    assignButton.disabled = count === 0;
    
    updateAssignSelectAllState();
}

function updateAssignSelectAllState() {
    const allVisibleCheckboxes = Array.from(document.querySelectorAll('.assign-user-checkbox'))
        .filter(cb => cb.closest('tr').style.display !== 'none');
    const selectedVisibleCheckboxes = allVisibleCheckboxes.filter(cb => cb.checked);
    const selectAllCheckbox = document.getElementById('select-all-assign-users');
    
    if (selectedVisibleCheckboxes.length === 0) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = false;
    } else if (selectedVisibleCheckboxes.length === allVisibleCheckboxes.length) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = true;
    } else {
        selectAllCheckbox.indeterminate = true;
        selectAllCheckbox.checked = false;
    }
}

function toggleAllAssignUsers(selectAllCheckbox) {
    const visibleCheckboxes = Array.from(document.querySelectorAll('.assign-user-checkbox'))
        .filter(cb => cb.closest('tr').style.display !== 'none');
    
    visibleCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    updateAssignSelectedCount();
}

function selectAllAssignUsers() {
    const visibleCheckboxes = Array.from(document.querySelectorAll('.assign-user-checkbox'))
        .filter(cb => cb.closest('tr').style.display !== 'none');
    const selectAllCheckbox = document.getElementById('select-all-assign-users');
    
    visibleCheckboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
    selectAllCheckbox.checked = true;
    selectAllCheckbox.indeterminate = false;
    updateAssignSelectedCount();
}

function selectNoneAssignUsers() {
    const visibleCheckboxes = Array.from(document.querySelectorAll('.assign-user-checkbox'))
        .filter(cb => cb.closest('tr').style.display !== 'none');
    const selectAllCheckbox = document.getElementById('select-all-assign-users');
    
    visibleCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    selectAllCheckbox.checked = false;
    selectAllCheckbox.indeterminate = false;
    updateAssignSelectedCount();
}

// Bulk assignment functionality
async function assignSelectedUsers() {
    const selectedCheckboxes = document.querySelectorAll('.assign-user-checkbox:checked');
    const selectedUserIds = Array.from(selectedCheckboxes).map(cb => cb.value);
    
    if (selectedUserIds.length === 0) {
        return;
    }
    
    const assignButton = document.getElementById('assign-button');
    const originalText = assignButton.innerHTML;
    assignButton.disabled = true;
    assignButton.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Assigning...';
    
    try {
        const promises = selectedUserIds.map(async (userId) => {
            const formData = new FormData();
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            formData.append('user_id', userId);
            
            return fetch('{{ route("admin.tenants.assign-user", $tenant) }}', {
                method: 'POST',
                body: formData
            });
        });
        
        await Promise.all(promises);
        
        // Show success message and reload page
        if (window.showToast) {
            window.showToast(`Successfully assigned ${selectedUserIds.length} users to tenant`, 'success');
        }
        
        // Reload page to reflect changes
        window.location.reload();
        
    } catch (error) {
        console.error('Error assigning users:', error);
        if (window.showToast) {
            window.showToast('Error assigning users: ' + error.message, 'error');
        }
        
        // Reset button
        assignButton.disabled = false;
        assignButton.innerHTML = originalText;
    }
}

// Pagination functionality
function initializeAssignPagination() {
    const tableBody = document.getElementById('assign-user-table-body');
    const visibleRows = Array.from(tableBody.querySelectorAll('tr'))
        .filter(row => row.style.display !== 'none');
    assignFilteredRows = visibleRows;
    
    if (visibleRows.length > assignItemsPerPage) {
        document.getElementById('assign-user-pagination').classList.remove('hidden');
        updateAssignPagination();
    } else {
        document.getElementById('assign-user-pagination').classList.add('hidden');
        // Show all rows if no pagination needed
        visibleRows.forEach(row => row.style.display = '');
    }
}

function updateAssignPagination() {
    const totalItems = assignFilteredRows.length;
    const totalPages = Math.ceil(totalItems / assignItemsPerPage);
    const startIndex = (assignCurrentPage - 1) * assignItemsPerPage;
    const endIndex = Math.min(startIndex + assignItemsPerPage, totalItems);
    
    // Update pagination info
    document.getElementById('assign-page-start').textContent = totalItems > 0 ? startIndex + 1 : 0;
    document.getElementById('assign-page-end').textContent = endIndex;
    document.getElementById('assign-total-users').textContent = totalItems;
    
    // Show/hide rows
    const allRows = document.querySelectorAll('#assign-user-table-body tr');
    allRows.forEach(row => row.style.display = 'none');
    
    assignFilteredRows.forEach((row, index) => {
        row.style.display = (index >= startIndex && index < endIndex) ? '' : 'none';
    });
    
    // Update pagination buttons
    document.getElementById('assign-prev-page').disabled = assignCurrentPage === 1;
    document.getElementById('assign-next-page').disabled = assignCurrentPage === totalPages;
    
    // Update page numbers
    updateAssignPageNumbers(totalPages);
}

function updateAssignPageNumbers(totalPages) {
    const pageNumbers = document.getElementById('assign-page-numbers');
    pageNumbers.innerHTML = '';
    
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= assignCurrentPage - 2 && i <= assignCurrentPage + 2)) {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = i;
            button.onclick = () => goToAssignPage(i);
            button.className = `px-3 py-2 text-sm font-medium leading-tight ${
                i === assignCurrentPage 
                    ? 'text-indigo-600 bg-indigo-50 border border-indigo-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white' 
                    : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white'
            }`;
            pageNumbers.appendChild(button);
        } else if (i === assignCurrentPage - 3 || i === assignCurrentPage + 3) {
            const span = document.createElement('span');
            span.textContent = '...';
            span.className = 'px-3 py-2 text-sm font-medium text-gray-500';
            pageNumbers.appendChild(span);
        }
    }
}

function changeAssignPage(direction) {
    const totalPages = Math.ceil(assignFilteredRows.length / assignItemsPerPage);
    const newPage = assignCurrentPage + direction;
    
    if (newPage >= 1 && newPage <= totalPages) {
        assignCurrentPage = newPage;
        updateAssignPagination();
    }
}

function goToAssignPage(page) {
    assignCurrentPage = page;
    updateAssignPagination();
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize pagination on page load
    initializeAssignPagination();
});
</script>
@endsection
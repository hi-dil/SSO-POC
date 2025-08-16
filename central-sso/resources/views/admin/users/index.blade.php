@extends('layouts.admin')

@section('title', 'User Management')

@section('header')
    <div>
        <h1 class="text-2xl font-semibold text-card-foreground">User Management</h1>
        <p class="text-sm text-muted-foreground mt-1">
            Manage central SSO users and their access permissions
        </p>
    </div>
@endsection

@section('actions')
    <div class="flex space-x-3">
        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            New User
        </a>
    </div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Search Bar (Always visible) -->
    <div class="flex items-center justify-between">
        <div class="flex-1 max-w-lg">
            <form method="GET" action="{{ route('admin.users.index') }}" class="relative">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Search users by name, email, job title, department..."
                           class="block w-full pl-10 pr-24 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                    <div class="absolute inset-y-0 right-0 flex items-center">
                        @if(request('search'))
                            <a href="{{ route('admin.users.index') }}" 
                               class="pr-2 flex items-center text-muted-foreground hover:text-foreground">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </a>
                        @endif
                        <button type="submit" class="pr-3 flex items-center text-muted-foreground hover:text-primary">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Preserve sort parameters -->
                @if(request('sort'))
                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                @endif
                @if(request('direction'))
                    <input type="hidden" name="direction" value="{{ request('direction') }}">
                @endif
            </form>
        </div>
        
        @if(request('search'))
            <div class="text-sm text-muted-foreground ml-4">
                {{ $users->total() }} result(s) for "{{ request('search') }}"
            </div>
        @endif
        
        <!-- Debug sorting info -->
        @if(config('app.debug'))
            <div class="text-xs text-gray-500">
                Sort: {{ request('sort', 'updated_at') }} | Direction: {{ request('direction', 'desc') }}
            </div>
        @endif
    </div>

    @if($users->count() > 0)
        <!-- Users Table -->
        <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
            <div class="overflow-hidden">
                <table class="w-full caption-bottom text-sm">
                    <thead class="[&_tr]:border-b border-border">
                        <tr class="border-b border-border transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                            <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0">
                                <button onclick="sortTable('name')" class="flex items-center space-x-1 hover:text-foreground">
                                    <span>User</span>
                                    @if(request('sort') === 'name')
                                        @if(request('direction') === 'asc')
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                            </svg>
                                        @else
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        @endif
                                    @else
                                        <svg class="h-4 w-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                        </svg>
                                    @endif
                                </button>
                            </th>
                            <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0">
                                <button onclick="sortTable('email')" class="flex items-center space-x-1 hover:text-foreground">
                                    <span>Contact</span>
                                    @if(request('sort') === 'email')
                                        @if(request('direction') === 'asc')
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                            </svg>
                                        @else
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        @endif
                                    @else
                                        <svg class="h-4 w-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                        </svg>
                                    @endif
                                </button>
                            </th>
                            <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0">
                                <button onclick="sortTable('job_title')" class="flex items-center space-x-1 hover:text-foreground">
                                    <span>Position</span>
                                    @if(request('sort') === 'job_title')
                                        @if(request('direction') === 'asc')
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                            </svg>
                                        @else
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        @endif
                                    @else
                                        <svg class="h-4 w-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                        </svg>
                                    @endif
                                </button>
                            </th>
                            <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0">
                                Tenants
                            </th>
                            <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0">
                                <button onclick="sortTable('is_admin')" class="flex items-center space-x-1 hover:text-foreground">
                                    <span>Status</span>
                                    @if(request('sort') === 'is_admin')
                                        @if(request('direction') === 'asc')
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                            </svg>
                                        @else
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        @endif
                                    @else
                                        <svg class="h-4 w-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                        </svg>
                                    @endif
                                </button>
                            </th>
                            <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0">
                                <button onclick="sortTable('updated_at')" class="flex items-center space-x-1 hover:text-foreground">
                                    <span>Last Updated</span>
                                    @if(request('sort') === 'updated_at' || !request('sort'))
                                        @if(request('direction') === 'asc')
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                            </svg>
                                        @else
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        @endif
                                    @else
                                        <svg class="h-4 w-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                        </svg>
                                    @endif
                                </button>
                            </th>
                            <th class="h-12 px-4 text-right align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="[&_tr:last-child]:border-0">
                        @foreach($users as $user)
                            <tr class="border-b border-border transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full overflow-hidden bg-muted flex items-center justify-center">
                                            @if($user->avatar_url)
                                                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                                            @else
                                                <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                                            @endif
                                        </div>
                                        <div>
                                            <div class="font-medium text-card-foreground">
                                                {{ $user->name }}
                                            </div>
                                            @if($user->employee_id)
                                                <div class="text-xs text-muted-foreground">
                                                    ID: {{ $user->employee_id }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    <div>
                                        <div class="text-sm text-card-foreground">{{ $user->email }}</div>
                                        @if($user->phone)
                                            <div class="text-xs text-muted-foreground">{{ $user->phone }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    <div>
                                        @if($user->job_title)
                                            <div class="text-sm text-card-foreground">{{ $user->job_title }}</div>
                                        @endif
                                        @if($user->department)
                                            <div class="text-xs text-muted-foreground">{{ $user->department }}</div>
                                        @endif
                                        @if(!$user->job_title && !$user->department)
                                            <span class="text-xs text-muted-foreground">Not specified</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    @if($user->tenants->count() > 0)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($user->tenants->take(2) as $tenant)
                                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-blue-50 text-blue-700">
                                                    {{ $tenant->slug }}
                                                </span>
                                            @endforeach
                                            @if($user->tenants->count() > 2)
                                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-input bg-background text-foreground">
                                                    +{{ $user->tenants->count() - 2 }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-muted-foreground">No access</span>
                                    @endif
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    @if($user->is_admin)
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-red-50 text-red-700">
                                            Admin
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-green-50 text-green-700">
                                            User
                                        </span>
                                    @endif
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0 text-muted-foreground">
                                    <div class="text-sm">
                                        <div>{{ $user->updated_at->format('M d, Y') }}</div>
                                        <div class="text-xs text-muted-foreground/70">{{ $user->updated_at->format('g:i A') }}</div>
                                    </div>
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    <div class="flex justify-end">
                                        <div class="relative inline-block text-left">
                                            <button type="button" onclick="toggleDropdown('dropdown-{{ $user->id }}')" 
                                                    class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 w-8">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                                </svg>
                                            </button>

                                            <div id="dropdown-{{ $user->id }}" class="hidden absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                                                <div class="py-1">
                                                    <!-- View Details Action -->
                                                    <a href="{{ route('admin.users.show', $user) }}" 
                                                       class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                        </svg>
                                                        View Details
                                                    </a>

                                                    @can('users.edit')
                                                        <!-- Edit Action -->
                                                        <a href="{{ route('admin.users.edit', $user) }}" 
                                                           class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                            <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                            </svg>
                                                            Edit User
                                                        </a>

                                                        <!-- Divider -->
                                                        <div class="border-t border-gray-100"></div>

                                                        <!-- Manage Tenants Action -->
                                                        <button onclick="showTenantModal({{ $user->id }})" 
                                                                class="flex w-full items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                            <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m14 0v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5"></path>
                                                            </svg>
                                                            Manage Tenants
                                                        </button>
                                                    @endcan

                                                    @can('users.delete')
                                                        @if($user->id !== auth()->id())
                                                            <!-- Delete Action -->
                                                            <button onclick="confirmDelete('{{ route('admin.users.destroy', $user) }}')" 
                                                                    class="flex w-full items-center px-4 py-2 text-sm text-red-700 hover:bg-red-50">
                                                                <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                </svg>
                                                                Delete User
                                                            </button>
                                                        @endif
                                                    @endcan
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($users->hasPages())
            <div class="mt-6">
                {{ $users->links() }}
            </div>
        @endif
    @else
        <!-- Empty State -->
        <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
            <div class="flex flex-col items-center justify-center py-12 px-6">
                @if(request('search'))
                    <!-- No Search Results -->
                    <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <h3 class="mt-4 text-lg font-semibold text-card-foreground">No search results</h3>
                    <p class="mt-2 text-sm text-muted-foreground text-center">
                        No users found for "{{ request('search') }}". Try adjusting your search terms.
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
                            Clear Search
                        </a>
                    </div>
                @else
                    <!-- No Users -->
                    <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                    </svg>
                    <h3 class="mt-4 text-lg font-semibold text-card-foreground">No users</h3>
                    <p class="mt-2 text-sm text-muted-foreground text-center">Get started by creating your first user account.</p>
                    @can('users.create')
                        <div class="mt-6">
                            <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                New User
                            </a>
                        </div>
                    @endcan
                @endif
            </div>
        </div>
    @endif
</div>

<!-- Delete Form Template -->
<form id="deleteForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
// Table sorting functionality
function sortTable(field) {
    console.log('Sorting by field:', field);
    
    const urlParams = new URLSearchParams(window.location.search);
    const currentSort = urlParams.get('sort');
    const currentDirection = urlParams.get('direction') || 'desc';
    
    console.log('Current sort:', currentSort, 'Current direction:', currentDirection);
    
    let newDirection = 'asc';
    if (currentSort === field && currentDirection === 'asc') {
        newDirection = 'desc';
    }
    
    console.log('New direction:', newDirection);
    
    const url = new URL(window.location);
    url.searchParams.set('sort', field);
    url.searchParams.set('direction', newDirection);
    
    // Preserve search parameter if it exists
    const search = urlParams.get('search');
    if (search) {
        url.searchParams.set('search', search);
    }
    
    const newUrl = url.toString();
    console.log('Redirecting to:', newUrl);
    
    window.location.href = newUrl;
}

function toggleDropdown(dropdownId) {
    // Close all other dropdowns first
    document.querySelectorAll('[id^="dropdown-"]').forEach(dropdown => {
        if (dropdown.id !== dropdownId) {
            dropdown.classList.add('hidden');
        }
    });
    
    // Toggle the clicked dropdown
    const dropdown = document.getElementById(dropdownId);
    dropdown.classList.toggle('hidden');
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    const isDropdownButton = event.target.closest('[onclick*="toggleDropdown"]');
    const isDropdownContent = event.target.closest('[id^="dropdown-"]');
    
    if (!isDropdownButton && !isDropdownContent) {
        document.querySelectorAll('[id^="dropdown-"]').forEach(dropdown => {
            dropdown.classList.add('hidden');
        });
    }
});

function confirmDelete(url) {
    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
        const form = document.getElementById('deleteForm');
        form.action = url;
        form.submit();
        
        // Show loading toast
        if (window.showToast) {
            window.showToast('Deleting user...', 'info');
        }
    }
}

// Placeholder functions for tenant management
function showTenantModal(userId) {
    // This would open a modal to manage user's tenant access
    alert('Tenant management modal for user ' + userId + ' - To be implemented');
}

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="search"]');
    const searchForm = document.querySelector('form[action*="users"]');
    
    if (searchInput && searchForm) {
        // Submit on Enter key
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchForm.submit();
            }
        });
        
        console.log('Search form initialized');
    }
    
    // Check for success/error messages from Laravel
    @if(session('success'))
        if (window.showToast) {
            window.showToast('{{ session('success') }}', 'success');
        }
    @endif
    
    @if(session('error'))
        if (window.showToast) {
            window.showToast('{{ session('error') }}', 'error');
        }
    @endif
});
</script>
@endsection
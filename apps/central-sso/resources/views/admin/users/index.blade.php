@extends('layouts.admin')

@section('title', 'User Management')

@section('header')
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">User Management</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Manage central SSO users and their access permissions
        </p>
    </div>
@endsection

@section('actions')
    <div class="flex space-x-3">
        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-teal-600 dark:bg-teal-500 text-white hover:bg-teal-700 dark:hover:bg-teal-600 h-10 px-4 py-2">
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
                        <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Search users by name, email, job title, department..."
                           class="block w-full pl-10 pr-24 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-md text-sm placeholder:text-gray-500 dark:placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    <div class="absolute inset-y-0 right-0 flex items-center">
                        @if(request('search'))
                            <a href="{{ route('admin.users.index') }}" 
                               class="pr-2 flex items-center text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </a>
                        @endif
                        <button type="submit" class="pr-3 flex items-center text-gray-500 dark:text-gray-400 hover:text-teal-600 dark:hover:text-teal-400">
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
            <div class="text-sm text-gray-600 dark:text-gray-400 ml-4">
                {{ $users->total() }} result(s) for "{{ request('search') }}"
            </div>
        @endif
        
        <!-- Bulk Actions -->
        <div id="bulk-actions" class="hidden ml-4 flex items-center space-x-3">
            <span class="text-sm text-gray-600 dark:text-gray-400">
                <span id="selected-count">0</span> selected
            </span>
            <button onclick="showBulkTenantModal()" 
                    class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white h-8 px-3 py-1">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m14 0v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5"></path>
                </svg>
                Bulk Assign Tenants
            </button>
        </div>
        
        <!-- Debug sorting info -->
        @if(config('app.debug'))
            <div class="text-xs text-gray-500">
                Sort: {{ request('sort', 'updated_at') }} | Direction: {{ request('direction', 'desc') }}
            </div>
        @endif
    </div>

    @if($users->count() > 0)
        <!-- Users Table -->
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="overflow-hidden">
                <table class="w-full caption-bottom text-sm">
                    <thead class="border-b border-gray-200 dark:border-gray-700">
                        <tr class="border-b border-gray-200 dark:border-gray-700 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <th class="h-12 px-4 text-left align-middle font-medium text-gray-600 dark:text-gray-400 w-12">
                                <input type="checkbox" 
                                       id="select-all" 
                                       onchange="toggleSelectAll(this)"
                                       class="rounded border-gray-300 dark:border-gray-600 text-teal-600 focus:ring-teal-500 dark:focus:ring-teal-400">
                            </th>
                            <th class="h-12 px-4 text-left align-middle font-medium text-gray-600 dark:text-gray-400">
                                <button onclick="sortTable('name')" class="flex items-center space-x-1 hover:text-gray-900 dark:hover:text-white">
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
                            <th class="h-12 px-4 text-left align-middle font-medium text-gray-600 dark:text-gray-400">
                                <button onclick="sortTable('email')" class="flex items-center space-x-1 hover:text-gray-900 dark:hover:text-white">
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
                            <th class="h-12 px-4 text-left align-middle font-medium text-gray-600 dark:text-gray-400">
                                <button onclick="sortTable('job_title')" class="flex items-center space-x-1 hover:text-gray-900 dark:hover:text-white">
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
                            <th class="h-12 px-4 text-left align-middle font-medium text-gray-600 dark:text-gray-400">
                                Tenants
                            </th>
                            <th class="h-12 px-4 text-left align-middle font-medium text-gray-600 dark:text-gray-400">
                                <button onclick="sortTable('is_admin')" class="flex items-center space-x-1 hover:text-gray-900 dark:hover:text-white">
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
                            <th class="h-12 px-4 text-left align-middle font-medium text-gray-600 dark:text-gray-400">
                                <button onclick="sortTable('updated_at')" class="flex items-center space-x-1 hover:text-gray-900 dark:hover:text-white">
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
                            <th class="h-12 px-4 text-right align-middle font-medium text-gray-600 dark:text-gray-400">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr class="border-b border-gray-200 dark:border-gray-700 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    <input type="checkbox" 
                                           class="user-checkbox rounded border-gray-300 dark:border-gray-600 text-teal-600 focus:ring-teal-500 dark:focus:ring-teal-400" 
                                           value="{{ $user->id }}"
                                           onchange="updateBulkActions()">
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full overflow-hidden bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                            @if($user->avatar_url)
                                                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                                            @else
                                                <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                                            @endif
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">
                                                {{ $user->name }}
                                            </div>
                                            @if($user->employee_id)
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    ID: {{ $user->employee_id }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    <div>
                                        <div class="text-sm text-gray-900 dark:text-white">{{ $user->email }}</div>
                                        @if($user->phone)
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $user->phone }}</div>
                                        @endif
                                    </div>
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    <div>
                                        @if($user->job_title)
                                            <div class="text-sm text-gray-900 dark:text-white">{{ $user->job_title }}</div>
                                        @endif
                                        @if($user->department)
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $user->department }}</div>
                                        @endif
                                        @if(!$user->job_title && !$user->department)
                                            <span class="text-xs text-gray-500 dark:text-gray-400">Not specified</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    @if($user->tenants->count() > 0)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($user->tenants->take(2) as $tenant)
                                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 border-transparent bg-teal-100 dark:bg-teal-900 text-teal-800 dark:text-teal-200">
                                                    {{ $tenant->slug }}
                                                </span>
                                            @endforeach
                                            @if($user->tenants->count() > 2)
                                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white">
                                                    +{{ $user->tenants->count() - 2 }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-500 dark:text-gray-400">No access</span>
                                    @endif
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    @if($user->is_admin)
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 border-transparent bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200">
                                            Admin
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 border-transparent bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                            User
                                        </span>
                                    @endif
                                </td>
                                <td class="p-4 align-middle text-gray-600 dark:text-gray-400">
                                    <div class="text-sm">
                                        <div>{{ $user->updated_at->format('M d, Y') }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-500">{{ $user->updated_at->format('g:i A') }}</div>
                                    </div>
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    <div class="flex justify-end">
                                        <div class="relative inline-block text-left">
                                            <button type="button" onclick="toggleDropdown('dropdown-{{ $user->id }}')" 
                                                    class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white h-8 w-8">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                                </svg>
                                            </button>

                                            <div id="dropdown-{{ $user->id }}" class="hidden absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white dark:bg-gray-800 shadow-lg ring-1 ring-gray-200 dark:ring-gray-700 focus:outline-none">
                                                <div class="py-1">
                                                    <!-- View Details Action -->
                                                    <a href="{{ route('admin.users.show', $user) }}" 
                                                       class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                        <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                        </svg>
                                                        View Details
                                                    </a>

                                                    @can('users.edit')
                                                        <!-- Edit Action -->
                                                        <a href="{{ route('admin.users.edit', $user) }}" 
                                                           class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                            <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                            </svg>
                                                            Edit User
                                                        </a>

                                                        <!-- Divider -->
                                                        <div class="border-t border-gray-200 dark:border-gray-600"></div>

                                                        <!-- Manage Tenants Action -->
                                                        <button onclick="showTenantModal({{ $user->id }})" 
                                                                class="flex w-full items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
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
                                                                    class="flex w-full items-center px-4 py-2 text-sm text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
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
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="flex flex-col items-center justify-center py-12 px-6">
                @if(request('search'))
                    <!-- No Search Results -->
                    <svg class="mx-auto h-12 w-12 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">No search results</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 text-center">
                        No users found for "{{ request('search') }}". Try adjusting your search terms.
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white h-10 px-4 py-2">
                            Clear Search
                        </a>
                    </div>
                @else
                    <!-- No Users -->
                    <svg class="mx-auto h-12 w-12 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">No users</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 text-center">Get started by creating your first user account.</p>
                    @can('users.create')
                        <div class="mt-6">
                            <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-teal-600 dark:bg-teal-500 text-white hover:bg-teal-700 dark:hover:bg-teal-600 h-10 px-4 py-2">
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

<!-- Tenant Management Modal -->
<div id="tenant-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <!-- Background overlay -->
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" onclick="closeTenantModal()"></div>
    
    <!-- Modal content -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-4xl transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 shadow-xl transition-all">
            <!-- Modal header -->
            <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Manage Tenant Access
                        </h3>
                        <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            <span id="modal-user-name" class="font-medium"></span>
                            <span class="text-gray-500">•</span>
                            <span id="modal-user-email"></span>
                        </div>
                    </div>
                    <button type="button" onclick="closeTenantModal()" 
                            class="rounded-md bg-white dark:bg-gray-800 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Modal body -->
            <div class="px-6 py-4">
                <div class="space-y-4">
                    <!-- Summary Information -->
                    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-4">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">Available Tenants:</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ count($tenants) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm mt-2">
                            <span class="text-gray-600 dark:text-gray-400">Currently Selected:</span>
                            <span id="selected-tenants-count" class="font-medium text-teal-600 dark:text-teal-400">0</span>
                        </div>
                    </div>
                    
                    <!-- Search and Controls -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                Select Tenant Access
                            </h4>
                            <div class="flex items-center space-x-2">
                                <button type="button" onclick="selectAllTenants()" 
                                        class="text-xs text-teal-600 dark:text-teal-400 hover:text-teal-700 dark:hover:text-teal-300">
                                    Select All
                                </button>
                                <span class="text-gray-300 dark:text-gray-600">|</span>
                                <button type="button" onclick="selectNoneTenants()" 
                                        class="text-xs text-teal-600 dark:text-teal-400 hover:text-teal-700 dark:hover:text-teal-300">
                                    Select None
                                </button>
                            </div>
                        </div>
                        
                        <!-- Search Bar -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input type="text" 
                                   id="tenant-search" 
                                   placeholder="Search tenants by name, slug, or domain..."
                                   onkeyup="searchTenants()"
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-md text-sm placeholder:text-gray-500 dark:placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        </div>
                    </div>
                    
                    <!-- Tenant Selection Table -->
                    <div>
                        
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                            <div class="max-h-80 overflow-y-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0">
                                        <tr>
                                            <th class="w-12 px-4 py-3 text-left">
                                                <input type="checkbox" 
                                                       id="select-all-tenants" 
                                                       onchange="toggleAllTenants(this)"
                                                       class="rounded border-gray-300 dark:border-gray-600 text-teal-600 focus:ring-teal-500 dark:focus:ring-teal-400">
                                            </th>
                                            <th class="px-4 py-3 text-left font-medium text-gray-900 dark:text-white">Tenant Name</th>
                                            <th class="px-4 py-3 text-left font-medium text-gray-900 dark:text-white">Slug</th>
                                            <th class="px-4 py-3 text-left font-medium text-gray-900 dark:text-white">Domain</th>
                                            <th class="px-4 py-3 text-left font-medium text-gray-900 dark:text-white">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tenant-table-body" class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($tenants as $tenant)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer transition-colors" 
                                                onclick="toggleTenantRow(this, '{{ $tenant->id }}')"
                                                data-tenant-id="{{ $tenant->id }}"
                                                data-tenant-name="{{ strtolower($tenant->name) }}"
                                                data-tenant-slug="{{ strtolower($tenant->slug) }}"
                                                data-tenant-domain="{{ strtolower($tenant->domain) }}">
                                                <td class="px-4 py-3" onclick="event.stopPropagation()">
                                                    <input type="checkbox" 
                                                           name="tenant_assignment" 
                                                           value="{{ $tenant->id }}"
                                                           onchange="updateSelectedCount()"
                                                           class="tenant-checkbox rounded border-gray-300 dark:border-gray-600 text-teal-600 focus:ring-teal-500 dark:focus:ring-teal-400">
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div>
                                                        <div class="font-medium text-gray-900 dark:text-white">
                                                            {{ $tenant->name }}
                                                        </div>
                                                        @if($tenant->description)
                                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                                {{ Str::limit($tenant->description, 50) }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <code class="text-xs bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-gray-700 dark:text-gray-300">
                                                        {{ $tenant->slug }}
                                                    </code>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <span class="text-gray-600 dark:text-gray-400">
                                                        {{ $tenant->domain }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3">
                                                    @if($tenant->is_active)
                                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                                            <svg class="w-1.5 h-1.5 mr-1.5" fill="currentColor" viewBox="0 0 8 8">
                                                                <circle cx="4" cy="4" r="3"/>
                                                            </svg>
                                                            Active
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200">
                                                            <svg class="w-1.5 h-1.5 mr-1.5" fill="currentColor" viewBox="0 0 8 8">
                                                                <circle cx="4" cy="4" r="3"/>
                                                            </svg>
                                                            Inactive
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
                        <div id="tenant-pagination" class="hidden border-t border-gray-200 dark:border-gray-700 px-4 py-3">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm text-gray-700 dark:text-gray-300">
                                        Showing <span id="page-start">1</span> to <span id="page-end">10</span> of <span id="total-tenants">{{ count($tenants) }}</span> tenants
                                    </span>
                                </div>
                                <div class="flex items-center space-x-1">
                                    <button id="prev-page" onclick="changePage(-1)" 
                                            class="inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                        </svg>
                                    </button>
                                    <span id="page-numbers" class="inline-flex"></span>
                                    <button id="next-page" onclick="changePage(1)" 
                                            class="inline-flex items-center px-2 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Empty State -->
                        <div id="no-tenants-message" class="hidden text-center py-8 text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto h-12 w-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <p class="text-sm">No tenants found</p>
                            <p class="text-xs text-gray-400 mt-1">Try adjusting your search criteria</p>
                        </div>
                        
                        @if(count($tenants) === 0)
                            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                                <svg class="mx-auto h-12 w-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m14 0v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5"></path>
                                </svg>
                                <p class="text-sm">No tenants available</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Modal footer -->
            <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeTenantModal()" 
                            class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white h-10 px-4 py-2">
                        Cancel
                    </button>
                    <button type="button" onclick="saveTenantAssignments()" 
                            class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-teal-600 dark:bg-teal-500 text-white hover:bg-teal-700 dark:hover:bg-teal-600 h-10 px-4 py-2">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Tenant Assignment Modal -->
<div id="bulk-tenant-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <!-- Background overlay -->
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" onclick="closeBulkTenantModal()"></div>
    
    <!-- Modal content -->
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-4xl transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 shadow-xl transition-all">
            <!-- Modal header -->
            <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Bulk Tenant Assignment
                        </h3>
                        <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            Assign tenants to <span id="bulk-selected-count" class="font-medium">0</span> selected users
                        </div>
                    </div>
                    <button type="button" onclick="closeBulkTenantModal()" 
                            class="rounded-md bg-white dark:bg-gray-800 text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Modal body -->
            <div class="px-6 py-4">
                <div class="space-y-4">
                    <div>
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">
                            Assignment Type
                        </h4>
                        <div class="space-y-2">
                            <label class="flex items-center space-x-2">
                                <input type="radio" name="bulk_assignment_type" value="add" checked
                                       class="text-teal-600 focus:ring-teal-500 dark:focus:ring-teal-400">
                                <span class="text-sm text-gray-900 dark:text-white">Add to existing tenant assignments</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="radio" name="bulk_assignment_type" value="replace"
                                       class="text-teal-600 focus:ring-teal-500 dark:focus:ring-teal-400">
                                <span class="text-sm text-gray-900 dark:text-white">Replace all tenant assignments</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="radio" name="bulk_assignment_type" value="remove"
                                       class="text-teal-600 focus:ring-teal-500 dark:focus:ring-teal-400">
                                <span class="text-sm text-gray-900 dark:text-white">Remove selected tenant assignments</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                Select Tenants
                            </h4>
                            <div class="flex items-center space-x-2">
                                <button type="button" onclick="selectAllBulkTenants()" 
                                        class="text-xs text-teal-600 dark:text-teal-400 hover:text-teal-700 dark:hover:text-teal-300">
                                    Select All
                                </button>
                                <span class="text-gray-300 dark:text-gray-600">|</span>
                                <button type="button" onclick="selectNoneBulkTenants()" 
                                        class="text-xs text-teal-600 dark:text-teal-400 hover:text-teal-700 dark:hover:text-teal-300">
                                    Select None
                                </button>
                            </div>
                        </div>
                        
                        <!-- Search Bar -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input type="text" 
                                   id="bulk-tenant-search" 
                                   placeholder="Search tenants by name or slug..."
                                   onkeyup="searchBulkTenants()"
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-md text-sm placeholder:text-gray-500 dark:placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        </div>
                    </div>
                        
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                            <div class="max-h-64 overflow-y-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50 dark:bg-gray-800 sticky top-0">
                                        <tr>
                                            <th class="w-12 px-3 py-2 text-left">
                                                <input type="checkbox" 
                                                       id="select-all-bulk-tenants" 
                                                       onchange="toggleAllBulkTenants(this)"
                                                       class="rounded border-gray-300 dark:border-gray-600 text-teal-600 focus:ring-teal-500 dark:focus:ring-teal-400">
                                            </th>
                                            <th class="px-3 py-2 text-left font-medium text-gray-900 dark:text-white">Tenant</th>
                                            <th class="px-3 py-2 text-left font-medium text-gray-900 dark:text-white">Slug</th>
                                            <th class="px-3 py-2 text-left font-medium text-gray-900 dark:text-white">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bulk-tenant-table-body" class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($tenants as $tenant)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 cursor-pointer transition-colors" 
                                                onclick="toggleBulkTenantRow(this, '{{ $tenant->id }}')"
                                                data-tenant-id="{{ $tenant->id }}"
                                                data-tenant-name="{{ strtolower($tenant->name) }}"
                                                data-tenant-slug="{{ strtolower($tenant->slug) }}">
                                                <td class="px-3 py-2" onclick="event.stopPropagation()">
                                                    <input type="checkbox" 
                                                           name="bulk_tenant_assignment" 
                                                           value="{{ $tenant->id }}"
                                                           onchange="updateBulkSelectedCount()"
                                                           class="bulk-tenant-checkbox rounded border-gray-300 dark:border-gray-600 text-teal-600 focus:ring-teal-500 dark:focus:ring-teal-400">
                                                </td>
                                                <td class="px-3 py-2">
                                                    <div class="font-medium text-gray-900 dark:text-white">
                                                        {{ $tenant->name }}
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2">
                                                    <code class="text-xs bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded text-gray-700 dark:text-gray-300">
                                                        {{ $tenant->slug }}
                                                    </code>
                                                </td>
                                                <td class="px-3 py-2">
                                                    @if($tenant->is_active)
                                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                                            <svg class="w-1.5 h-1.5 mr-1" fill="currentColor" viewBox="0 0 8 8">
                                                                <circle cx="4" cy="4" r="3"/>
                                                            </svg>
                                                            Active
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200">
                                                            <svg class="w-1.5 h-1.5 mr-1" fill="currentColor" viewBox="0 0 8 8">
                                                                <circle cx="4" cy="4" r="3"/>
                                                            </svg>
                                                            Inactive
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Modal footer -->
            <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeBulkTenantModal()" 
                            class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white h-10 px-4 py-2">
                        Cancel
                    </button>
                    <button type="button" onclick="saveBulkTenantAssignments()" 
                            class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-teal-600 dark:bg-teal-500 text-white hover:bg-teal-700 dark:hover:bg-teal-600 h-10 px-4 py-2">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Apply Changes
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

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

// Tenant management modal functionality
let currentUserId = null;
let userTenants = new Set();

async function showTenantModal(userId) {
    currentUserId = userId;
    userTenants.clear();
    
    try {
        const response = await fetch(`/api/admin/users/${userId}`);
        const data = await response.json();
        
        if (data.success) {
            const user = data.data;
            
            // Store current user's tenants
            if (user.tenants) {
                user.tenants.forEach(tenant => userTenants.add(tenant.id));
            }
            
            // Update modal content
            document.getElementById('modal-user-name').textContent = user.name;
            document.getElementById('modal-user-email').textContent = user.email;
            
            // Update tenant checkboxes
            const checkboxes = document.querySelectorAll('input[name="tenant_assignment"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = userTenants.has(checkbox.value);
            });
            
            // Update counts
            updateSelectedCount();
            updateSelectAllState();
            
            // Initialize pagination
            currentPage = 1;
            initializePagination();
            
            // Show the modal
            document.getElementById('tenant-modal').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error loading user data:', error);
        if (window.showToast) {
            window.showToast('Error loading user data', 'error');
        }
    }
}

function closeTenantModal() {
    document.getElementById('tenant-modal').classList.add('hidden');
    currentUserId = null;
    userTenants.clear();
}

// Table-specific tenant management functions
function updateSelectedCount() {
    const selectedCheckboxes = document.querySelectorAll('input[name="tenant_assignment"]:checked');
    const count = selectedCheckboxes.length;
    document.getElementById('selected-tenants-count').textContent = count;
    updateSelectAllState();
}

function updateSelectAllState() {
    const allCheckboxes = document.querySelectorAll('.tenant-checkbox');
    const selectedCheckboxes = document.querySelectorAll('.tenant-checkbox:checked');
    const selectAllCheckbox = document.getElementById('select-all-tenants');
    
    if (selectedCheckboxes.length === 0) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = false;
    } else if (selectedCheckboxes.length === allCheckboxes.length) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = true;
    } else {
        selectAllCheckbox.indeterminate = true;
        selectAllCheckbox.checked = false;
    }
}

function toggleAllTenants(selectAllCheckbox) {
    const tenantCheckboxes = document.querySelectorAll('.tenant-checkbox');
    tenantCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    updateSelectedCount();
}

function selectAllTenants() {
    const tenantCheckboxes = document.querySelectorAll('.tenant-checkbox');
    const selectAllCheckbox = document.getElementById('select-all-tenants');
    tenantCheckboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
    selectAllCheckbox.checked = true;
    selectAllCheckbox.indeterminate = false;
    updateSelectedCount();
}

function selectNoneTenants() {
    const tenantCheckboxes = document.querySelectorAll('.tenant-checkbox');
    const selectAllCheckbox = document.getElementById('select-all-tenants');
    tenantCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    selectAllCheckbox.checked = false;
    selectAllCheckbox.indeterminate = false;
    updateSelectedCount();
}

// Row click functionality for individual tenant modal
function toggleTenantRow(row, tenantId) {
    const checkbox = row.querySelector('input[name="tenant_assignment"]');
    checkbox.checked = !checkbox.checked;
    updateSelectedCount();
}

// Search functionality for individual tenant modal
let searchTimer;
function searchTenants() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        const searchTerm = document.getElementById('tenant-search').value.toLowerCase();
        const tableBody = document.getElementById('tenant-table-body');
        const rows = tableBody.querySelectorAll('tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const name = row.dataset.tenantName || '';
            const slug = row.dataset.tenantSlug || '';
            const domain = row.dataset.tenantDomain || '';
            
            const isVisible = name.includes(searchTerm) || 
                            slug.includes(searchTerm) || 
                            domain.includes(searchTerm);
            
            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });
        
        // Show/hide empty state
        const noTenantsMessage = document.getElementById('no-tenants-message');
        if (visibleCount === 0 && searchTerm !== '') {
            noTenantsMessage.classList.remove('hidden');
            tableBody.style.display = 'none';
        } else {
            noTenantsMessage.classList.add('hidden');
            tableBody.style.display = '';
        }
    }, 300);
}

// Pagination functionality (client-side for simplicity)
let currentPage = 1;
const itemsPerPage = 15;
let filteredRows = [];

function initializePagination() {
    const tableBody = document.getElementById('tenant-table-body');
    const rows = Array.from(tableBody.querySelectorAll('tr'));
    filteredRows = rows;
    
    if (rows.length > itemsPerPage) {
        document.getElementById('tenant-pagination').classList.remove('hidden');
        updatePagination();
    } else {
        document.getElementById('tenant-pagination').classList.add('hidden');
    }
}

function updatePagination() {
    const totalItems = filteredRows.length;
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, totalItems);
    
    // Update pagination info
    document.getElementById('page-start').textContent = totalItems > 0 ? startIndex + 1 : 0;
    document.getElementById('page-end').textContent = endIndex;
    document.getElementById('total-tenants').textContent = totalItems;
    
    // Show/hide rows
    filteredRows.forEach((row, index) => {
        row.style.display = (index >= startIndex && index < endIndex) ? '' : 'none';
    });
    
    // Update pagination buttons
    document.getElementById('prev-page').disabled = currentPage === 1;
    document.getElementById('next-page').disabled = currentPage === totalPages;
    
    // Update page numbers
    updatePageNumbers(totalPages);
}

function updatePageNumbers(totalPages) {
    const pageNumbers = document.getElementById('page-numbers');
    pageNumbers.innerHTML = '';
    
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currentPage - 2 && i <= currentPage + 2)) {
            const button = document.createElement('button');
            button.textContent = i;
            button.onclick = () => goToPage(i);
            button.className = `px-3 py-2 text-sm font-medium leading-tight ${
                i === currentPage 
                    ? 'text-teal-600 bg-teal-50 border border-teal-300 dark:border-gray-700 dark:bg-gray-700 dark:text-white' 
                    : 'text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white'
            }`;
            pageNumbers.appendChild(button);
        } else if (i === currentPage - 3 || i === currentPage + 3) {
            const span = document.createElement('span');
            span.textContent = '...';
            span.className = 'px-3 py-2 text-sm font-medium text-gray-500';
            pageNumbers.appendChild(span);
        }
    }
}

function changePage(direction) {
    const totalPages = Math.ceil(filteredRows.length / itemsPerPage);
    const newPage = currentPage + direction;
    
    if (newPage >= 1 && newPage <= totalPages) {
        currentPage = newPage;
        updatePagination();
    }
}

function goToPage(page) {
    currentPage = page;
    updatePagination();
}

async function saveTenantAssignments() {
    if (!currentUserId) return;
    
    const checkboxes = document.querySelectorAll('input[name="tenant_assignment"]:checked');
    const selectedTenants = Array.from(checkboxes).map(cb => cb.value);
    
    try {
        const response = await fetch(`/admin/users/${currentUserId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                tenant_ids: selectedTenants
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (window.showToast) {
                window.showToast('Tenant assignments updated successfully', 'success');
            }
            closeTenantModal();
            // Reload the page to reflect changes
            window.location.reload();
        } else {
            throw new Error(data.message || 'Failed to update tenant assignments');
        }
    } catch (error) {
        console.error('Error updating tenant assignments:', error);
        if (window.showToast) {
            window.showToast('Error updating tenant assignments: ' + error.message, 'error');
        }
    }
}

// Bulk selection functionality
function toggleSelectAll(selectAllCheckbox) {
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    userCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    updateBulkActions();
}

function updateBulkActions() {
    const selectedCheckboxes = document.querySelectorAll('.user-checkbox:checked');
    const selectedCount = selectedCheckboxes.length;
    const bulkActions = document.getElementById('bulk-actions');
    const selectedCountSpan = document.getElementById('selected-count');
    
    selectedCountSpan.textContent = selectedCount;
    
    if (selectedCount > 0) {
        bulkActions.classList.remove('hidden');
    } else {
        bulkActions.classList.add('hidden');
    }
    
    // Update select all checkbox state
    const allCheckboxes = document.querySelectorAll('.user-checkbox');
    const selectAllCheckbox = document.getElementById('select-all');
    
    if (selectedCount === 0) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = false;
    } else if (selectedCount === allCheckboxes.length) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = true;
    } else {
        selectAllCheckbox.indeterminate = true;
        selectAllCheckbox.checked = false;
    }
}

// Bulk tenant assignment functionality
function showBulkTenantModal() {
    const selectedCheckboxes = document.querySelectorAll('.user-checkbox:checked');
    const selectedCount = selectedCheckboxes.length;
    
    if (selectedCount === 0) {
        if (window.showToast) {
            window.showToast('Please select users first', 'warning');
        }
        return;
    }
    
    document.getElementById('bulk-selected-count').textContent = selectedCount;
    document.getElementById('bulk-tenant-modal').classList.remove('hidden');
    
    // Reset form
    document.querySelectorAll('input[name="bulk_tenant_assignment"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    document.querySelector('input[name="bulk_assignment_type"][value="add"]').checked = true;
    
    // Reset bulk selection states
    updateBulkSelectedCount();
    updateBulkSelectAllState();
}

function closeBulkTenantModal() {
    document.getElementById('bulk-tenant-modal').classList.add('hidden');
}

// Bulk tenant selection functions
function updateBulkSelectedCount() {
    const selectedCheckboxes = document.querySelectorAll('input[name="bulk_tenant_assignment"]:checked');
    // You could add a count display here if needed
    updateBulkSelectAllState();
}

function updateBulkSelectAllState() {
    const allCheckboxes = document.querySelectorAll('.bulk-tenant-checkbox');
    const selectedCheckboxes = document.querySelectorAll('.bulk-tenant-checkbox:checked');
    const selectAllCheckbox = document.getElementById('select-all-bulk-tenants');
    
    if (selectedCheckboxes.length === 0) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = false;
    } else if (selectedCheckboxes.length === allCheckboxes.length) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = true;
    } else {
        selectAllCheckbox.indeterminate = true;
        selectAllCheckbox.checked = false;
    }
}

function toggleAllBulkTenants(selectAllCheckbox) {
    const tenantCheckboxes = document.querySelectorAll('.bulk-tenant-checkbox');
    tenantCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    updateBulkSelectedCount();
}

function selectAllBulkTenants() {
    const tenantCheckboxes = document.querySelectorAll('.bulk-tenant-checkbox');
    const selectAllCheckbox = document.getElementById('select-all-bulk-tenants');
    tenantCheckboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
    selectAllCheckbox.checked = true;
    selectAllCheckbox.indeterminate = false;
    updateBulkSelectedCount();
}

function selectNoneBulkTenants() {
    const tenantCheckboxes = document.querySelectorAll('.bulk-tenant-checkbox');
    const selectAllCheckbox = document.getElementById('select-all-bulk-tenants');
    tenantCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    selectAllCheckbox.checked = false;
    selectAllCheckbox.indeterminate = false;
    updateBulkSelectedCount();
}

// Bulk tenant row click functionality
function toggleBulkTenantRow(row, tenantId) {
    const checkbox = row.querySelector('input[name="bulk_tenant_assignment"]');
    checkbox.checked = !checkbox.checked;
    updateBulkSelectedCount();
}

// Bulk tenant search functionality
function searchBulkTenants() {
    const searchTerm = document.getElementById('bulk-tenant-search').value.toLowerCase();
    const tableBody = document.getElementById('bulk-tenant-table-body');
    const rows = tableBody.querySelectorAll('tr');
    
    rows.forEach(row => {
        const name = row.dataset.tenantName || '';
        const slug = row.dataset.tenantSlug || '';
        
        const isVisible = name.includes(searchTerm) || slug.includes(searchTerm);
        row.style.display = isVisible ? '' : 'none';
    });
}

async function saveBulkTenantAssignments() {
    const selectedUserCheckboxes = document.querySelectorAll('.user-checkbox:checked');
    const selectedUserIds = Array.from(selectedUserCheckboxes).map(cb => cb.value);
    
    const selectedTenantCheckboxes = document.querySelectorAll('input[name="bulk_tenant_assignment"]:checked');
    const selectedTenantIds = Array.from(selectedTenantCheckboxes).map(cb => cb.value);
    
    const assignmentType = document.querySelector('input[name="bulk_assignment_type"]:checked').value;
    
    if (selectedTenantIds.length === 0) {
        if (window.showToast) {
            window.showToast('Please select at least one tenant', 'warning');
        }
        return;
    }
    
    try {
        const promises = selectedUserIds.map(async (userId) => {
            let tenantIds;
            
            if (assignmentType === 'add') {
                // Get current user tenants and add new ones
                const response = await fetch(`/api/admin/users/${userId}`);
                const data = await response.json();
                const currentTenantIds = data.data.tenants ? data.data.tenants.map(t => t.id) : [];
                tenantIds = [...new Set([...currentTenantIds, ...selectedTenantIds])];
            } else if (assignmentType === 'replace') {
                // Replace with selected tenants
                tenantIds = selectedTenantIds;
            } else if (assignmentType === 'remove') {
                // Get current user tenants and remove selected ones
                const response = await fetch(`/api/admin/users/${userId}`);
                const data = await response.json();
                const currentTenantIds = data.data.tenants ? data.data.tenants.map(t => t.id) : [];
                tenantIds = currentTenantIds.filter(id => !selectedTenantIds.includes(id));
            }
            
            return fetch(`/admin/users/${userId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    tenant_ids: tenantIds
                })
            });
        });
        
        await Promise.all(promises);
        
        if (window.showToast) {
            window.showToast(`Bulk tenant assignments updated for ${selectedUserIds.length} users`, 'success');
        }
        
        closeBulkTenantModal();
        window.location.reload();
        
    } catch (error) {
        console.error('Error updating bulk tenant assignments:', error);
        if (window.showToast) {
            window.showToast('Error updating bulk tenant assignments: ' + error.message, 'error');
        }
    }
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
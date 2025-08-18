@extends('layouts.admin')

@section('title', 'Tenant Management')

@section('header')
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Tenant Management</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Manage all tenants in the central SSO system
        </p>
    </div>
@endsection

@section('actions')
    <div class="flex space-x-3">
        @can('tenants.create')
            <button onclick="showBulkCreateModal()" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white h-10 px-4 py-2">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                Bulk Create 50 Tenants
            </button>
            <a href="{{ route('admin.tenants.create') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-teal-600 dark:bg-teal-500 text-white hover:bg-teal-700 dark:hover:bg-teal-600 h-10 px-4 py-2">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                New Tenant
            </a>
        @endcan
    </div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Search Bar (Always visible) -->
    <div class="flex items-center justify-between">
        <div class="flex-1 max-w-lg">
            <form method="GET" action="{{ route('admin.tenants.index') }}" class="relative">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" 
                           name="search" 
                           value="{{ request('search') }}"
                           placeholder="Search tenants by name, domain, plan, or industry..."
                           class="block w-full pl-10 pr-24 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 rounded-md text-sm text-gray-900 dark:text-white placeholder:text-gray-500 dark:placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    <div class="absolute inset-y-0 right-0 flex items-center">
                        @if(request('search'))
                            <a href="{{ route('admin.tenants.index') }}" 
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
                {{ $tenants->total() }} result(s) for "{{ request('search') }}"
            </div>
        @endif
        
        <!-- Debug sorting info -->
        @if(config('app.debug'))
            <div class="text-xs text-gray-500">
                Sort: {{ request('sort', 'updated_at') }} | Direction: {{ request('direction', 'desc') }}
            </div>
        @endif
    </div>

    @if($tenants->count() > 0)
        <!-- Tenants Table -->
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="overflow-hidden">
                <table class="w-full caption-bottom text-sm">
                    <thead class="[&_tr]:border-b border-gray-200 dark:border-gray-700">
                        <tr class="border-b border-gray-200 dark:border-gray-700 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50 ">
                            <th class="h-12 px-4 text-left align-middle font-medium text-gray-600 dark:text-gray-400 [&:has([role=checkbox])]:pr-0">
                                <button onclick="sortTable('name')" class="flex items-center space-x-1 hover:text-gray-900 dark:hover:text-white">
                                    <span>Tenant</span>
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
                            <th class="h-12 px-4 text-left align-middle font-medium text-gray-600 dark:text-gray-400 [&:has([role=checkbox])]:pr-0">
                                <button onclick="sortTable('domain')" class="flex items-center space-x-1 hover:text-gray-900 dark:hover:text-white">
                                    <span>Domain</span>
                                    @if(request('sort') === 'domain')
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
                            <th class="h-12 px-4 text-left align-middle font-medium text-gray-600 dark:text-gray-400 [&:has([role=checkbox])]:pr-0">
                                Users
                            </th>
                            <th class="h-12 px-4 text-left align-middle font-medium text-gray-600 dark:text-gray-400 [&:has([role=checkbox])]:pr-0">
                                <button onclick="sortTable('is_active')" class="flex items-center space-x-1 hover:text-gray-900 dark:hover:text-white">
                                    <span>Status</span>
                                    @if(request('sort') === 'is_active')
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
                            <th class="h-12 px-4 text-left align-middle font-medium text-gray-600 dark:text-gray-400 [&:has([role=checkbox])]:pr-0">
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
                            <th class="h-12 px-4 text-right align-middle font-medium text-gray-600 dark:text-gray-400 [&:has([role=checkbox])]:pr-0">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="[&_tr:last-child]:border-0">
                        @foreach($tenants as $tenant)
                            <tr class="border-b border-gray-200 dark:border-gray-700 transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50 ">
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                                {{ substr($tenant->name, 0, 2) }}
                                            </span>
                                        </div>
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white">
                                                {{ $tenant->name }}
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ $tenant->slug }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0 text-gray-900 dark:text-white">
                                    {{ $tenant->domain ?? 'Not set' }}
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-gray-200 dark:bg-gray-600 text-gray-800 dark:text-gray-200 hover:bg-gray-300 dark:hover:bg-gray-500">
                                        {{ $tenant->users_count }} users
                                    </span>
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 
                                        @if($tenant->is_active) border-transparent bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400 @else border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 @endif">
                                        {{ $tenant->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0 text-gray-600 dark:text-gray-400">
                                    <div class="text-sm">
                                        <div>{{ $tenant->updated_at->format('M d, Y') }}</div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400/70">{{ $tenant->updated_at->format('g:i A') }}</div>
                                    </div>
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    <div class="flex justify-end">
                                        <div class="relative inline-block text-left">
                                            <button type="button" onclick="toggleDropdown('dropdown-{{ $tenant->id }}')" 
                                                    class="inline-flex items-center justify-center rounded-md text-sm font-medium  transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white h-8 w-8">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                                </svg>
                                            </button>

                                            <div id="dropdown-{{ $tenant->id }}" class="hidden absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black dark:ring-gray-600 ring-opacity-5 focus:outline-none">
                                                <div class="py-1">
                                                    <!-- View Action -->
                                                    <a href="{{ route('admin.tenants.show', $tenant) }}" 
                                                       class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                        <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                        </svg>
                                                        View Details
                                                    </a>

                                                    @can('tenants.view')
                                                        <!-- Manage Users Action -->
                                                        <a href="{{ route('admin.tenants.users', $tenant) }}" 
                                                           class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                            <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                            </svg>
                                                            Manage Users
                                                        </a>
                                                    @endcan

                                                    @can('tenants.edit')
                                                        <!-- Edit Action -->
                                                        <a href="{{ route('admin.tenants.edit', $tenant) }}" 
                                                           class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                            <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                            </svg>
                                                            Edit Tenant
                                                        </a>

                                                        <!-- Divider -->
                                                        <div class="border-t border-gray-100 dark:border-gray-700"></div>

                                                        <!-- Toggle Status Action -->
                                                        <form method="POST" action="{{ route('admin.tenants.toggle', $tenant) }}" class="block">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="flex w-full items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                                <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    @if($tenant->is_active)
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
                                                                    @else
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                    @endif
                                                                </svg>
                                                                {{ $tenant->is_active ? 'Deactivate' : 'Activate' }}
                                                            </button>
                                                        </form>
                                                    @endcan

                                                    @can('tenants.delete')
                                                        @if($tenant->users_count == 0)
                                                            <!-- Delete Action -->
                                                            <button onclick="confirmDelete('{{ route('admin.tenants.destroy', $tenant) }}')" 
                                                                    class="flex w-full items-center px-4 py-2 text-sm text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30">
                                                                <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                </svg>
                                                                Delete Tenant
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
        @if($tenants->hasPages())
            <div class="mt-6">
                {{ $tenants->links() }}
            </div>
        @endif
    @else
        <!-- Empty State -->
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm">
            <div class="flex flex-col items-center justify-center py-12 px-6">
                @if(request('search'))
                    <!-- No Search Results -->
                    <svg class="mx-auto h-12 w-12 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">No search results</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 text-center">
                        No tenants found for "{{ request('search') }}". Try adjusting your search terms.
                    </p>
                    <div class="mt-6">
                        <a href="{{ route('admin.tenants.index') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium  transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white h-10 px-4 py-2">
                            Clear Search
                        </a>
                    </div>
                @else
                    <!-- No Tenants -->
                    <svg class="mx-auto h-12 w-12 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m14 0v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5m-4 0h4"></path>
                    </svg>
                    <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">No tenants</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 text-center">Get started by creating a new tenant application.</p>
                    @can('tenants.create')
                        <div class="mt-6">
                            <a href="{{ route('admin.tenants.create') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium  transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-teal-600 dark:bg-teal-500 text-white hover:bg-teal-700 dark:hover:bg-teal-600 h-10 px-4 py-2">
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                New Tenant
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

<!-- Bulk Create Modal -->
<div id="bulkCreateModal" class="fixed inset-0 bg-black/80 z-50 hidden">
    <div class="fixed left-[50%] top-[50%] translate-x-[-50%] translate-y-[-50%] grid w-full max-w-lg gap-4 border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 shadow-lg duration-200 rounded-lg">
        <div class="flex flex-col space-y-1.5 text-center sm:text-left">
            <h2 class="text-lg font-semibold leading-none tracking-tight text-gray-900 dark:text-white">Bulk Create Tenants</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400">This will create 50 realistic tenants for testing purposes.</p>
        </div>
        
        <div class="space-y-3">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                <p class="mb-2">This will create tenants with:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Various plans (starter, basic, premium, pro, enterprise)</li>
                    <li>Different industries and regions</li>
                    <li>Realistic company names</li>
                    <li>Plan-specific and industry-specific features</li>
                    <li>90% active, 10% inactive for testing</li>
                </ul>
            </div>
        </div>
        
        <div class="flex flex-col-reverse sm:flex-row sm:justify-end sm:space-x-2">
            <button onclick="hideBulkCreateModal()" class="inline-flex items-center justify-center rounded-md text-sm font-medium  transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white h-10 px-4 py-2 mt-2 sm:mt-0">
                Cancel
            </button>
            <button onclick="bulkCreateTenants()" class="inline-flex items-center justify-center rounded-md text-sm font-medium  transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-teal-600 dark:bg-teal-500 text-white hover:bg-teal-700 dark:hover:bg-teal-600 h-10 px-4 py-2">
                Create 50 Tenants
            </button>
        </div>
    </div>
</div>

<script>
function showBulkCreateModal() {
    document.getElementById('bulkCreateModal').classList.remove('hidden');
}

function hideBulkCreateModal() {
    document.getElementById('bulkCreateModal').classList.add('hidden');
}

// Close modal when clicking on backdrop
document.addEventListener('click', function(event) {
    const modal = document.getElementById('bulkCreateModal');
    const modalContent = modal.querySelector('.grid');
    
    // If modal is visible and click is on the backdrop (not the content)
    if (!modal.classList.contains('hidden') && 
        event.target === modal && 
        !modalContent.contains(event.target)) {
        hideBulkCreateModal();
    }
});

async function bulkCreateTenants() {
    if (!confirm('This will create 50 tenants. Are you sure?')) {
        return;
    }
    
    // Show loading toast
    if (window.showToast) {
        window.showToast('Creating 50 tenants...', 'info');
    }
    
    try {
        const response = await fetch('{{ route("admin.tenants.bulk-create") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ count: 50 })
        });
        
        const data = await response.json();
        
        if (data.success) {
            hideBulkCreateModal();
            if (window.showToast) {
                window.showToast(data.message, 'success');
            }
            // Reload page after a short delay to show the success message
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            if (window.showToast) {
                window.showToast('Error: ' + (data.message || 'Failed to bulk create tenants'), 'error');
            }
        }
    } catch (error) {
        if (window.showToast) {
            window.showToast('Error: ' + error.message, 'error');
        }
    }
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
    const isModalContent = event.target.closest('#bulkCreateModal .grid');
    const isModalButton = event.target.closest('[onclick*="Modal"]');
    
    // Don't close dropdowns if clicking on modal content or modal buttons
    if (!isDropdownButton && !isDropdownContent && !isModalContent && !isModalButton) {
        document.querySelectorAll('[id^="dropdown-"]').forEach(dropdown => {
            dropdown.classList.add('hidden');
        });
    }
});

function confirmDelete(url) {
    if (confirm('Are you sure you want to delete this tenant? This action cannot be undone.')) {
        const form = document.getElementById('deleteForm');
        form.action = url;
        form.submit();
        
        // Show loading toast
        if (window.showToast) {
            window.showToast('Deleting tenant...', 'info');
        }
    }
}

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

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="search"]');
    const searchForm = document.querySelector('form[action*="tenants"]');
    
    if (searchInput && searchForm) {
        // Submit on Enter key
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchForm.submit();
            }
        });
        
        // Optional: Submit on search button click (already handled by form submit)
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
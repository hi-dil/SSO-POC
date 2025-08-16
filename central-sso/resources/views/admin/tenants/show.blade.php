@extends('layouts.admin')

@section('title', 'Tenant Details')

@section('header')
    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
        {{ $tenant->name }}
    </h2>
    <p class="mt-1 text-sm text-gray-500">
        Tenant details and statistics
    </p>
@endsection

@section('actions')
    <div class="flex space-x-3">
        @can('tenants.view')
            <a href="{{ route('admin.tenants.users', $tenant) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                </svg>
                Manage Users
            </a>
        @endcan
        @can('tenants.edit')
            <a href="{{ route('admin.tenants.edit', $tenant) }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Edit
            </a>
        @endcan
        <a href="{{ route('admin.tenants.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Tenants
        </a>
    </div>
@endsection

@section('content')
<div class="px-4 py-5 sm:p-6">
    <!-- Tenant Overview -->
    <div class="mb-8">
        <div class="bg-white overflow-hidden">
            <div class="px-4 py-5 sm:p-0">
                <dl class="sm:divide-y sm:divide-gray-200">
                    <!-- Basic Info -->
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $tenant->name }}</dd>
                    </div>
                    
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Slug</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <code class="bg-gray-100 px-2 py-1 rounded">{{ $tenant->slug }}</code>
                        </dd>
                    </div>
                    
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Domain</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            @if($tenant->domain)
                                <a href="https://{{ $tenant->domain }}" target="_blank" class="text-indigo-600 hover:text-indigo-500">
                                    {{ $tenant->domain }}
                                    <svg class="inline w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                </a>
                            @else
                                <span class="text-gray-400">Not set</span>
                            @endif
                        </dd>
                    </div>
                    
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                @if($tenant->is_active) bg-green-100 text-green-800 @else bg-red-100 text-red-800 @endif">
                                {{ $tenant->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </dd>
                    </div>
                    
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Max Users</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $tenant->max_users ?? 'Unlimited' }}
                        </dd>
                    </div>
                    
                    @if($tenant->description)
                        <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4">
                            <dt class="text-sm font-medium text-gray-500">Description</dt>
                            <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $tenant->description }}</dd>
                        </div>
                    @endif
                    
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4">
                        <dt class="text-sm font-medium text-gray-500">Created</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            {{ $tenant->created_at->format('F j, Y \a\t g:i A') }}
                            <span class="text-gray-500">({{ $tenant->created_at->diffForHumans() }})</span>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="mb-8">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Statistics</h3>
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Total Users -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $stats['total_users'] }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Admin Users -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Admin Users</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $stats['admin_users'] }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Regular Users -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Regular Users</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $stats['regular_users'] }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Last Activity -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Last Activity</dt>
                                <dd class="text-sm font-medium text-gray-900">
                                    @if($stats['last_login'])
                                        {{ \Carbon\Carbon::parse($stats['last_login'])->diffForHumans() }}
                                    @else
                                        No activity
                                    @endif
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Users -->
    @if($tenant->users->count() > 0)
        <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Recent Users</h3>
                @can('tenants.view')
                    <a href="{{ route('admin.tenants.users', $tenant) }}" class="text-sm text-indigo-600 hover:text-indigo-500">
                        View all users →
                    </a>
                @endcan
            </div>
            
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <ul class="divide-y divide-gray-200">
                    @foreach($tenant->users->take(5) as $user)
                        <li>
                            <div class="px-4 py-4 flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                            <span class="text-sm font-medium text-gray-700">
                                                {{ substr($user->name, 0, 1) }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    @foreach($user->roles as $role)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            @if($role->name === 'super-admin') bg-red-100 text-red-800 
                                            @elseif($role->name === 'tenant-admin') bg-yellow-100 text-yellow-800 
                                            @else bg-blue-100 text-blue-800 @endif">
                                            {{ ucwords(str_replace('-', ' ', $role->name)) }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Quick Actions -->
    @can('tenants.edit')
        <div class="border-t border-gray-200 pt-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
            <div class="flex flex-wrap gap-3">
                <form method="POST" action="{{ route('admin.tenants.toggle', $tenant) }}" class="inline">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ $tenant->is_active ? 'Deactivate' : 'Activate' }} Tenant
                    </button>
                </form>
                
                @can('tenants.delete')
                    @if($tenant->users()->count() == 0)
                        <button onclick="confirmDelete('{{ route('admin.tenants.destroy', $tenant) }}')" class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            Delete Tenant
                        </button>
                    @endif
                @endcan
            </div>
        </div>
    @endcan
</div>

<!-- Delete Form Template -->
<form id="deleteForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
function confirmDelete(url) {
    if (confirm('Are you sure you want to delete this tenant? This action cannot be undone.')) {
        const form = document.getElementById('deleteForm');
        form.action = url;
        form.submit();
    }
}
</script>
@endsection
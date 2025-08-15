@extends('layouts.admin')

@section('title', 'Tenant Users')

@section('header')
    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
        {{ $tenant->name }} - Users
    </h2>
    <p class="mt-1 text-sm text-gray-500">
        Manage users assigned to this tenant
    </p>
@endsection

@section('actions')
    <div class="flex space-x-3">
        <a href="{{ route('admin.tenants.show', $tenant) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            View Tenant
        </a>
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
    <!-- Assign New User -->
    @if($availableUsers->count() > 0)
        <div class="mb-8 bg-gray-50 p-4 rounded-lg">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Assign User to Tenant</h3>
            <form method="POST" action="{{ route('admin.tenants.assign-user', $tenant) }}" class="flex items-end space-x-4">
                @csrf
                <div class="flex-1">
                    <label for="user_id" class="block text-sm font-medium text-gray-700">Select User</label>
                    <select name="user_id" id="user_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('user_id') border-red-300 @enderror">
                        <option value="">Choose a user...</option>
                        @foreach($availableUsers as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    Assign User
                </button>
            </form>
        </div>
    @endif

    <!-- Users List -->
    @if($users->count() > 0)
        <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            User
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Roles
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Assigned
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Last Login
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($users as $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
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
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($user->roles as $role)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            @if($role->name === 'super-admin') bg-red-100 text-red-800 
                                            @elseif($role->name === 'tenant-admin') bg-yellow-100 text-yellow-800 
                                            @else bg-blue-100 text-blue-800 @endif">
                                            {{ ucwords(str_replace('-', ' ', $role->name)) }}
                                        </span>
                                    @empty
                                        <span class="text-gray-400 text-sm">No roles assigned</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $user->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $user->updated_at->diffForHumans() }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                @can('manage-tenants')
                                    <form method="POST" action="{{ route('admin.tenants.remove-user', [$tenant, $user]) }}" class="inline" onsubmit="return confirm('Are you sure you want to remove this user from the tenant?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">
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
            <h3 class="mt-2 text-sm font-medium text-gray-900">No users assigned</h3>
            <p class="mt-1 text-sm text-gray-500">
                @if($availableUsers->count() > 0)
                    Assign users to this tenant using the form above.
                @else
                    All users are already assigned to this tenant.
                @endif
            </p>
        </div>
    @endif

    <!-- Tenant Usage Summary -->
    <div class="mt-8 bg-gray-50 p-4 rounded-lg">
        <h3 class="text-lg font-medium text-gray-900 mb-3">Usage Summary</h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="text-center">
                <div class="text-2xl font-bold text-indigo-600">{{ $users->total() }}</div>
                <div class="text-sm text-gray-500">Assigned Users</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold text-gray-600">{{ $availableUsers->count() }}</div>
                <div class="text-sm text-gray-500">Available Users</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold {{ $tenant->max_users && $users->total() >= $tenant->max_users ? 'text-red-600' : 'text-green-600' }}">
                    @if($tenant->max_users)
                        {{ $tenant->max_users - $users->total() }}
                    @else
                        ∞
                    @endif
                </div>
                <div class="text-sm text-gray-500">
                    @if($tenant->max_users)
                        Remaining Slots
                    @else
                        Unlimited
                    @endif
                </div>
            </div>
        </div>
        
        @if($tenant->max_users && $users->total() >= $tenant->max_users)
            <div class="mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
@endsection
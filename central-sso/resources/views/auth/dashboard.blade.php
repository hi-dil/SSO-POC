@extends('layouts.admin')

@section('title', 'Dashboard')

@section('header')
    <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
        Welcome, {{ auth()->user()->name }}!
    </h2>
    <p class="mt-1 text-sm text-gray-500">
        Choose your next action
    </p>
@endsection

@section('content')
<div class="px-4 py-5 sm:p-6">
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        
        <!-- Admin Dashboard Option -->
        @can('manage-tenants')
            <div class="bg-white overflow-hidden shadow rounded-lg border-2 border-indigo-200 hover:border-indigo-300 transition-colors">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <h3 class="text-lg font-medium text-gray-900">Admin Dashboard</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Manage tenants, users, and system settings
                            </p>
                        </div>
                    </div>
                    <div class="mt-6">
                        <a href="{{ route('admin.tenants.index') }}" 
                           class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Go to Admin Panel
                        </a>
                    </div>
                </div>
            </div>
        @endcan

        <!-- Tenant Applications -->
        @if($tenants->count() > 0)
            <div class="bg-white overflow-hidden shadow rounded-lg border-2 border-blue-200 hover:border-blue-300 transition-colors">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <h3 class="text-lg font-medium text-gray-900">Tenant Applications</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                Access your tenant applications
                            </p>
                        </div>
                    </div>
                    <div class="mt-6">
                        @if($tenants->count() === 1)
                            <form method="POST" action="{{ route('tenant.access') }}">
                                @csrf
                                <input type="hidden" name="tenant_slug" value="{{ $tenants->first()->slug }}">
                                <button type="submit" 
                                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Access {{ $tenants->first()->name }}
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('tenant.access') }}">
                                @csrf
                                <div class="mb-3">
                                    <label for="tenant_slug" class="block text-sm font-medium text-gray-700">Select Tenant</label>
                                    <select name="tenant_slug" id="tenant_slug" required 
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                        <option value="">Choose a tenant...</option>
                                        @foreach($tenants as $tenant)
                                            <option value="{{ $tenant->slug }}">{{ $tenant->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="submit" 
                                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Access Selected Tenant
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- User Info Section -->
    <div class="mt-8 bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Account Information</h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <span class="text-sm font-medium text-gray-500">Name</span>
                <p class="text-sm text-gray-900">{{ auth()->user()->name }}</p>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-500">Email</span>
                <p class="text-sm text-gray-900">{{ auth()->user()->email }}</p>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-500">Tenant Access</span>
                <p class="text-sm text-gray-900">{{ $tenants->count() }} tenant(s)</p>
            </div>
        </div>
        
        @if($tenants->count() > 0)
            <div class="mt-4">
                <span class="text-sm font-medium text-gray-500">Available Tenants</span>
                <div class="mt-1 flex flex-wrap gap-2">
                    @foreach($tenants as $tenant)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $tenant->name }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
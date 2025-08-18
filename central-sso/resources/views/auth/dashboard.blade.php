@extends('layouts.admin')

@section('title', 'Dashboard')

@section('header')
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Welcome, {{ auth()->user()->name }}!</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Manage your SSO ecosystem and access tenant applications
        </p>
    </div>
@endsection

@section('content')
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden transition-colors duration-200">
    <!-- Header -->
    <div class="px-6 py-4 bg-gradient-to-r from-teal-custom to-teal-custom-light">
        <h1 class="text-2xl font-semibold text-white">
            Central SSO Dashboard
        </h1>
    </div>

    <!-- Content -->
    <div class="p-6">
        <!-- Quick Actions Section -->
        @if($tenants->count() > 0 || auth()->user()->can('manage-tenants'))
            <div class="mb-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Quick Actions
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Admin Dashboard Option -->
                    @can('manage-tenants')
                        <div class="bg-gradient-to-br from-teal-50 to-cyan-50 dark:from-teal-900/20 dark:to-cyan-900/20 rounded-lg p-6 border border-teal-200 dark:border-teal-700 hover:shadow-md transition-all duration-200">
                            <div class="flex items-center mb-3">
                                <div class="w-10 h-10 bg-gradient-to-r from-teal-custom to-teal-custom-light rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Admin Dashboard</h3>
                            </div>
                            <p class="text-gray-600 dark:text-gray-300 leading-relaxed mb-4">
                                Manage tenants, users, roles, and system settings. Full administrative control over the SSO ecosystem.
                            </p>
                            <a href="{{ route('admin.tenants.index') }}" 
                               class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-gradient-to-r from-teal-custom to-teal-custom-light text-white hover:from-teal-600 hover:to-cyan-600 h-10 px-4 py-2 w-full">
                                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                                </svg>
                                Go to Admin Panel
                            </a>
                        </div>
                    @endcan

                    <!-- Tenant Applications -->
                    @if($tenants->count() > 0)
                        <div class="bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-lg p-6 border border-purple-200 dark:border-purple-700 hover:shadow-md transition-all duration-200">
                            <div class="flex items-center mb-3">
                                <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-indigo-500 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tenant Applications</h3>
                            </div>
                            <p class="text-gray-600 dark:text-gray-300 leading-relaxed mb-4">
                                Access your authorized tenant applications. You have access to {{ $tenants->count() }} tenant(s).
                            </p>
                            
                            @if($tenants->count() === 1)
                                <form method="POST" action="{{ route('tenant.access') }}">
                                    @csrf
                                    <input type="hidden" name="tenant_slug" value="{{ $tenants->first()->slug }}">
                                    <button type="submit" 
                                            class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-gradient-to-r from-purple-500 to-indigo-500 text-white hover:from-purple-600 hover:to-indigo-600 h-10 px-4 py-2 w-full">
                                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                        Access {{ $tenants->first()->name }}
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('tenant.access') }}">
                                    @csrf
                                    <div class="space-y-4">
                                        <div class="space-y-2">
                                            <label for="tenant_slug" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-gray-900 dark:text-white">Select Tenant</label>
                                            <select name="tenant_slug" id="tenant_slug" required 
                                                    class="flex h-10 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-gray-500 dark:placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                                <option value="">Choose a tenant...</option>
                                                @foreach($tenants as $tenant)
                                                    <option value="{{ $tenant->slug }}">{{ $tenant->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <button type="submit" 
                                                class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-gradient-to-r from-purple-500 to-indigo-500 text-white hover:from-purple-600 hover:to-indigo-600 h-10 px-4 py-2 w-full">
                                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                            </svg>
                                            Access Selected Tenant
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- User Information Card -->
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-6 transition-colors duration-200">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                Account Information
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-3">
                    <div class="flex items-center">
                        <span class="font-medium text-gray-700 dark:text-gray-300 min-w-[120px]">Name:</span>
                        <span class="text-gray-900 dark:text-white">{{ auth()->user()->name }}</span>
                    </div>
                    <div class="flex items-center">
                        <span class="font-medium text-gray-700 dark:text-gray-300 min-w-[120px]">Email:</span>
                        <span class="text-gray-900 dark:text-white">{{ auth()->user()->email }}</span>
                    </div>
                    <div class="flex items-center">
                        <span class="font-medium text-gray-700 dark:text-gray-300 min-w-[120px]">User ID:</span>
                        <span class="text-gray-900 dark:text-white font-mono text-sm">{{ auth()->user()->id }}</span>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <div class="flex items-center">
                        <span class="font-medium text-gray-700 dark:text-gray-300 min-w-[120px]">Role:</span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-teal-100 dark:bg-teal-900 text-teal-800 dark:text-teal-200">
                            @if(auth()->user()->can('manage-tenants'))
                                System Administrator
                            @else
                                User
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-300 mb-2 block">Tenant Access:</span>
                        <div class="flex flex-wrap gap-2">
                            @if($tenants->count() > 0)
                                @foreach($tenants as $tenant)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200">
                                        {{ $tenant->name }}
                                    </span>
                                @endforeach
                            @else
                                <span class="text-sm text-gray-500 dark:text-gray-400">No tenant access assigned</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- SSO Features Section -->
        <div class="border-t border-gray-200 dark:border-gray-600 pt-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                Central SSO Features
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6">
                <!-- Centralized Authentication -->
                <div class="bg-gradient-to-br from-teal-50 to-cyan-50 dark:from-teal-900/20 dark:to-cyan-900/20 rounded-lg p-6 border border-teal-200 dark:border-teal-700 hover:shadow-md transition-all duration-200">
                    <div class="flex items-center mb-3">
                        <div class="w-10 h-10 bg-gradient-to-r from-teal-custom to-teal-custom-light rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Centralized Authentication</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                        Single point of authentication for all tenant applications. Manage user access and permissions centrally.
                    </p>
                </div>
                
                <!-- Multi-Tenant Management -->
                <div class="bg-gradient-to-br from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 rounded-lg p-6 border border-purple-200 dark:border-purple-700 hover:shadow-md transition-all duration-200">
                    <div class="flex items-center mb-3">
                        <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-indigo-500 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m14 0v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Multi-Tenant Management</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                        Seamless access to {{ $tenants->count() }} tenant application(s). Switch between tenants with unified authentication.
                    </p>
                </div>
                
                <!-- Enterprise Security -->
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg p-6 border border-green-200 dark:border-green-700 hover:shadow-md transition-all duration-200">
                    <div class="flex items-center mb-3">
                        <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-emerald-500 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Enterprise Security</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                        JWT token-based authentication with configurable expiration. Enterprise-grade security protocols and audit trails.
                    </p>
                </div>
                
                <!-- Role-Based Access Control -->
                <div class="bg-gradient-to-br from-orange-50 to-yellow-50 dark:from-orange-900/20 dark:to-yellow-900/20 rounded-lg p-6 border border-orange-200 dark:border-orange-700 hover:shadow-md transition-all duration-200">
                    <div class="flex items-center mb-3">
                        <div class="w-10 h-10 bg-gradient-to-r from-orange-500 to-yellow-500 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Role-Based Access Control</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                        Granular permission system with roles and policies. Control access to features and data at multiple levels.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
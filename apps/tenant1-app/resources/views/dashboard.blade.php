@extends('layouts.app')

@section('content')
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden transition-colors duration-200">
    <!-- Header -->
    <div class="px-6 py-4 bg-gradient-to-r from-teal-custom to-teal-custom-light">
        <h1 class="text-2xl font-semibold text-white">
            Welcome to {{ config('app.name') }} Dashboard
        </h1>
    </div>

    <!-- Content -->
    <div class="p-6">
        <!-- User Information Card -->
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-6 transition-colors duration-200">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                User Information
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-3">
                    <div class="flex items-center">
                        <span class="font-medium text-gray-700 dark:text-gray-300 min-w-[120px]">Name:</span>
                        <span class="text-gray-900 dark:text-white">{{ $user['name'] ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-center">
                        <span class="font-medium text-gray-700 dark:text-gray-300 min-w-[120px]">Email:</span>
                        <span class="text-gray-900 dark:text-white">{{ $user['email'] ?? 'N/A' }}</span>
                    </div>
                    <div class="flex items-center">
                        <span class="font-medium text-gray-700 dark:text-gray-300 min-w-[120px]">User ID:</span>
                        <span class="text-gray-900 dark:text-white font-mono text-sm">{{ $user['id'] ?? 'N/A' }}</span>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <div class="flex items-center">
                        <span class="font-medium text-gray-700 dark:text-gray-300 min-w-[120px]">Current Tenant:</span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                            {{ $user['current_tenant'] ?? env('TENANT_SLUG') }}
                        </span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-300 mb-2 block">Available Tenants:</span>
                        <div class="flex flex-wrap gap-2">
                            @if(isset($user['tenants']) && is_array($user['tenants']))
                                @foreach($user['tenants'] as $tenant)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $tenant == ($user['current_tenant'] ?? env('TENANT_SLUG')) ? 'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200' : 'bg-gray-100 dark:bg-gray-600 text-gray-700 dark:text-gray-300' }}">
                                        {{ $tenant }}
                                        @if($tenant == ($user['current_tenant'] ?? env('TENANT_SLUG')))
                                            <svg class="w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        @endif
                                    </span>
                                @endforeach
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200">
                                    {{ env('TENANT_SLUG') }}
                                    <svg class="w-3 h-3 ml-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Features Section -->
        <div class="border-t border-gray-200 dark:border-gray-600 pt-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                Available Features
            </h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6">
                <!-- SSO Authentication -->
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-6 border border-blue-200 dark:border-blue-700 hover:shadow-md transition-all duration-200">
                    <div class="flex items-center mb-3">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-800 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">SSO Authentication</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                        You've successfully authenticated using our Single Sign-On system. Access multiple tenants with a single login seamlessly.
                    </p>
                </div>
                
                <!-- Multi-Tenant Access -->
                <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg p-6 border border-purple-200 dark:border-purple-700 hover:shadow-md transition-all duration-200">
                    <div class="flex items-center mb-3">
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-800 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m14 0v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Multi-Tenant Access</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                        Your account has access to {{ count($user['tenants'] ?? [env('TENANT_SLUG')]) }} tenant(s). Switch between them seamlessly with unified authentication.
                    </p>
                </div>
                
                <!-- Secure JWT Tokens -->
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg p-6 border border-green-200 dark:border-green-700 hover:shadow-md transition-all duration-200">
                    <div class="flex items-center mb-3">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-800 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Secure JWT Tokens</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                        Your session is protected with JWT tokens that expire after {{ env('JWT_TTL', 60) }} minutes for enhanced security and data protection.
                    </p>
                </div>
                
                <!-- Tenant Isolation -->
                <div class="bg-gradient-to-br from-orange-50 to-red-50 dark:from-orange-900/20 dark:to-red-900/20 rounded-lg p-6 border border-orange-200 dark:border-orange-700 hover:shadow-md transition-all duration-200">
                    <div class="flex items-center mb-3">
                        <div class="w-10 h-10 bg-orange-100 dark:bg-orange-800 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Tenant Isolation</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-300 leading-relaxed">
                        Each tenant has its own isolated database and configuration, ensuring complete data separation and privacy.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
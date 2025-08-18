<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Successful - Central SSO</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-50 to-emerald-100 min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl w-full space-y-8">
            <div class="bg-white rounded-lg shadow-xl p-8">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                        <svg class="h-6 w-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                        Login Successful!
                    </h2>
                    <p class="mt-2 text-center text-sm text-gray-600">
                        Welcome, <span class="font-medium text-green-600">{{ $user->name }}</span>
                    </p>
                </div>
                
                <div class="mt-8 space-y-6">
                    <div class="bg-gray-50 p-4 rounded-md">
                        <h3 class="text-lg font-medium text-gray-900 mb-3">User Information</h3>
                        <dl class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Email</dt>
                                <dd class="text-sm text-gray-900">{{ $user->email }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Current Tenant</dt>
                                <dd class="text-sm text-gray-900">{{ $tenant_slug }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Available Tenants</dt>
                                <dd class="text-sm text-gray-900">{{ $user->tenants->pluck('slug')->join(', ') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Role</dt>
                                <dd class="text-sm text-gray-900">{{ $user->is_admin ? 'Administrator' : 'User' }}</dd>
                            </div>
                        </dl>
                    </div>
                    
                    <div class="bg-teal-50 p-4 rounded-md">
                        <h3 class="text-lg font-medium text-gray-900 mb-3">JWT Token</h3>
                        <div class="bg-white p-3 rounded border">
                            <code class="text-xs text-gray-800 break-all">{{ $token }}</code>
                        </div>
                        <p class="text-sm text-gray-600 mt-2">
                            This token can be used to authenticate with tenant applications.
                        </p>
                    </div>
                    
                    <div class="bg-cyan-50 p-4 rounded-md">
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Next Steps</h3>
                        <p class="text-sm text-gray-600 mb-3">
                            You can now access your tenant application:
                        </p>
                        
                        @php
                            $tenantUrl = $tenant_urls[$tenant_slug] ?? null;
                        @endphp
                        
                        @if($tenantUrl)
                            <div class="space-y-2">
                                <a href="{{ $tenantUrl }}/auth/callback?token={{ $token }}" 
                                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-teal-600 hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500">
                                    Go to {{ ucfirst($tenant_slug) }} App
                                    <svg class="ml-2 -mr-1 w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                                
                                <p class="text-xs text-gray-500">
                                    URL: {{ $tenantUrl }}/auth/callback?token={{ substr($token, 0, 20) }}...
                                </p>
                            </div>
                        @endif
                    </div>
                    
                    <div class="flex space-x-3">
                        @if($user->tenants->count() > 1)
                            <a href="{{ route('tenant.select') }}" 
                               class="flex-1 py-2 px-4 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 text-center">
                                Switch Tenant
                            </a>
                        @endif
                        <a href="{{ route('main.logout') }}" 
                           class="flex-1 py-2 px-4 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 text-center">
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
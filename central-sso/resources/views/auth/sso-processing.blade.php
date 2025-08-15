<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSO Authentication - {{ $tenant->name ?? $tenant->id }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full space-y-8">
            <!-- Loading State -->
            <div id="loading-state">
                <div class="text-center">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
                    <h2 class="mt-6 text-center text-2xl font-bold text-gray-900">
                        Checking Authentication...
                    </h2>
                    <p class="mt-2 text-center text-sm text-gray-600">
                        Please wait while we verify your login status for <span class="font-medium text-indigo-600">{{ $tenant->name ?? $tenant->id }}</span>
                    </p>
                </div>
            </div>

            <!-- Error State (hidden by default) -->
            <div id="error-state" class="hidden">
                <div class="rounded-md bg-red-50 p-4">
                    <div class="flex">
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800" id="error-message">
                                Authentication check failed. Please try again.
                            </h3>
                            <div class="mt-4">
                                <button onclick="checkAuthentication()" 
                                        class="text-sm bg-red-100 hover:bg-red-200 text-red-800 font-medium py-2 px-4 rounded">
                                    Try Again
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Access Denied State (hidden by default) -->
            <div id="access-denied-state" class="hidden">
                <div class="rounded-md bg-yellow-50 p-4">
                    <div class="flex">
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800" id="access-denied-message">
                                Access Denied
                            </h3>
                            <div class="mt-4">
                                <a href="{{ $callback_url }}" 
                                   class="text-sm bg-yellow-100 hover:bg-yellow-200 text-yellow-800 font-medium py-2 px-4 rounded">
                                    Back to {{ $tenant->name ?? $tenant->id }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Login Form (hidden by default) -->
            <div id="login-form-state" class="hidden">
                <div>
                    <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                        Central SSO Login
                    </h2>
                    <p class="mt-2 text-center text-sm text-gray-600">
                        Sign in to <span class="font-medium text-indigo-600">{{ $tenant->name ?? $tenant->id }}</span>
                    </p>
                </div>
                
                <form class="mt-8 space-y-6" action="{{ route('sso.login') }}" method="POST">
                    @csrf
                    <input type="hidden" name="tenant_slug" value="{{ $tenant_slug }}">
                    <input type="hidden" name="callback_url" value="{{ $callback_url }}">
                    
                    <div class="rounded-md shadow-sm -space-y-px">
                        <div>
                            <label for="email" class="sr-only">Email address</label>
                            <input id="email" name="email" type="email" autocomplete="email" required 
                                   class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                                   placeholder="Email address">
                        </div>
                        <div>
                            <label for="password" class="sr-only">Password</label>
                            <input id="password" name="password" type="password" autocomplete="current-password" required 
                                   class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                                   placeholder="Password">
                        </div>
                    </div>
                    
                    <div>
                        <button type="submit" 
                                class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Sign in
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <a href="{{ $callback_url }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                            Back to {{ $tenant->name ?? $tenant->id }}
                        </a>
                    </div>
                </form>
                
                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-gray-100 text-gray-500">Test Credentials</span>
                        </div>
                    </div>
                    
                    <div class="mt-6 grid grid-cols-1 gap-3">
                        <div class="text-sm text-gray-600">
                            @if($tenant_slug == 'tenant1')
                                <p><strong>Regular User:</strong> user@tenant1.com / password</p>
                                <p><strong>Admin:</strong> admin@tenant1.com / password</p>
                            @elseif($tenant_slug == 'tenant2')
                                <p><strong>Regular User:</strong> user@tenant2.com / password</p>
                                <p><strong>Admin:</strong> admin@tenant2.com / password</p>
                            @endif
                            <p><strong>Super Admin:</strong> superadmin@sso.com / password</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showState(stateName) {
            // Hide all states
            document.getElementById('loading-state').classList.add('hidden');
            document.getElementById('error-state').classList.add('hidden');
            document.getElementById('access-denied-state').classList.add('hidden');
            document.getElementById('login-form-state').classList.add('hidden');
            
            // Show the requested state
            document.getElementById(stateName + '-state').classList.remove('hidden');
        }

        function checkAuthentication() {
            console.log('Checking authentication status...');
            showState('loading');
            
            const checkUrl = new URL('{{ route("sso.check", ["tenant_slug" => $tenant_slug]) }}');
            @if($callback_url)
            checkUrl.searchParams.append('callback_url', '{{ $callback_url }}');
            @endif
            
            fetch(checkUrl.toString(), {
                method: 'GET',
                credentials: 'include', // Include cookies in the request
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Authentication check response:', data);
                
                if (data.authenticated) {
                    if (data.access_denied) {
                        // User is authenticated but doesn't have access to this tenant
                        document.getElementById('access-denied-message').textContent = data.message || 'You do not have access to this tenant.';
                        showState('access-denied');
                    } else if (data.redirect_to) {
                        // User is authenticated and has access - redirect to tenant app
                        console.log('Redirecting to:', data.redirect_to);
                        window.location.href = data.redirect_to;
                    } else {
                        // Fallback: show login form
                        showState('login-form');
                    }
                } else {
                    // User is not authenticated - show login form
                    showState('login-form');
                }
            })
            .catch(error => {
                console.error('Authentication check failed:', error);
                document.getElementById('error-message').textContent = 'Failed to check authentication: ' + error.message;
                showState('error');
            });
        }

        // Start the authentication check when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Add a small delay to show the loading state briefly
            setTimeout(checkAuthentication, 500);
        });
    </script>
</body>
</html>
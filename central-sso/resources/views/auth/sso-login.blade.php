<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSO Login - {{ $tenant->name ?? $tenant->id }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full space-y-8">
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
                
                @if($errors->any())
                    <div class="rounded-md bg-red-50 p-4">
                        <div class="flex">
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">
                                    @foreach($errors->all() as $error)
                                        {{ $error }}
                                    @endforeach
                                </h3>
                            </div>
                        </div>
                    </div>
                @endif
                
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="email" class="sr-only">Email address</label>
                        <input id="email" name="email" type="email" autocomplete="email" required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                               placeholder="Email address" value="{{ old('email') }}">
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
                            <p><strong>Regular User:</strong> user@tenant1.com / tenant123</p>
                            <p><strong>Admin:</strong> admin@tenant1.com / admin123</p>
                        @elseif($tenant_slug == 'tenant2')
                            <p><strong>Regular User:</strong> user@tenant2.com / tenant456</p>
                            <p><strong>Admin:</strong> admin@tenant2.com / admin456</p>
                        @endif
                        <p><strong>Super Admin:</strong> superadmin@sso.com / super123</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
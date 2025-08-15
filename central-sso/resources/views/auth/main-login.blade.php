<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Central SSO - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="bg-white rounded-lg shadow-xl p-8">
                <div>
                    <h2 class="text-center text-3xl font-extrabold text-gray-900">
                        Central SSO Portal
                    </h2>
                    <p class="mt-2 text-center text-sm text-gray-600">
                        Sign in to access your applications
                    </p>
                </div>
                
                <form class="mt-8 space-y-6" action="{{ route('main.login.submit') }}" method="POST">
                    @csrf
                    
                    @if($errors->any())
                        <div class="rounded-md bg-red-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
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

                    @if(session('success'))
                        <div class="rounded-md bg-green-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-green-800">
                                        {{ session('success') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <div class="space-y-4">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">
                                Email address
                            </label>
                            <input id="email" name="email" type="email" autocomplete="email" required 
                                   class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                                   placeholder="Enter your email" value="{{ old('email') }}">
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">
                                Password
                            </label>
                            <input id="password" name="password" type="password" autocomplete="current-password" required 
                                   class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                                   placeholder="Enter your password">
                        </div>
                    </div>
                    
                    <div>
                        <button type="submit" 
                                class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-indigo-500 group-hover:text-indigo-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            Sign in
                        </button>
                    </div>
                </form>
                
                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">Test Accounts</span>
                        </div>
                    </div>
                    
                    <div class="mt-6 grid grid-cols-1 gap-2 text-sm">
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="font-semibold text-gray-700">Single Tenant Users:</p>
                            <p class="text-gray-600">user@tenant1.com / password (Tenant 1)</p>
                            <p class="text-gray-600">admin@tenant1.com / password (Tenant 1)</p>
                            <p class="text-gray-600">user@tenant2.com / password (Tenant 2)</p>
                            <p class="text-gray-600">admin@tenant2.com / password (Tenant 2)</p>
                        </div>
                        <div class="bg-blue-50 p-3 rounded">
                            <p class="font-semibold text-gray-700">Multi-Tenant User:</p>
                            <p class="text-gray-600">superadmin@sso.com / password (Both tenants)</p>
                        </div>
                        <div class="bg-yellow-50 p-3 rounded">
                            <p class="font-semibold text-gray-700">Legacy Users (unknown passwords):</p>
                            <p class="text-gray-600">admin@example.com, multi@example.com</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
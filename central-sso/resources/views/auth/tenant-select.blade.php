<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Tenant - Central SSO</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div class="bg-white rounded-lg shadow-xl p-8">
                <div>
                    <h2 class="text-center text-3xl font-extrabold text-gray-900">
                        Select Application
                    </h2>
                    <p class="mt-2 text-center text-sm text-gray-600">
                        Welcome, <span class="font-medium text-indigo-600">{{ $user->name }}</span>
                    </p>
                    <p class="mt-1 text-center text-sm text-gray-500">
                        You have access to multiple applications. Please select one to continue.
                    </p>
                </div>
                
                <form class="mt-8 space-y-6" action="{{ route('tenant.select.submit') }}" method="POST">
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
                    
                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            Available Applications:
                        </label>
                        
                        @foreach($tenants as $tenant)
                            <div class="relative">
                                <input type="radio" 
                                       id="tenant_{{ $tenant->id }}" 
                                       name="tenant_slug" 
                                       value="{{ $tenant->slug }}"
                                       class="peer sr-only"
                                       {{ $loop->first ? 'checked' : '' }}>
                                <label for="tenant_{{ $tenant->id }}" 
                                       class="flex items-center justify-between w-full p-4 text-gray-700 bg-white border-2 border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 hover:border-indigo-300 peer-checked:border-indigo-600 peer-checked:bg-indigo-50 transition duration-150 ease-in-out">
                                    <div class="flex items-center">
                                        <div class="ml-2">
                                            <p class="text-base font-semibold">{{ $tenant->name }}</p>
                                            <p class="text-sm text-gray-500">{{ $tenant->domain ?? $tenant->slug . '.local' }}</p>
                                        </div>
                                    </div>
                                    <svg class="w-5 h-5 text-indigo-600 opacity-0 peer-checked:opacity-100" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                </label>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="flex space-x-3">
                        <button type="submit" 
                                class="flex-1 py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out">
                            Continue to Application
                        </button>
                        <a href="{{ route('main.logout') }}" 
                           class="py-2 px-4 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out">
                            Logout
                        </a>
                    </div>
                </form>
                
                <div class="mt-6 text-center">
                    <p class="text-xs text-gray-500">
                        You are logged in as {{ $user->email }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
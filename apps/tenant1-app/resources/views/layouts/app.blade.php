<!DOCTYPE html>
<html lang="en" x-data="{ darkMode: $persist(false) }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Tenant One') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'teal-custom': '#06beb6',
                        'teal-custom-light': '#48b1bf'
                    }
                }
            }
        }

        // Alpine.js persist plugin for theme persistence
        document.addEventListener('alpine:init', () => {
            Alpine.magic('persist', (el, { interceptor }) => {
                return interceptor((initialValue, getter, setter, path, key) => {
                    let lookup = key.replace(/\./g, '__')
                    let initial = localStorage.getItem(lookup)
                    
                    if (initial !== null) {
                        setter(JSON.parse(initial))
                    } else {
                        setter(initialValue)
                    }
                    
                    Alpine.effect(() => {
                        localStorage.setItem(lookup, JSON.stringify(getter()))
                    })
                    
                    return getter()
                })
            })
        })

        // Initialize theme based on system preference or saved preference
        (function() {
            const savedTheme = localStorage.getItem('darkMode')
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
            
            if (savedTheme !== null) {
                if (JSON.parse(savedTheme)) {
                    document.documentElement.classList.add('dark')
                }
            } else if (systemPrefersDark) {
                document.documentElement.classList.add('dark')
                localStorage.setItem('darkMode', 'true')
            }
        })()
    </script>
    <style>
        [x-cloak] { display: none !important; }
        :root {
            --tenant-primary: 6, 190, 182;
            --tenant-secondary: 72, 177, 191;
        }
    </style>
    @yield('styles')
</head>
<body class="bg-gradient-to-br from-teal-50 via-cyan-50 to-blue-50 dark:from-gray-900 dark:via-teal-900 dark:to-cyan-900 min-h-screen transition-colors duration-300">
    <!-- Navigation -->
    <nav class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-sm shadow-sm border-b border-gray-200 dark:border-gray-700 transition-colors duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <h1 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-teal-custom to-teal-custom-light">
                            {{ config('app.name', 'Tenant One') }}
                        </h1>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- Theme Toggle -->
                    <button @click="darkMode = !darkMode" 
                            class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-200"
                            :title="darkMode ? 'Switch to light mode' : 'Switch to dark mode'">
                        <svg x-show="!darkMode" class="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"></path>
                        </svg>
                        <svg x-show="darkMode" x-cloak class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                        </svg>
                    </button>

                    @if(auth()->check())
                        <span class="text-gray-600 dark:text-gray-400 text-sm">
                            Welcome, {{ auth()->user()->name ?? 'User' }}
                        </span>
                        <a href="{{ route('dashboard') }}" class="bg-gradient-to-r from-teal-custom to-teal-custom-light hover:from-teal-600 hover:to-cyan-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            Dashboard
                        </a>
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 text-sm font-medium transition-colors flex items-center">
                                Logout
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div x-show="open" @click.away="open = false" x-cloak 
                                 class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-md shadow-lg border border-gray-200 dark:border-gray-700 z-50">
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                        Logout from {{ config('app.name') }}
                                    </button>
                                </form>
                                <form action="{{ route('logout') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="sso_logout" value="1">
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 border-t border-gray-200 dark:border-gray-600 transition-colors">
                                        Logout from all apps
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 text-sm font-medium transition-colors">
                            Login
                        </a>
                        <a href="{{ route('register') }}" class="bg-gradient-to-r from-teal-custom to-teal-custom-light hover:from-teal-600 hover:to-cyan-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            Register
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-1">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Flash Messages -->
            @if(session('success'))
                <div class="mb-6 bg-green-100 dark:bg-green-900/20 border border-green-400 dark:border-green-500 text-green-700 dark:text-green-400 px-4 py-3 rounded transition-colors duration-200" x-data="{ show: true }" x-show="show">
                    <div class="flex justify-between items-center">
                        <span>{{ session('success') }}</span>
                        <button @click="show = false" class="text-green-700 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            @endif

            @if($errors->has('error'))
                <div class="mb-6 bg-red-100 dark:bg-red-900/20 border border-red-400 dark:border-red-500 text-red-700 dark:text-red-400 px-4 py-3 rounded transition-colors duration-200" x-data="{ show: true }" x-show="show">
                    <div class="flex justify-between items-center">
                        <span>{{ $errors->first('error') }}</span>
                        <button @click="show = false" class="text-red-700 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            @endif

            @if(session('warning'))
                <div class="mb-6 bg-yellow-100 dark:bg-yellow-900/20 border border-yellow-400 dark:border-yellow-500 text-yellow-700 dark:text-yellow-400 px-4 py-3 rounded transition-colors duration-200" x-data="{ show: true }" x-show="show">
                    <div class="flex justify-between items-center">
                        <span>{{ session('warning') }}</span>
                        <button @click="show = false" class="text-yellow-700 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-300">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            @endif

            @yield('content')
        </div>
    </main>
</body>
</html>
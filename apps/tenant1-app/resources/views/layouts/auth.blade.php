<!DOCTYPE html>
<html lang="en" x-data="{ darkMode: $persist(false) }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Authentication') - {{ config('app.name', 'Tenant One') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        'teal-custom': '#06beb6',
                        'teal-custom-light': '#48b1bf',
                        border: "hsl(214.3 31.8% 91.4%)",
                        input: "hsl(214.3 31.8% 91.4%)",
                        ring: "hsl(222.2 84% 4.9%)",
                        background: "hsl(0 0% 100%)",
                        foreground: "hsl(222.2 84% 4.9%)",
                        primary: {
                            DEFAULT: "hsl(222.2 47.4% 11.2%)",
                            foreground: "hsl(210 40% 98%)",
                        },
                        secondary: {
                            DEFAULT: "hsl(210 40% 96%)",
                            foreground: "hsl(222.2 84% 4.9%)",
                        },
                        destructive: {
                            DEFAULT: "hsl(0 84.2% 60.2%)",
                            foreground: "hsl(210 40% 98%)",
                        },
                        muted: {
                            DEFAULT: "hsl(210 40% 96%)",
                            foreground: "hsl(215.4 16.3% 46.9%)",
                        },
                        accent: {
                            DEFAULT: "hsl(210 40% 96%)",
                            foreground: "hsl(222.2 84% 4.9%)",
                        },
                        popover: {
                            DEFAULT: "hsl(0 0% 100%)",
                            foreground: "hsl(222.2 84% 4.9%)",
                        },
                        card: {
                            DEFAULT: "hsl(0 0% 100%)",
                            foreground: "hsl(222.2 84% 4.9%)",
                        },
                    },
                    borderRadius: {
                        lg: "var(--radius)",
                        md: "calc(var(--radius) - 2px)",
                        sm: "calc(var(--radius) - 4px)",
                    },
                }
            }
        }
    </script>
    <style>
        :root {
            --radius: 0.5rem;
            --tenant-primary: 6, 190, 182;
            --tenant-secondary: 72, 177, 191;
        }
        [x-cloak] { display: none !important; }
    </style>
    <script>
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
</head>
<body class="bg-gradient-to-br from-teal-50 via-cyan-50 to-blue-50 dark:from-gray-900 dark:via-teal-900 dark:to-cyan-900 min-h-screen flex items-center justify-center transition-colors duration-300">
    <div class="max-w-md w-full mx-auto">
        <!-- Theme Toggle -->
        <div class="flex justify-end mb-4">
            <button @click="darkMode = !darkMode" 
                    class="p-2 rounded-lg bg-white dark:bg-gray-800 shadow-md hover:shadow-lg transition-all duration-200 border border-gray-200 dark:border-gray-700"
                    :title="darkMode ? 'Switch to light mode' : 'Switch to dark mode'">
                <svg x-show="!darkMode" class="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"></path>
                </svg>
                <svg x-show="darkMode" x-cloak class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                </svg>
            </button>
        </div>

        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="/" class="inline-block">
                <h1 class="text-3xl font-bold bg-gradient-to-r from-teal-custom to-teal-custom-light bg-clip-text text-transparent">
                    {{ config('app.name', 'Tenant One') }}
                </h1>
            </a>
            <p class="text-gray-600 dark:text-gray-400 mt-2 transition-colors duration-200">@yield('subtitle', 'Secure Tenant Application')</p>
        </div>

        <!-- Main Card -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden transition-colors duration-200">
            <!-- Header -->
            @hasSection('header')
                <div class="px-6 py-4 bg-gradient-to-r from-teal-custom to-teal-custom-light">
                    <h2 class="text-xl font-semibold text-white">
                        @yield('header')
                    </h2>
                </div>
            @endif

            <!-- Content -->
            <div class="p-6">
                <!-- Flash Messages -->
                @if(session('success'))
                    <div class="mb-4 bg-green-100 dark:bg-green-900/20 border border-green-400 dark:border-green-500 text-green-700 dark:text-green-400 px-4 py-3 rounded transition-colors duration-200" x-data="{ show: true }" x-show="show">
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

                @if(session('error'))
                    <div class="mb-4 bg-red-100 dark:bg-red-900/20 border border-red-400 dark:border-red-500 text-red-700 dark:text-red-400 px-4 py-3 rounded transition-colors duration-200" x-data="{ show: true }" x-show="show">
                        <div class="flex justify-between items-center">
                            <span>{{ session('error') }}</span>
                            <button @click="show = false" class="text-red-700 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4 bg-red-100 dark:bg-red-900/20 border border-red-400 dark:border-red-500 text-red-700 dark:text-red-400 px-4 py-3 rounded transition-colors duration-200" x-data="{ show: true }" x-show="show">
                        <div class="flex justify-between items-start">
                            <div>
                                <ul class="list-disc list-inside space-y-1">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            <button @click="show = false" class="text-red-700 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 ml-4">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="mb-4 bg-yellow-100 dark:bg-yellow-900/20 border border-yellow-400 dark:border-yellow-500 text-yellow-700 dark:text-yellow-400 px-4 py-3 rounded transition-colors duration-200" x-data="{ show: true }" x-show="show">
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

            <!-- Footer -->
            @hasSection('footer')
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600 transition-colors duration-200">
                    @yield('footer')
                </div>
            @endif
        </div>

        <!-- Bottom Links -->
        <div class="mt-6 text-center space-y-2">
            @yield('bottom-links')
            <div class="text-sm text-gray-500 dark:text-gray-400 transition-colors duration-200">
                <a href="/" class="hover:text-gray-700 dark:hover:text-gray-300">← Back to Home</a>
                <span class="mx-2">•</span>
                <a href="{{ route('login') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Login</a>
                <span class="mx-2">•</span>
                <a href="{{ route('register') }}" class="hover:text-gray-700 dark:hover:text-gray-300">Register</a>
            </div>
        </div>
    </div>
</body>
</html>
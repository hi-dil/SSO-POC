<!DOCTYPE html>
<html lang="en" x-data="{ darkMode: $persist(false) }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Central SSO - Enterprise Authentication Platform</title>
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
                    }
                }
            }
        }
    </script>
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
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gradient-to-br from-teal-50 via-cyan-50 to-teal-100 dark:from-gray-900 dark:via-teal-900 dark:to-cyan-900 min-h-screen transition-colors duration-300">
    <!-- Navigation -->
    <nav class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50 transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-teal-custom to-teal-custom-light bg-clip-text text-transparent">
                        Central SSO
                    </h1>
                </div>

                <!-- Navigation Links -->
                <div class="flex items-center space-x-4">
                    <!-- Theme Toggle -->
                    <button @click="darkMode = !darkMode" 
                            class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors duration-200"
                            :title="darkMode ? 'Switch to light mode' : 'Switch to dark mode'">
                        <svg x-show="!darkMode" class="w-5 h-5 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" clip-rule="evenodd"></path>
                        </svg>
                        <svg x-show="darkMode" x-cloak class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                        </svg>
                    </button>

                    @auth
                        <a href="{{ route('dashboard') }}" 
                           class="bg-gradient-to-r from-teal-custom to-teal-custom-light text-white px-6 py-2 rounded-lg hover:from-teal-600 hover:to-cyan-600 transition-all duration-200 font-medium">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" 
                           class="text-gray-700 dark:text-gray-200 hover:text-teal-600 dark:hover:text-teal-400 px-4 py-2 rounded-lg transition-colors duration-200">
                            Sign In
                        </a>
                        <a href="{{ route('login') }}" 
                           class="bg-gradient-to-r from-teal-custom to-teal-custom-light text-white px-6 py-2 rounded-lg hover:from-teal-600 hover:to-cyan-600 transition-all duration-200 font-medium">
                            Get Started
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto text-center">
            <h1 class="text-5xl md:text-6xl font-bold text-gray-900 dark:text-white mb-6">
                Enterprise
                <span class="bg-gradient-to-r from-teal-custom to-teal-custom-light bg-clip-text text-transparent">
                    SSO Platform
                </span>
            </h1>
            <p class="text-xl text-gray-600 dark:text-gray-300 mb-8 max-w-3xl mx-auto leading-relaxed">
                Secure, centralized authentication server powering seamless access across multiple tenant applications. 
                Built for enterprise scale with modern security standards and developer-friendly APIs.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                @auth
                    <a href="{{ route('dashboard') }}" 
                       class="bg-gradient-to-r from-teal-custom to-teal-custom-light text-white px-8 py-4 rounded-xl hover:from-teal-600 hover:to-cyan-600 transition-all duration-200 font-semibold text-lg shadow-lg hover:shadow-xl">
                        Access Dashboard
                    </a>
                    @can('manage-tenants')
                        <a href="{{ route('admin.tenants.index') }}" 
                           class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-8 py-4 rounded-xl border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 font-semibold text-lg">
                            Manage Tenants
                        </a>
                    @endcan
                @else
                    <a href="{{ route('login') }}" 
                       class="bg-gradient-to-r from-teal-custom to-teal-custom-light text-white px-8 py-4 rounded-xl hover:from-teal-600 hover:to-cyan-600 transition-all duration-200 font-semibold text-lg shadow-lg hover:shadow-xl">
                        Sign In to Dashboard
                    </a>
                    <a href="/docs" 
                       class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-8 py-4 rounded-xl border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 font-semibold text-lg">
                        API Documentation
                    </a>
                @endauth
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                    Enterprise-Grade SSO Features
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                    Built with modern technologies and enterprise security standards for seamless authentication experiences.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Multi-Tenant Architecture -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-200 border border-gray-200 dark:border-gray-700">
                    <div class="w-12 h-12 bg-gradient-to-r from-teal-custom to-teal-custom-light rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m14 0v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Multi-Tenant Architecture</h3>
                    <p class="text-gray-600 dark:text-gray-300">
                        Support for multiple tenant applications with isolated access control and seamless cross-tenant authentication.
                    </p>
                </div>

                <!-- Role-Based Access Control -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-200 border border-gray-200 dark:border-gray-700">
                    <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-indigo-500 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Role-Based Access Control</h3>
                    <p class="text-gray-600 dark:text-gray-300">
                        Granular permissions system with {{ \App\Models\Permission::count() }}+ built-in permissions for fine-grained access control.
                    </p>
                </div>

                <!-- Enterprise Security -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-200 border border-gray-200 dark:border-gray-700">
                    <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-500 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Enterprise Security</h3>
                    <p class="text-gray-600 dark:text-gray-300">
                        JWT tokens with HMAC-SHA256 signing, bcrypt password hashing, and comprehensive security measures.
                    </p>
                </div>

                <!-- Developer Tools -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-200 border border-gray-200 dark:border-gray-700">
                    <div class="w-12 h-12 bg-gradient-to-r from-orange-500 to-red-500 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">RESTful API</h3>
                    <p class="text-gray-600 dark:text-gray-300">
                        Complete OpenAPI 3.0 documented REST API with structured responses and comprehensive error handling.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-20 px-4 sm:px-6 lg:px-8 bg-white dark:bg-gray-800 transition-colors duration-200">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                    Real-Time System Statistics
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-300">
                    Live data from your Central SSO instance
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center">
                <div class="bg-gradient-to-r from-teal-custom to-teal-custom-light rounded-xl p-8 text-white">
                    <div class="text-4xl font-bold mb-2">{{ \App\Models\Tenant::count() }}</div>
                    <div class="text-lg opacity-90">Active Tenants</div>
                </div>
                <div class="bg-gradient-to-r from-teal-custom to-teal-custom-light rounded-xl p-8 text-white">
                    <div class="text-4xl font-bold mb-2">{{ \App\Models\User::count() }}</div>
                    <div class="text-lg opacity-90">Registered Users</div>
                </div>
                <div class="bg-gradient-to-r from-teal-custom to-teal-custom-light rounded-xl p-8 text-white">
                    <div class="text-4xl font-bold mb-2">{{ \App\Models\Role::count() }}</div>
                    <div class="text-lg opacity-90">Available Roles</div>
                </div>
                <div class="bg-gradient-to-r from-teal-custom to-teal-custom-light rounded-xl p-8 text-white">
                    <div class="text-4xl font-bold mb-2">{{ \App\Models\Permission::count() }}</div>
                    <div class="text-lg opacity-90">System Permissions</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Start Guide -->
    <section class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                    Quick Start Guide
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                    Get started with Central SSO in three simple steps
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-8 shadow-lg border border-gray-200 dark:border-gray-700 text-center">
                    <div class="w-16 h-16 bg-gradient-to-r from-teal-custom to-teal-custom-light rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-2xl font-bold text-white">1</span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Access Dashboard</h3>
                    <p class="text-gray-600 dark:text-gray-300">
                        Login with your administrator credentials to access the SSO management dashboard.
                    </p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-8 shadow-lg border border-gray-200 dark:border-gray-700 text-center">
                    <div class="w-16 h-16 bg-gradient-to-r from-teal-custom to-teal-custom-light rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-2xl font-bold text-white">2</span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Configure Tenants</h3>
                    <p class="text-gray-600 dark:text-gray-300">
                        Set up your tenant applications and configure their access permissions and user assignments.
                    </p>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-8 shadow-lg border border-gray-200 dark:border-gray-700 text-center">
                    <div class="w-16 h-16 bg-gradient-to-r from-teal-custom to-teal-custom-light rounded-full flex items-center justify-center mx-auto mb-6">
                        <span class="text-2xl font-bold text-white">3</span>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">Manage Access</h3>
                    <p class="text-gray-600 dark:text-gray-300">
                        Assign roles and permissions to users for granular access control across all tenant applications.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto text-center">
            <div class="bg-gradient-to-r from-teal-custom to-teal-custom-light rounded-2xl p-12 text-white">
                <h2 class="text-4xl font-bold mb-4">Ready to Centralize Authentication?</h2>
                <p class="text-xl mb-8 opacity-90">
                    Join organizations using Central SSO to streamline authentication across multiple applications with enterprise-grade security.
                </p>
                @auth
                    <a href="{{ route('dashboard') }}" 
                       class="bg-white text-teal-600 px-8 py-4 rounded-xl hover:bg-gray-100 transition-all duration-200 font-semibold text-lg inline-block">
                        Access Your Dashboard
                    </a>
                @else
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ route('login') }}" 
                           class="bg-white text-teal-600 px-8 py-4 rounded-xl hover:bg-gray-100 transition-all duration-200 font-semibold text-lg">
                            Get Started Now
                        </a>
                        <a href="/docs" 
                           class="border-2 border-white text-white px-8 py-4 rounded-xl hover:bg-white hover:text-teal-600 transition-all duration-200 font-semibold text-lg">
                            View Documentation
                        </a>
                    </div>
                @endauth
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-12 transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="col-span-2">
                    <h3 class="text-2xl font-bold bg-gradient-to-r from-teal-custom to-teal-custom-light bg-clip-text text-transparent mb-4">
                        Central SSO
                    </h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-6">
                        Enterprise Single Sign-On authentication server built with Laravel. 
                        Secure, scalable, and developer-friendly authentication for modern applications.
                    </p>
                    <div class="flex space-x-4">
                        <a href="/docs" class="text-gray-500 dark:text-gray-400 hover:text-teal-600 dark:hover:text-teal-400 transition-colors duration-200">
                            Documentation
                        </a>
                        <a href="/telescope" class="text-gray-500 dark:text-gray-400 hover:text-teal-600 dark:hover:text-teal-400 transition-colors duration-200">
                            Monitoring
                        </a>
                        <a href="#" class="text-gray-500 dark:text-gray-400 hover:text-teal-600 dark:hover:text-teal-400 transition-colors duration-200">
                            Support
                        </a>
                    </div>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white tracking-wider uppercase mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="{{ route('login') }}" class="text-gray-600 dark:text-gray-400 hover:text-teal-600 dark:hover:text-teal-400 transition-colors duration-200">Login</a></li>
                        @auth
                            <li><a href="{{ route('dashboard') }}" class="text-gray-600 dark:text-gray-400 hover:text-teal-600 dark:hover:text-teal-400 transition-colors duration-200">Dashboard</a></li>
                            @can('manage-tenants')
                                <li><a href="{{ route('admin.tenants.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-teal-600 dark:hover:text-teal-400 transition-colors duration-200">Tenants</a></li>
                                <li><a href="{{ route('admin.roles.index') }}" class="text-gray-600 dark:text-gray-400 hover:text-teal-600 dark:hover:text-teal-400 transition-colors duration-200">Roles</a></li>
                            @endcan
                        @endauth
                        <li><a href="/docs" class="text-gray-600 dark:text-gray-400 hover:text-teal-600 dark:hover:text-teal-400 transition-colors duration-200">API Docs</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white tracking-wider uppercase mb-4">System Info</h4>
                    <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <li>Laravel {{ app()->version() }}</li>
                        <li>PHP {{ PHP_VERSION }}</li>
                        <li>Environment: {{ config('app.env') }}</li>
                        <li>Debug: {{ config('app.debug') ? 'Enabled' : 'Disabled' }}</li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-200 dark:border-gray-600 mt-8 pt-8 text-center">
                <p class="text-gray-600 dark:text-gray-400 text-sm">
                    © {{ date('Y') }} Central SSO. Built with Laravel & Tailwind CSS.
                </p>
            </div>
        </div>
    </footer>
</body>
</html>
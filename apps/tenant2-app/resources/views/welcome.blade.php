<!DOCTYPE html>
<html lang="en" x-data="{ darkMode: $persist(false) }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - Digital Innovation Platform</title>
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
<body class="bg-gradient-to-br from-teal-50 via-cyan-50 to-blue-50 dark:from-gray-900 dark:via-teal-900 dark:to-cyan-900 min-h-screen transition-colors duration-300">
    <!-- Navigation -->
    <nav class="bg-white/80 dark:bg-gray-800/80 backdrop-blur-md border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50 transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold bg-gradient-to-r from-teal-custom to-teal-custom-light bg-clip-text text-transparent">
                        {{ config('app.name') }}
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
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" 
                               class="bg-gradient-to-r from-teal-custom to-teal-custom-light text-white px-6 py-2 rounded-lg hover:from-teal-600 hover:to-cyan-600 transition-all duration-200 font-medium">
                                Get Started
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto text-center">
            <h1 class="text-5xl md:text-6xl font-bold text-gray-900 dark:text-white mb-6">
                Digital Innovation
                <span class="bg-gradient-to-r from-teal-custom to-teal-custom-light bg-clip-text text-transparent">
                    Platform
                </span>
            </h1>
            <p class="text-xl text-gray-600 dark:text-gray-300 mb-8 max-w-3xl mx-auto leading-relaxed">
                Transform your organization with cutting-edge digital solutions. 
                Enterprise security, intelligent automation, and seamless team collaboration for the modern workplace.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                @guest
                    <a href="{{ route('login') }}" 
                       class="bg-gradient-to-r from-teal-custom to-teal-custom-light text-white px-8 py-4 rounded-xl hover:from-teal-600 hover:to-cyan-600 transition-all duration-200 font-semibold text-lg shadow-lg hover:shadow-xl">
                        Access Your Platform
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" 
                           class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-8 py-4 rounded-xl border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 font-semibold text-lg">
                            Request Demo
                        </a>
                    @endif
                @else
                    <a href="{{ route('dashboard') }}" 
                       class="bg-gradient-to-r from-teal-custom to-teal-custom-light text-white px-8 py-4 rounded-xl hover:from-teal-600 hover:to-cyan-600 transition-all duration-200 font-semibold text-lg shadow-lg hover:shadow-xl">
                        Go to Dashboard
                    </a>
                @endguest
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                    Enterprise-Grade Innovation
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                    Comprehensive solutions designed for modern enterprises seeking digital transformation.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Feature 1: Enterprise Security -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-200 border border-gray-200 dark:border-gray-700">
                    <div class="w-12 h-12 bg-gradient-to-r from-teal-custom to-teal-custom-light rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Enterprise Security</h3>
                    <p class="text-gray-600 dark:text-gray-300">
                        Military-grade encryption and zero-trust architecture. Protect your enterprise data with industry-leading security protocols.
                    </p>
                </div>

                <!-- Feature 2: Team Management -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-200 border border-gray-200 dark:border-gray-700">
                    <div class="w-12 h-12 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Seamless Team Management</h3>
                    <p class="text-gray-600 dark:text-gray-300">
                        Centralized team coordination and project management. Organize teams, assign roles, and track progress effortlessly.
                    </p>
                </div>

                <!-- Feature 3: Automated Workflows -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-200 border border-gray-200 dark:border-gray-700">
                    <div class="w-12 h-12 bg-gradient-to-r from-orange-500 to-red-500 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Automated Workflow Tools</h3>
                    <p class="text-gray-600 dark:text-gray-300">
                        Intelligent automation that adapts to your business processes. Reduce manual tasks and increase efficiency.
                    </p>
                </div>

                <!-- Feature 4: Comprehensive Reporting -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-200 border border-gray-200 dark:border-gray-700">
                    <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-500 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Comprehensive Reporting</h3>
                    <p class="text-gray-600 dark:text-gray-300">
                        Advanced analytics and customizable dashboards. Generate detailed reports and gain actionable business insights.
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
                    Trusted by Industry Leaders
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-300">
                    Join thousands of organizations already transforming their operations
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 text-center">
                <div class="bg-gradient-to-r from-teal-custom to-teal-custom-light rounded-xl p-8 text-white">
                    <div class="text-4xl font-bold mb-2">99.9%</div>
                    <div class="text-lg opacity-90">Uptime Guarantee</div>
                </div>
                <div class="bg-gradient-to-r from-teal-custom to-teal-custom-light rounded-xl p-8 text-white">
                    <div class="text-4xl font-bold mb-2">10K+</div>
                    <div class="text-lg opacity-90">Active Organizations</div>
                </div>
                <div class="bg-gradient-to-r from-teal-custom to-teal-custom-light rounded-xl p-8 text-white">
                    <div class="text-4xl font-bold mb-2">50M+</div>
                    <div class="text-lg opacity-90">Secure Authentications</div>
                </div>
                <div class="bg-gradient-to-r from-teal-custom to-teal-custom-light rounded-xl p-8 text-white">
                    <div class="text-4xl font-bold mb-2">24/7</div>
                    <div class="text-lg opacity-90">Expert Support</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto text-center">
            <div class="bg-gradient-to-r from-teal-custom to-teal-custom-light rounded-2xl p-12 text-white">
                <h2 class="text-4xl font-bold mb-4">Ready to Innovate?</h2>
                <p class="text-xl mb-8 opacity-90">
                    Experience the future of enterprise technology. Start your digital transformation journey today.
                </p>
                @guest
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ route('register') }}" 
                           class="bg-white text-teal-600 px-8 py-4 rounded-xl hover:bg-gray-100 transition-all duration-200 font-semibold text-lg">
                            Request Demo
                        </a>
                        <a href="{{ route('login') }}" 
                           class="border-2 border-white text-white px-8 py-4 rounded-xl hover:bg-white hover:text-teal-600 transition-all duration-200 font-semibold text-lg">
                            Sign In Now
                        </a>
                    </div>
                @else
                    <a href="{{ route('dashboard') }}" 
                       class="bg-white text-teal-600 px-8 py-4 rounded-xl hover:bg-gray-100 transition-all duration-200 font-semibold text-lg inline-block">
                        Access Your Dashboard
                    </a>
                @endguest
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-12 transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h3 class="text-2xl font-bold bg-gradient-to-r from-teal-custom to-teal-custom-light bg-clip-text text-transparent mb-4">
                    {{ config('app.name') }}
                </h3>
                <p class="text-gray-600 dark:text-gray-300 mb-6">
                    Leading the digital transformation with innovative enterprise solutions.
                </p>
                <div class="flex justify-center space-x-6">
                    <a href="#" class="text-gray-500 dark:text-gray-400 hover:text-teal-600 dark:hover:text-teal-400 transition-colors duration-200">
                        Privacy Policy
                    </a>
                    <a href="#" class="text-gray-500 dark:text-gray-400 hover:text-teal-600 dark:hover:text-teal-400 transition-colors duration-200">
                        Terms of Service
                    </a>
                    <a href="#" class="text-gray-500 dark:text-gray-400 hover:text-teal-600 dark:hover:text-teal-400 transition-colors duration-200">
                        Support
                    </a>
                    <a href="#" class="text-gray-500 dark:text-gray-400 hover:text-teal-600 dark:hover:text-teal-400 transition-colors duration-200">
                        Documentation
                    </a>
                </div>
                <div class="mt-6 text-sm text-gray-500 dark:text-gray-400">
                    © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
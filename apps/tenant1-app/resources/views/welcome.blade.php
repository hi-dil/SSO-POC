<!DOCTYPE html>
<html lang="en" x-data="{ darkMode: $persist(false) }" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - Enterprise Business Solutions</title>
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
                Enterprise Business
                <span class="bg-gradient-to-r from-teal-custom to-teal-custom-light bg-clip-text text-transparent">
                    Solutions
                </span>
            </h1>
            <p class="text-xl text-gray-600 dark:text-gray-300 mb-8 max-w-3xl mx-auto leading-relaxed">
                Streamline your business operations with our comprehensive suite of enterprise tools. 
                Secure authentication, advanced analytics, and seamless collaboration in one platform.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                @guest
                    <a href="{{ route('login') }}" 
                       class="bg-gradient-to-r from-teal-custom to-teal-custom-light text-white px-8 py-4 rounded-xl hover:from-teal-600 hover:to-cyan-600 transition-all duration-200 font-semibold text-lg shadow-lg hover:shadow-xl">
                        Sign In to Your Account
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" 
                           class="bg-white dark:bg-gray-800 text-gray-900 dark:text-white px-8 py-4 rounded-xl border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 font-semibold text-lg">
                            Start Free Trial
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
                    Powerful Features for Modern Business
                </h2>
                <p class="text-xl text-gray-600 dark:text-gray-300 max-w-2xl mx-auto">
                    Everything you need to manage your business operations efficiently and securely.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Feature 1: Secure SSO Authentication -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-200 border border-gray-200 dark:border-gray-700">
                    <div class="w-12 h-12 bg-gradient-to-r from-teal-custom to-teal-custom-light rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Secure SSO Authentication</h3>
                    <p class="text-gray-600 dark:text-gray-300">
                        Single sign-on with enterprise-grade security. Access all your business tools with one secure login.
                    </p>
                </div>

                <!-- Feature 2: Multi-Factor Authentication -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-200 border border-gray-200 dark:border-gray-700">
                    <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Multi-Factor Authentication</h3>
                    <p class="text-gray-600 dark:text-gray-300">
                        Advanced security with MFA support. Protect your business data with multiple authentication layers.
                    </p>
                </div>

                <!-- Feature 3: Real-time Collaboration -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-200 border border-gray-200 dark:border-gray-700">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Real-time Collaboration</h3>
                    <p class="text-gray-600 dark:text-gray-300">
                        Collaborate seamlessly with your team. Share documents, communicate, and work together in real-time.
                    </p>
                </div>

                <!-- Feature 4: Advanced Analytics -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-200 border border-gray-200 dark:border-gray-700">
                    <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-500 rounded-lg flex items-center justify-center mb-4">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Advanced Analytics</h3>
                    <p class="text-gray-600 dark:text-gray-300">
                        Comprehensive insights and reporting. Make data-driven decisions with powerful analytics tools.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action Section -->
    <section class="py-20 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto text-center">
            <div class="bg-gradient-to-r from-teal-custom to-teal-custom-light rounded-2xl p-12 text-white">
                <h2 class="text-4xl font-bold mb-4">Ready to Transform Your Business?</h2>
                <p class="text-xl mb-8 opacity-90">
                    Join thousands of businesses already using our platform to streamline their operations and boost productivity.
                </p>
                @guest
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ route('register') }}" 
                           class="bg-white text-teal-600 px-8 py-4 rounded-xl hover:bg-gray-100 transition-all duration-200 font-semibold text-lg">
                            Start Your Free Trial
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
                    Empowering businesses with secure, scalable, and innovative solutions.
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
                </div>
                <div class="mt-6 text-sm text-gray-500 dark:text-gray-400">
                    © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
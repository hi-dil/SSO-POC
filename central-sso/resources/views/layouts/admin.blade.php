<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') - Central SSO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
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
        }
    </style>
</head>
<body class="bg-background text-foreground min-h-screen">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <div class="hidden w-64 bg-card border-r border-border lg:block fixed h-screen">
            <div class="flex h-full flex-col">
                <!-- Logo -->
                <div class="flex h-16 items-center border-b border-border px-6">
                    <a href="/" class="flex items-center space-x-2">
                        <div class="h-8 w-8 rounded-lg bg-primary flex items-center justify-center">
                            <svg class="h-5 w-5 text-primary-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <span class="text-lg font-semibold">Central SSO</span>
                    </a>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 overflow-y-auto space-y-1 px-3 py-4">
                    <a href="{{ route('dashboard') }}" 
                       class="@if(request()->routeIs('dashboard')) bg-accent text-accent-foreground @else text-muted-foreground hover:bg-accent hover:text-accent-foreground @endif group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors">
                        <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>
                        </svg>
                        Dashboard
                    </a>
                    
                    <a href="{{ route('admin.tenants.index') }}" 
                       class="@if(request()->routeIs('admin.tenants.*')) bg-accent text-accent-foreground @else text-muted-foreground hover:bg-accent hover:text-accent-foreground @endif group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors">
                        <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m14 0v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5"></path>
                        </svg>
                        Tenants
                    </a>
                    
                    <a href="{{ route('admin.roles.index') }}" 
                       class="@if(request()->routeIs('admin.roles.*')) bg-accent text-accent-foreground @else text-muted-foreground hover:bg-accent hover:text-accent-foreground @endif group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors">
                        <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                        Roles & Permissions
                    </a>

                    <div class="pt-4">
                        <p class="px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wider">Developer Tools</p>
                        <div class="mt-2 space-y-1">
                            @if(auth()->user()->hasPermission('swagger.access'))
                                <a href="/docs" target="_blank" class="text-muted-foreground hover:bg-accent hover:text-accent-foreground group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors">
                                    <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    API Documentation
                                    <svg class="ml-auto h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                </a>
                            @endif
                            @if(auth()->user()->hasPermission('telescope.access'))
                                <a href="/telescope" target="_blank" class="text-muted-foreground hover:bg-accent hover:text-accent-foreground group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors">
                                    <svg class="mr-3 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                    Telescope
                                    <svg class="ml-auto h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                </a>
                            @endif
                        </div>
                    </div>
                </nav>

                <!-- User Profile - Fixed to bottom -->
                <div class="mt-auto border-t border-border p-3">
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="w-full flex items-center px-3 py-2 text-sm rounded-md hover:bg-accent hover:text-accent-foreground transition-colors">
                            <div class="h-8 w-8 rounded-full bg-muted flex items-center justify-center mr-3">
                                <span class="text-sm font-medium text-muted-foreground">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </span>
                            </div>
                            <div class="flex-1 text-left">
                                <p class="text-sm font-medium">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-muted-foreground">{{ auth()->user()->email }}</p>
                            </div>
                            <svg class="h-4 w-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        
                        <div x-show="open" @click.away="open = false" x-transition class="absolute bottom-full left-0 w-full mb-2 bg-popover border border-border rounded-md shadow-md py-1">
                            <a href="{{ route('login') }}" class="block px-3 py-2 text-sm text-popover-foreground hover:bg-accent hover:text-accent-foreground transition-colors">
                                Back to Login
                            </a>
                            <div class="border-t border-border my-1"></div>
                            <a href="{{ route('main.logout') }}" class="block px-3 py-2 text-sm text-popover-foreground hover:bg-accent hover:text-accent-foreground transition-colors">
                                Sign out
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col lg:ml-64">
            <!-- Top Header -->
            <header class="h-16 border-b border-border bg-card px-6 flex items-center justify-between lg:px-8">
                <div class="flex items-center">
                    <!-- Mobile menu button -->
                    <button class="lg:hidden p-2 rounded-md hover:bg-accent hover:text-accent-foreground">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                    
                    @hasSection('header')
                        <div class="ml-4 lg:ml-0">
                            @yield('header')
                        </div>
                    @endif
                </div>
                
                @hasSection('actions')
                    <div class="flex items-center space-x-2">
                        @yield('actions')
                    </div>
                @endif
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto bg-muted/30 p-6">
                <!-- Flash Messages -->
                @if(session('success'))
                    <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg" x-data="{ show: true }" x-show="show">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-sm font-medium">{{ session('success') }}</span>
                            </div>
                            <button @click="show = false" class="text-green-600 hover:text-green-800">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 bg-destructive/10 border border-destructive/20 text-destructive px-4 py-3 rounded-lg" x-data="{ show: true }" x-show="show">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-sm font-medium">{{ session('error') }}</span>
                            </div>
                            <button @click="show = false" class="text-destructive hover:text-destructive/80">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg" x-data="{ show: true }" x-show="show">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center">
                                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <span class="text-sm font-medium">{{ session('warning') }}</span>
                            </div>
                            <button @click="show = false" class="text-yellow-600 hover:text-yellow-800">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif

                <!-- Main Content -->
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <!-- Confirmation Modal -->
    <div x-data="{ showModal: false, confirmAction: null }" @confirm-action.window="showModal = true; confirmAction = $event.detail.action">
        <div x-show="showModal" class="fixed inset-0 bg-black/50 overflow-y-auto h-full w-full z-50" x-transition>
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-card">
                <div class="mt-3 text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-destructive/10">
                        <svg class="h-6 w-6 text-destructive" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-card-foreground mt-2">Confirm Action</h3>
                    <p class="text-sm text-muted-foreground mt-2">Are you sure you want to proceed? This action cannot be undone.</p>
                    <div class="flex gap-3 justify-center mt-6">
                        <button @click="if(confirmAction) confirmAction(); showModal = false" class="px-4 py-2 bg-destructive text-destructive-foreground text-sm font-medium rounded-md hover:bg-destructive/80 focus:outline-none focus:ring-2 focus:ring-ring transition-colors">
                            Confirm
                        </button>
                        <button @click="showModal = false" class="px-4 py-2 bg-secondary text-secondary-foreground text-sm font-medium rounded-md hover:bg-secondary/80 focus:outline-none focus:ring-2 focus:ring-ring transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toast notification system
        window.showToast = function(message, type = 'info', duration = 4000) {
            const container = document.getElementById('toast-container');
            if (!container) return;

            // Create toast element
            const toast = document.createElement('div');
            toast.className = `min-w-80 max-w-lg w-full bg-card border border-border rounded-lg shadow-lg pointer-events-auto transform transition-all duration-300 ease-in-out translate-x-full opacity-0`;
            
            // Set colors based on type
            let iconColor, bgColor;
            switch (type) {
                case 'success':
                    iconColor = 'text-green-600';
                    bgColor = 'bg-green-50 border-green-200';
                    break;
                case 'error':
                    iconColor = 'text-destructive';
                    bgColor = 'bg-destructive/10 border-destructive/20';
                    break;
                case 'warning':
                    iconColor = 'text-yellow-600';
                    bgColor = 'bg-yellow-50 border-yellow-200';
                    break;
                default:
                    iconColor = 'text-blue-600';
                    bgColor = 'bg-blue-50 border-blue-200';
            }

            // Get icon based on type
            let icon;
            switch (type) {
                case 'success':
                    icon = `<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>`;
                    break;
                case 'error':
                    icon = `<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>`;
                    break;
                case 'warning':
                    icon = `<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>`;
                    break;
                default:
                    icon = `<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>`;
            }

            toast.innerHTML = `
                <div class="p-4 ${bgColor}">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 ${iconColor}">
                            ${icon}
                        </div>
                        <div class="ml-3 flex-1 min-w-0">
                            <p class="text-sm font-medium text-card-foreground break-words">${message}</p>
                        </div>
                        <div class="ml-4 flex-shrink-0 flex">
                            <button onclick="this.closest('[data-toast]').remove()" class="rounded-md inline-flex text-muted-foreground hover:text-card-foreground focus:outline-none focus:ring-2 focus:ring-ring">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            `;

            toast.setAttribute('data-toast', 'true');
            container.appendChild(toast);

            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-x-full', 'opacity-0');
                toast.classList.add('translate-x-0', 'opacity-100');
            }, 10);

            // Auto remove after duration
            setTimeout(() => {
                toast.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }, duration);
        };

        // Add keyboard shortcut to dismiss all toasts (Escape key)
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const toasts = document.querySelectorAll('[data-toast]');
                toasts.forEach(toast => {
                    toast.classList.add('translate-x-full', 'opacity-0');
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.parentNode.removeChild(toast);
                        }
                    }, 300);
                });
            }
        });
    </script>
</body>
</html>
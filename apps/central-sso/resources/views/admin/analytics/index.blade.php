@extends('layouts.admin')

@section('title', 'Login Analytics')

@section('header')
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Login Analytics</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Monitor user authentication activity and system usage
        </p>
    </div>
@endsection

@section('actions')
    <div class="flex items-center space-x-2">
        <button onclick="refreshData()" class="inline-flex items-center justify-center rounded-md text-sm font-medium  transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white h-10 px-4 py-2">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Refresh
        </button>
        <button onclick="exportData()" class="inline-flex items-center justify-center rounded-md text-sm font-medium  transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-teal-600 dark:bg-teal-500 text-white hover:bg-teal-700 dark:hover:bg-teal-600 h-10 px-4 py-2">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Export
        </button>
    </div>
@endsection

@section('content')
<div x-data="analyticsPage()" x-init="initializeData()">
    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Active Users -->
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Users</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white" x-text="stats.active_users || 0"></p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Currently online</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                    <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Today's Logins -->
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Today's Logins</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white" x-text="stats.today_logins || 0"></p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1" x-show="stats.login_trend !== undefined">
                        <span :class="stats.login_trend >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600'" x-text="(stats.login_trend >= 0 ? '+' : '') + stats.login_trend + ' from yesterday'"></span>
                    </p>
                </div>
                <div class="h-12 w-12 rounded-full bg-teal-100 dark:bg-teal-900 flex items-center justify-center">
                    <svg class="h-6 w-6 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Total Sessions -->
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Sessions</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white" x-text="stats.total_sessions || 0"></p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Active sessions</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center">
                    <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Unique Users (30 days) -->
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Unique Users</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white" x-text="stats.unique_users_30_days || 0"></p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">Last 30 days</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-orange-100 dark:bg-orange-900 flex items-center justify-center">
                    <svg class="h-6 w-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Analytics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Active Users by Tenant -->
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">Active Users by Tenant</h3>
            <div class="space-y-3">
                <template x-for="(data, tenantId) in stats.active_by_tenant" :key="tenantId">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="h-3 w-3 rounded-full bg-teal-500"></div>
                            <span class="text-sm font-medium" x-text="data.tenant?.name || tenantId"></span>
                        </div>
                        <span class="text-sm text-gray-600 dark:text-gray-400" x-text="data.count + ' users'"></span>
                    </div>
                </template>
                <div x-show="Object.keys(stats.active_by_tenant || {}).length === 0" class="text-center py-4 text-sm text-gray-600 dark:text-gray-400">
                    No active tenant sessions
                </div>
            </div>
        </div>

        <!-- Login Methods Distribution -->
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">Login Methods</h3>
            <div class="space-y-3">
                <template x-for="(count, method) in stats.active_by_method" :key="method">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="h-3 w-3 rounded-full" :class="getMethodColor(method)"></div>
                            <span class="text-sm font-medium capitalize" x-text="method"></span>
                        </div>
                        <span class="text-sm text-gray-600 dark:text-gray-400" x-text="count + ' sessions'"></span>
                    </div>
                </template>
                <div x-show="Object.keys(stats.active_by_method || {}).length === 0" class="text-center py-4 text-sm text-gray-600 dark:text-gray-400">
                    No active sessions
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Logins -->
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm">
            <div class="p-6 border-b border-border">
                <h3 class="text-lg font-semibold">Recent Logins</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Latest authentication activities</p>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    <template x-for="login in stats.recent_logins?.slice(0, 10)" :key="login.id">
                        <div class="flex items-center justify-between py-2 border-b border-border last:border-0">
                            <div class="flex items-center space-x-3">
                                <div class="h-8 w-8 rounded-full bg-muted flex items-center justify-center">
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400" x-text="login.user?.name?.charAt(0)?.toUpperCase()"></span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium" x-text="login.user?.name"></p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400" x-text="login.tenant?.name || 'Direct Login'"></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-600 dark:text-gray-400" x-text="formatTime(login.login_at)"></p>
                                <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-xs font-medium" 
                                      :class="getMethodBadgeClass(login.login_method)" 
                                      x-text="login.login_method?.toUpperCase()"></span>
                            </div>
                        </div>
                    </template>
                    <div x-show="!stats.recent_logins?.length" class="text-center py-8 text-sm text-gray-600 dark:text-gray-400">
                        No recent login activity
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Sessions -->
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm">
            <div class="p-6 border-b border-border">
                <h3 class="text-lg font-semibold">Active Sessions</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Currently active user sessions</p>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    <template x-for="session in stats.active_sessions?.slice(0, 10)" :key="session.id">
                        <div class="flex items-center justify-between py-2 border-b border-border last:border-0">
                            <div class="flex items-center space-x-3">
                                <div class="h-2 w-2 rounded-full bg-green-500"></div>
                                <div>
                                    <p class="text-sm font-medium" x-text="session.user?.name"></p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400" x-text="session.tenant?.name || 'Global Session'"></p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-xs text-gray-600 dark:text-gray-400" x-text="'Active for ' + getDuration(session.created_at, session.last_activity)"></p>
                                <p class="text-xs text-gray-600 dark:text-gray-400" x-text="'Last: ' + formatTime(session.last_activity)"></p>
                            </div>
                        </div>
                    </template>
                    <div x-show="!stats.active_sessions?.length" class="text-center py-8 text-sm text-gray-600 dark:text-gray-400">
                        No active sessions
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function analyticsPage() {
    return {
        stats: {},
        refreshInterval: null,
        
        initializeData() {
            this.stats = {!! json_encode($statistics ?? []) !!};
            this.startAutoRefresh();
        },
        
        async refreshData() {
            try {
                const response = await fetch('/admin/analytics/statistics', {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': await this.getToken()
                    },
                    credentials: 'same-origin'
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.stats = data.data;
                } else {
                    console.error('Failed to refresh analytics data');
                }
            } catch (error) {
                console.error('Error refreshing data:', error);
            }
        },
        
        startAutoRefresh() {
            // Refresh every 30 seconds
            this.refreshInterval = setInterval(() => {
                this.refreshData();
            }, 30000);
        },
        
        getMethodColor(method) {
            const colors = {
                'sso': 'bg-teal-500',
                'direct': 'bg-green-500',
                'api': 'bg-purple-500'
            };
            return colors[method] || 'bg-gray-500';
        },
        
        getMethodBadgeClass(method) {
            const classes = {
                'sso': 'bg-teal-100 dark:bg-teal-900 text-blue-800 dark:text-blue-200 ring-1 ring-inset ring-blue-200 dark:ring-blue-700',
                'direct': 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 ring-1 ring-inset ring-green-200 dark:ring-green-700',
                'api': 'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 ring-1 ring-inset ring-purple-200 dark:ring-purple-700'
            };
            return classes[method] || 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 ring-1 ring-inset ring-gray-200 dark:ring-gray-600';
        },
        
        formatTime(timestamp) {
            if (!timestamp) return 'N/A';
            const date = new Date(timestamp);
            const now = new Date();
            const diff = now - date;
            const minutes = Math.floor(diff / (1000 * 60));
            const hours = Math.floor(minutes / 60);
            const days = Math.floor(hours / 24);
            
            if (days > 0) return `${days}d ago`;
            if (hours > 0) return `${hours}h ago`;
            if (minutes > 0) return `${minutes}m ago`;
            return 'Just now';
        },
        
        getDuration(start, end) {
            if (!start || !end) return 'N/A';
            const startDate = new Date(start);
            const endDate = new Date(end);
            const diff = endDate - startDate;
            const minutes = Math.floor(diff / (1000 * 60));
            const hours = Math.floor(minutes / 60);
            
            if (hours > 0) return `${hours}h ${minutes % 60}m`;
            return `${minutes}m`;
        },
        
        async getToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        }
    };
}

// Global functions for header buttons
function refreshData() {
    const component = document.querySelector('[x-data*="analyticsPage"]');
    if (component && component._x_dataStack && component._x_dataStack[0]) {
        component._x_dataStack[0].refreshData();
    }
}

function exportData() {
    window.open('/admin/analytics/export?format=csv&start_date=' + 
                new Date(Date.now() - 30*24*60*60*1000).toISOString().split('T')[0] + 
                '&end_date=' + new Date().toISOString().split('T')[0], '_blank');
}
</script>
@endsection
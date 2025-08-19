@extends('layouts.admin')

@section('title', 'Audit Logs')

@section('header')
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Audit Logs</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Track and monitor all system activities and user changes
        </p>
    </div>
@endsection

@section('actions')
    <div class="flex items-center space-x-2">
        <button type="button" id="refresh-activities" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white h-10 px-4 py-2">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Refresh
        </button>
        @can('audit.export')
        <button type="button" id="export-csv" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-teal-600 dark:bg-teal-500 text-white hover:bg-teal-700 dark:hover:bg-teal-600 h-10 px-4 py-2">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Export
        </button>
        @endcan
    </div>
@endsection

@section('content')
<div class="space-y-6">

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Activities</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white" id="total-activities">{{ number_format($statistics['total_activities']) }}</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-teal-100 dark:bg-teal-900/20 flex items-center justify-center">
                    <i class="fas fa-list-alt text-teal-600 dark:text-teal-400 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Today's Activities</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white" id="today-activities">{{ number_format($statistics['today_activities']) }}</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-teal-100 dark:bg-teal-900/20 flex items-center justify-center">
                    <i class="fas fa-calendar-day text-teal-600 dark:text-teal-400 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Users</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white" id="active-users">{{ number_format($statistics['active_users']) }}</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-teal-100 dark:bg-teal-900/20 flex items-center justify-center">
                    <i class="fas fa-users text-teal-600 dark:text-teal-400 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Top Module</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white" id="top-module">{{ $statistics['top_module']['name'] ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $statistics['top_module']['count'] ?? 0 }} activities</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-teal-100 dark:bg-teal-900/20 flex items-center justify-center">
                    <i class="fas fa-th-large text-teal-600 dark:text-teal-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm mb-6">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Filter Activities</h2>
                
                <form id="filter-form" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7 gap-4">
                    <!-- Module Filter -->
                    <div>
                        <label for="module" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Module</label>
                        <select name="module" id="module" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                            <option value="">All Modules</option>
                            @foreach($filterOptions['modules'] as $moduleKey => $moduleData)
                                <option value="{{ $moduleKey }}" {{ request('module') === $moduleKey ? 'selected' : '' }}>
                                    {{ $moduleData['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Submodule Filter -->
                    <div>
                        <label for="submodule" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Submodule</label>
                        <select name="submodule" id="submodule" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                            <option value="">All Submodules</option>
                        </select>
                    </div>

                    <!-- User Filter -->
                    <div>
                        <label for="user_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">User</label>
                        <input type="text" name="user_id" id="user_id" placeholder="User ID"
                               value="{{ request('user_id') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                    </div>

                    <!-- Start Date -->
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                        <input type="date" name="start_date" id="start_date"
                               value="{{ request('start_date') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                    </div>

                    <!-- End Date -->
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Date</label>
                        <input type="date" name="end_date" id="end_date"
                               value="{{ request('end_date') }}"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                    </div>

                    <!-- Per Page -->
                    <div>
                        <label for="per_page" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Per Page</label>
                        <select name="per_page" id="per_page" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                            <option value="50" {{ request('per_page', 100) == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page', 100) == 100 ? 'selected' : '' }}>100</option>
                            <option value="200" {{ request('per_page', 100) == 200 ? 'selected' : '' }}>200</option>
                            <option value="500" {{ request('per_page', 100) == 500 ? 'selected' : '' }}>500</option>
                        </select>
                    </div>

                    <!-- Actions -->
                    <div class="xl:col-span-1 flex flex-col sm:flex-row xl:items-end space-y-2 sm:space-y-0 sm:space-x-2">
                        <button type="submit" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-teal-600 dark:bg-teal-500 text-white hover:bg-teal-700 dark:hover:bg-teal-600 h-10 px-4 py-2">
                            <i class="fas fa-search mr-2"></i>Filter
                        </button>
                        <button type="button" id="clear-filters" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white h-10 px-4 py-2">
                            <i class="fas fa-times mr-2"></i>Clear
                        </button>
                    </div>
                </form>
            </div>

            <!-- Export and Actions -->
            <div class="p-6 bg-gray-50 dark:bg-gray-700/50 flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Auto-refresh:</span>
                    <label class="inline-flex items-center">
                        <input type="checkbox" id="auto-refresh" class="form-checkbox h-4 w-4 text-teal-600 dark:text-teal-500 rounded focus:ring-teal-500 focus:ring-offset-0 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Every 30s</span>
                    </label>
                </div>

                <div class="flex flex-wrap gap-2">
                    @can('audit.export')
                    <button type="button" id="export-csv" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-green-600 dark:bg-green-500 text-white hover:bg-green-700 dark:hover:bg-green-600 h-10 px-4 py-2">
                        <i class="fas fa-file-csv mr-2"></i><span class="hidden sm:inline">Export </span>CSV
                    </button>
                    <button type="button" id="export-json" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-blue-600 dark:bg-blue-500 text-white hover:bg-blue-700 dark:hover:bg-blue-600 h-10 px-4 py-2">
                        <i class="fas fa-file-code mr-2"></i><span class="hidden sm:inline">Export </span>JSON
                    </button>
                    @endcan

                    @can('audit.manage')
                    <button type="button" id="cleanup-logs" class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-red-600 dark:bg-red-500 text-white hover:bg-red-700 dark:hover:bg-red-600 h-10 px-4 py-2">
                        <i class="fas fa-trash mr-2"></i>Cleanup
                    </button>
                    @endcan
                </div>
            </div>
        </div>

        <!-- Activities Table -->
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">All Activities</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Showing {{ $activities->firstItem() ?? 0 }} to {{ $activities->lastItem() ?? 0 }} 
                            of {{ $activities->total() }} total entries
                            @if(request('per_page'))
                                ({{ request('per_page') }} per page)
                            @endif
                        </p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Last updated:</span>
                        <span id="last-updated" class="text-sm text-teal-600 dark:text-teal-400 font-medium">{{ now()->format('H:i:s') }}</span>
                        <button type="button" id="refresh-activities" class="p-2 text-gray-400 dark:text-gray-500 hover:text-teal-600 dark:hover:text-teal-400 transition duration-200">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Activity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Module</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Subject</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="activities-table" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($activities as $activity)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition duration-200">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8">
                                        @php
                                            $module = $activity->properties['module'] ?? $activity->log_name;
                                            $moduleIcon = $filterOptions['modules'][$module]['icon'] ?? 'fas fa-circle';
                                            $moduleColor = $filterOptions['modules'][$module]['color'] ?? 'gray';
                                        @endphp
                                        <div class="h-8 w-8 rounded-full bg-{{ $moduleColor }}-100 dark:bg-{{ $moduleColor }}-900/20 flex items-center justify-center">
                                            <i class="{{ $moduleIcon }} text-{{ $moduleColor }}-600 dark:text-{{ $moduleColor }}-400 text-sm"></i>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $activity->description }}</div>
                                        @if($activity->properties['submodule'] ?? false)
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $activity->properties['submodule'] }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $moduleColor }}-100 dark:bg-{{ $moduleColor }}-900/20 text-{{ $moduleColor }}-800 dark:text-{{ $moduleColor }}-300">
                                    {{ $filterOptions['modules'][$module]['name'] ?? $module }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($activity->causer)
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-8 w-8">
                                        <div class="h-8 w-8 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                            <span class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ substr($activity->causer->name, 0, 2) }}</span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $activity->causer->name }}</div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $activity->causer->email }}</div>
                                    </div>
                                </div>
                                @else
                                <span class="text-sm text-gray-500 dark:text-gray-400 italic">System</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($activity->subject)
                                <div class="text-sm text-gray-900 dark:text-white">{{ class_basename($activity->subject_type) }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">#{{ $activity->subject_id }}</div>
                                @else
                                <span class="text-sm text-gray-500 dark:text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">{{ $activity->created_at->format('M j, Y') }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $activity->created_at->format('H:i:s') }}</div>
                                <div class="text-xs text-gray-400 dark:text-gray-500">{{ $activity->created_at->diffForHumans() }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('admin.audit-logs.show', $activity) }}" 
                                   class="text-teal-600 dark:text-teal-400 hover:text-teal-900 dark:hover:text-teal-300 transition duration-200">
                                    <i class="fas fa-eye mr-1"></i>View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="text-gray-500 dark:text-gray-400">
                                    <i class="fas fa-search text-4xl mb-4"></i>
                                    <p class="text-lg">No audit logs found</p>
                                    <p class="text-sm">Try adjusting your filters or check back later</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View -->
            <div class="md:hidden" id="activities-mobile">
                @forelse($activities as $activity)
                    @php
                        $module = $activity->properties['module'] ?? $activity->log_name;
                        $moduleIcon = $filterOptions['modules'][$module]['icon'] ?? 'fas fa-circle';
                        $moduleColor = $filterOptions['modules'][$module]['color'] ?? 'gray';
                    @endphp
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 last:border-b-0 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition duration-200">
                        <!-- Activity Header -->
                        <div class="flex items-start space-x-3 mb-3">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-{{ $moduleColor }}-100 dark:bg-{{ $moduleColor }}-900/20 flex items-center justify-center">
                                    <i class="{{ $moduleIcon }} text-{{ $moduleColor }}-600 dark:text-{{ $moduleColor }}-400"></i>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $activity->description }}</h3>
                                    <a href="{{ route('admin.audit-logs.show', $activity) }}" 
                                       class="text-teal-600 dark:text-teal-400 hover:text-teal-900 dark:hover:text-teal-300 transition duration-200">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                                @if($activity->properties['submodule'] ?? false)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $activity->properties['submodule'] }}</p>
                                @endif
                            </div>
                        </div>

                        <!-- Activity Details -->
                        <div class="space-y-2">
                            <!-- Module & User Row -->
                            <div class="flex items-center justify-between">
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-{{ $moduleColor }}-100 dark:bg-{{ $moduleColor }}-900/20 text-{{ $moduleColor }}-800 dark:text-{{ $moduleColor }}-300">
                                    {{ $filterOptions['modules'][$module]['name'] ?? $module }}
                                </span>
                                @if($activity->causer)
                                <div class="flex items-center space-x-2">
                                    <div class="h-6 w-6 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                        <span class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ substr($activity->causer->name, 0, 1) }}</span>
                                    </div>
                                    <span class="text-xs text-gray-600 dark:text-gray-400 truncate max-w-24">{{ $activity->causer->name }}</span>
                                </div>
                                @else
                                <span class="text-xs text-gray-500 dark:text-gray-400 italic">System</span>
                                @endif
                            </div>

                            <!-- Subject & Date Row -->
                            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                <div>
                                    @if($activity->subject)
                                        <span>{{ class_basename($activity->subject_type) }} #{{ $activity->subject_id }}</span>
                                    @else
                                        <span>-</span>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <div>{{ $activity->created_at->format('M j, Y') }}</div>
                                    <div>{{ $activity->created_at->format('H:i:s') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center">
                        <div class="text-gray-500 dark:text-gray-400">
                            <i class="fas fa-search text-3xl mb-3"></i>
                            <p class="text-base font-medium">No audit logs found</p>
                            <p class="text-sm">Try adjusting your filters or check back later</p>
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($activities->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $activities->appends(request()->query())->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Export Modal -->
<div id="export-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 dark:bg-gray-900 dark:bg-opacity-75 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border border-gray-200 dark:border-gray-700 w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3 text-center">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Export Audit Logs</h3>
            <div class="mt-4">
                <form id="export-form">
                    <input type="hidden" name="format" id="export-format">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date Range</label>
                        <div class="grid grid-cols-2 gap-2">
                            <input type="date" name="start_date" class="px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md focus:ring-teal-500 focus:border-teal-500">
                            <input type="date" name="end_date" class="px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md focus:ring-teal-500 focus:border-teal-500">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Module (Optional)</label>
                        <select name="module" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md focus:ring-teal-500 focus:border-teal-500">
                            <option value="">All Modules</option>
                            @foreach($filterOptions['modules'] as $moduleKey => $moduleData)
                                <option value="{{ $moduleKey }}">{{ $moduleData['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex space-x-3">
                        <button type="submit" class="flex-1 inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-teal-600 dark:bg-teal-500 text-white hover:bg-teal-700 dark:hover:bg-teal-600 h-10 px-4 py-2">
                            Export
                        </button>
                        <button type="button" id="cancel-export" class="flex-1 inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white h-10 px-4 py-2">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Cleanup Modal -->
@can('audit.manage')
<div id="cleanup-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 dark:bg-gray-900 dark:bg-opacity-75 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border border-gray-200 dark:border-gray-700 w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/20">
                <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mt-4">Cleanup Old Audit Logs</h3>
            <div class="mt-4">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">Delete audit logs older than the specified number of days. This action cannot be undone.</p>
                
                <form id="cleanup-form">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Delete logs older than (days)</label>
                        <input type="number" name="days" min="30" max="365" value="90" 
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-md focus:ring-red-500 focus:border-red-500">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Minimum: 30 days, Maximum: 365 days</p>
                    </div>

                    <div class="flex space-x-3">
                        <button type="submit" class="flex-1 inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-red-600 dark:bg-red-500 text-white hover:bg-red-700 dark:hover:bg-red-600 h-10 px-4 py-2">
                            Delete Logs
                        </button>
                        <button type="button" id="cancel-cleanup" class="flex-1 inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white h-10 px-4 py-2">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endcan

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let autoRefreshInterval;
    
    // Module-Submodule dependency
    const moduleSubmodules = @json($filterOptions['modules']);
    
    document.getElementById('module').addEventListener('change', function() {
        const submoduleSelect = document.getElementById('submodule');
        const selectedModule = this.value;
        
        // Clear existing options
        submoduleSelect.innerHTML = '<option value="">All Submodules</option>';
        
        if (selectedModule && moduleSubmodules[selectedModule] && moduleSubmodules[selectedModule].submodules) {
            Object.entries(moduleSubmodules[selectedModule].submodules).forEach(([key, value]) => {
                const option = document.createElement('option');
                option.value = key;
                option.textContent = value.name;
                submoduleSelect.appendChild(option);
            });
        }
    });
    
    // Auto-refresh functionality
    document.getElementById('auto-refresh').addEventListener('change', function() {
        if (this.checked) {
            autoRefreshInterval = setInterval(refreshActivities, 30000);
        } else {
            clearInterval(autoRefreshInterval);
        }
    });
    
    // Manual refresh
    document.getElementById('refresh-activities').addEventListener('click', refreshActivities);
    
    // Clear filters
    document.getElementById('clear-filters').addEventListener('click', function() {
        document.getElementById('filter-form').reset();
        window.location.href = window.location.pathname;
    });
    
    // Export functionality
    document.getElementById('export-csv').addEventListener('click', function() {
        showExportModal('csv');
    });
    
    document.getElementById('export-json').addEventListener('click', function() {
        showExportModal('json');
    });
    
    // Cleanup functionality
    @can('audit.manage')
    document.getElementById('cleanup-logs').addEventListener('click', function() {
        document.getElementById('cleanup-modal').classList.remove('hidden');
    });
    
    document.getElementById('cancel-cleanup').addEventListener('click', function() {
        document.getElementById('cleanup-modal').classList.add('hidden');
    });
    
    document.getElementById('cleanup-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('{{ route("admin.audit-logs.cleanup") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Successfully deleted ${data.deleted_count} old audit log entries.`);
                document.getElementById('cleanup-modal').classList.add('hidden');
                refreshActivities();
            } else {
                alert('Error cleaning up logs. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error cleaning up logs. Please try again.');
        });
    });
    @endcan
    
    // Export modal functionality
    document.getElementById('cancel-export').addEventListener('click', function() {
        document.getElementById('export-modal').classList.add('hidden');
    });
    
    document.getElementById('export-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        // Create and submit form for file download
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.audit-logs.export") }}';
        
        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        form.appendChild(csrfInput);
        
        // Add form data
        for (let [key, value] of formData.entries()) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        }
        
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
        
        document.getElementById('export-modal').classList.add('hidden');
    });
    
    function showExportModal(format) {
        document.getElementById('export-format').value = format;
        document.getElementById('export-modal').classList.remove('hidden');
    }
    
    function refreshActivities() {
        const formData = new FormData(document.getElementById('filter-form'));
        const params = new URLSearchParams(formData);
        
        fetch(`{{ route('admin.audit-logs.activities') }}?${params}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateActivitiesTable(data.data);
                    document.getElementById('last-updated').textContent = new Date().toLocaleTimeString();
                }
            })
            .catch(error => console.error('Error refreshing activities:', error));
        
        // Also refresh statistics
        fetch(`{{ route('admin.audit-logs.statistics') }}?${params}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateStatistics(data.data);
                }
            })
            .catch(error => console.error('Error refreshing statistics:', error));
    }
    
    function updateActivitiesTable(activities) {
        const tbody = document.getElementById('activities-table');
        
        if (activities.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <div class="text-gray-500">
                            <i class="fas fa-search text-4xl mb-4"></i>
                            <p class="text-lg">No audit logs found</p>
                            <p class="text-sm">Try adjusting your filters or check back later</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = activities.map(activity => {
            const moduleKey = activity.properties.module || activity.log_name;
            const module = moduleSubmodules[moduleKey] || { name: moduleKey, icon: 'fas fa-circle', color: 'gray' };
            
            return `
                <tr class="hover:bg-gray-50 transition duration-200">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-8 w-8">
                                <div class="h-8 w-8 rounded-full bg-${module.color}-100 flex items-center justify-center">
                                    <i class="${module.icon} text-${module.color}-600 text-sm"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">${activity.description}</div>
                                ${activity.properties.submodule ? `<div class="text-sm text-gray-500">${activity.properties.submodule}</div>` : ''}
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-${module.color}-100 text-${module.color}-800">
                            ${module.name}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        ${activity.causer ? `
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-8 w-8">
                                    <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                                        <span class="text-xs font-medium text-gray-600">${activity.causer.name.substring(0, 2)}</span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">${activity.causer.name}</div>
                                    <div class="text-sm text-gray-500">${activity.causer.email}</div>
                                </div>
                            </div>
                        ` : '<span class="text-sm text-gray-500 italic">System</span>'}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        ${activity.subject ? `
                            <div class="text-sm text-gray-900">${activity.subject.type}</div>
                            <div class="text-sm text-gray-500">#${activity.subject.id}</div>
                        ` : '<span class="text-sm text-gray-500">-</span>'}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">${new Date(activity.created_at_full).toLocaleDateString()}</div>
                        <div class="text-sm text-gray-500">${new Date(activity.created_at_full).toLocaleTimeString()}</div>
                        <div class="text-xs text-gray-400">${activity.created_at}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="/admin/audit-logs/${activity.id}" class="text-teal-600 hover:text-teal-900 transition duration-200">
                            <i class="fas fa-eye mr-1"></i>View
                        </a>
                    </td>
                </tr>
            `;
        }).join('');
    }
    
    function updateStatistics(statistics) {
        document.getElementById('total-activities').textContent = new Intl.NumberFormat().format(statistics.total_activities);
        document.getElementById('today-activities').textContent = new Intl.NumberFormat().format(statistics.today_activities);
        document.getElementById('active-users').textContent = new Intl.NumberFormat().format(statistics.active_users);
        
        const topModuleElement = document.getElementById('top-module');
        if (statistics.top_module && statistics.top_module.name) {
            topModuleElement.textContent = statistics.top_module.name;
            topModuleElement.nextElementSibling.textContent = `${statistics.top_module.count} activities`;
        } else {
            topModuleElement.textContent = 'N/A';
            topModuleElement.nextElementSibling.textContent = '0 activities';
        }
    }
});
</script>
@endpush
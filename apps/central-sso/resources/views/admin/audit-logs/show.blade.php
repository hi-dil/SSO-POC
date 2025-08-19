@extends('layouts.admin')

@section('title', 'Audit Log Details')

@section('header')
    <div>
        <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('admin.audit-logs.index') }}" class="inline-flex items-center text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-teal-600 dark:hover:text-teal-400">
                        <i class="fas fa-list-alt mr-2"></i>
                        Audit Logs
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <i class="fas fa-chevron-right text-gray-400 dark:text-gray-500 mx-2"></i>
                        <span class="ml-1 text-sm font-medium text-gray-500 dark:text-gray-400 md:ml-2">Activity #{{ $activity->id }}</span>
                    </div>
                </li>
            </ol>
        </nav>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Audit Log Details</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Detailed information about activity #{{ $activity->id }}
        </p>
    </div>
@endsection

@section('actions')
    <div class="flex items-center space-x-2">
        <a href="{{ route('admin.audit-logs.index') }}" 
           class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white h-10 px-4 py-2">
            <i class="fas fa-arrow-left mr-2"></i>Back to List
        </a>
    </div>
@endsection

@section('content')
<div class="space-y-6">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Activity Details -->
        <div class="lg:col-span-2">
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                <!-- Activity Header -->
                <div class="p-6 bg-gradient-to-r from-teal-500 to-teal-600 dark:from-teal-600 dark:to-teal-700 text-white">
                    <div class="flex items-center">
                        @php
                            $module = $activity->properties['module'] ?? $activity->log_name;
                            $moduleConfig = config('audit-modules.modules.' . $module, []);
                            $moduleIcon = $moduleConfig['icon'] ?? 'fas fa-circle';
                            $moduleName = $moduleConfig['name'] ?? $module;
                        @endphp
                        <div class="flex-shrink-0 h-12 w-12">
                            <div class="h-12 w-12 rounded-full bg-white bg-opacity-20 flex items-center justify-center">
                                <i class="{{ $moduleIcon }} text-white text-xl"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h2 class="text-xl font-semibold">{{ $activity->description }}</h2>
                            <p class="text-teal-100">{{ $moduleName }}</p>
                            @if($activity->properties['submodule'] ?? false)
                            <p class="text-teal-200 text-sm">{{ $activity->properties['submodule'] }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Activity Content -->
                <div class="p-6">
                    <!-- Basic Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Basic Information</h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Activity ID</dt>
                                    <dd class="text-sm text-gray-900 dark:text-white font-mono">#{{ $activity->id }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Log Name</dt>
                                    <dd class="text-sm text-gray-900 dark:text-white">{{ $activity->log_name }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Event Type</dt>
                                    <dd class="text-sm text-gray-900 dark:text-white">{{ $activity->event ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Date & Time</dt>
                                    <dd class="text-sm text-gray-900 dark:text-white">
                                        {{ $activity->created_at->format('F j, Y \a\t g:i:s A') }}
                                        <span class="text-gray-500 dark:text-gray-400">({{ $activity->created_at->diffForHumans() }})</span>
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Module Information</h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Module</dt>
                                    <dd class="text-sm text-gray-900 dark:text-white">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-100 dark:bg-teal-900/20 text-teal-800 dark:text-teal-300">
                                            <i class="{{ $moduleIcon }} mr-1"></i>
                                            {{ $moduleName }}
                                        </span>
                                    </dd>
                                </div>
                                @if($activity->properties['submodule'] ?? false)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Submodule</dt>
                                    <dd class="text-sm text-gray-900 dark:text-white">{{ $activity->properties['submodule'] }}</dd>
                                </div>
                                @endif
                                @if($activity->properties['ip_address'] ?? false)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">IP Address</dt>
                                    <dd class="text-sm text-gray-900 dark:text-white font-mono">{{ $activity->properties['ip_address'] }}</dd>
                                </div>
                                @endif
                                @if($activity->properties['request_id'] ?? false)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Request ID</dt>
                                    <dd class="text-sm text-gray-900 dark:text-white font-mono">{{ $activity->properties['request_id'] }}</dd>
                                </div>
                                @endif
                            </dl>
                        </div>
                    </div>

                    <!-- User Agent Information -->
                    @if($activity->properties['user_agent'] ?? false)
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">User Agent</h3>
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                            <p class="text-sm text-gray-700 dark:text-gray-300 font-mono break-all">{{ $activity->properties['user_agent'] }}</p>
                        </div>
                    </div>
                    @endif

                    <!-- Combined Activity Details -->
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Activity Details</h3>
                        
                        <!-- Changes Made (if any) -->
                        @if($activity->changes && count($activity->changes) > 0)
                        <div class="mb-6">
                            @if(isset($activity->changes['attributes']) && count($activity->changes['attributes']) > 0)
                            <div class="mb-4">
                                <h4 class="text-md font-medium text-gray-800 dark:text-gray-200 mb-3 flex items-center">
                                    <i class="fas fa-plus-circle text-green-600 dark:text-green-400 mr-2"></i>
                                    Data Changes
                                </h4>
                                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                                    <dl class="space-y-2">
                                        @foreach($activity->changes['attributes'] as $key => $value)
                                        <div class="flex border-b border-green-200 dark:border-green-700 last:border-0 pb-2 last:pb-0">
                                            <dt class="text-sm font-medium text-green-800 dark:text-green-300 w-1/4 flex-shrink-0">{{ ucfirst(str_replace('_', ' ', $key)) }}:</dt>
                                            <dd class="text-sm w-3/4 ml-4">
                                                <div class="text-green-900 dark:text-green-200 font-mono">
                                                    @if(isset($activity->changes['old'][$key]))
                                                        <span class="text-red-600 dark:text-red-400 line-through">{{ is_array($activity->changes['old'][$key]) ? json_encode($activity->changes['old'][$key]) : $activity->changes['old'][$key] }}</span>
                                                        <span class="mx-2">→</span>
                                                    @endif
                                                    <span class="text-green-600 dark:text-green-400 font-semibold">{{ is_array($value) ? json_encode($value) : $value }}</span>
                                                </div>
                                            </dd>
                                        </div>
                                        @endforeach
                                    </dl>
                                </div>
                            </div>
                            @endif
                        </div>
                        @endif

                        <!-- Request Context & Properties -->
                        @if($activity->properties && count($activity->properties) > 0)
                        <div>
                            <h4 class="text-md font-medium text-gray-800 dark:text-gray-200 mb-3 flex items-center">
                                <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 mr-2"></i>
                                Request Context
                            </h4>
                            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @foreach($activity->properties as $key => $value)
                                        @if(!in_array($key, ['module', 'submodule'])) {{-- Skip module/submodule as they're shown elsewhere --}}
                                        <div>
                                            <dt class="text-xs font-medium text-blue-800 dark:text-blue-300 uppercase tracking-wider">{{ ucfirst(str_replace('_', ' ', $key)) }}</dt>
                                            <dd class="text-sm text-blue-900 dark:text-blue-200 mt-1 font-mono break-all">
                                                @if(is_array($value))
                                                    <details class="cursor-pointer">
                                                        <summary class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">View array data</summary>
                                                        <pre class="mt-2 text-xs bg-blue-100 dark:bg-blue-900/30 p-2 rounded">{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                                    </details>
                                                @elseif(strlen((string)$value) > 100)
                                                    <details class="cursor-pointer">
                                                        <summary class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">{{ substr($value, 0, 100) }}...</summary>
                                                        <div class="mt-2 text-xs bg-blue-100 dark:bg-blue-900/30 p-2 rounded break-all">{{ $value }}</div>
                                                    </details>
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </dd>
                                        </div>
                                        @endif
                                    @endforeach
                                </dl>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- User Information -->
            @if($activity->causer)
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Performed By</h3>
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-12 w-12">
                            <div class="h-12 w-12 rounded-full bg-teal-100 dark:bg-teal-900/20 flex items-center justify-center">
                                <span class="text-lg font-medium text-teal-600 dark:text-teal-400">{{ substr($activity->causer->name, 0, 2) }}</span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $activity->causer->name }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $activity->causer->email }}</div>
                            @if($activity->causer->is_admin)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-300 mt-1">
                                Admin
                            </span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">User ID</dt>
                                <dd class="text-sm text-gray-900 dark:text-white font-mono">#{{ $activity->causer->id }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">Member Since</dt>
                                <dd class="text-sm text-gray-900 dark:text-white">{{ $activity->causer->created_at->format('M j, Y') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('admin.audit-logs.user-activities', $activity->causer->id) }}" 
                           class="text-sm text-teal-600 dark:text-teal-400 hover:text-teal-800 dark:hover:text-teal-300 font-medium">
                            <i class="fas fa-history mr-1"></i>View User's Activity History
                        </a>
                    </div>
                </div>
            </div>
            @else
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Performed By</h3>
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-12 w-12">
                            <div class="h-12 w-12 rounded-full bg-gray-100 dark:bg-gray-600 flex items-center justify-center">
                                <i class="fas fa-cog text-gray-600 dark:text-gray-400"></i>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">System</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Automated action</div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Subject Information -->
            @if($activity->subject)
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Subject</h3>
                    <div class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Type</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">{{ class_basename($activity->subject_type) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">ID</dt>
                            <dd class="text-sm text-gray-900 dark:text-white font-mono">#{{ $activity->subject_id }}</dd>
                        </div>
                        @if($activity->subject)
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Current State</dt>
                            <dd class="text-sm text-gray-900 dark:text-white">
                                @if(method_exists($activity->subject, 'name'))
                                    {{ $activity->subject->name }}
                                @elseif(method_exists($activity->subject, 'title'))
                                    {{ $activity->subject->title }}
                                @elseif(method_exists($activity->subject, 'email'))
                                    {{ $activity->subject->email }}
                                @else
                                    Available
                                @endif
                            </dd>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Related Activities -->
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Quick Actions</h3>
                    <div class="space-y-2">
                        @if($activity->causer)
                        <a href="{{ route('admin.audit-logs.index', ['user_id' => $activity->causer->id]) }}" 
                           class="block w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-md transition duration-200">
                            <i class="fas fa-user mr-2 text-gray-400 dark:text-gray-500"></i>
                            View all activities by this user
                        </a>
                        @endif
                        
                        @if($activity->properties['module'] ?? false)
                        <a href="{{ route('admin.audit-logs.index', ['module' => $activity->properties['module']]) }}" 
                           class="block w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-md transition duration-200">
                            <i class="fas fa-th-large mr-2 text-gray-400 dark:text-gray-500"></i>
                            View all {{ $moduleName }} activities
                        </a>
                        @endif

                        @if($activity->subject)
                        <a href="{{ route('admin.audit-logs.index', ['subject_type' => $activity->subject_type, 'subject_id' => $activity->subject_id]) }}" 
                           class="block w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-md transition duration-200">
                            <i class="fas fa-crosshairs mr-2 text-gray-400 dark:text-gray-500"></i>
                            View all activities for this subject
                        </a>
                        @endif

                        <a href="{{ route('admin.audit-logs.index', ['start_date' => $activity->created_at->format('Y-m-d')]) }}" 
                           class="block w-full text-left px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-md transition duration-200">
                            <i class="fas fa-calendar mr-2 text-gray-400 dark:text-gray-500"></i>
                            View all activities from {{ $activity->created_at->format('M j, Y') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add copy functionality for monospace text (IDs, request IDs, etc.)
    document.querySelectorAll('.font-mono').forEach(element => {
        element.style.cursor = 'pointer';
        element.title = 'Click to copy';
        
        element.addEventListener('click', function() {
            navigator.clipboard.writeText(this.textContent.trim()).then(() => {
                // Show temporary feedback
                const original = this.textContent;
                this.textContent = 'Copied!';
                this.style.color = '#059669'; // green
                
                setTimeout(() => {
                    this.textContent = original;
                    this.style.color = '';
                }, 1000);
            });
        });
    });
});
</script>
@endpush
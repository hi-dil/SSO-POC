@extends('layouts.admin')

@section('title', 'System Settings')

@section('header')
    <div class="flex items-center space-x-4">
        <div class="flex-shrink-0">
            <div class="w-12 h-12 bg-gradient-to-r from-teal-custom to-teal-custom-light rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
            </div>
        </div>
        <div>
            <h1 class="text-xl font-bold bg-gradient-to-r from-teal-custom to-teal-custom-light bg-clip-text text-transparent">
                System Settings
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Configure JWT tokens, sessions, security, and system preferences
            </p>
        </div>
    </div>
@endsection

@section('actions')
    <div class="flex items-center space-x-3">
        <button onclick="clearCache()" 
                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 transition-all duration-200">
            <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Clear Cache
        </button>
    </div>
@endsection

@section('content')
<div class="max-w-6xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-8">
        @csrf
        @method('PUT')

        @foreach($settings as $groupName => $groupSettings)
            <div class="bg-white dark:bg-gray-800 shadow-lg rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="bg-gradient-to-r from-teal-custom to-teal-custom-light px-6 py-4">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        @if($groupName === 'jwt')
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                            JWT Token Settings
                        @elseif($groupName === 'session')
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Session Management
                        @elseif($groupName === 'security')
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            Security Settings
                        @elseif($groupName === 'system')
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                            </svg>
                            System Configuration
                        @else
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4" />
                            </svg>
                            {{ ucfirst($groupName) }} Settings
                        @endif
                    </h3>
                    <p class="text-teal-100 text-sm mt-1">
                        @if($groupName === 'jwt')
                            Configure JWT access tokens, refresh tokens, and security parameters
                        @elseif($groupName === 'session')
                            Manage user session duration and behavior settings
                        @elseif($groupName === 'security')
                            Control login attempts, lockouts, and password policies
                        @elseif($groupName === 'system')
                            System-wide configuration and maintenance settings
                        @else
                            Configuration options for {{ $groupName }}
                        @endif
                    </p>
                </div>
                <div class="p-6">
                    <div class="space-y-6">
                        @foreach($groupSettings as $setting)
                            <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-4 hover:border-teal-300 dark:hover:border-teal-500 transition-colors">
                                <div class="flex items-start justify-between">
                                    <div class="flex-grow">
                                        <label for="setting_{{ $setting['key'] }}" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                            {{ $setting['label'] }}
                                        </label>
                                        @if($setting['description'])
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ $setting['description'] }}</p>
                                        @endif
                                        
                                        @if($setting['type'] === 'boolean')
                                            <div class="flex items-center">
                                                <label class="relative inline-flex items-center cursor-pointer">
                                                    <input type="checkbox" 
                                                           name="settings[{{ $setting['key'] }}]" 
                                                           id="setting_{{ $setting['key'] }}"
                                                           value="1"
                                                           {{ old("settings.{$setting['key']}", $setting['value']) ? 'checked' : '' }}
                                                           class="sr-only peer">
                                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-teal-300 dark:peer-focus:ring-teal-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-teal-600"></div>
                                                    <span class="ml-3 text-sm text-gray-900 dark:text-gray-300">
                                                        {{ $setting['value'] ? 'Enabled' : 'Disabled' }}
                                                    </span>
                                                </label>
                                            </div>
                                        @elseif($setting['type'] === 'json')
                                            <textarea name="settings[{{ $setting['key'] }}]" 
                                                      id="setting_{{ $setting['key'] }}"
                                                      rows="4"
                                                      class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg shadow-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all duration-200 @error("settings.{$setting['key']}") border-red-300 focus:ring-red-500 focus:border-red-500 @enderror font-mono text-sm"
                                                      placeholder="Enter valid JSON">{{ old("settings.{$setting['key']}", $setting['raw_value']) }}</textarea>
                                        @else
                                            <input type="{{ $setting['type'] === 'integer' ? 'number' : 'text' }}" 
                                                   name="settings[{{ $setting['key'] }}]" 
                                                   id="setting_{{ $setting['key'] }}"
                                                   value="{{ old("settings.{$setting['key']}", $setting['raw_value']) }}"
                                                   @if($setting['type'] === 'integer') min="1" @endif
                                                   class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg shadow-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all duration-200 @error("settings.{$setting['key']}") border-red-300 focus:ring-red-500 focus:border-red-500 @enderror">
                                        @endif
                                        
                                        @error("settings.{$setting['key']}")
                                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                                {{ $message }}
                                            </p>
                                        @enderror
                                    </div>
                                    
                                    <button type="button" 
                                            onclick="resetSetting('{{ $setting['key'] }}')"
                                            class="ml-4 p-2 text-gray-400 hover:text-teal-600 dark:hover:text-teal-400 transition-colors"
                                            title="Reset to default">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Form Actions -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-xl border border-gray-200 dark:border-gray-700 px-6 py-4">
            <div class="flex flex-col sm:flex-row justify-between items-center space-y-3 sm:space-y-0 sm:space-x-4">
                <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Settings are cached for 1 hour. Clear cache to apply changes immediately.</span>
                </div>
                <div class="flex items-center space-x-3">
                    <button type="submit" 
                            class="inline-flex items-center px-6 py-3 border border-transparent rounded-lg shadow-lg text-sm font-semibold text-white bg-gradient-to-r from-teal-custom to-teal-custom-light hover:from-teal-600 hover:to-cyan-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 transition-all duration-200 transform hover:scale-105">
                        <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Save Settings
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function resetSetting(key) {
    if (!confirm('Are you sure you want to reset this setting to its default value?')) {
        return;
    }
    
    fetch(`{{ route('admin.settings.index') }}/reset/${key}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload the page to show the reset value
            window.location.reload();
        } else {
            alert(data.error || 'Failed to reset setting');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to reset setting');
    });
}

function clearCache() {
    if (!confirm('Are you sure you want to clear the settings cache?')) {
        return;
    }
    
    fetch(`{{ route('admin.settings.index') }}/clear-cache`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
        } else {
            alert(data.error || 'Failed to clear cache');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to clear cache');
    });
}
</script>
@endsection
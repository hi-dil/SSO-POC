@extends('layouts.admin')

@section('title', 'Edit Tenant')

@section('header')
    <h2 class="text-2xl font-bold leading-7 text-gray-900 dark:text-white sm:text-3xl sm:truncate">
        Edit Tenant: {{ $tenant->name }}
    </h2>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Update tenant information and settings
    </p>
@endsection

@section('actions')
    <div class="flex space-x-3">
        @can('tenants.view')
            <a href="{{ route('admin.tenants.show', $tenant) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                View
            </a>
            <a href="{{ route('admin.tenants.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Tenants
            </a>
        @endcan
    </div>
@endsection

@section('content')
<div class="px-4 py-5 sm:p-6">
    <form method="POST" action="{{ route('admin.tenants.update', $tenant) }}" class="space-y-6">
        @csrf
        @method('PUT')
        
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Tenant Name <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       name="name" 
                       id="name" 
                       value="{{ old('name', $tenant->name) }}"
                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('name') border-red-300 @enderror"
                       placeholder="e.g., Acme Corporation"
                       required>
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Slug -->
            <div>
                <label for="slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Slug <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       name="slug" 
                       id="slug" 
                       value="{{ old('slug', $tenant->slug) }}"
                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('slug') border-red-300 @enderror"
                       placeholder="e.g., acme-corp"
                       pattern="[a-z0-9-]+"
                       title="Only lowercase letters, numbers, and hyphens allowed"
                       required>
                @error('slug')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Used in URLs and must be unique. Only lowercase letters, numbers, and hyphens.</p>
            </div>

            <!-- Domain -->
            <div>
                <label for="domain" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Domain
                </label>
                <input type="text" 
                       name="domain" 
                       id="domain" 
                       value="{{ old('domain', $tenant->domain) }}"
                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('domain') border-red-300 @enderror"
                       placeholder="e.g., acme.example.com">
                @error('domain')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Max Users -->
            <div>
                <label for="max_users" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Max Users
                </label>
                <input type="number" 
                       name="max_users" 
                       id="max_users" 
                       value="{{ old('max_users', $tenant->max_users) }}"
                       min="1"
                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('max_users') border-red-300 @enderror"
                       placeholder="Leave empty for unlimited">
                @error('max_users')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Plan -->
            <div>
                <label for="plan" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Plan <span class="text-red-500">*</span>
                </label>
                <select name="plan" 
                        id="plan" 
                        required
                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('plan') border-red-300 @enderror">
                    <option value="">Select a plan</option>
                    <option value="starter" {{ old('plan', $tenant->plan) == 'starter' ? 'selected' : '' }}>Starter</option>
                    <option value="basic" {{ old('plan', $tenant->plan) == 'basic' ? 'selected' : '' }}>Basic</option>
                    <option value="premium" {{ old('plan', $tenant->plan) == 'premium' ? 'selected' : '' }}>Premium</option>
                    <option value="pro" {{ old('plan', $tenant->plan) == 'pro' ? 'selected' : '' }}>Pro</option>
                    <option value="enterprise" {{ old('plan', $tenant->plan) == 'enterprise' ? 'selected' : '' }}>Enterprise</option>
                </select>
                @error('plan')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Industry -->
            <div>
                <label for="industry" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Industry <span class="text-red-500">*</span>
                </label>
                <select name="industry" 
                        id="industry" 
                        required
                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('industry') border-red-300 @enderror">
                    <option value="">Select an industry</option>
                    <option value="technology" {{ old('industry', $tenant->industry) == 'technology' ? 'selected' : '' }}>Technology</option>
                    <option value="healthcare" {{ old('industry', $tenant->industry) == 'healthcare' ? 'selected' : '' }}>Healthcare</option>
                    <option value="finance" {{ old('industry', $tenant->industry) == 'finance' ? 'selected' : '' }}>Finance</option>
                    <option value="education" {{ old('industry', $tenant->industry) == 'education' ? 'selected' : '' }}>Education</option>
                    <option value="retail" {{ old('industry', $tenant->industry) == 'retail' ? 'selected' : '' }}>Retail</option>
                    <option value="manufacturing" {{ old('industry', $tenant->industry) == 'manufacturing' ? 'selected' : '' }}>Manufacturing</option>
                    <option value="consulting" {{ old('industry', $tenant->industry) == 'consulting' ? 'selected' : '' }}>Consulting</option>
                    <option value="media" {{ old('industry', $tenant->industry) == 'media' ? 'selected' : '' }}>Media</option>
                    <option value="nonprofit" {{ old('industry', $tenant->industry) == 'nonprofit' ? 'selected' : '' }}>Nonprofit</option>
                    <option value="government" {{ old('industry', $tenant->industry) == 'government' ? 'selected' : '' }}>Government</option>
                </select>
                @error('industry')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Region -->
            <div>
                <label for="region" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Region <span class="text-red-500">*</span>
                </label>
                <select name="region" 
                        id="region" 
                        required
                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('region') border-red-300 @enderror">
                    <option value="">Select a region</option>
                    <option value="us-east" {{ old('region', $tenant->region) == 'us-east' ? 'selected' : '' }}>US East</option>
                    <option value="us-west" {{ old('region', $tenant->region) == 'us-west' ? 'selected' : '' }}>US West</option>
                    <option value="eu-central" {{ old('region', $tenant->region) == 'eu-central' ? 'selected' : '' }}>EU Central</option>
                    <option value="asia-pacific" {{ old('region', $tenant->region) == 'asia-pacific' ? 'selected' : '' }}>Asia Pacific</option>
                    <option value="canada" {{ old('region', $tenant->region) == 'canada' ? 'selected' : '' }}>Canada</option>
                    <option value="australia" {{ old('region', $tenant->region) == 'australia' ? 'selected' : '' }}>Australia</option>
                </select>
                @error('region')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Employee Count -->
            <div>
                <label for="employee_count" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Employee Count
                </label>
                <input type="number" 
                       name="employee_count" 
                       id="employee_count" 
                       value="{{ old('employee_count', $tenant->employee_count) }}"
                       min="1"
                       class="mt-1 block w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('employee_count') border-red-300 @enderror"
                       placeholder="e.g., 150">
                @error('employee_count')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Description -->
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                Description
            </label>
            <textarea name="description" 
                      id="description" 
                      rows="3"
                      class="mt-1 block w-full border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('description') border-red-300 @enderror"
                      placeholder="Brief description of this tenant...">{{ old('description', $tenant->description) }}</textarea>
            @error('description')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Active Status -->
        <div class="flex items-center">
            <input type="checkbox" 
                   name="is_active" 
                   id="is_active" 
                   value="1"
                   {{ old('is_active', $tenant->is_active) ? 'checked' : '' }}
                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 rounded">
            <label for="is_active" class="ml-2 block text-sm text-gray-900 dark:text-white">
                Active tenant (users can login)
            </label>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('admin.tenants.show', $tenant) }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Cancel
            </a>
            <button type="submit" 
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 dark:bg-indigo-500 hover:bg-indigo-700 dark:hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="-ml-1 mr-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Update Tenant
            </button>
        </div>
    </form>
</div>

<script>
// Auto-generate slug from name
document.getElementById('name').addEventListener('input', function() {
    const name = this.value;
    const slug = name.toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '') // Remove special characters
        .replace(/\s+/g, '-')         // Replace spaces with hyphens
        .replace(/-+/g, '-')          // Replace multiple hyphens with single
        .trim();
    
    document.getElementById('slug').value = slug;
});
</script>
@endsection
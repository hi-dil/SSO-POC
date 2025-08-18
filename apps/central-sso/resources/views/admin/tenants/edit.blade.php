@extends('layouts.admin')

@section('title', 'Edit Tenant')

@section('header')
    <div class="flex items-center space-x-4">
        <div class="flex-shrink-0">
            <div class="w-12 h-12 bg-gradient-to-r from-teal-custom to-teal-custom-light rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
        </div>
        <div>
            <h1 class="text-xl font-bold bg-gradient-to-r from-teal-custom to-teal-custom-light bg-clip-text text-transparent">
                Edit Tenant
            </h1>
            <p class="text-sm text-gray-600 dark:text-gray-300 font-medium">{{ $tenant->name }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Update tenant information and settings
            </p>
        </div>
    </div>
@endsection

@section('actions')
    <div class="flex items-center space-x-3">
        @can('tenants.view')
            <a href="{{ route('admin.tenants.show', $tenant) }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 transition-all duration-200">
                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                View Details
            </a>
            <a href="{{ route('admin.tenants.index') }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 transition-all duration-200">
                <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Tenants
            </a>
        @endcan
    </div>
@endsection

@section('content')
<div class="max-w-4xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
    <form method="POST" action="{{ route('admin.tenants.update', $tenant) }}" class="space-y-8">
        @csrf
        @method('PUT')

        <!-- Basic Information Card -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-teal-custom to-teal-custom-light px-6 py-4">
                <h3 class="text-lg font-semibold text-white flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Basic Information
                </h3>
                <p class="text-teal-100 text-sm mt-1">Core tenant details and identification</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Tenant Name <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="text"
                                   name="name"
                                   id="name"
                                   value="{{ old('name', $tenant->name) }}"
                                   class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg shadow-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all duration-200 @error('name') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                                   placeholder="e.g., Acme Corporation"
                                   required>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                        </div>
                        @error('name')
                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Slug -->
                    <div>
                        <label for="slug" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            URL Slug <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="text"
                                   name="slug"
                                   id="slug"
                                   value="{{ old('slug', $tenant->slug) }}"
                                   class="block w-full px-4 py-3 pl-12 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg shadow-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all duration-200 @error('slug') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                                   placeholder="e.g., acme-corp"
                                   pattern="[a-z0-9-]+"
                                   title="Only lowercase letters, numbers, and hyphens allowed"
                                   required>
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                            </div>
                        </div>
                        @error('slug')
                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                {{ $message }}
                            </p>
                        @else
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Used in URLs and must be unique. Only lowercase letters, numbers, and hyphens.
                            </p>
                        @enderror
                    </div>

                    <!-- Domain -->
                    <div>
                        <label for="domain" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Domain
                        </label>
                        <div class="relative">
                            <input type="text"
                                   name="domain"
                                   id="domain"
                                   value="{{ old('domain', $tenant->domain) }}"
                                   class="block w-full px-4 py-3 pl-12 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg shadow-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all duration-200 @error('domain') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                                   placeholder="e.g., acme.example.com">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 01-9 9m9-9H3m9 9v-9m9 9a9 9 0 00-9-9m9 9c0 5-4 9-9 9s-9-4-9-9m9-9a9 9 0 00-9 9" />
                                </svg>
                            </div>
                        </div>
                        @error('domain')
                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div class="sm:col-span-2">
                        <label for="description" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Description
                        </label>
                        <div class="relative">
                            <textarea name="description"
                                      id="description"
                                      rows="4"
                                      class="block w-full px-4 py-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg shadow-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all duration-200 @error('description') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                                      placeholder="Brief description of this tenant organization...">{{ old('description', $tenant->description) }}</textarea>
                        </div>
                        @error('description')
                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Organization Details Card -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-teal-custom to-teal-custom-light px-6 py-4">
                <h3 class="text-lg font-semibold text-white flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Organization Details
                </h3>
                <p class="text-teal-100 text-sm mt-1">Business and operational information</p>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">

                    <!-- Plan -->
                    <div>
                        <label for="plan" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Subscription Plan <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select name="plan"
                                    id="plan"
                                    required
                                    class="block w-full px-4 py-3 pr-10 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg shadow-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all duration-200 @error('plan') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror">
                                <option value="">Select a subscription plan</option>
                                <option value="starter" {{ old('plan', $tenant->plan) == 'starter' ? 'selected' : '' }}>🚀 Starter</option>
                                <option value="basic" {{ old('plan', $tenant->plan) == 'basic' ? 'selected' : '' }}>📈 Basic</option>
                                <option value="premium" {{ old('plan', $tenant->plan) == 'premium' ? 'selected' : '' }}>⭐ Premium</option>
                                <option value="pro" {{ old('plan', $tenant->plan) == 'pro' ? 'selected' : '' }}>💎 Pro</option>
                                <option value="enterprise" {{ old('plan', $tenant->plan) == 'enterprise' ? 'selected' : '' }}>🏢 Enterprise</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                        @error('plan')
                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Industry -->
                    <div>
                        <label for="industry" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Industry <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select name="industry"
                                    id="industry"
                                    required
                                    class="block w-full px-4 py-3 pr-10 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg shadow-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all duration-200 @error('industry') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror">
                                <option value="">Select an industry</option>
                                <option value="technology" {{ old('industry', $tenant->industry) == 'technology' ? 'selected' : '' }}>💻 Technology</option>
                                <option value="healthcare" {{ old('industry', $tenant->industry) == 'healthcare' ? 'selected' : '' }}>🏥 Healthcare</option>
                                <option value="finance" {{ old('industry', $tenant->industry) == 'finance' ? 'selected' : '' }}>💰 Finance</option>
                                <option value="education" {{ old('industry', $tenant->industry) == 'education' ? 'selected' : '' }}>🎓 Education</option>
                                <option value="retail" {{ old('industry', $tenant->industry) == 'retail' ? 'selected' : '' }}>🛍️ Retail</option>
                                <option value="manufacturing" {{ old('industry', $tenant->industry) == 'manufacturing' ? 'selected' : '' }}>🏭 Manufacturing</option>
                                <option value="consulting" {{ old('industry', $tenant->industry) == 'consulting' ? 'selected' : '' }}>📊 Consulting</option>
                                <option value="media" {{ old('industry', $tenant->industry) == 'media' ? 'selected' : '' }}>📺 Media</option>
                                <option value="nonprofit" {{ old('industry', $tenant->industry) == 'nonprofit' ? 'selected' : '' }}>❤️ Nonprofit</option>
                                <option value="government" {{ old('industry', $tenant->industry) == 'government' ? 'selected' : '' }}>🏛️ Government</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                        @error('industry')
                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Region -->
                    <div>
                        <label for="region" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Region <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select name="region"
                                    id="region"
                                    required
                                    class="block w-full px-4 py-3 pr-10 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg shadow-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all duration-200 @error('region') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror">
                                <option value="">Select a region</option>
                                <option value="us-east" {{ old('region', $tenant->region) == 'us-east' ? 'selected' : '' }}>🇺🇸 US East</option>
                                <option value="us-west" {{ old('region', $tenant->region) == 'us-west' ? 'selected' : '' }}>🇺🇸 US West</option>
                                <option value="eu-central" {{ old('region', $tenant->region) == 'eu-central' ? 'selected' : '' }}>🇪🇺 EU Central</option>
                                <option value="asia-pacific" {{ old('region', $tenant->region) == 'asia-pacific' ? 'selected' : '' }}>🌏 Asia Pacific</option>
                                <option value="canada" {{ old('region', $tenant->region) == 'canada' ? 'selected' : '' }}>🇨🇦 Canada</option>
                                <option value="australia" {{ old('region', $tenant->region) == 'australia' ? 'selected' : '' }}>🇦🇺 Australia</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                        @error('region')
                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Employee Count -->
                    <div>
                        <label for="employee_count" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Employee Count
                        </label>
                        <div class="relative">
                            <input type="number"
                                   name="employee_count"
                                   id="employee_count"
                                   value="{{ old('employee_count', $tenant->employee_count) }}"
                                   min="1"
                                   class="block w-full px-4 py-3 pl-12 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg shadow-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all duration-200 @error('employee_count') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                                   placeholder="e.g., 150">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                        </div>
                        @error('employee_count')
                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Max Users -->
                    <div>
                        <label for="max_users" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Maximum Users
                        </label>
                        <div class="relative">
                            <input type="number"
                                   name="max_users"
                                   id="max_users"
                                   value="{{ old('max_users', $tenant->max_users) }}"
                                   min="1"
                                   class="block w-full px-4 py-3 pl-12 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg shadow-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition-all duration-200 @error('max_users') border-red-300 focus:ring-red-500 focus:border-red-500 @enderror"
                                   placeholder="Leave empty for unlimited">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                        </div>
                        @error('max_users')
                            <p class="mt-2 text-sm text-red-600 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                {{ $message }}
                            </p>
                        @else
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Leave empty for unlimited users
                            </p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Status & Settings Card -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
            <div class="bg-gradient-to-r from-teal-custom to-teal-custom-light px-6 py-4">
                <h3 class="text-lg font-semibold text-white flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Status & Settings
                </h3>
                <p class="text-teal-100 text-sm mt-1">Tenant activation and operational settings</p>
            </div>
            <div class="p-6">
                <!-- Active Status -->
                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                    <div class="flex items-center space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 bg-gradient-to-r from-green-400 to-green-600 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                        <div>
                            <label for="is_active" class="text-sm font-semibold text-gray-900 dark:text-white">
                                Active Tenant
                            </label>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Allow users to login and access this tenant
                            </p>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox"
                                   name="is_active"
                                   id="is_active"
                                   value="1"
                                   {{ old('is_active', $tenant->is_active) ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-teal-300 dark:peer-focus:ring-teal-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-teal-600"></div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="bg-white dark:bg-gray-800 shadow-lg rounded-xl border border-gray-200 dark:border-gray-700 px-6 py-4">
            <div class="flex flex-col sm:flex-row justify-between items-center space-y-3 sm:space-y-0 sm:space-x-4">
                <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>All changes will be saved immediately</span>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('admin.tenants.show', $tenant) }}"
                       class="inline-flex items-center px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 transition-all duration-200">
                        <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Cancel
                    </a>
                    <button type="submit"
                            class="inline-flex items-center px-6 py-3 border border-transparent rounded-lg shadow-lg text-sm font-semibold text-white bg-gradient-to-r from-teal-custom to-teal-custom-light hover:from-teal-600 hover:to-cyan-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-teal-500 transition-all duration-200 transform hover:scale-105">
                        <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Update Tenant
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Auto-generate slug from name with visual feedback
document.getElementById('name').addEventListener('input', function() {
    const name = this.value;
    const slug = name.toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '') // Remove special characters
        .replace(/\s+/g, '-')         // Replace spaces with hyphens
        .replace(/-+/g, '-')          // Replace multiple hyphens with single
        .replace(/^-+|-+$/g, '')      // Remove leading/trailing hyphens
        .trim();

    const slugInput = document.getElementById('slug');
    slugInput.value = slug;

    // Add visual feedback for slug generation
    if (slug) {
        slugInput.classList.add('border-teal-300', 'bg-teal-50', 'dark:bg-teal-900/20');
        setTimeout(() => {
            slugInput.classList.remove('border-teal-300', 'bg-teal-50', 'dark:bg-teal-900/20');
        }, 1000);
    }
});

// Form submission feedback
document.querySelector('form').addEventListener('submit', function(e) {
    const submitBtn = document.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;

    submitBtn.innerHTML = `
        <svg class="-ml-1 mr-2 h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Updating...
    `;

    submitBtn.disabled = true;
    submitBtn.classList.add('opacity-75');
});

// Real-time validation feedback
const requiredFields = ['name', 'slug', 'plan', 'industry', 'region'];
requiredFields.forEach(fieldName => {
    const field = document.getElementById(fieldName);
    if (field) {
        field.addEventListener('blur', function() {
            const value = this.value.trim();
            const isValid = value.length > 0;

            if (isValid) {
                this.classList.remove('border-red-300', 'focus:ring-red-500', 'focus:border-red-500');
                this.classList.add('border-green-300', 'focus:ring-green-500', 'focus:border-green-500');
            } else {
                this.classList.remove('border-green-300', 'focus:ring-green-500', 'focus:border-green-500');
                this.classList.add('border-red-300', 'focus:ring-red-500', 'focus:border-red-500');
            }

            setTimeout(() => {
                this.classList.remove('border-green-300', 'focus:ring-green-500', 'focus:border-green-500');
            }, 2000);
        });
    }
});
</script>
@endsection

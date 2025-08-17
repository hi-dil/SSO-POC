@extends('layouts.admin')

@section('title', 'Edit User')

@section('header')
    <div>
        <h1 class="text-2xl font-semibold text-card-foreground">Edit User</h1>
        <p class="text-sm text-muted-foreground mt-1">
            Update {{ $user->name }}'s profile information
        </p>
    </div>
@endsection

@section('actions')
    <div class="flex space-x-3">
        <a href="{{ route('admin.users.show', $user) }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
            View Details
        </a>
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Users
        </a>
    </div>
@endsection

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <form action="{{ route('admin.users.update', $user) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        
        <!-- Basic Information -->
        <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-card-foreground mb-1">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-card-foreground mb-1">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-card-foreground mb-1">
                            New Password <span class="text-muted-foreground">(leave blank to keep current)</span>
                        </label>
                        <input type="password" id="password" name="password"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring @error('password') border-red-500 @enderror">
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-card-foreground mb-1">
                            Confirm New Password
                        </label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                    </div>
                    
                    <div>
                        <label for="phone" class="block text-sm font-medium text-card-foreground mb-1">
                            Phone Number
                        </label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring @error('phone') border-red-500 @enderror">
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <input type="checkbox" id="is_admin" name="is_admin" value="1" {{ old('is_admin', $user->is_admin) ? 'checked' : '' }}
                               class="h-4 w-4 text-primary border-input rounded focus:ring-ring">
                        <label for="is_admin" class="text-sm font-medium text-card-foreground">
                            Admin User
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Personal Information -->
        <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Personal Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="date_of_birth" class="block text-sm font-medium text-card-foreground mb-1">
                            Date of Birth
                        </label>
                        <input type="date" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring @error('date_of_birth') border-red-500 @enderror">
                        @error('date_of_birth')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="gender" class="block text-sm font-medium text-card-foreground mb-1">
                            Gender
                        </label>
                        <select id="gender" name="gender"
                                class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring @error('gender') border-red-500 @enderror">
                            <option value="">Select Gender</option>
                            <option value="male" {{ old('gender', $user->gender) === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender', $user->gender) === 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender', $user->gender) === 'other' ? 'selected' : '' }}>Other</option>
                            <option value="prefer_not_to_say" {{ old('gender', $user->gender) === 'prefer_not_to_say' ? 'selected' : '' }}>Prefer not to say</option>
                        </select>
                        @error('gender')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="nationality" class="block text-sm font-medium text-card-foreground mb-1">
                            Nationality
                        </label>
                        <input type="text" id="nationality" name="nationality" value="{{ old('nationality', $user->nationality) }}"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring @error('nationality') border-red-500 @enderror">
                        @error('nationality')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="avatar_url" class="block text-sm font-medium text-card-foreground mb-1">
                            Avatar URL
                        </label>
                        <input type="url" id="avatar_url" name="avatar_url" value="{{ old('avatar_url', $user->avatar_url) }}"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring @error('avatar_url') border-red-500 @enderror">
                        @error('avatar_url')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <div class="mt-4">
                    <label for="bio" class="block text-sm font-medium text-card-foreground mb-1">
                        Bio
                    </label>
                    <textarea id="bio" name="bio" rows="3"
                              class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring @error('bio') border-red-500 @enderror">{{ old('bio', $user->bio) }}</textarea>
                    @error('bio')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Work Information -->
        <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Work Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="employee_id" class="block text-sm font-medium text-card-foreground mb-1">
                            Employee ID
                        </label>
                        <input type="text" id="employee_id" name="employee_id" value="{{ old('employee_id', $user->employee_id) }}"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring @error('employee_id') border-red-500 @enderror">
                        @error('employee_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="job_title" class="block text-sm font-medium text-card-foreground mb-1">
                            Job Title
                        </label>
                        <input type="text" id="job_title" name="job_title" value="{{ old('job_title', $user->job_title) }}"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring @error('job_title') border-red-500 @enderror">
                        @error('job_title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="department" class="block text-sm font-medium text-card-foreground mb-1">
                            Department
                        </label>
                        <input type="text" id="department" name="department" value="{{ old('department', $user->department) }}"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring @error('department') border-red-500 @enderror">
                        @error('department')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="hire_date" class="block text-sm font-medium text-card-foreground mb-1">
                            Hire Date
                        </label>
                        <input type="date" id="hire_date" name="hire_date" value="{{ old('hire_date', $user->hire_date?->format('Y-m-d')) }}"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring @error('hire_date') border-red-500 @enderror">
                        @error('hire_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Address Information -->
        <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Address Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label for="address_line_1" class="block text-sm font-medium text-card-foreground mb-1">
                            Address Line 1
                        </label>
                        <input type="text" id="address_line_1" name="address_line_1" value="{{ old('address_line_1', $user->address_line_1) }}"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring @error('address_line_1') border-red-500 @enderror">
                        @error('address_line_1')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div class="md:col-span-2">
                        <label for="address_line_2" class="block text-sm font-medium text-card-foreground mb-1">
                            Address Line 2
                        </label>
                        <input type="text" id="address_line_2" name="address_line_2" value="{{ old('address_line_2', $user->address_line_2) }}"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring @error('address_line_2') border-red-500 @enderror">
                        @error('address_line_2')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="city" class="block text-sm font-medium text-card-foreground mb-1">
                            City
                        </label>
                        <input type="text" id="city" name="city" value="{{ old('city', $user->city) }}"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring @error('city') border-red-500 @enderror">
                        @error('city')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="state_province" class="block text-sm font-medium text-card-foreground mb-1">
                            State/Province
                        </label>
                        <input type="text" id="state_province" name="state_province" value="{{ old('state_province', $user->state_province) }}"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring @error('state_province') border-red-500 @enderror">
                        @error('state_province')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="postal_code" class="block text-sm font-medium text-card-foreground mb-1">
                            Postal Code
                        </label>
                        <input type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $user->postal_code) }}"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring @error('postal_code') border-red-500 @enderror">
                        @error('postal_code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="country" class="block text-sm font-medium text-card-foreground mb-1">
                            Country
                        </label>
                        <input type="text" id="country" name="country" value="{{ old('country', $user->country) }}"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring @error('country') border-red-500 @enderror">
                        @error('country')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Emergency Contact -->
        <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Emergency Contact</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="emergency_contact_name" class="block text-sm font-medium text-card-foreground mb-1">
                            Contact Name
                        </label>
                        <input type="text" id="emergency_contact_name" name="emergency_contact_name" value="{{ old('emergency_contact_name', $user->emergency_contact_name) }}"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring @error('emergency_contact_name') border-red-500 @enderror">
                        @error('emergency_contact_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="emergency_contact_phone" class="block text-sm font-medium text-card-foreground mb-1">
                            Contact Phone
                        </label>
                        <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $user->emergency_contact_phone) }}"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring @error('emergency_contact_phone') border-red-500 @enderror">
                        @error('emergency_contact_phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="emergency_contact_relationship" class="block text-sm font-medium text-card-foreground mb-1">
                            Relationship
                        </label>
                        <input type="text" id="emergency_contact_relationship" name="emergency_contact_relationship" value="{{ old('emergency_contact_relationship', $user->emergency_contact_relationship) }}"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring @error('emergency_contact_relationship') border-red-500 @enderror">
                        @error('emergency_contact_relationship')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Tenant Access -->
        <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
            <div class="p-6">
                <div class="space-y-4">
                    <!-- Header and Summary -->
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Tenant Access</h3>
                        <div class="text-sm text-muted-foreground">
                            <span id="edit-selected-count">{{ $user->tenants->count() }}</span> of {{ $tenants->count() }} tenants selected
                        </div>
                    </div>
                    
                    <!-- Search and Controls -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <span class="text-sm font-medium text-card-foreground">Select Tenant Access</span>
                                <div class="flex items-center space-x-2">
                                    <button type="button" onclick="selectAllEditTenants()" 
                                            class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300">
                                        Select All
                                    </button>
                                    <span class="text-gray-300 dark:text-gray-600">|</span>
                                    <button type="button" onclick="selectNoneEditTenants()" 
                                            class="text-xs text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300">
                                        Select None
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Search Bar -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input type="text" 
                                   id="edit-tenant-search" 
                                   placeholder="Search tenants by name, slug, or domain..."
                                   onkeyup="searchEditTenants()"
                                   class="block w-full pl-10 pr-3 py-2 border border-input bg-background text-card-foreground rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                        </div>
                    </div>
                    
                    <!-- Tenant Selection Table -->
                    <div class="border border-border rounded-lg overflow-hidden">
                        <div class="max-h-80 overflow-y-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-muted sticky top-0">
                                    <tr>
                                        <th class="w-12 px-4 py-3 text-left">
                                            <input type="checkbox" 
                                                   id="select-all-edit-tenants" 
                                                   onchange="toggleAllEditTenants(this)"
                                                   class="rounded border-input text-primary focus:ring-ring">
                                        </th>
                                        <th class="px-4 py-3 text-left font-medium text-card-foreground">Tenant Name</th>
                                        <th class="px-4 py-3 text-left font-medium text-card-foreground">Slug</th>
                                        <th class="px-4 py-3 text-left font-medium text-card-foreground">Domain</th>
                                        <th class="px-4 py-3 text-left font-medium text-card-foreground">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="edit-tenant-table-body" class="divide-y divide-border">
                                    @foreach($tenants as $tenant)
                                        <tr class="hover:bg-muted/50 cursor-pointer transition-colors" 
                                            onclick="toggleEditTenantRow(this, '{{ $tenant->id }}')"
                                            data-tenant-id="{{ $tenant->id }}"
                                            data-tenant-name="{{ strtolower($tenant->name) }}"
                                            data-tenant-slug="{{ strtolower($tenant->slug) }}"
                                            data-tenant-domain="{{ strtolower($tenant->domain) }}">
                                            <td class="px-4 py-3" onclick="event.stopPropagation()">
                                                <input type="checkbox" 
                                                       id="tenant_{{ $tenant->id }}" 
                                                       name="tenant_ids[]" 
                                                       value="{{ $tenant->id }}"
                                                       {{ in_array($tenant->id, old('tenant_ids', $user->tenants->pluck('id')->toArray())) ? 'checked' : '' }}
                                                       onchange="updateEditSelectedCount()"
                                                       class="edit-tenant-checkbox rounded border-input text-primary focus:ring-ring">
                                            </td>
                                            <td class="px-4 py-3">
                                                <div>
                                                    <div class="font-medium text-card-foreground">
                                                        {{ $tenant->name }}
                                                    </div>
                                                    @if($tenant->description)
                                                        <div class="text-xs text-muted-foreground mt-1">
                                                            {{ Str::limit($tenant->description, 50) }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <code class="text-xs bg-muted px-2 py-1 rounded text-muted-foreground">
                                                    {{ $tenant->slug }}
                                                </code>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="text-muted-foreground">
                                                    {{ $tenant->domain }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                @if($tenant->is_active)
                                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200">
                                                        <svg class="w-1.5 h-1.5 mr-1.5" fill="currentColor" viewBox="0 0 8 8">
                                                            <circle cx="4" cy="4" r="3"/>
                                                        </svg>
                                                        Active
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200">
                                                        <svg class="w-1.5 h-1.5 mr-1.5" fill="currentColor" viewBox="0 0 8 8">
                                                            <circle cx="4" cy="4" r="3"/>
                                                        </svg>
                                                        Inactive
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Pagination Controls -->
                    <div id="edit-tenant-pagination" class="hidden border-t border-border px-4 py-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <span class="text-sm text-muted-foreground">
                                    Showing <span id="edit-page-start">1</span> to <span id="edit-page-end">15</span> of <span id="edit-total-tenants">{{ $tenants->count() }}</span> tenants
                                </span>
                            </div>
                            <div class="flex items-center space-x-1">
                                <button type="button" id="edit-prev-page" onclick="changeEditPage(-1)" 
                                        class="inline-flex items-center px-2 py-2 text-sm font-medium text-muted-foreground bg-background border border-input rounded-l-lg hover:bg-muted hover:text-card-foreground">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                </button>
                                <span id="edit-page-numbers" class="inline-flex"></span>
                                <button type="button" id="edit-next-page" onclick="changeEditPage(1)" 
                                        class="inline-flex items-center px-2 py-2 text-sm font-medium text-muted-foreground bg-background border border-input rounded-r-lg hover:bg-muted hover:text-card-foreground">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Empty State -->
                    <div id="edit-no-tenants-message" class="hidden text-center py-8 text-muted-foreground">
                        <svg class="mx-auto h-12 w-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <p class="text-sm">No tenants found</p>
                        <p class="text-xs text-muted-foreground mt-1">Try adjusting your search criteria</p>
                    </div>
                    
                    @error('tenant_ids')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Preferences -->
        <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Preferences</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="timezone" class="block text-sm font-medium text-card-foreground mb-1">
                            Timezone
                        </label>
                        <select id="timezone" name="timezone"
                                class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring @error('timezone') border-red-500 @enderror">
                            <option value="UTC" {{ old('timezone', $user->timezone) === 'UTC' ? 'selected' : '' }}>UTC</option>
                            <option value="America/New_York" {{ old('timezone', $user->timezone) === 'America/New_York' ? 'selected' : '' }}>Eastern Time</option>
                            <option value="America/Chicago" {{ old('timezone', $user->timezone) === 'America/Chicago' ? 'selected' : '' }}>Central Time</option>
                            <option value="America/Denver" {{ old('timezone', $user->timezone) === 'America/Denver' ? 'selected' : '' }}>Mountain Time</option>
                            <option value="America/Los_Angeles" {{ old('timezone', $user->timezone) === 'America/Los_Angeles' ? 'selected' : '' }}>Pacific Time</option>
                            <option value="Europe/London" {{ old('timezone', $user->timezone) === 'Europe/London' ? 'selected' : '' }}>London</option>
                            <option value="Europe/Paris" {{ old('timezone', $user->timezone) === 'Europe/Paris' ? 'selected' : '' }}>Paris</option>
                            <option value="Asia/Tokyo" {{ old('timezone', $user->timezone) === 'Asia/Tokyo' ? 'selected' : '' }}>Tokyo</option>
                            <option value="Asia/Shanghai" {{ old('timezone', $user->timezone) === 'Asia/Shanghai' ? 'selected' : '' }}>Shanghai</option>
                        </select>
                        @error('timezone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <div>
                        <label for="language" class="block text-sm font-medium text-card-foreground mb-1">
                            Language
                        </label>
                        <select id="language" name="language"
                                class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring @error('language') border-red-500 @enderror">
                            <option value="en" {{ old('language', $user->language) === 'en' ? 'selected' : '' }}>English</option>
                            <option value="es" {{ old('language', $user->language) === 'es' ? 'selected' : '' }}>Spanish</option>
                            <option value="fr" {{ old('language', $user->language) === 'fr' ? 'selected' : '' }}>French</option>
                            <option value="de" {{ old('language', $user->language) === 'de' ? 'selected' : '' }}>German</option>
                            <option value="it" {{ old('language', $user->language) === 'it' ? 'selected' : '' }}>Italian</option>
                            <option value="pt" {{ old('language', $user->language) === 'pt' ? 'selected' : '' }}>Portuguese</option>
                            <option value="zh" {{ old('language', $user->language) === 'zh' ? 'selected' : '' }}>Chinese</option>
                            <option value="ja" {{ old('language', $user->language) === 'ja' ? 'selected' : '' }}>Japanese</option>
                        </select>
                        @error('language')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3">
            <a href="{{ route('admin.users.show', $user) }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
                Cancel
            </a>
            <button type="submit" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Update User
            </button>
        </div>
    </form>
</div>

<script>
// Edit form tenant management functions
let editCurrentPage = 1;
const editItemsPerPage = 15;
let editFilteredRows = [];

// Row click functionality
function toggleEditTenantRow(row, tenantId) {
    const checkbox = row.querySelector('input[name="tenant_ids[]"]');
    checkbox.checked = !checkbox.checked;
    updateEditSelectedCount();
}

// Search functionality
let editSearchTimer;
function searchEditTenants() {
    clearTimeout(editSearchTimer);
    editSearchTimer = setTimeout(() => {
        const searchTerm = document.getElementById('edit-tenant-search').value.toLowerCase();
        const tableBody = document.getElementById('edit-tenant-table-body');
        const rows = tableBody.querySelectorAll('tr');
        let visibleCount = 0;
        
        rows.forEach(row => {
            const name = row.dataset.tenantName || '';
            const slug = row.dataset.tenantSlug || '';
            const domain = row.dataset.tenantDomain || '';
            
            const isVisible = name.includes(searchTerm) || 
                            slug.includes(searchTerm) || 
                            domain.includes(searchTerm);
            
            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });
        
        // Show/hide empty state
        const noTenantsMessage = document.getElementById('edit-no-tenants-message');
        if (visibleCount === 0 && searchTerm !== '') {
            noTenantsMessage.classList.remove('hidden');
            tableBody.style.display = 'none';
        } else {
            noTenantsMessage.classList.add('hidden');
            tableBody.style.display = '';
        }
        
        // Update pagination after search
        initializeEditPagination();
    }, 300);
}

// Selection count update
function updateEditSelectedCount() {
    const selectedCheckboxes = document.querySelectorAll('.edit-tenant-checkbox:checked');
    const count = selectedCheckboxes.length;
    document.getElementById('edit-selected-count').textContent = count;
    updateEditSelectAllState();
}

function updateEditSelectAllState() {
    const allVisibleCheckboxes = Array.from(document.querySelectorAll('.edit-tenant-checkbox'))
        .filter(cb => cb.closest('tr').style.display !== 'none');
    const selectedVisibleCheckboxes = allVisibleCheckboxes.filter(cb => cb.checked);
    const selectAllCheckbox = document.getElementById('select-all-edit-tenants');
    
    if (selectedVisibleCheckboxes.length === 0) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = false;
    } else if (selectedVisibleCheckboxes.length === allVisibleCheckboxes.length) {
        selectAllCheckbox.indeterminate = false;
        selectAllCheckbox.checked = true;
    } else {
        selectAllCheckbox.indeterminate = true;
        selectAllCheckbox.checked = false;
    }
}

function toggleAllEditTenants(selectAllCheckbox) {
    const visibleCheckboxes = Array.from(document.querySelectorAll('.edit-tenant-checkbox'))
        .filter(cb => cb.closest('tr').style.display !== 'none');
    
    visibleCheckboxes.forEach(checkbox => {
        checkbox.checked = selectAllCheckbox.checked;
    });
    updateEditSelectedCount();
}

function selectAllEditTenants() {
    const visibleCheckboxes = Array.from(document.querySelectorAll('.edit-tenant-checkbox'))
        .filter(cb => cb.closest('tr').style.display !== 'none');
    const selectAllCheckbox = document.getElementById('select-all-edit-tenants');
    
    visibleCheckboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
    selectAllCheckbox.checked = true;
    selectAllCheckbox.indeterminate = false;
    updateEditSelectedCount();
}

function selectNoneEditTenants() {
    const visibleCheckboxes = Array.from(document.querySelectorAll('.edit-tenant-checkbox'))
        .filter(cb => cb.closest('tr').style.display !== 'none');
    const selectAllCheckbox = document.getElementById('select-all-edit-tenants');
    
    visibleCheckboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    selectAllCheckbox.checked = false;
    selectAllCheckbox.indeterminate = false;
    updateEditSelectedCount();
}

// Pagination functionality
function initializeEditPagination() {
    const tableBody = document.getElementById('edit-tenant-table-body');
    const visibleRows = Array.from(tableBody.querySelectorAll('tr'))
        .filter(row => row.style.display !== 'none');
    editFilteredRows = visibleRows;
    
    if (visibleRows.length > editItemsPerPage) {
        document.getElementById('edit-tenant-pagination').classList.remove('hidden');
        updateEditPagination();
    } else {
        document.getElementById('edit-tenant-pagination').classList.add('hidden');
        // Show all rows if no pagination needed
        visibleRows.forEach(row => row.style.display = '');
    }
}

function updateEditPagination() {
    const totalItems = editFilteredRows.length;
    const totalPages = Math.ceil(totalItems / editItemsPerPage);
    const startIndex = (editCurrentPage - 1) * editItemsPerPage;
    const endIndex = Math.min(startIndex + editItemsPerPage, totalItems);
    
    // Update pagination info
    document.getElementById('edit-page-start').textContent = totalItems > 0 ? startIndex + 1 : 0;
    document.getElementById('edit-page-end').textContent = endIndex;
    document.getElementById('edit-total-tenants').textContent = totalItems;
    
    // Show/hide rows
    const allRows = document.querySelectorAll('#edit-tenant-table-body tr');
    allRows.forEach(row => row.style.display = 'none');
    
    editFilteredRows.forEach((row, index) => {
        row.style.display = (index >= startIndex && index < endIndex) ? '' : 'none';
    });
    
    // Update pagination buttons
    document.getElementById('edit-prev-page').disabled = editCurrentPage === 1;
    document.getElementById('edit-next-page').disabled = editCurrentPage === totalPages;
    
    // Update page numbers
    updateEditPageNumbers(totalPages);
}

function updateEditPageNumbers(totalPages) {
    const pageNumbers = document.getElementById('edit-page-numbers');
    pageNumbers.innerHTML = '';
    
    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= editCurrentPage - 2 && i <= editCurrentPage + 2)) {
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = i;
            button.onclick = () => goToEditPage(i);
            button.className = `px-3 py-2 text-sm font-medium leading-tight ${
                i === editCurrentPage 
                    ? 'text-primary bg-muted border border-input' 
                    : 'text-muted-foreground bg-background border border-input hover:bg-muted hover:text-card-foreground'
            }`;
            pageNumbers.appendChild(button);
        } else if (i === editCurrentPage - 3 || i === editCurrentPage + 3) {
            const span = document.createElement('span');
            span.textContent = '...';
            span.className = 'px-3 py-2 text-sm font-medium text-muted-foreground';
            pageNumbers.appendChild(span);
        }
    }
}

function changeEditPage(direction) {
    const totalPages = Math.ceil(editFilteredRows.length / editItemsPerPage);
    const newPage = editCurrentPage + direction;
    
    if (newPage >= 1 && newPage <= totalPages) {
        editCurrentPage = newPage;
        updateEditPagination();
    }
}

function goToEditPage(page) {
    editCurrentPage = page;
    updateEditPagination();
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialize pagination on page load
    initializeEditPagination();
    
    // Check for success/error messages from Laravel
    @if(session('success'))
        if (window.showToast) {
            window.showToast('{{ session('success') }}', 'success');
        }
    @endif
    
    @if(session('error'))
        if (window.showToast) {
            window.showToast('{{ session('error') }}', 'error');
        }
    @endif
});
</script>
@endsection
@extends('layouts.admin')

@section('title', 'User Details')

@section('header')
    <div>
        <h1 class="text-2xl font-semibold text-card-foreground">User Details</h1>
        <p class="text-sm text-muted-foreground mt-1">
            View complete profile information for {{ $user->name }}
        </p>
    </div>
@endsection

@section('actions')
    <div class="flex space-x-3">
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Users
        </a>
        @can('users.edit')
            <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Edit User
            </a>
        @endcan
        
        <!-- Profile Management Dropdown -->
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                Manage Profile
                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            
            <div x-show="open" @click.away="open = false" x-transition class="absolute right-0 mt-2 w-56 bg-popover border border-border rounded-md shadow-lg z-50">
                <div class="py-1">
                    @can('View User Contacts')
                        <a href="{{ route('admin.users.contacts', $user) }}" class="block px-4 py-2 text-sm text-popover-foreground hover:bg-accent hover:text-accent-foreground transition-colors">
                            <svg class="inline mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            Manage Contacts
                        </a>
                    @endcan
                    
                    @can('View User Addresses')
                        <a href="{{ route('admin.users.addresses', $user) }}" class="block px-4 py-2 text-sm text-popover-foreground hover:bg-accent hover:text-accent-foreground transition-colors">
                            <svg class="inline mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            Manage Addresses
                        </a>
                    @endcan
                    
                    @can('View User Family Members')
                        <a href="{{ route('admin.users.family', $user) }}" class="block px-4 py-2 text-sm text-popover-foreground hover:bg-accent hover:text-accent-foreground transition-colors">
                            <svg class="inline mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            Manage Family
                        </a>
                    @endcan
                    
                    @can('View User Social Media')
                        <a href="{{ route('admin.users.social-media', $user) }}" class="block px-4 py-2 text-sm text-popover-foreground hover:bg-accent hover:text-accent-foreground transition-colors">
                            <svg class="inline mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                            </svg>
                            Manage Social Media
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Profile Header -->
    <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
        <div class="p-6">
            <div class="flex items-center space-x-6">
                <div class="flex-shrink-0">
                    <div class="h-24 w-24 rounded-full overflow-hidden bg-muted flex items-center justify-center">
                        @if($user->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                        @else
                            <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="h-full w-full object-cover">
                        @endif
                    </div>
                </div>
                <div class="flex-1">
                    <div class="flex items-center space-x-3">
                        <h2 class="text-2xl font-bold text-card-foreground">{{ $user->name }}</h2>
                        @if($user->is_admin)
                            <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-red-50 text-red-700">
                                Admin
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-green-50 text-green-700">
                                User
                            </span>
                        @endif
                    </div>
                    <p class="text-muted-foreground">{{ $user->email }}</p>
                    @if($user->job_title || $user->department)
                        <p class="text-sm text-muted-foreground">
                            @if($user->job_title)
                                {{ $user->job_title }}
                                @if($user->department)
                                    at {{ $user->department }}
                                @endif
                            @elseif($user->department)
                                {{ $user->department }}
                            @endif
                        </p>
                    @endif
                    @if($user->bio)
                        <p class="mt-2 text-sm text-muted-foreground">{{ $user->bio }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Personal Information -->
        <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Personal Information</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Email:</span>
                        <span>{{ $user->email }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Phone:</span>
                        <span>{{ $user->phone ?: 'Not provided' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Date of Birth:</span>
                        <span>
                            @if($user->date_of_birth)
                                {{ $user->date_of_birth->format('M d, Y') }}
                                @if($user->age)
                                    ({{ $user->age }} years old)
                                @endif
                            @else
                                Not provided
                            @endif
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Gender:</span>
                        <span>{{ $user->gender ? ucfirst(str_replace('_', ' ', $user->gender)) : 'Not provided' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Nationality:</span>
                        <span>{{ $user->nationality ?: 'Not provided' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Contact Information</h3>
                    @can('View User Contacts')
                        <a href="{{ route('admin.users.contacts', $user) }}" class="text-sm text-primary hover:underline">
                            Manage Contacts
                        </a>
                    @endcan
                </div>
                @if($user->contacts->count() > 0)
                    <div class="space-y-3">
                        @foreach($user->contacts->take(5) as $contact)
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    @if($contact->type === 'email')
                                        <svg class="h-4 w-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                    @elseif(in_array($contact->type, ['phone', 'mobile', 'work_phone', 'home_phone']))
                                        <svg class="h-4 w-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                        </svg>
                                    @else
                                        <svg class="h-4 w-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                        </svg>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm text-card-foreground">{{ $contact->value }}</span>
                                        @if($contact->is_primary)
                                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-blue-50 text-blue-700">
                                                Primary
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ $contact->label ?: ucwords(str_replace('_', ' ', $contact->type)) }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @if($user->contacts->count() > 5)
                            <div class="text-xs text-muted-foreground">
                                +{{ $user->contacts->count() - 5 }} more contacts
                            </div>
                        @endif
                    </div>
                @else
                    <p class="text-muted-foreground">No contact information provided</p>
                @endif
            </div>
        </div>

        <!-- Work Information -->
        <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Work Information</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Employee ID:</span>
                        <span>{{ $user->employee_id ?: 'Not provided' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Job Title:</span>
                        <span>{{ $user->job_title ?: 'Not provided' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Department:</span>
                        <span>{{ $user->department ?: 'Not provided' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Hire Date:</span>
                        <span>{{ $user->hire_date ? $user->hire_date->format('M d, Y') : 'Not provided' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Account Created:</span>
                        <span>{{ $user->created_at->format('M d, Y g:i A') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Last Updated:</span>
                        <span>{{ $user->updated_at->format('M d, Y g:i A') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Addresses -->
        <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Addresses</h3>
                    @can('View User Addresses')
                        <a href="{{ route('admin.users.addresses', $user) }}" class="text-sm text-primary hover:underline">
                            Manage Addresses
                        </a>
                    @endcan
                </div>
                @if($user->addresses->count() > 0)
                    <div class="space-y-4">
                        @foreach($user->addresses->take(3) as $address)
                            <div class="border border-border rounded-lg p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-medium text-card-foreground">
                                            {{ $address->label ?: ucwords($address->type) }}
                                        </span>
                                        @if($address->is_primary)
                                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-blue-50 text-blue-700">
                                                Primary
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-sm text-muted-foreground">
                                    {{ $address->formatted_address }}
                                </div>
                            </div>
                        @endforeach
                        @if($user->addresses->count() > 3)
                            <div class="text-xs text-muted-foreground">
                                +{{ $user->addresses->count() - 3 }} more addresses
                            </div>
                        @endif
                    </div>
                @elseif($user->address_line_1 || $user->address_line_2 || $user->city || $user->state_province || $user->postal_code || $user->country)
                    <!-- Legacy address data from user table -->
                    <div class="border border-border rounded-lg p-3">
                        <div class="text-sm font-medium text-card-foreground mb-2">Primary Address (Legacy)</div>
                        <div class="text-sm text-muted-foreground space-y-1">
                            @if($user->address_line_1)
                                <div>{{ $user->address_line_1 }}</div>
                            @endif
                            @if($user->address_line_2)
                                <div>{{ $user->address_line_2 }}</div>
                            @endif
                            @if($user->city || $user->state_province || $user->postal_code)
                                <div>
                                    {{ $user->city }}@if($user->city && ($user->state_province || $user->postal_code)), @endif
                                    {{ $user->state_province }}@if($user->state_province && $user->postal_code) @endif
                                    {{ $user->postal_code }}
                                </div>
                            @endif
                            @if($user->country)
                                <div>{{ $user->country }}</div>
                            @endif
                        </div>
                    </div>
                @else
                    <p class="text-muted-foreground">No addresses provided</p>
                @endif
            </div>
        </div>

        <!-- Emergency Contact -->
        <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Emergency Contact</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Name:</span>
                        <span>{{ $user->emergency_contact_name ?: 'Not provided' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Phone:</span>
                        <span>{{ $user->emergency_contact_phone ?: 'Not provided' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Relationship:</span>
                        <span>{{ $user->emergency_contact_relationship ?: 'Not provided' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tenant Access -->
    <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4">Tenant Access</h3>
            @if($user->tenants->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($user->tenants as $tenant)
                        <div class="p-4 border border-border rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-card-foreground">{{ $tenant->name }}</h4>
                                    <p class="text-sm text-muted-foreground">{{ $tenant->slug }}</p>
                                    @if($tenant->description)
                                        <p class="text-xs text-muted-foreground mt-1">{{ $tenant->description }}</p>
                                    @endif
                                </div>
                                <div class="flex-shrink-0">
                                    @if($tenant->is_active)
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-green-50 text-green-700">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-red-50 text-red-700">
                                            Inactive
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-muted-foreground">This user does not have access to any tenants.</p>
            @endif
        </div>
    </div>

    <!-- Family Members -->
    @if($user->familyMembers->count() > 0)
        <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Family Members ({{ $user->familyMembers->count() }})</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($user->familyMembers as $member)
                        <div class="p-4 border border-border rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="h-10 w-10 rounded-full bg-muted flex items-center justify-center">
                                    <span class="text-sm font-medium text-muted-foreground">
                                        {{ substr($member->first_name, 0, 1) }}{{ substr($member->last_name, 0, 1) }}
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-card-foreground">
                                        {{ $member->full_name }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ ucfirst($member->relationship) }}
                                    </p>
                                    @if($member->date_of_birth)
                                        <p class="text-xs text-muted-foreground">
                                            Born {{ $member->date_of_birth->format('M d, Y') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Preferences -->
    <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4">Preferences</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Timezone:</span>
                    <span>{{ $user->timezone ?: 'UTC' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Language:</span>
                    <span>{{ strtoupper($user->language ?: 'en') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
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
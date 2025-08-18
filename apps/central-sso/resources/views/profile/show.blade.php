@extends('layouts.admin')

@section('title', 'My Profile')

@section('header')
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">My Profile</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            View and manage your personal information
        </p>
    </div>
@endsection

@section('actions')
    <div class="flex space-x-3">
        <a href="{{ route('profile.edit') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium  transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-teal-600 dark:bg-teal-500 text-white hover:bg-teal-700 dark:hover:bg-teal-600 h-10 px-4 py-2">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
            Edit Profile
        </a>
        <a href="{{ route('profile.family') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium  transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white h-10 px-4 py-2">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            Family Members
        </a>
    </div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Profile Header -->
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm">
        <div class="p-6">
            <div class="flex items-center space-x-6">
                <div class="flex-shrink-0">
                    <img class="h-24 w-24 rounded-full object-cover" src="{{ $user->avatar }}" alt="{{ $user->name }}">
                </div>
                <div class="flex-1">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h2>
                    <p class="text-gray-600 dark:text-gray-400">{{ $user->email }}</p>
                    @if($user->job_title || $user->department)
                        <p class="text-sm text-gray-600 dark:text-gray-400">
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
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $user->bio }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Personal Information -->
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Personal Information</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Phone:</span>
                        <span>{{ $user->phone ?: 'Not provided' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Date of Birth:</span>
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
                        <span class="text-gray-600 dark:text-gray-400">Gender:</span>
                        <span>{{ $user->gender ? ucfirst(str_replace('_', ' ', $user->gender)) : 'Not provided' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Nationality:</span>
                        <span>{{ $user->nationality ?: 'Not provided' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Contact Information</h3>
                    <a href="{{ route('profile.contacts') }}" class="text-sm text-primary hover:underline">
                        Manage Contacts
                    </a>
                </div>
                @if($user->contacts->count() > 0)
                    <div class="space-y-3">
                        @foreach($user->contacts->take(5) as $contact)
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    @if($contact->type === 'email')
                                        <svg class="h-4 w-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                    @elseif(in_array($contact->type, ['phone', 'mobile', 'work_phone', 'home_phone']))
                                        <svg class="h-4 w-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                        </svg>
                                    @else
                                        <svg class="h-4 w-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                        </svg>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm text-gray-900 dark:text-white">{{ $contact->value }}</span>
                                        @if($contact->is_primary)
                                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 border-transparent bg-teal-100 dark:bg-teal-900 text-teal-800 dark:text-teal-200">
                                                Primary
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-600 dark:text-gray-400">
                                        {{ $contact->label ?: ucwords(str_replace('_', ' ', $contact->type)) }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @if($user->contacts->count() > 5)
                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                +{{ $user->contacts->count() - 5 }} more contacts
                            </div>
                        @endif
                    </div>
                @else
                    <p class="text-gray-600 dark:text-gray-400">No contact information provided</p>
                @endif
            </div>
        </div>

        <!-- Addresses -->
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Addresses</h3>
                    <a href="{{ route('profile.addresses') }}" class="text-sm text-primary hover:underline">
                        Manage Addresses
                    </a>
                </div>
                @if($user->addresses->count() > 0)
                    <div class="space-y-4">
                        @foreach($user->addresses->take(3) as $address)
                            <div class="border border-border rounded-lg p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $address->label ?: ucwords($address->type) }}
                                        </span>
                                        @if($address->is_primary)
                                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 border-transparent bg-teal-100 dark:bg-teal-900 text-teal-800 dark:text-teal-200">
                                                Primary
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $address->formatted_address }}
                                </div>
                            </div>
                        @endforeach
                        @if($user->addresses->count() > 3)
                            <div class="text-xs text-gray-600 dark:text-gray-400">
                                +{{ $user->addresses->count() - 3 }} more addresses
                            </div>
                        @endif
                    </div>
                @else
                    <p class="text-gray-600 dark:text-gray-400">No addresses provided</p>
                @endif
            </div>
        </div>

        <!-- Work Information -->
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Work Information</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Employee ID:</span>
                        <span>{{ $user->employee_id ?: 'Not provided' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Hire Date:</span>
                        <span>{{ $user->hire_date ? $user->hire_date->format('M d, Y') : 'Not provided' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Admin Status:</span>
                        <span>
                            @if($user->is_admin)
                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-green-50 text-green-700">
                                    Admin
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-input bg-background text-foreground">
                                    User
                                </span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Emergency Contact -->
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Emergency Contact</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Name:</span>
                        <span>{{ $user->emergency_contact_name ?: 'Not provided' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Phone:</span>
                        <span>{{ $user->emergency_contact_phone ?: 'Not provided' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Relationship:</span>
                        <span>{{ $user->emergency_contact_relationship ?: 'Not provided' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Social Media -->
    @if($user->socialMedia->count() > 0)
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Social Media</h3>
                    <a href="{{ route('profile.social-media') }}" class="text-sm text-primary hover:underline">
                        Manage Social Media
                    </a>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    @foreach($user->publicSocialMedia as $social)
                        <a href="{{ $social->url }}" target="_blank" rel="noopener noreferrer"
                           class="flex items-center space-x-2 p-3 border border-border rounded-lg hover:bg-accent hover:text-accent-foreground transition-colors"
                           style="border-color: {{ $social->platform_color }}20;">
                            <div class="flex-shrink-0">
                                <i class="{{ $social->platform_icon }}" style="color: {{ $social->platform_color }}"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ $social->display_name ?: ucfirst($social->platform) }}
                                </div>
                                @if($social->username)
                                    <div class="text-xs text-gray-600 dark:text-gray-400 truncate">
                                        {{ $social->username }}
                                    </div>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Family Members Summary -->
    @if($user->familyMembers->count() > 0)
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Family Members</h3>
                    <a href="{{ route('profile.family') }}" class="text-sm text-primary hover:underline">
                        View All ({{ $user->familyMembers->count() }})
                    </a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($user->familyMembers->take(6) as $member)
                        <div class="p-3 border border-border rounded-lg">
                            <div class="flex items-center space-x-3">
                                <div class="h-10 w-10 rounded-full bg-muted flex items-center justify-center">
                                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                        {{ substr($member->first_name, 0, 1) }}{{ substr($member->last_name, 0, 1) }}
                                    </span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                        {{ $member->full_name }}
                                    </p>
                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                        {{ ucfirst($member->relationship) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- Preferences -->
    <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm">
        <div class="p-6">
            <h3 class="text-lg font-semibold mb-4">Preferences</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Timezone:</span>
                    <span>{{ $user->timezone }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 dark:text-gray-400">Language:</span>
                    <span>{{ strtoupper($user->language) }}</span>
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
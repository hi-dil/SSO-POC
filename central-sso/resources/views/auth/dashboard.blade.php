@extends('layouts.admin')

@section('title', 'Dashboard')

@section('header')
    <div>
        <h1 class="text-2xl font-semibold text-card-foreground">Welcome, {{ auth()->user()->name }}!</h1>
        <p class="text-sm text-muted-foreground mt-1">
            Choose your next action from the options below
        </p>
    </div>
@endsection

@section('content')
<div class="space-y-6">
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        
        <!-- Admin Dashboard Option -->
        @can('manage-tenants')
            <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-12 w-12 rounded-lg bg-primary/10 flex items-center justify-center">
                                <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-lg font-semibold text-card-foreground">Admin Dashboard</h3>
                            <p class="text-sm text-muted-foreground">
                                Manage tenants, users, and system settings
                            </p>
                        </div>
                    </div>
                    <div class="mt-6">
                        <a href="{{ route('admin.tenants.index') }}" 
                           class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2 w-full">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                            </svg>
                            Go to Admin Panel
                        </a>
                    </div>
                </div>
            </div>
        @endcan

        <!-- Tenant Applications -->
        @if($tenants->count() > 0)
            <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm hover:shadow-md transition-shadow">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-12 w-12 rounded-lg bg-blue-50 flex items-center justify-center">
                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="text-lg font-semibold text-card-foreground">Tenant Applications</h3>
                            <p class="text-sm text-muted-foreground">
                                Access your tenant applications
                            </p>
                        </div>
                    </div>
                    <div class="mt-6">
                        @if($tenants->count() === 1)
                            <form method="POST" action="{{ route('tenant.access') }}">
                                @csrf
                                <input type="hidden" name="tenant_slug" value="{{ $tenants->first()->slug }}">
                                <button type="submit" 
                                        class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-blue-600 text-white hover:bg-blue-700 h-10 px-4 py-2 w-full">
                                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                    Access {{ $tenants->first()->name }}
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('tenant.access') }}">
                                @csrf
                                <div class="space-y-4">
                                    <div class="space-y-2">
                                        <label for="tenant_slug" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">Select Tenant</label>
                                        <select name="tenant_slug" id="tenant_slug" required 
                                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                                            <option value="">Choose a tenant...</option>
                                            @foreach($tenants as $tenant)
                                                <option value="{{ $tenant->slug }}">{{ $tenant->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="submit" 
                                            class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-blue-600 text-white hover:bg-blue-700 h-10 px-4 py-2 w-full">
                                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                        Access Selected Tenant
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- User Info Section -->
    <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
        <div class="p-6">
            <h3 class="text-lg font-semibold text-card-foreground mb-4">Account Information</h3>
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                <div class="space-y-1">
                    <span class="text-sm font-medium text-muted-foreground">Name</span>
                    <p class="text-sm text-card-foreground">{{ auth()->user()->name }}</p>
                </div>
                <div class="space-y-1">
                    <span class="text-sm font-medium text-muted-foreground">Email</span>
                    <p class="text-sm text-card-foreground">{{ auth()->user()->email }}</p>
                </div>
                <div class="space-y-1">
                    <span class="text-sm font-medium text-muted-foreground">Tenant Access</span>
                    <p class="text-sm text-card-foreground">{{ $tenants->count() }} tenant(s)</p>
                </div>
            </div>
            
            @if($tenants->count() > 0)
                <div class="mt-6 space-y-2">
                    <span class="text-sm font-medium text-muted-foreground">Available Tenants</span>
                    <div class="flex flex-wrap gap-2">
                        @foreach($tenants as $tenant)
                            <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-blue-50 text-blue-700">
                                {{ $tenant->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
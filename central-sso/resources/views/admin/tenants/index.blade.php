@extends('layouts.admin')

@section('title', 'Tenant Management')

@section('header')
    <div>
        <h1 class="text-2xl font-semibold text-card-foreground">Tenant Management</h1>
        <p class="text-sm text-muted-foreground mt-1">
            Manage all tenants in the central SSO system
        </p>
    </div>
@endsection

@section('actions')
    @can('manage-tenants')
        <a href="{{ route('admin.tenants.create') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            New Tenant
        </a>
    @endcan
@endsection

@section('content')
<div class="space-y-6">
    @if($tenants->count() > 0)
        <!-- Tenants Table -->
        <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
            <div class="overflow-hidden">
                <table class="w-full caption-bottom text-sm">
                    <thead class="[&_tr]:border-b border-border">
                        <tr class="border-b border-border transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                            <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0">
                                Tenant
                            </th>
                            <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0">
                                Domain
                            </th>
                            <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0">
                                Users
                            </th>
                            <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0">
                                Status
                            </th>
                            <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0">
                                Created
                            </th>
                            <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="[&_tr:last-child]:border-0">
                        @foreach($tenants as $tenant)
                            <tr class="border-b border-border transition-colors hover:bg-muted/50 data-[state=selected]:bg-muted">
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 rounded-full bg-muted flex items-center justify-center">
                                            <span class="text-sm font-medium text-muted-foreground">
                                                {{ substr($tenant->name, 0, 2) }}
                                            </span>
                                        </div>
                                        <div>
                                            <div class="font-medium text-card-foreground">
                                                {{ $tenant->name }}
                                            </div>
                                            <div class="text-sm text-muted-foreground">
                                                {{ $tenant->slug }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0 text-card-foreground">
                                    {{ $tenant->domain ?? 'Not set' }}
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80">
                                        {{ $tenant->users_count }} users
                                    </span>
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 
                                        @if($tenant->is_active) border-transparent bg-green-50 text-green-700 @else border-destructive/10 text-destructive @endif">
                                        {{ $tenant->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0 text-muted-foreground">
                                    {{ $tenant->created_at->format('M d, Y') }}
                                </td>
                                <td class="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                                    <div class="flex items-center gap-2 justify-end">
                                        <a href="{{ route('admin.tenants.show', $tenant) }}" 
                                           class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 px-3">
                                            <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            View
                                        </a>
                                        
                                        @can('manage-tenants')
                                            <a href="{{ route('admin.tenants.users', $tenant) }}" 
                                               class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 px-3">
                                                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                                </svg>
                                                Users
                                            </a>
                                            
                                            <a href="{{ route('admin.tenants.edit', $tenant) }}" 
                                               class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 px-3">
                                                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                                Edit
                                            </a>
                                            
                                            <form method="POST" action="{{ route('admin.tenants.toggle', $tenant) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 px-3">
                                                    {{ $tenant->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                            
                                            @if($tenant->users_count == 0)
                                                <button onclick="confirmDelete('{{ route('admin.tenants.destroy', $tenant) }}')" 
                                                        class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-destructive/20 bg-destructive/10 text-destructive hover:bg-destructive hover:text-destructive-foreground h-8 px-3">
                                                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                    Delete
                                                </button>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        @if($tenants->hasPages())
            <div class="mt-6">
                {{ $tenants->links() }}
            </div>
        @endif
    @else
        <!-- Empty State -->
        <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
            <div class="flex flex-col items-center justify-center py-12 px-6">
                <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m14 0v-5a2 2 0 00-2-2H9a2 2 0 00-2 2v5m-4 0h4"></path>
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-card-foreground">No tenants</h3>
                <p class="mt-2 text-sm text-muted-foreground text-center">Get started by creating a new tenant application.</p>
                @can('manage-tenants')
                    <div class="mt-6">
                        <a href="{{ route('admin.tenants.create') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            New Tenant
                        </a>
                    </div>
                @endcan
            </div>
        </div>
    @endif
</div>

<!-- Delete Form Template -->
<form id="deleteForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
function confirmDelete(url) {
    if (confirm('Are you sure you want to delete this tenant? This action cannot be undone.')) {
        const form = document.getElementById('deleteForm');
        form.action = url;
        form.submit();
        
        // Show loading toast
        if (window.showToast) {
            window.showToast('Deleting tenant...', 'info');
        }
    }
}

// Show success message for form submissions
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
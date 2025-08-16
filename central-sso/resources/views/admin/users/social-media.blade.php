@extends('layouts.admin')

@section('title', 'User Social Media Management')

@section('header')
    <div>
        <h1 class="text-2xl font-semibold text-card-foreground">Social Media Management</h1>
        <p class="text-sm text-muted-foreground mt-1">
            Manage social media profiles for {{ $user->name }}
        </p>
    </div>
@endsection

@section('actions')
    <div class="flex space-x-3">
        <a href="{{ route('admin.users.show', $user) }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to User
        </a>
        @can('Manage User Social Media')
            <button onclick="showAddModal()" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Add Social Media
            </button>
        @endcan
    </div>
@endsection

@section('content')
<div class="space-y-6">
    @if($user->socialMedia->count() > 0)
        <!-- Social Media Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($user->socialMedia as $social)
                <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-3">
                                    <div class="flex-shrink-0">
                                        <div class="h-10 w-10 rounded-lg flex items-center justify-center" 
                                             style="background-color: {{ $social->platform_color }}20;">
                                            <i class="{{ $social->platform_icon }} text-xl" 
                                               style="color: {{ $social->platform_color }};"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-lg font-medium text-card-foreground">
                                            {{ $social->display_name ?: ucfirst($social->platform) }}
                                        </h3>
                                        <div class="flex items-center space-x-2 mt-1">
                                            @if($social->url)
                                                <a href="{{ $social->url }}" target="_blank" class="text-blue-600 hover:underline text-sm">
                                                    {{ $social->username ? '@' . $social->username : 'Visit Profile' }}
                                                </a>
                                            @else
                                                <span class="text-sm text-muted-foreground">
                                                    {{ $social->username ? '@' . $social->username : 'No link provided' }}
                                                </span>
                                            @endif
                                            
                                            @if($social->is_public)
                                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-green-50 text-green-700">
                                                    Public
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-gray-50 text-gray-700">
                                                    Private
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                @if($social->notes)
                                    <div class="mt-3 p-2 bg-muted rounded text-sm text-muted-foreground">
                                        {{ $social->notes }}
                                    </div>
                                @endif
                                
                                @if($social->order)
                                    <div class="mt-2 text-xs text-muted-foreground">
                                        Display Order: {{ $social->order }}
                                    </div>
                                @endif
                            </div>
                            
                            @can('Manage User Social Media')
                                <div class="flex space-x-2 ml-4">
                                    <button onclick="showEditModal({{ $social->id }})" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 w-8">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <button onclick="confirmDelete({{ $social->id }})" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-destructive hover:text-destructive-foreground h-8 w-8">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            @endcan
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Empty State -->
        <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
            <div class="flex flex-col items-center justify-center py-12 px-6">
                <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-card-foreground">No social media profiles</h3>
                <p class="mt-2 text-sm text-muted-foreground text-center">
                    This user hasn't added any social media profiles yet.
                </p>
                @can('Manage User Social Media')
                    <div class="mt-6">
                        <button onclick="showAddModal()" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Social Media
                        </button>
                    </div>
                @endcan
            </div>
        </div>
    @endif
</div>

@can('Manage User Social Media')
<!-- Add/Edit Social Media Modal -->
<div x-data="{ 
    showModal: false, 
    isEdit: false, 
    currentSocial: null,
    init() {
        window.addEventListener('show-social-modal', (e) => {
            this.isEdit = e.detail.isEdit;
            this.currentSocial = e.detail.social;
            this.showModal = true;
        });
        window.addEventListener('close-social-modal', () => {
            this.showModal = false;
        });
    }
}" x-cloak>
    <div x-show="showModal" class="fixed inset-0 bg-black/50 overflow-y-auto h-full w-full z-50" x-transition>
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-lg bg-card">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-card-foreground mb-4" x-text="isEdit ? 'Edit Social Media' : 'Add Social Media'"></h3>
                
                <form id="socialForm" class="space-y-4">
                    @csrf
                    <input type="hidden" id="social_id" name="social_id">
                    
                    <div>
                        <label for="platform" class="block text-sm font-medium text-card-foreground mb-1">
                            Platform <span class="text-red-500">*</span>
                        </label>
                        <select id="platform" name="platform" required
                                class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                            <option value="">Select Platform</option>
                            @foreach(\App\Models\UserSocialMedia::getSocialPlatforms() as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div>
                        <label for="display_name" class="block text-sm font-medium text-card-foreground mb-1">
                            Display Name
                        </label>
                        <input type="text" id="display_name" name="display_name" placeholder="e.g., My Professional LinkedIn"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                    </div>
                    
                    <div>
                        <label for="username" class="block text-sm font-medium text-card-foreground mb-1">
                            Username/Handle
                        </label>
                        <input type="text" id="username" name="username" placeholder="e.g., john_doe (without @)"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                    </div>
                    
                    <div>
                        <label for="url" class="block text-sm font-medium text-card-foreground mb-1">
                            Profile URL <span class="text-red-500">*</span>
                        </label>
                        <input type="url" id="url" name="url" required placeholder="https://platform.com/username"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="order" class="block text-sm font-medium text-card-foreground mb-1">
                                Display Order
                            </label>
                            <input type="number" id="order" name="order" min="1" max="100" placeholder="1"
                                   class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                        </div>
                        
                        <div class="flex items-center space-x-2 pt-6">
                            <input type="checkbox" id="is_public" name="is_public" value="1"
                                   class="h-4 w-4 text-primary border-input rounded focus:ring-ring">
                            <label for="is_public" class="text-sm font-medium text-card-foreground">
                                Public Profile
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <label for="notes" class="block text-sm font-medium text-card-foreground mb-1">
                            Notes
                        </label>
                        <textarea id="notes" name="notes" rows="2" placeholder="Additional notes about this profile..."
                                  class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring"></textarea>
                    </div>
                </form>
                
                <div class="flex gap-3 justify-end mt-6">
                    <button @click="showModal = false" class="px-4 py-2 bg-secondary text-secondary-foreground text-sm font-medium rounded-md hover:bg-secondary/80 focus:outline-none focus:ring-2 focus:ring-ring transition-colors">
                        Cancel
                    </button>
                    <button onclick="submitForm()" class="px-4 py-2 bg-primary text-primary-foreground text-sm font-medium rounded-md hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-ring transition-colors" x-text="isEdit ? 'Update' : 'Add'">
                        Add
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

<script>
// Store social media data for editing
const socialMedia = @json($user->socialMedia);

function showAddModal() {
    // Reset form
    document.getElementById('socialForm').reset();
    document.getElementById('social_id').value = '';
    
    // Show modal using Alpine.js event
    window.dispatchEvent(new CustomEvent('show-social-modal', {
        detail: { isEdit: false, social: null }
    }));
}

function showEditModal(socialId) {
    const social = socialMedia.find(s => s.id === socialId);
    if (!social) return;
    
    // Populate form
    document.getElementById('social_id').value = social.id;
    document.getElementById('platform').value = social.platform;
    document.getElementById('display_name').value = social.display_name || '';
    document.getElementById('username').value = social.username || '';
    document.getElementById('url').value = social.url;
    document.getElementById('order').value = social.order || '';
    document.getElementById('is_public').checked = social.is_public;
    document.getElementById('notes').value = social.notes || '';
    
    // Show modal using Alpine.js event
    window.dispatchEvent(new CustomEvent('show-social-modal', {
        detail: { isEdit: true, social: social }
    }));
}

function submitForm() {
    const form = document.getElementById('socialForm');
    const formData = new FormData(form);
    const socialId = document.getElementById('social_id').value;
    const isEdit = !!socialId;
    
    let url;
    if (isEdit) {
        url = '{{ route('admin.users.social-media.update', [$user, ':id']) }}'.replace(':id', socialId);
    } else {
        url = '{{ route('admin.users.social-media.store', $user) }}';
    }
    
    if (isEdit) {
        formData.append('_method', 'PUT');
    }
    
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal using Alpine.js event
            window.dispatchEvent(new CustomEvent('close-social-modal'));
            
            // Show success message
            if (window.showToast) {
                window.showToast(data.message, 'success');
            }
            
            // Reload page to show updated data
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            if (window.showToast) {
                window.showToast(data.message || 'An error occurred', 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (window.showToast) {
            window.showToast('An error occurred while saving', 'error');
        }
    });
}

function confirmDelete(socialId) {
    const social = socialMedia.find(s => s.id === socialId);
    if (!social) return;
    
    if (confirm(`Are you sure you want to delete this social media profile: ${social.display_name || social.platform}?`)) {
        const form = document.getElementById('deleteForm');
        form.action = '{{ route('admin.users.social-media.destroy', [$user, ':id']) }}'.replace(':id', socialId);
        form.submit();
        
        // Show loading toast
        if (window.showToast) {
            window.showToast('Removing social media profile...', 'info');
        }
    }
}

// Handle success/error messages
document.addEventListener('DOMContentLoaded', function() {
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
@endcan
@endsection
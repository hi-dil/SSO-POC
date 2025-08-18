@extends('layouts.admin')

@section('title', 'Social Media Management')

@section('header')
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Social Media Management</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Manage your social media profiles and online presence
        </p>
    </div>
@endsection

@section('actions')
    <div class="flex space-x-3">
        <a href="{{ route('profile.show') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium  transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white h-10 px-4 py-2">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Profile
        </a>
        <button onclick="showAddModal()" class="inline-flex items-center justify-center rounded-md text-sm font-medium  transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-teal-600 dark:bg-teal-500 text-white hover:bg-teal-700 dark:hover:bg-teal-600 h-10 px-4 py-2">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Add Social Media
        </button>
    </div>
@endsection

@section('content')
<div class="space-y-6">
    @if($user->socialMedia->count() > 0)
        <!-- Social Media Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($user->socialMedia as $social)
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-3">
                                    <div class="flex-shrink-0">
                                        <i class="{{ $social->platform_icon }} text-2xl" style="color: {{ $social->platform_color }}"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                                            {{ $social->display_name ?: ucfirst($social->platform) }}
                                        </h3>
                                        @if($social->username)
                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $social->username }}</p>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="space-y-2">
                                    <a href="{{ $social->url }}" target="_blank" rel="noopener noreferrer" 
                                       class="text-sm text-primary hover:underline break-all">
                                        {{ $social->url }}
                                    </a>
                                    
                                    <div class="flex items-center space-x-2">
                                        @if($social->is_public)
                                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-green-50 text-green-700">
                                                Public
                                            </span>
                                        @else
                                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-input bg-background text-foreground">
                                                Private
                                            </span>
                                        @endif
                                        
                                        <span class="text-xs text-gray-600 dark:text-gray-400">Order: {{ $social->order }}</span>
                                    </div>
                                    
                                    @if($social->notes)
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">{{ $social->notes }}</p>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="flex space-x-2 ml-4">
                                <button onclick="showEditModal({{ $social->id }})" class="inline-flex items-center justify-center rounded-md text-sm font-medium  transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white h-8 w-8">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button onclick="confirmDelete({{ $social->id }})" class="inline-flex items-center justify-center rounded-md text-sm font-medium  transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-destructive hover:text-destructive-foreground h-8 w-8">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Empty State -->
        <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm">
            <div class="flex flex-col items-center justify-center py-12 px-6">
                <svg class="mx-auto h-12 w-12 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m0 0V1a1 1 0 011-1h2a1 1 0 011 1v18a1 1 0 01-1 1H4a1 1 0 01-1-1V1a1 1 0 011-1h2a1 1 0 011 1v3m0 0h8m-8 0V4"></path>
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">No social media profiles</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 text-center">
                    You haven't added any social media profiles yet. Add your first profile to get started.
                </p>
                <div class="mt-6">
                    <button onclick="showAddModal()" class="inline-flex items-center justify-center rounded-md text-sm font-medium  transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-teal-600 dark:bg-teal-500 text-white hover:bg-teal-700 dark:hover:bg-teal-600 h-10 px-4 py-2">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add Social Media
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

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
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-lg bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4" x-text="isEdit ? 'Edit Social Media' : 'Add Social Media'"></h3>
                
                <form id="socialForm" class="space-y-4">
                    @csrf
                    <input type="hidden" id="social_id" name="social_id">
                    
                    <div>
                        <label for="platform" class="block text-sm font-medium text-gray-900 dark:text-white mb-1">
                            Platform <span class="text-red-500">*</span>
                        </label>
                        <select id="platform" name="platform" required onchange="updatePlatformIcon()"
                                class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                            <option value="">Select Platform</option>
                            @foreach(\App\Models\UserSocialMedia::getPlatforms() as $key => $platform)
                                <option value="{{ $key }}">{{ $platform['name'] }}</option>
                            @endforeach
                        </select>
                        <div id="platform-preview" class="mt-2 flex items-center space-x-2 hidden">
                            <i id="platform-icon" class="text-lg"></i>
                            <span id="platform-name" class="text-sm text-gray-600 dark:text-gray-400"></span>
                        </div>
                    </div>
                    
                    <div>
                        <label for="url" class="block text-sm font-medium text-gray-900 dark:text-white mb-1">
                            Profile URL <span class="text-red-500">*</span>
                        </label>
                        <input type="url" id="url" name="url" required placeholder="https://example.com/yourprofile"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-gray-600 dark:text-gray-400 focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-900 dark:text-white mb-1">
                                Username
                            </label>
                            <input type="text" id="username" name="username" placeholder="@username"
                                   class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-gray-600 dark:text-gray-400 focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                        </div>
                        
                        <div>
                            <label for="display_name" class="block text-sm font-medium text-gray-900 dark:text-white mb-1">
                                Display Name
                            </label>
                            <input type="text" id="display_name" name="display_name" placeholder="Custom name"
                                   class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-gray-600 dark:text-gray-400 focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="order" class="block text-sm font-medium text-gray-900 dark:text-white mb-1">
                                Display Order
                            </label>
                            <input type="number" id="order" name="order" value="0" min="0"
                                   class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                        </div>
                        
                        <div class="flex items-center space-x-2 pt-6">
                            <input type="checkbox" id="is_public" name="is_public" value="1"
                                   class="h-4 w-4 text-primary border-input rounded focus:ring-ring">
                            <label for="is_public" class="text-sm font-medium text-gray-900 dark:text-white">
                                Public Profile
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-900 dark:text-white mb-1">
                            Notes
                        </label>
                        <textarea id="notes" name="notes" rows="2" placeholder="Additional notes..."
                                  class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-gray-600 dark:text-gray-400 focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring"></textarea>
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
const platforms = @json(\App\Models\UserSocialMedia::getPlatforms());

function showAddModal() {
    // Reset form
    document.getElementById('socialForm').reset();
    document.getElementById('social_id').value = '';
    document.getElementById('order').value = socialMedia.length;
    document.getElementById('platform-preview').classList.add('hidden');
    
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
    document.getElementById('url').value = social.url;
    document.getElementById('username').value = social.username || '';
    document.getElementById('display_name').value = social.display_name || '';
    document.getElementById('order').value = social.order;
    document.getElementById('is_public').checked = social.is_public;
    document.getElementById('notes').value = social.notes || '';
    
    // Update platform preview
    updatePlatformIcon();
    
    // Show modal using Alpine.js event
    window.dispatchEvent(new CustomEvent('show-social-modal', {
        detail: { isEdit: true, social: social }
    }));
}

function updatePlatformIcon() {
    const platformSelect = document.getElementById('platform');
    const preview = document.getElementById('platform-preview');
    const icon = document.getElementById('platform-icon');
    const name = document.getElementById('platform-name');
    
    if (platformSelect.value && platforms[platformSelect.value]) {
        const platform = platforms[platformSelect.value];
        icon.className = platform.icon + ' text-lg';
        icon.style.color = platform.color;
        name.textContent = platform.name;
        preview.classList.remove('hidden');
    } else {
        preview.classList.add('hidden');
    }
}

function submitForm() {
    const form = document.getElementById('socialForm');
    const formData = new FormData(form);
    const socialId = document.getElementById('social_id').value;
    const isEdit = !!socialId;
    
    let url;
    if (isEdit) {
        url = '{{ route('profile.social-media.update', ':id') }}'.replace(':id', socialId);
    } else {
        url = '{{ route('profile.social-media.store') }}';
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
    
    if (confirm(`Are you sure you want to delete this social media profile: ${social.platform}?`)) {
        const form = document.getElementById('deleteForm');
        form.action = '{{ route('profile.social-media.destroy', ':id') }}'.replace(':id', socialId);
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
@endsection
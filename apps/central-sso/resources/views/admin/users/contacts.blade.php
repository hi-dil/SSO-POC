@extends('layouts.admin')

@section('title', 'User Contact Management')

@section('header')
    <div>
        <h1 class="text-2xl font-semibold text-card-foreground">Contact Management</h1>
        <p class="text-sm text-muted-foreground mt-1">
            Manage contact information for {{ $user->name }}
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
        @can('Manage User Contacts')
            <button onclick="showAddModal()" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Add Contact
            </button>
        @endcan
    </div>
@endsection

@section('content')
<div class="space-y-6">
    @if($user->contacts->count() > 0)
        <!-- Contacts Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($user->contacts as $contact)
                <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-2">
                                    <div class="flex-shrink-0">
                                        @if($contact->type === 'email')
                                            <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                            </svg>
                                        @elseif(in_array($contact->type, ['phone', 'mobile', 'work_phone', 'home_phone']))
                                            <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                            </svg>
                                        @else
                                            <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-lg font-medium text-card-foreground">
                                            {{ $contact->label ?: ucwords(str_replace('_', ' ', $contact->type)) }}
                                        </h3>
                                        <div class="flex items-center space-x-2">
                                            @if($contact->type === 'email')
                                                <a href="mailto:{{ $contact->value }}" class="text-blue-600 hover:underline">
                                                    {{ $contact->value }}
                                                </a>
                                            @elseif(in_array($contact->type, ['phone', 'mobile', 'work_phone', 'home_phone']))
                                                <a href="tel:{{ $contact->value }}" class="text-green-600 hover:underline">
                                                    {{ $contact->value }}
                                                </a>
                                            @else
                                                <span class="text-card-foreground">{{ $contact->value }}</span>
                                            @endif
                                            
                                            @if($contact->is_primary)
                                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                                                    Primary
                                                </span>
                                            @endif
                                            
                                            @if($contact->is_public)
                                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300">
                                                    Public
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                @if($contact->notes)
                                    <p class="text-sm text-muted-foreground mt-2">{{ $contact->notes }}</p>
                                @endif
                            </div>
                            
                            @can('Manage User Contacts')
                                <div class="flex space-x-2 ml-4">
                                    <button onclick="showEditModal({{ $contact->id }})" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 w-8">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <button onclick="confirmDelete({{ $contact->id }})" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-destructive hover:text-destructive-foreground h-8 w-8">
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-card-foreground">No contact information</h3>
                <p class="mt-2 text-sm text-muted-foreground text-center">
                    This user hasn't added any contact details yet.
                </p>
                @can('Manage User Contacts')
                    <div class="mt-6">
                        <button onclick="showAddModal()" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Contact
                        </button>
                    </div>
                @endcan
            </div>
        </div>
    @endif
</div>

@can('Manage User Contacts')
<!-- Add/Edit Contact Modal -->
<div x-data="{ 
    showModal: false, 
    isEdit: false, 
    currentContact: null,
    init() {
        window.addEventListener('show-contact-modal', (e) => {
            this.isEdit = e.detail.isEdit;
            this.currentContact = e.detail.contact;
            this.showModal = true;
        });
        window.addEventListener('close-contact-modal', () => {
            this.showModal = false;
        });
    }
}" x-cloak>
    <div x-show="showModal" class="fixed inset-0 bg-black/50 overflow-y-auto h-full w-full z-50" x-transition>
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-lg bg-card">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-card-foreground mb-4" x-text="isEdit ? 'Edit Contact' : 'Add Contact'"></h3>
                
                <form id="contactForm" class="space-y-4">
                    @csrf
                    <input type="hidden" id="contact_id" name="contact_id">
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="type" class="block text-sm font-medium text-card-foreground mb-1">
                                Type <span class="text-red-500">*</span>
                            </label>
                            <select id="type" name="type" required
                                    class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                                <option value="">Select Type</option>
                                @foreach(\App\Models\UserContact::getContactTypes() as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div>
                            <label for="label" class="block text-sm font-medium text-card-foreground mb-1">
                                Custom Label
                            </label>
                            <input type="text" id="label" name="label" placeholder="e.g., Work Email"
                                   class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                        </div>
                    </div>
                    
                    <div>
                        <label for="value" class="block text-sm font-medium text-card-foreground mb-1">
                            Contact Value <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="value" name="value" required placeholder="Email, phone number, etc."
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex items-center space-x-2">
                            <input type="checkbox" id="is_primary" name="is_primary" value="1"
                                   class="h-4 w-4 text-primary border-input rounded focus:ring-ring">
                            <label for="is_primary" class="text-sm font-medium text-card-foreground">
                                Primary Contact
                            </label>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <input type="checkbox" id="is_public" name="is_public" value="1"
                                   class="h-4 w-4 text-primary border-input rounded focus:ring-ring">
                            <label for="is_public" class="text-sm font-medium text-card-foreground">
                                Public
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <label for="notes" class="block text-sm font-medium text-card-foreground mb-1">
                            Notes
                        </label>
                        <textarea id="notes" name="notes" rows="2" placeholder="Additional notes..."
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
// Store contact data for editing
const contacts = @json($user->contacts);

function showAddModal() {
    // Reset form
    document.getElementById('contactForm').reset();
    document.getElementById('contact_id').value = '';
    
    // Show modal using Alpine.js event
    window.dispatchEvent(new CustomEvent('show-contact-modal', {
        detail: { isEdit: false, contact: null }
    }));
}

function showEditModal(contactId) {
    const contact = contacts.find(c => c.id === contactId);
    if (!contact) return;
    
    // Populate form
    document.getElementById('contact_id').value = contact.id;
    document.getElementById('type').value = contact.type;
    document.getElementById('label').value = contact.label || '';
    document.getElementById('value').value = contact.value;
    document.getElementById('is_primary').checked = contact.is_primary;
    document.getElementById('is_public').checked = contact.is_public;
    document.getElementById('notes').value = contact.notes || '';
    
    // Show modal using Alpine.js event
    window.dispatchEvent(new CustomEvent('show-contact-modal', {
        detail: { isEdit: true, contact: contact }
    }));
}

function submitForm() {
    const form = document.getElementById('contactForm');
    const formData = new FormData(form);
    const contactId = document.getElementById('contact_id').value;
    const isEdit = !!contactId;
    
    let url;
    if (isEdit) {
        url = '{{ route('admin.users.contacts.update', [$user, ':id']) }}'.replace(':id', contactId);
    } else {
        url = '{{ route('admin.users.contacts.store', $user) }}';
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
            window.dispatchEvent(new CustomEvent('close-contact-modal'));
            
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

function confirmDelete(contactId) {
    const contact = contacts.find(c => c.id === contactId);
    if (!contact) return;
    
    if (confirm(`Are you sure you want to delete this contact: ${contact.value}?`)) {
        const form = document.getElementById('deleteForm');
        form.action = '{{ route('admin.users.contacts.destroy', [$user, ':id']) }}'.replace(':id', contactId);
        form.submit();
        
        // Show loading toast
        if (window.showToast) {
            window.showToast('Removing contact...', 'info');
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
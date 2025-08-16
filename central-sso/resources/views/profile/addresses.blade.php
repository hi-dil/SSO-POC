@extends('layouts.admin')

@section('title', 'Address Management')

@section('header')
    <div>
        <h1 class="text-2xl font-semibold text-card-foreground">Address Management</h1>
        <p class="text-sm text-muted-foreground mt-1">
            Manage your physical addresses and location details
        </p>
    </div>
@endsection

@section('actions')
    <div class="flex space-x-3">
        <a href="{{ route('profile.show') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-10 px-4 py-2">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Back to Profile
        </a>
        <button onclick="showAddModal()" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Add Address
        </button>
    </div>
@endsection

@section('content')
<div class="space-y-6">
    @if($user->addresses->count() > 0)
        <!-- Addresses Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach($user->addresses as $address)
                <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
                    <div class="p-6">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3 mb-3">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-lg font-medium text-card-foreground">
                                            {{ $address->label ?: ucwords(str_replace('_', ' ', $address->type)) }}
                                        </h3>
                                        <div class="flex items-center space-x-2 mt-1">
                                            @if($address->is_primary)
                                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-blue-50 text-blue-700">
                                                    Primary
                                                </span>
                                            @endif
                                            
                                            @if($address->is_public)
                                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-green-50 text-green-700">
                                                    Public
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="space-y-2">
                                    <div class="text-sm text-card-foreground">
                                        <div>{{ $address->address_line_1 }}</div>
                                        @if($address->address_line_2)
                                            <div>{{ $address->address_line_2 }}</div>
                                        @endif
                                        <div>
                                            {{ $address->city }}@if($address->state_province), {{ $address->state_province }}@endif
                                            @if($address->postal_code) {{ $address->postal_code }}@endif
                                        </div>
                                        <div>{{ $address->country }}</div>
                                    </div>
                                    
                                    @if($address->notes)
                                        <p class="text-sm text-muted-foreground mt-2">{{ $address->notes }}</p>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="flex space-x-2 ml-4">
                                <button onclick="showEditModal({{ $address->id }})" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 w-8">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button onclick="confirmDelete({{ $address->id }})" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-destructive hover:text-destructive-foreground h-8 w-8">
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
        <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
            <div class="flex flex-col items-center justify-center py-12 px-6">
                <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-card-foreground">No addresses</h3>
                <p class="mt-2 text-sm text-muted-foreground text-center">
                    You haven't added any addresses yet. Add your first address to get started.
                </p>
                <div class="mt-6">
                    <button onclick="showAddModal()" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add Address
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Add/Edit Address Modal -->
<div x-data="{ 
    showModal: false, 
    isEdit: false, 
    currentAddress: null,
    init() {
        window.addEventListener('show-address-modal', (e) => {
            this.isEdit = e.detail.isEdit;
            this.currentAddress = e.detail.address;
            this.showModal = true;
        });
        window.addEventListener('close-address-modal', () => {
            this.showModal = false;
        });
    }
}" x-cloak>
    <div x-show="showModal" class="fixed inset-0 bg-black/50 overflow-y-auto h-full w-full z-50" x-transition>
        <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-lg bg-card">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-card-foreground mb-4" x-text="isEdit ? 'Edit Address' : 'Add Address'"></h3>
                
                <form id="addressForm" class="space-y-4">
                    @csrf
                    <input type="hidden" id="address_id" name="address_id">
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="type" class="block text-sm font-medium text-card-foreground mb-1">
                                Type <span class="text-red-500">*</span>
                            </label>
                            <select id="type" name="type" required
                                    class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                                <option value="">Select Type</option>
                                <option value="home">Home</option>
                                <option value="work">Work</option>
                                <option value="billing">Billing</option>
                                <option value="shipping">Shipping</option>
                                <option value="mailing">Mailing</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="label" class="block text-sm font-medium text-card-foreground mb-1">
                                Custom Label
                            </label>
                            <input type="text" id="label" name="label" placeholder="e.g., Parents House"
                                   class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                        </div>
                    </div>
                    
                    <div>
                        <label for="address_line_1" class="block text-sm font-medium text-card-foreground mb-1">
                            Address Line 1 <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="address_line_1" name="address_line_1" required placeholder="Street address, P.O. box, etc."
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                    </div>
                    
                    <div>
                        <label for="address_line_2" class="block text-sm font-medium text-card-foreground mb-1">
                            Address Line 2
                        </label>
                        <input type="text" id="address_line_2" name="address_line_2" placeholder="Apartment, suite, unit, building, floor, etc."
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="city" class="block text-sm font-medium text-card-foreground mb-1">
                                City <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="city" name="city" required
                                   class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                        </div>
                        
                        <div>
                            <label for="state_province" class="block text-sm font-medium text-card-foreground mb-1">
                                State/Province
                            </label>
                            <input type="text" id="state_province" name="state_province"
                                   class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="postal_code" class="block text-sm font-medium text-card-foreground mb-1">
                                Postal Code
                            </label>
                            <input type="text" id="postal_code" name="postal_code"
                                   class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                        </div>
                        
                        <div>
                            <label for="country" class="block text-sm font-medium text-card-foreground mb-1">
                                Country <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="country" name="country" required
                                   class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex items-center space-x-2">
                            <input type="checkbox" id="is_primary" name="is_primary" value="1"
                                   class="h-4 w-4 text-primary border-input rounded focus:ring-ring">
                            <label for="is_primary" class="text-sm font-medium text-card-foreground">
                                Primary Address
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
// Store address data for editing
const addresses = @json($user->addresses);

function showAddModal() {
    // Reset form
    document.getElementById('addressForm').reset();
    document.getElementById('address_id').value = '';
    
    // Show modal using Alpine.js event
    window.dispatchEvent(new CustomEvent('show-address-modal', {
        detail: { isEdit: false, address: null }
    }));
}

function showEditModal(addressId) {
    const address = addresses.find(a => a.id === addressId);
    if (!address) return;
    
    // Populate form
    document.getElementById('address_id').value = address.id;
    document.getElementById('type').value = address.type;
    document.getElementById('label').value = address.label || '';
    document.getElementById('address_line_1').value = address.address_line_1;
    document.getElementById('address_line_2').value = address.address_line_2 || '';
    document.getElementById('city').value = address.city;
    document.getElementById('state_province').value = address.state_province || '';
    document.getElementById('postal_code').value = address.postal_code || '';
    document.getElementById('country').value = address.country;
    document.getElementById('is_primary').checked = address.is_primary;
    document.getElementById('is_public').checked = address.is_public;
    document.getElementById('notes').value = address.notes || '';
    
    // Show modal using Alpine.js event
    window.dispatchEvent(new CustomEvent('show-address-modal', {
        detail: { isEdit: true, address: address }
    }));
}

function submitForm() {
    const form = document.getElementById('addressForm');
    const formData = new FormData(form);
    const addressId = document.getElementById('address_id').value;
    const isEdit = !!addressId;
    
    let url;
    if (isEdit) {
        url = '{{ route('profile.addresses.update', ':id') }}'.replace(':id', addressId);
    } else {
        url = '{{ route('profile.addresses.store') }}';
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
            window.dispatchEvent(new CustomEvent('close-address-modal'));
            
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

function confirmDelete(addressId) {
    const address = addresses.find(a => a.id === addressId);
    if (!address) return;
    
    if (confirm(`Are you sure you want to delete this address: ${address.address_line_1}?`)) {
        const form = document.getElementById('deleteForm');
        form.action = '{{ route('profile.addresses.destroy', ':id') }}'.replace(':id', addressId);
        form.submit();
        
        // Show loading toast
        if (window.showToast) {
            window.showToast('Removing address...', 'info');
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
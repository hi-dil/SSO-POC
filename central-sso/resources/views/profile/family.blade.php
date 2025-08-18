@extends('layouts.admin')

@section('title', 'Family Members')

@section('header')
    <div>
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Family Members</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Manage your family member information
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
            Add Family Member
        </button>
    </div>
@endsection

@section('content')
<div class="space-y-6">
    @if($user->familyMembers->count() > 0)
        <!-- Family Members Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($user->familyMembers as $member)
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm">
                    <div class="p-6">
                        <div class="flex items-center space-x-4">
                            <div class="h-16 w-16 rounded-full bg-muted flex items-center justify-center">
                                <span class="text-lg font-medium text-gray-600 dark:text-gray-400">
                                    {{ substr($member->first_name, 0, 1) }}{{ substr($member->last_name, 0, 1) }}
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white truncate">
                                    {{ $member->full_name }}
                                </h3>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ ucfirst($member->relationship) }}
                                </p>
                                @if($member->date_of_birth)
                                    <p class="text-xs text-gray-600 dark:text-gray-400">
                                        Born {{ $member->date_of_birth->format('M d, Y') }}
                                        @if($member->age)
                                            ({{ $member->age }} years old)
                                        @endif
                                    </p>
                                @endif
                            </div>
                        </div>
                        
                        @if($member->phone || $member->email)
                            <div class="mt-4 space-y-1">
                                @if($member->phone)
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        📞 {{ $member->phone }}
                                    </p>
                                @endif
                                @if($member->email)
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        ✉️ {{ $member->email }}
                                    </p>
                                @endif
                            </div>
                        @endif
                        
                        <div class="mt-4 flex justify-end space-x-2">
                            <button onclick="showEditModal({{ $member->id }})" class="inline-flex items-center justify-center rounded-md text-sm font-medium  transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white h-8 px-3">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button onclick="confirmDelete({{ $member->id }})" class="inline-flex items-center justify-center rounded-md text-sm font-medium  transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-destructive hover:text-destructive-foreground h-8 px-3">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">No family members</h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400 text-center">
                    You haven't added any family members yet. Get started by adding your first family member.
                </p>
                <div class="mt-6">
                    <button onclick="showAddModal()" class="inline-flex items-center justify-center rounded-md text-sm font-medium  transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-teal-600 dark:bg-teal-500 text-white hover:bg-teal-700 dark:hover:bg-teal-600 h-10 px-4 py-2">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add Family Member
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Add/Edit Family Member Modal -->
<div x-data="{ 
    showModal: false, 
    isEdit: false, 
    currentMember: null,
    init() {
        window.addEventListener('show-family-modal', (e) => {
            this.isEdit = e.detail.isEdit;
            this.currentMember = e.detail.member;
            this.showModal = true;
        });
        window.addEventListener('close-family-modal', () => {
            this.showModal = false;
        });
    }
}" x-cloak>
    <div x-show="showModal" class="fixed inset-0 bg-black/50 overflow-y-auto h-full w-full z-50" x-transition>
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-lg bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4" x-text="isEdit ? 'Edit Family Member' : 'Add Family Member'"></h3>
                
                <form id="familyForm" class="space-y-4">
                    @csrf
                    <input type="hidden" id="member_id" name="member_id">
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-900 dark:text-white mb-1">
                                First Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="first_name" name="first_name" required
                                   class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-gray-600 dark:text-gray-400 focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                        </div>
                        
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-900 dark:text-white mb-1">
                                Last Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="last_name" name="last_name" required
                                   class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-gray-600 dark:text-gray-400 focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                        </div>
                    </div>
                    
                    <div>
                        <label for="relationship" class="block text-sm font-medium text-gray-900 dark:text-white mb-1">
                            Relationship <span class="text-red-500">*</span>
                        </label>
                        <select id="relationship" name="relationship" required
                                class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                            <option value="">Select Relationship</option>
                            <option value="spouse">Spouse</option>
                            <option value="partner">Partner</option>
                            <option value="child">Child</option>
                            <option value="parent">Parent</option>
                            <option value="sibling">Sibling</option>
                            <option value="grandparent">Grandparent</option>
                            <option value="grandchild">Grandchild</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="date_of_birth" class="block text-sm font-medium text-gray-900 dark:text-white mb-1">
                            Date of Birth
                        </label>
                        <input type="date" id="date_of_birth" name="date_of_birth"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-gray-600 dark:text-gray-400 focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                    </div>
                    
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-900 dark:text-white mb-1">
                            Phone Number
                        </label>
                        <input type="tel" id="phone" name="phone"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-gray-600 dark:text-gray-400 focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-900 dark:text-white mb-1">
                            Email Address
                        </label>
                        <input type="email" id="email" name="email"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-gray-600 dark:text-gray-400 focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
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
// Store family member data for editing
const familyMembers = @json($user->familyMembers);

function showAddModal() {
    // Reset form
    document.getElementById('familyForm').reset();
    document.getElementById('member_id').value = '';
    
    // Show modal using Alpine.js event
    window.dispatchEvent(new CustomEvent('show-family-modal', {
        detail: { isEdit: false, member: null }
    }));
}

function showEditModal(memberId) {
    const member = familyMembers.find(m => m.id === memberId);
    if (!member) return;
    
    // Populate form
    document.getElementById('member_id').value = member.id;
    document.getElementById('first_name').value = member.first_name;
    document.getElementById('last_name').value = member.last_name;
    document.getElementById('relationship').value = member.relationship;
    document.getElementById('date_of_birth').value = member.date_of_birth || '';
    document.getElementById('phone').value = member.phone || '';
    document.getElementById('email').value = member.email || '';
    
    // Show modal using Alpine.js event
    window.dispatchEvent(new CustomEvent('show-family-modal', {
        detail: { isEdit: true, member: member }
    }));
}

function submitForm() {
    const form = document.getElementById('familyForm');
    const formData = new FormData(form);
    const memberId = document.getElementById('member_id').value;
    const isEdit = !!memberId;
    
    let url;
    if (isEdit) {
        // For edit, we need to build the URL properly
        url = '{{ route('profile.family.update', ':id') }}'.replace(':id', memberId);
    } else {
        url = '{{ route('profile.family.store') }}';
    }
    
    const method = isEdit ? 'PUT' : 'POST';
    
    // Add method override for PUT requests
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
            window.dispatchEvent(new CustomEvent('close-family-modal'));
            
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

function confirmDelete(memberId) {
    const member = familyMembers.find(m => m.id === memberId);
    if (!member) return;
    
    if (confirm(`Are you sure you want to remove ${member.first_name} ${member.last_name} from your family members?`)) {
        const form = document.getElementById('deleteForm');
        form.action = '{{ route('profile.family.destroy', ':id') }}'.replace(':id', memberId);
        form.submit();
        
        // Show loading toast
        if (window.showToast) {
            window.showToast('Removing family member...', 'info');
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
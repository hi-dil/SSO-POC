@extends('layouts.admin')

@section('title', 'User Family Management')

@section('header')
    <div>
        <h1 class="text-2xl font-semibold text-card-foreground">Family Management</h1>
        <p class="text-sm text-muted-foreground mt-1">
            Manage family member information for {{ $user->name }}
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
        @can('Manage User Family Members')
            <button onclick="showAddModal()" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Add Family Member
            </button>
        @endcan
    </div>
@endsection

@section('content')
<div class="space-y-6">
    @if($user->familyMembers->count() > 0)
        <!-- Family Members Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($user->familyMembers as $member)
                <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
                    <div class="p-6">
                        <div class="flex items-center space-x-4">
                            <div class="h-16 w-16 rounded-full bg-muted flex items-center justify-center">
                                <span class="text-lg font-medium text-muted-foreground">
                                    {{ substr($member->first_name, 0, 1) }}{{ substr($member->last_name, 0, 1) }}
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-medium text-card-foreground truncate">
                                    {{ $member->full_name }}
                                </h3>
                                <p class="text-sm text-muted-foreground">
                                    {{ ucfirst($member->relationship) }}
                                </p>
                                @if($member->date_of_birth)
                                    <p class="text-xs text-muted-foreground">
                                        Born {{ $member->date_of_birth->format('M d, Y') }}
                                        @if($member->age)
                                            ({{ $member->age }} years old)
                                        @endif
                                    </p>
                                @endif
                                
                                <div class="flex items-center space-x-2 mt-2">
                                    @if($member->is_emergency_contact)
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300">
                                            Emergency Contact
                                        </span>
                                    @endif
                                    
                                    @if($member->is_dependent)
                                        <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 border-transparent bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300">
                                            Dependent
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        @if($member->phone || $member->email || $member->occupation)
                            <div class="mt-4 space-y-2">
                                @if($member->phone)
                                    <div class="flex items-center space-x-2">
                                        <svg class="h-4 w-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                        </svg>
                                        <span class="text-sm text-muted-foreground">{{ $member->phone }}</span>
                                    </div>
                                @endif
                                @if($member->email)
                                    <div class="flex items-center space-x-2">
                                        <svg class="h-4 w-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        <span class="text-sm text-muted-foreground">{{ $member->email }}</span>
                                    </div>
                                @endif
                                @if($member->occupation)
                                    <div class="flex items-center space-x-2">
                                        <svg class="h-4 w-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
                                        </svg>
                                        <span class="text-sm text-muted-foreground">{{ $member->occupation }}</span>
                                    </div>
                                @endif
                            </div>
                        @endif
                        
                        @if($member->notes)
                            <div class="mt-3 p-2 bg-muted rounded text-sm text-muted-foreground">
                                {{ $member->notes }}
                            </div>
                        @endif
                        
                        @can('Manage User Family Members')
                            <div class="mt-4 flex justify-end space-x-2">
                                <button onclick="showEditModal({{ $member->id }})" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-8 w-8">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <button onclick="confirmDelete({{ $member->id }})" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-destructive hover:text-destructive-foreground h-8 w-8">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Empty State -->
        <div class="rounded-lg border border-border bg-card text-card-foreground shadow-sm">
            <div class="flex flex-col items-center justify-center py-12 px-6">
                <svg class="mx-auto h-12 w-12 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-card-foreground">No family members</h3>
                <p class="mt-2 text-sm text-muted-foreground text-center">
                    This user hasn't added any family members yet.
                </p>
                @can('Manage User Family Members')
                    <div class="mt-6">
                        <button onclick="showAddModal()" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Family Member
                        </button>
                    </div>
                @endcan
            </div>
        </div>
    @endif
</div>

@can('Manage User Family Members')
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
        <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-lg bg-card">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-card-foreground mb-4" x-text="isEdit ? 'Edit Family Member' : 'Add Family Member'"></h3>
                
                <form id="familyForm" class="space-y-4">
                    @csrf
                    <input type="hidden" id="member_id" name="member_id">
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-card-foreground mb-1">
                                First Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="first_name" name="first_name" required
                                   class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                        </div>
                        
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-card-foreground mb-1">
                                Last Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="last_name" name="last_name" required
                                   class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="relationship" class="block text-sm font-medium text-card-foreground mb-1">
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
                                <option value="uncle">Uncle</option>
                                <option value="aunt">Aunt</option>
                                <option value="cousin">Cousin</option>
                                <option value="nephew">Nephew</option>
                                <option value="niece">Niece</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="gender" class="block text-sm font-medium text-card-foreground mb-1">
                                Gender
                            </label>
                            <select id="gender" name="gender"
                                    class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                                <option value="prefer_not_to_say">Prefer not to say</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label for="date_of_birth" class="block text-sm font-medium text-card-foreground mb-1">
                            Date of Birth
                        </label>
                        <input type="date" id="date_of_birth" name="date_of_birth"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="phone" class="block text-sm font-medium text-card-foreground mb-1">
                                Phone Number
                            </label>
                            <input type="tel" id="phone" name="phone"
                                   class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium text-card-foreground mb-1">
                                Email Address
                            </label>
                            <input type="email" id="email" name="email"
                                   class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                        </div>
                    </div>
                    
                    <div>
                        <label for="occupation" class="block text-sm font-medium text-card-foreground mb-1">
                            Occupation
                        </label>
                        <input type="text" id="occupation" name="occupation"
                               class="block w-full px-3 py-2 border border-input bg-background rounded-md text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-ring">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div class="flex items-center space-x-2">
                            <input type="checkbox" id="is_emergency_contact" name="is_emergency_contact" value="1"
                                   class="h-4 w-4 text-primary border-input rounded focus:ring-ring">
                            <label for="is_emergency_contact" class="text-sm font-medium text-card-foreground">
                                Emergency Contact
                            </label>
                        </div>
                        
                        <div class="flex items-center space-x-2">
                            <input type="checkbox" id="is_dependent" name="is_dependent" value="1"
                                   class="h-4 w-4 text-primary border-input rounded focus:ring-ring">
                            <label for="is_dependent" class="text-sm font-medium text-card-foreground">
                                Dependent
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <label for="notes" class="block text-sm font-medium text-card-foreground mb-1">
                            Notes
                        </label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Additional notes about this family member..."
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
    document.getElementById('gender').value = member.gender || '';
    document.getElementById('date_of_birth').value = member.date_of_birth || '';
    document.getElementById('phone').value = member.phone || '';
    document.getElementById('email').value = member.email || '';
    document.getElementById('occupation').value = member.occupation || '';
    document.getElementById('is_emergency_contact').checked = member.is_emergency_contact || false;
    document.getElementById('is_dependent').checked = member.is_dependent || false;
    document.getElementById('notes').value = member.notes || '';
    
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
        url = '{{ route('admin.users.family.update', [$user, ':id']) }}'.replace(':id', memberId);
    } else {
        url = '{{ route('admin.users.family.store', $user) }}';
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
    
    if (confirm(`Are you sure you want to remove ${member.first_name} ${member.last_name} from this user's family members?`)) {
        const form = document.getElementById('deleteForm');
        form.action = '{{ route('admin.users.family.destroy', [$user, ':id']) }}'.replace(':id', memberId);
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
@endcan
@endsection
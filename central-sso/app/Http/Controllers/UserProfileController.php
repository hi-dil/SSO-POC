<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserFamilyMember;
use App\Models\UserContact;
use App\Models\UserAddress;
use App\Models\UserSocialMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user()->load([
            'familyMembers',
            'contacts',
            'addresses',
            'socialMedia' => function($query) {
                $query->public()->ordered();
            }
        ]);
        return view('profile.show', compact('user'));
    }

    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
            'nationality' => 'nullable|string|max:100',
            'bio' => 'nullable|string|max:1000',
            'avatar_url' => 'nullable|url|max:500',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state_province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_relationship' => 'nullable|string|max:100',
            'job_title' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'employee_id' => 'nullable|string|max:50',
            'hire_date' => 'nullable|date',
            'timezone' => 'nullable|string|max:50',
            'language' => 'nullable|string|max:10',
        ]);

        $user->update($request->except(['password', 'password_confirmation']));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => $user
            ]);
        }

        return redirect()->route('profile.show')->with('success', 'Profile updated successfully!');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully'
            ]);
        }

        return redirect()->route('profile.show')->with('success', 'Password updated successfully!');
    }

    public function family()
    {
        $user = Auth::user();
        $familyMembers = $user->familyMembers()->orderBy('relationship')->orderBy('first_name')->get();
        
        return view('profile.family', compact('user', 'familyMembers'));
    }

    public function storeFamilyMember(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'relationship' => 'required|string|max:100',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'occupation' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'is_emergency_contact' => 'boolean',
            'is_dependent' => 'boolean',
        ]);

        $familyMember = Auth::user()->familyMembers()->create($request->all());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Family member added successfully',
                'family_member' => $familyMember
            ]);
        }

        return redirect()->route('profile.family')->with('success', 'Family member added successfully!');
    }

    public function updateFamilyMember(Request $request, UserFamilyMember $familyMember)
    {
        // Ensure the family member belongs to the authenticated user
        if ($familyMember->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'relationship' => 'required|string|max:100',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'occupation' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'is_emergency_contact' => 'boolean',
            'is_dependent' => 'boolean',
        ]);

        $familyMember->update($request->all());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Family member updated successfully',
                'family_member' => $familyMember
            ]);
        }

        return redirect()->route('profile.family')->with('success', 'Family member updated successfully!');
    }

    public function destroyFamilyMember(UserFamilyMember $familyMember)
    {
        // Ensure the family member belongs to the authenticated user
        if ($familyMember->user_id !== Auth::id()) {
            abort(403);
        }

        $familyMember->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Family member deleted successfully'
            ]);
        }

        return redirect()->route('profile.family')->with('success', 'Family member deleted successfully!');
    }

    // Contact Management Methods
    public function contacts()
    {
        $user = Auth::user()->load('contacts');
        return view('profile.contacts', compact('user'));
    }

    public function storeContact(Request $request)
    {
        $request->validate([
            'type' => 'required|string|max:50',
            'label' => 'nullable|string|max:255',
            'value' => 'required|string|max:255',
            'is_primary' => 'boolean',
            'is_public' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $contact = Auth::user()->contacts()->create($request->all());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Contact added successfully',
                'contact' => $contact
            ]);
        }

        return redirect()->route('profile.contacts')->with('success', 'Contact added successfully!');
    }

    public function updateContact(Request $request, $contact)
    {
        $contact = Auth::user()->contacts()->findOrFail($contact);

        $request->validate([
            'type' => 'required|string|max:50',
            'label' => 'nullable|string|max:255',
            'value' => 'required|string|max:255',
            'is_primary' => 'boolean',
            'is_public' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $contact->update($request->all());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Contact updated successfully',
                'contact' => $contact
            ]);
        }

        return redirect()->route('profile.contacts')->with('success', 'Contact updated successfully!');
    }

    public function destroyContact($contact)
    {
        $contact = Auth::user()->contacts()->findOrFail($contact);
        $contact->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Contact deleted successfully'
            ]);
        }

        return redirect()->route('profile.contacts')->with('success', 'Contact deleted successfully!');
    }

    // Address Management Methods
    public function addresses()
    {
        $user = Auth::user()->load('addresses');
        return view('profile.addresses', compact('user'));
    }

    public function storeAddress(Request $request)
    {
        $request->validate([
            'type' => 'required|string|max:50',
            'label' => 'nullable|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state_province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'required|string|max:100',
            'is_primary' => 'boolean',
            'is_public' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $address = Auth::user()->addresses()->create($request->all());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Address added successfully',
                'address' => $address
            ]);
        }

        return redirect()->route('profile.addresses')->with('success', 'Address added successfully!');
    }

    public function updateAddress(Request $request, $address)
    {
        $address = Auth::user()->addresses()->findOrFail($address);

        $request->validate([
            'type' => 'required|string|max:50',
            'label' => 'nullable|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state_province' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'required|string|max:100',
            'is_primary' => 'boolean',
            'is_public' => 'boolean',
            'notes' => 'nullable|string|max:1000',
        ]);

        $address->update($request->all());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Address updated successfully',
                'address' => $address
            ]);
        }

        return redirect()->route('profile.addresses')->with('success', 'Address updated successfully!');
    }

    public function destroyAddress($address)
    {
        $address = Auth::user()->addresses()->findOrFail($address);
        $address->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Address deleted successfully'
            ]);
        }

        return redirect()->route('profile.addresses')->with('success', 'Address deleted successfully!');
    }

    // Social Media Management Methods
    public function socialMedia()
    {
        $user = Auth::user()->load(['socialMedia' => function($query) {
            $query->ordered();
        }]);
        return view('profile.social-media', compact('user'));
    }

    public function storeSocialMedia(Request $request)
    {
        $request->validate([
            'platform' => 'required|string|max:50',
            'username' => 'nullable|string|max:255',
            'url' => 'required|url|max:500',
            'display_name' => 'nullable|string|max:255',
            'is_public' => 'boolean',
            'order' => 'integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $socialMedia = Auth::user()->socialMedia()->create($request->all());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Social media link added successfully',
                'social_media' => $socialMedia
            ]);
        }

        return redirect()->route('profile.social-media')->with('success', 'Social media link added successfully!');
    }

    public function updateSocialMedia(Request $request, $socialMedia)
    {
        $socialMedia = Auth::user()->socialMedia()->findOrFail($socialMedia);

        $request->validate([
            'platform' => 'required|string|max:50',
            'username' => 'nullable|string|max:255',
            'url' => 'required|url|max:500',
            'display_name' => 'nullable|string|max:255',
            'is_public' => 'boolean',
            'order' => 'integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $socialMedia->update($request->all());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Social media link updated successfully',
                'social_media' => $socialMedia
            ]);
        }

        return redirect()->route('profile.social-media')->with('success', 'Social media link updated successfully!');
    }

    public function destroySocialMedia($socialMedia)
    {
        $socialMedia = Auth::user()->socialMedia()->findOrFail($socialMedia);
        $socialMedia->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Social media link deleted successfully'
            ]);
        }

        return redirect()->route('profile.social-media')->with('success', 'Social media link deleted successfully!');
    }
}

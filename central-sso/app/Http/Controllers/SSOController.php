<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class SSOController extends Controller
{
    public function showLoginForm($tenant_slug, Request $request)
    {
        $tenant = Tenant::where('id', $tenant_slug)
            ->orWhereJsonContains('data->slug', $tenant_slug)
            ->first();
        
        if (!$tenant) {
            abort(404, 'Tenant not found');
        }
        
        $callback_url = $request->get('callback_url');
        
        return view('auth.sso-login', [
            'tenant' => $tenant,
            'tenant_slug' => $tenant_slug,
            'callback_url' => $callback_url
        ]);
    }
    
    public function handleLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'tenant_slug' => 'required',
            'callback_url' => 'required|url'
        ]);
        
        $user = User::where('email', $request->email)->with('tenants')->first();
        
        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors(['email' => 'Invalid credentials'])->withInput();
        }
        
        if (!$user->hasAccessToTenant($request->tenant_slug)) {
            return back()->withErrors(['email' => 'Access denied to this tenant'])->withInput();
        }
        
        try {
            $customClaims = [
                'tenants' => $user->tenants->map(function($tenant) { return $tenant->slug; })->toArray(),
                'current_tenant' => $request->tenant_slug,
            ];
            
            $token = JWTAuth::customClaims($customClaims)->fromUser($user);
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Could not create authentication token'])->withInput();
        }
        
        // Redirect back to tenant app with token
        return redirect($request->callback_url . '?token=' . $token);
    }
}
@extends('layouts.app')

@section('styles')
<style>
    .auth-container {
        max-width: 400px;
        margin: 4rem auto;
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    }
    .auth-title {
        text-align: center;
        margin-bottom: 2rem;
        color: #333;
    }
    .form-group {
        margin-bottom: 1.5rem;
    }
    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        color: #555;
        font-weight: 500;
    }
    .form-input {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }
    .form-input:focus {
        outline: none;
        border-color: #667eea;
    }
    .form-error {
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
    .btn {
        width: 100%;
        padding: 0.75rem;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 5px;
        font-size: 1rem;
        cursor: pointer;
        transition: background 0.3s;
    }
    .btn:hover {
        background: #5a67d8;
    }
    .auth-links {
        text-align: center;
        margin-top: 1.5rem;
    }
    .auth-links a {
        color: #667eea;
        text-decoration: none;
    }
    .auth-links a:hover {
        text-decoration: underline;
    }
    .tenant-info {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 5px;
        margin-bottom: 1.5rem;
        text-align: center;
        color: #666;
        font-size: 0.9rem;
    }
</style>
@endsection

@section('content')
<div class="auth-container">
    <h2 class="auth-title">Register for {{ config('app.name') }}</h2>
    
    <div class="tenant-info">
        You are registering for <strong>{{ config('app.name') }}</strong> (Tenant: {{ env('TENANT_SLUG') }})
    </div>
    
    <form method="POST" action="{{ route('register') }}">
        @csrf
        
        <div class="form-group">
            <label for="name" class="form-label">Full Name</label>
            <input type="text" id="name" name="name" class="form-input" value="{{ old('name') }}" required autofocus>
            @error('name')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>
        
        <div class="form-group">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" id="email" name="email" class="form-input" value="{{ old('email') }}" required>
            @error('email')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>
        
        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <input type="password" id="password" name="password" class="form-input" required>
            @error('password')
                <div class="form-error">{{ $message }}</div>
            @enderror
        </div>
        
        <div class="form-group">
            <label for="password_confirmation" class="form-label">Confirm Password</label>
            <input type="password" id="password_confirmation" name="password_confirmation" class="form-input" required>
        </div>
        
        <button type="submit" class="btn">Register</button>
    </form>
    
    <div class="auth-links">
        <p>Already have an account? <a href="{{ route('login') }}">Login here</a></p>
    </div>
</div>
@endsection
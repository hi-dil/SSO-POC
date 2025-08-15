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
    .btn-sso {
        background: #764ba2;
        margin-bottom: 1rem;
    }
    .btn-sso:hover {
        background: #6b4194;
    }
    .divider {
        text-align: center;
        margin: 1.5rem 0;
        position: relative;
    }
    .divider::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
        background: #ddd;
    }
    .divider span {
        background: white;
        padding: 0 1rem;
        position: relative;
        color: #999;
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
</style>
@endsection

@section('content')
<div class="auth-container">
    <h2 class="auth-title">Login to {{ config('app.name') }}</h2>
    
    <a href="{{ route('sso.redirect') }}" class="btn btn-sso">Login with Central SSO</a>
    
    <div class="divider">
        <span>OR</span>
    </div>
    
    <form method="POST" action="{{ route('login') }}">
        @csrf
        
        <div class="form-group">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" id="email" name="email" class="form-input" value="{{ old('email') }}" required autofocus>
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
        
        <button type="submit" class="btn">Login</button>
    </form>
    
    <div class="auth-links">
        <p>Don't have an account? <a href="{{ route('register') }}">Register here</a></p>
    </div>
</div>
@endsection
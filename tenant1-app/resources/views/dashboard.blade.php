@extends('layouts.app')

@section('styles')
<style>
    .dashboard-container {
        background: white;
        padding: 2rem;
        border-radius: 10px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    }
    .dashboard-title {
        color: #333;
        margin-bottom: 2rem;
    }
    .user-info {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 5px;
        margin-bottom: 2rem;
    }
    .info-row {
        display: flex;
        margin-bottom: 1rem;
    }
    .info-label {
        font-weight: bold;
        color: #555;
        min-width: 150px;
    }
    .info-value {
        color: #333;
    }
    .tenant-list {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .tenant-badge {
        background: #667eea;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        font-size: 0.875rem;
    }
    .current-tenant {
        background: #764ba2;
    }
    .features-section {
        margin-top: 2rem;
        padding-top: 2rem;
        border-top: 1px solid #e9ecef;
    }
    .features-title {
        color: #333;
        margin-bottom: 1rem;
    }
    .feature-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }
    .feature-card {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 5px;
        border: 1px solid #e9ecef;
    }
    .feature-card h3 {
        color: #667eea;
        margin-bottom: 0.5rem;
    }
    .feature-card p {
        color: #666;
        line-height: 1.5;
    }
</style>
@endsection

@section('content')
<div class="dashboard-container">
    <h1 class="dashboard-title">Welcome to {{ config('app.name') }} Dashboard</h1>
    
    <div class="user-info">
        <h2>User Information</h2>
        <div class="info-row">
            <span class="info-label">Name:</span>
            <span class="info-value">{{ $user['name'] ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Email:</span>
            <span class="info-value">{{ $user['email'] ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">User ID:</span>
            <span class="info-value">{{ $user['id'] ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Current Tenant:</span>
            <span class="info-value">{{ $user['current_tenant'] ?? env('TENANT_SLUG') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Available Tenants:</span>
            <div class="tenant-list">
                @if(isset($user['tenants']) && is_array($user['tenants']))
                    @foreach($user['tenants'] as $tenant)
                        <span class="tenant-badge {{ $tenant == ($user['current_tenant'] ?? env('TENANT_SLUG')) ? 'current-tenant' : '' }}">
                            {{ $tenant }}
                        </span>
                    @endforeach
                @else
                    <span class="tenant-badge current-tenant">{{ env('TENANT_SLUG') }}</span>
                @endif
            </div>
        </div>
    </div>
    
    <div class="features-section">
        <h2 class="features-title">Available Features</h2>
        <div class="feature-grid">
            <div class="feature-card">
                <h3>SSO Authentication</h3>
                <p>You've successfully authenticated using our Single Sign-On system. You can access multiple tenants with a single login.</p>
            </div>
            <div class="feature-card">
                <h3>Multi-Tenant Access</h3>
                <p>Your account has access to {{ count($user['tenants'] ?? []) }} tenant(s). Switch between them seamlessly.</p>
            </div>
            <div class="feature-card">
                <h3>Secure JWT Tokens</h3>
                <p>Your session is protected with JWT tokens that expire after {{ env('JWT_TTL', 60) }} minutes for enhanced security.</p>
            </div>
            <div class="feature-card">
                <h3>Tenant Isolation</h3>
                <p>Each tenant has its own isolated database and configuration, ensuring complete data separation.</p>
            </div>
        </div>
    </div>
</div>
@endsection
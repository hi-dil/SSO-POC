@extends('layouts.auth')

@section('title', 'Register')
@section('subtitle', 'Create your account to get started')
@section('header', 'Create Account')

@section('content')
                <!-- Tenant Info -->
                <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 px-4 py-3 rounded-lg">
                    <div class="text-sm text-blue-800 dark:text-blue-200 text-center">
                        You are registering for <span class="font-semibold">{{ config('app.name') }}</span>
                        <br><span class="text-xs text-blue-600 dark:text-blue-300">(Tenant: {{ env('TENANT_SLUG') }})</span>
                    </div>
                </div>

                <!-- Registration Form -->
                <form class="space-y-6" action="{{ route('register') }}" method="POST">
                    @csrf
                    
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <label for="name" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-gray-700 dark:text-gray-200">
                                Full Name
                            </label>
                            <input id="name" name="name" type="text" autocomplete="name" required 
                                   class="flex h-10 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-gray-500 dark:placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 transition-colors duration-200" 
                                   placeholder="Enter your full name" value="{{ old('name') }}">
                            @error('name')
                                <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="space-y-2">
                            <label for="email" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-gray-700 dark:text-gray-200">
                                Email address
                            </label>
                            <input id="email" name="email" type="email" autocomplete="email" required 
                                   class="flex h-10 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-gray-500 dark:placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 transition-colors duration-200" 
                                   placeholder="Enter your email" value="{{ old('email') }}">
                            @error('email')
                                <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="space-y-2">
                            <label for="password" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-gray-700 dark:text-gray-200">
                                Password
                            </label>
                            <input id="password" name="password" type="password" autocomplete="new-password" required 
                                   class="flex h-10 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-gray-500 dark:placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 transition-colors duration-200" 
                                   placeholder="Enter your password">
                            @error('password')
                                <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="space-y-2">
                            <label for="password_confirmation" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-gray-700 dark:text-gray-200">
                                Confirm Password
                            </label>
                            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required 
                                   class="flex h-10 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-gray-500 dark:placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 transition-colors duration-200" 
                                   placeholder="Confirm your password">
                        </div>
                    </div>
                    
                    <div>
                        <button type="submit" 
                                class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-gradient-to-r from-teal-custom to-teal-custom-light text-white hover:from-teal-600 hover:to-cyan-600 h-10 px-4 py-2 w-full">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                            </svg>
                            Create Account
                        </button>
                    </div>
                </form>
@endsection

@section('bottom-links')
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Already have an account? 
                    <a href="{{ route('login') }}" class="font-medium text-teal-600 dark:text-teal-400 hover:text-teal-500 dark:hover:text-teal-300 transition-colors">
                        Login here
                    </a>
                </div>
@endsection
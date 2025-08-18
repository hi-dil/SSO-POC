@extends('layouts.auth')

@section('title', 'Login')
@section('subtitle', 'Sign in to access your applications')
@section('header', 'Sign In')

@section('content')
                <!-- SSO Login Button -->
                <div class="mb-6">
                    <a href="{{ route('sso.redirect') }}" 
                       class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-gradient-to-r from-teal-custom to-teal-custom-light text-white hover:from-teal-600 hover:to-cyan-600 h-10 px-4 py-2 w-full">
                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        Login with Central SSO
                    </a>
                </div>

                <!-- Divider -->
                <div class="relative mb-6">
                    <div class="absolute inset-0 flex items-center">
                        <span class="w-full border-t border-gray-300 dark:border-gray-600"></span>
                    </div>
                    <div class="relative flex justify-center text-xs uppercase">
                        <span class="bg-white dark:bg-gray-800 px-2 text-gray-500 dark:text-gray-400">Or continue with</span>
                    </div>
                </div>

                <!-- Direct Login Form -->
                <form class="space-y-6" action="{{ route('login') }}" method="POST">
                    @csrf
                    
                    <div class="space-y-4">
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
                            <input id="password" name="password" type="password" autocomplete="current-password" required 
                                   class="flex h-10 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-gray-500 dark:placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 transition-colors duration-200" 
                                   placeholder="Enter your password">
                            @error('password')
                                <div class="text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    
                    <div>
                        <button type="submit" 
                                class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-gradient-to-r from-teal-custom to-teal-custom-light text-white hover:from-teal-600 hover:to-cyan-600 h-10 px-4 py-2 w-full">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                            Sign In
                        </button>
                    </div>
                </form>
@endsection

@section('bottom-links')
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Don't have an account? 
                    <a href="{{ route('register') }}" class="font-medium text-teal-600 dark:text-teal-400 hover:text-teal-500 dark:hover:text-teal-300 transition-colors">
                        Register here
                    </a>
                </div>
@endsection
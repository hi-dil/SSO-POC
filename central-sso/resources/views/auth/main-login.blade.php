@extends('layouts.auth')

@section('title', 'Login')
@section('subtitle', 'Sign in to access your applications')
@section('header', 'Sign In')

@section('content')
                <form class="space-y-6" action="{{ route('main.login.submit') }}" method="POST">
                    @csrf
                    
                    <div class="space-y-4">
                        <div class="space-y-2">
                            <label for="email" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-gray-700 dark:text-gray-200">
                                Email address
                            </label>
                            <input id="email" name="email" type="email" autocomplete="email" required 
                                   class="flex h-10 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-gray-500 dark:placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 transition-colors duration-200" 
                                   placeholder="Enter your email" value="{{ old('email') }}">
                        </div>
                        <div class="space-y-2">
                            <label for="password" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70 text-gray-700 dark:text-gray-200">
                                Password
                            </label>
                            <input id="password" name="password" type="password" autocomplete="current-password" required 
                                   class="flex h-10 w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-gray-500 dark:placeholder:text-gray-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 transition-colors duration-200" 
                                   placeholder="Enter your password">
                        </div>
                    </div>
                    
                    <div>
                        <button type="submit" 
                                class="inline-flex items-center justify-center rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-gradient-to-r from-teal-custom to-teal-custom-light text-white hover:from-teal-600 hover:to-cyan-600 h-10 px-4 py-2 w-full">
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                            </svg>
                            Sign in to Central SSO
                        </button>
                    </div>
                </form>
@endsection

@section('footer')
                <div class="space-y-3 text-xs">
                    <div class="bg-teal-50 dark:bg-teal-900/20 p-3 rounded border border-teal-200 dark:border-teal-700">
                        <p class="font-semibold text-gray-700 dark:text-gray-200 mb-1">Super Admin:</p>
                        <p class="text-gray-600 dark:text-gray-300">superadmin@sso.com / password</p>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded border border-gray-200 dark:border-gray-600">
                        <p class="font-semibold text-gray-700 dark:text-gray-200 mb-1">Test Users:</p>
                        <p class="text-gray-600 dark:text-gray-300">admin@tenant1.com, user@tenant2.com, etc. / password</p>
                    </div>
                </div>
@endsection
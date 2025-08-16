@extends('layouts.auth')

@section('title', 'Login')
@section('subtitle', 'Sign in to access your applications')
@section('header', 'Sign In')

@section('content')
                <form class="space-y-6" action="{{ route('main.login.submit') }}" method="POST">
                    @csrf
                    
                    <div class="space-y-4">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">
                                Email address
                            </label>
                            <input id="email" name="email" type="email" autocomplete="email" required 
                                   class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                                   placeholder="Enter your email" value="{{ old('email') }}">
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">
                                Password
                            </label>
                            <input id="password" name="password" type="password" autocomplete="current-password" required 
                                   class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                                   placeholder="Enter your password">
                        </div>
                    </div>
                    
                    <div>
                        <button type="submit" 
                                class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-blue-300 group-hover:text-blue-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            Sign in to Central SSO
                        </button>
                    </div>
                </form>
@endsection

@section('footer')
                <div class="space-y-3 text-xs">
                    <div class="bg-blue-50 p-3 rounded">
                        <p class="font-semibold text-gray-700 mb-1">Super Admin:</p>
                        <p class="text-gray-600">superadmin@sso.com / password</p>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <p class="font-semibold text-gray-700 mb-1">Test Users:</p>
                        <p class="text-gray-600">admin@tenant1.com, user@tenant2.com, etc. / password</p>
                    </div>
                </div>
@endsection
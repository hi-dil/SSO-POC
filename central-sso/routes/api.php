<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\UserRoleController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/validate', [AuthController::class, 'validateToken']);
    
    Route::middleware('auth:api')->group(function () {
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware(['auth:api,web'])->group(function () {
    // Role management routes - allow super admins full access for UI
    Route::get('roles', [RoleController::class, 'index']);
    Route::get('roles/{role}', [RoleController::class, 'show']);
    Route::post('roles', [RoleController::class, 'store']);
    Route::put('roles/{role}', [RoleController::class, 'update']);
    Route::patch('roles/{role}', [RoleController::class, 'update']);
    Route::delete('roles/{role}', [RoleController::class, 'destroy']);
    
    // Permission management routes
    Route::get('permissions/categories', [PermissionController::class, 'categories']);
    Route::get('permissions', [PermissionController::class, 'index']);
    Route::get('permissions/{permission}', [PermissionController::class, 'show']);
    Route::post('permissions', [PermissionController::class, 'store']);
    Route::put('permissions/{permission}', [PermissionController::class, 'update']);
    Route::patch('permissions/{permission}', [PermissionController::class, 'update']);
    Route::delete('permissions/{permission}', [PermissionController::class, 'destroy']);
    
    // User role assignment routes
    Route::prefix('users/{userId}')->group(function () {
        Route::get('roles', [UserRoleController::class, 'getUserRoles']);
        Route::get('permissions', [UserRoleController::class, 'getUserPermissions']);
        Route::post('roles', [UserRoleController::class, 'assignRole']);
        Route::delete('roles', [UserRoleController::class, 'removeRole']);
        Route::put('roles/sync', [UserRoleController::class, 'syncRoles']);
    });
});
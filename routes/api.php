<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\OAuthController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\AdminLeaveController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Auth Routes
Route::prefix('auth')->group(function () {
    // Conventional Auth
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    
    // OAuth
    Route::get('google', [OAuthController::class, 'redirectToGoogle']);
    Route::get('google/callback', [OAuthController::class, 'handleGoogleCallback']);
    
    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Employee Routes
    Route::middleware('role:employee')->prefix('employee')->group(function () {
        Route::get('leave-requests', [LeaveRequestController::class, 'index']);
        Route::post('leave-requests', [LeaveRequestController::class, 'store']);
        Route::get('leave-requests/{id}', [LeaveRequestController::class, 'show']);
        Route::delete('leave-requests/{id}', [LeaveRequestController::class, 'destroy']);
        Route::get('leave-statistics', [LeaveRequestController::class, 'statistics']);
    });
    
    // Admin Routes
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('leave-requests', [AdminLeaveController::class, 'index']);
        Route::get('leave-requests/{id}', [AdminLeaveController::class, 'show']);
        Route::patch('leave-requests/{id}/approve', [AdminLeaveController::class, 'approve']);
        Route::patch('leave-requests/{id}/reject', [AdminLeaveController::class, 'reject']);
        Route::get('dashboard', [AdminLeaveController::class, 'dashboard']);
    });
});

Route::get('/test', function () {
    return response()->json(['message' => 'API Working!']);
});
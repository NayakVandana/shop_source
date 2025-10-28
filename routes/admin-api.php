<?php

use App\Http\Controllers\admin\AuthController;
use App\Http\Controllers\admin\DashboardController;
use Illuminate\Support\Facades\Route;

    
    // Admin Authentication
    Route::post('/login', [AuthController::class, 'adminLogin']);

    // Admin Routes (Protected)
    Route::middleware(['user.verify', 'admin.verify', 'uuid.validate'])->group(function () {
        
        // Authentication
        Route::post('/logout', [AuthController::class, 'adminLogout']);
        Route::post('/profile', [AuthController::class, 'adminProfile']);
        
        // Dashboard
        Route::post('/dashboard/stats', [DashboardController::class, 'stats']);
        
    });


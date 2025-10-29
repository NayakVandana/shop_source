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
        
        // Products
        Route::post('/products/index', [\App\Http\Controllers\admin\ProductController::class, 'index']);
        Route::post('/products/store', [\App\Http\Controllers\admin\ProductController::class, 'store']);
        Route::post('/products/show', [\App\Http\Controllers\admin\ProductController::class, 'show']);
        Route::post('/products/update', [\App\Http\Controllers\admin\ProductController::class, 'update']);
        Route::post('/products/destroy', [\App\Http\Controllers\admin\ProductController::class, 'destroy']);
        
        // Categories (for product form dropdowns)
        Route::post('/categories/list', [\App\Http\Controllers\Api\CategoryController::class, 'index']);
        
    });


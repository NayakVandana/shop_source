<?php

use App\Http\Controllers\auth\AuthController;
use App\Http\Controllers\user\CartController;
use App\Http\Controllers\user\OrderController;
use Illuminate\Support\Facades\Route;


    
    // Public Routes
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    // Cart Routes (Public - works with session for guests)
    Route::middleware([\Illuminate\Session\Middleware\StartSession::class])->group(function () {
        Route::post('/cart/index', [CartController::class, 'index']);
        Route::post('/cart/add', [CartController::class, 'add']);
        Route::post('/cart/update', [CartController::class, 'update']);
        Route::post('/cart/remove', [CartController::class, 'remove']);
        Route::post('/cart/clear', [CartController::class, 'clear']);
    });

    // Product Routes (Public - alias for /api/products/index)
    Route::post('/products/list', [\App\Http\Controllers\Api\ProductController::class, 'index']);

    // Protected User Routes
    Route::middleware(['user.verify', 'uuid.validate'])->group(function () {
        
        // Authentication
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/profile', [AuthController::class, 'profile']);
        Route::post('/profile/update', [AuthController::class, 'updateProfile']);
        
        // Orders
        Route::post('/orders/index', [OrderController::class, 'index']);
        Route::post('/orders/store', [OrderController::class, 'store']);
        Route::post('/orders/show', [OrderController::class, 'show']);
        
    });


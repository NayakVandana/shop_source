<?php

use App\Http\Controllers\auth\AuthController;
use App\Http\Controllers\user\ProductController as UserProductController;
use Illuminate\Support\Facades\Route;

// Public Routes - All POST methods
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Public Product Routes
Route::post('/products/list', [UserProductController::class, 'index']);
Route::post('/products/show', [UserProductController::class, 'show']);
Route::post('/products/featured', [UserProductController::class, 'featured']);
Route::post('/products/related', [UserProductController::class, 'related']);

// Protected User Routes - All POST methods
Route::middleware(['user.verify', 'uuid.validate'])->group(function () {
    
    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/profile', [AuthController::class, 'profile']);
    
});

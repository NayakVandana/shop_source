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
        
        // Categories
        Route::post('/categories/index', [\App\Http\Controllers\admin\CategoryController::class, 'index']);
        Route::post('/categories/store', [\App\Http\Controllers\admin\CategoryController::class, 'store']);
        Route::post('/categories/show', [\App\Http\Controllers\admin\CategoryController::class, 'show']);
        Route::post('/categories/update', [\App\Http\Controllers\admin\CategoryController::class, 'update']);
        Route::post('/categories/destroy', [\App\Http\Controllers\admin\CategoryController::class, 'destroy']);
        
        // Categories (for product form dropdowns - keep for backward compatibility)
        Route::post('/categories/list', [\App\Http\Controllers\Api\CategoryController::class, 'index']);
        
        // Permissions - All operations save to user_permissions table
        Route::post('/permissions/roles', [\App\Http\Controllers\admin\PermissionController::class, 'roles']);
        Route::post('/permissions/grouped-by-role', [\App\Http\Controllers\admin\PermissionController::class, 'groupedByRole']);
        Route::post('/permissions/bulk-create', [\App\Http\Controllers\admin\PermissionController::class, 'createBulk']);
        Route::post('/permissions/update-roles', [\App\Http\Controllers\admin\PermissionController::class, 'updateRoles']);
        Route::post('/permissions/destroy', [\App\Http\Controllers\admin\PermissionController::class, 'destroy']);
        
        // Users
        Route::post('/users/index', [\App\Http\Controllers\admin\UserController::class, 'index']);
        Route::post('/users/show', [\App\Http\Controllers\admin\UserController::class, 'show']);
        Route::post('/users/update', [\App\Http\Controllers\admin\UserController::class, 'update']);
        Route::post('/users/destroy', [\App\Http\Controllers\admin\UserController::class, 'destroy']);
        
        // Discounts
        Route::post('/discounts/index', [\App\Http\Controllers\admin\DiscountController::class, 'index']);
        Route::post('/discounts/store', [\App\Http\Controllers\admin\DiscountController::class, 'store']);
        Route::post('/discounts/show', [\App\Http\Controllers\admin\DiscountController::class, 'show']);
        Route::post('/discounts/update', [\App\Http\Controllers\admin\DiscountController::class, 'update']);
        Route::post('/discounts/destroy', [\App\Http\Controllers\admin\DiscountController::class, 'destroy']);
        
        // Coupon Codes
        Route::post('/coupon-codes/index', [\App\Http\Controllers\admin\CouponCodeController::class, 'index']);
        Route::post('/coupon-codes/store', [\App\Http\Controllers\admin\CouponCodeController::class, 'store']);
        Route::post('/coupon-codes/show', [\App\Http\Controllers\admin\CouponCodeController::class, 'show']);
        Route::post('/coupon-codes/update', [\App\Http\Controllers\admin\CouponCodeController::class, 'update']);
        Route::post('/coupon-codes/destroy', [\App\Http\Controllers\admin\CouponCodeController::class, 'destroy']);
        
        // Orders
        Route::post('/orders/index', [\App\Http\Controllers\admin\OrderController::class, 'index']);
        Route::post('/orders/show', [\App\Http\Controllers\admin\OrderController::class, 'show']);
        Route::post('/orders/update-status', [\App\Http\Controllers\admin\OrderController::class, 'updateStatus']);
        Route::post('/orders/update', [\App\Http\Controllers\admin\OrderController::class, 'update']);
    });


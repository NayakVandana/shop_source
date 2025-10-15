<?php

use App\Http\Controllers\auth\AuthController;
use App\Http\Controllers\user\ProductController as UserProductController;
use App\Http\Controllers\user\CategoryController as UserCategoryController;
use App\Http\Controllers\user\CartController;
use App\Http\Controllers\user\OrderController as UserOrderController;
use App\Http\Controllers\user\DiscountController as UserDiscountController;
use App\Http\Controllers\user\CouponController as UserCouponController;
use App\Http\Controllers\user\LocationController as UserLocationController;
use Illuminate\Support\Facades\Route;

// Public Routes - All POST methods
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Public Product & Category Routes
Route::post('/products/list', [UserProductController::class, 'index']);
Route::post('/products/show', [UserProductController::class, 'show']);
Route::post('/products/featured', [UserProductController::class, 'featured']);
Route::post('/products/related', [UserProductController::class, 'related']);

Route::post('/categories/list', [UserCategoryController::class, 'index']);
Route::post('/categories/show', [UserCategoryController::class, 'show']);
Route::post('/categories/products', [UserCategoryController::class, 'products']);

Route::post('/discounts/list', [UserDiscountController::class, 'index']);
Route::post('/discounts/validate', [UserDiscountController::class, 'validateCode']);

// Coupon routes
Route::post('/coupons/list', [UserCouponController::class, 'index']);
Route::post('/coupons/validate', [UserCouponController::class, 'validateCode']);
Route::post('/coupons/validate-cart', [UserCouponController::class, 'validateCartCoupon']);
Route::post('/coupons/applicable', [UserCouponController::class, 'getApplicableCoupons']);

// Location routes
Route::post('/locations/list', [UserLocationController::class, 'index']);
Route::post('/locations/find-nearest', [UserLocationController::class, 'findNearest']);
Route::post('/locations/check-delivery', [UserLocationController::class, 'checkDelivery']);

// Protected User Routes - All POST methods
Route::middleware(['user.verify', 'uuid.validate'])->group(function () {
    
    // Authentication
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/profile', [AuthController::class, 'profile']);

    // Cart Management
    Route::post('/cart/list', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'store']);
    Route::post('/cart/update', [CartController::class, 'update']);
    Route::post('/cart/remove', [CartController::class, 'destroy']);
    Route::post('/cart/clear', [CartController::class, 'clear']);
    Route::post('/cart/count', [CartController::class, 'count']);

    // Order Management
    Route::post('/orders/list', [UserOrderController::class, 'index']);
    Route::post('/orders/show', [UserOrderController::class, 'show']);
    Route::post('/orders/create', [UserOrderController::class, 'store']);
    Route::post('/orders/cancel', [UserOrderController::class, 'cancelOrder']);
    Route::post('/orders/timeline', [UserOrderController::class, 'getOrderTimeline']);
    Route::post('/orders/track', [UserOrderController::class, 'trackOrder']);
    Route::post('/orders/stats', [UserOrderController::class, 'getOrderStats']);

    // Return Management
    Route::post('/returns/request', [UserOrderController::class, 'requestReturn']);
    Route::post('/returns/list', [UserOrderController::class, 'getReturns']);
    Route::post('/returns/reasons', [UserOrderController::class, 'getReturnReasons']);
});

<?php

use App\Http\Controllers\auth\AuthController;
use App\Http\Controllers\admin\AuthController as AdminAuthController;
use App\Http\Controllers\admin\ProductController as AdminProductController;
use App\Http\Controllers\admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\admin\OrderController as AdminOrderController;
use App\Http\Controllers\admin\DiscountController as AdminDiscountController;
use App\Http\Controllers\admin\CouponController as AdminCouponController;
use App\Http\Controllers\admin\LocationController as AdminLocationController;
use App\Http\Controllers\admin\DeliveryScheduleController as AdminDeliveryScheduleController;
use App\Http\Controllers\admin\AdminRoleController as AdminRoleController;
use App\Http\Controllers\admin\UserController as AdminUserController;
use Illuminate\Support\Facades\Route;

// Admin Authentication
Route::post('/admin-login', [AdminAuthController::class, 'adminLogin']);

// Admin Routes (Protected) - All POST methods
Route::middleware(['user.verify', 'admin.verify', 'uuid.validate'])->group(function () {
    
    // Dashboard & Statistics
    Route::post('/dashboard/stats', function() {
        return response()->json([
            'success' => true,
            'message' => 'Dashboard statistics',
            'data' => [
                'products' => \App\Models\Product::count(),
                'orders' => \App\Models\Order::count(),
                'users' => \App\Models\User::count(),
                'categories' => \App\Models\Category::count(),
            ]
        ]);
    });

    // Product Management
    Route::post('/products/list', [AdminProductController::class, 'index']);
    Route::post('/products/create', [AdminProductController::class, 'store']);
    Route::post('/products/show', [AdminProductController::class, 'show']);
    Route::post('/products/update', [AdminProductController::class, 'update']);
    Route::post('/products/delete', [AdminProductController::class, 'destroy']);
    Route::post('/products/toggle-status', [AdminProductController::class, 'toggleStatus']);
    Route::post('/products/update-stock', [AdminProductController::class, 'updateStock']);

    // Category Management
    Route::post('/categories/list', [AdminCategoryController::class, 'index']);
    Route::post('/categories/create', [AdminCategoryController::class, 'store']);
    Route::post('/categories/show', [AdminCategoryController::class, 'show']);
    Route::post('/categories/update', [AdminCategoryController::class, 'update']);
    Route::post('/categories/delete', [AdminCategoryController::class, 'destroy']);
    Route::post('/categories/toggle-status', [AdminCategoryController::class, 'toggleStatus']);

    // Order Management
    Route::post('/orders/list', [AdminOrderController::class, 'index']);
    Route::post('/orders/show', [AdminOrderController::class, 'show']);
    Route::post('/orders/delete', [AdminOrderController::class, 'destroy']);
    Route::post('/orders/update-status', [AdminOrderController::class, 'updateStatus']);
    Route::post('/orders/update-payment-status', [AdminOrderController::class, 'updatePaymentStatus']);
    Route::post('/orders/ship', [AdminOrderController::class, 'shipOrder']);
    Route::post('/orders/deliver', [AdminOrderController::class, 'deliverOrder']);
    Route::post('/orders/cancel', [AdminOrderController::class, 'cancelOrder']);
    Route::post('/orders/timeline', [AdminOrderController::class, 'getOrderTimeline']);
    Route::post('/orders/stats', [AdminOrderController::class, 'getOrderStats']);

    // Return Management
    Route::post('/returns/list', [AdminOrderController::class, 'getReturns']);
    Route::post('/returns/process', [AdminOrderController::class, 'processReturn']);
    Route::post('/returns/stats', [AdminOrderController::class, 'getReturnStats']);

    // Discount Management
    Route::post('/discounts/list', [AdminDiscountController::class, 'index']);
    Route::post('/discounts/create', [AdminDiscountController::class, 'store']);
    Route::post('/discounts/show', [AdminDiscountController::class, 'show']);
    Route::post('/discounts/update', [AdminDiscountController::class, 'update']);
    Route::post('/discounts/delete', [AdminDiscountController::class, 'destroy']);
    Route::post('/discounts/toggle-status', [AdminDiscountController::class, 'toggleStatus']);
    Route::post('/discounts/validate', [AdminDiscountController::class, 'validateCode']);

    // Coupon Management
    Route::post('/coupons/list', [AdminCouponController::class, 'index']);
    Route::post('/coupons/create', [AdminCouponController::class, 'store']);
    Route::post('/coupons/show', [AdminCouponController::class, 'show']);
    Route::post('/coupons/update', [AdminCouponController::class, 'update']);
    Route::post('/coupons/delete', [AdminCouponController::class, 'destroy']);
    Route::post('/coupons/toggle-status', [AdminCouponController::class, 'toggleStatus']);
    Route::post('/coupons/validate', [AdminCouponController::class, 'validateCode']);
    Route::post('/coupons/stats', [AdminCouponController::class, 'getStats']);
    Route::post('/coupons/generate-code', [AdminCouponController::class, 'generateCode']);

    // Delivery Location Management
    Route::post('/locations/list', [AdminLocationController::class, 'index']);
    Route::post('/locations/create', [AdminLocationController::class, 'store']);
    Route::post('/locations/show', [AdminLocationController::class, 'show']);
    Route::post('/locations/update', [AdminLocationController::class, 'update']);
    Route::post('/locations/delete', [AdminLocationController::class, 'destroy']);
    Route::post('/locations/toggle-status', [AdminLocationController::class, 'toggleStatus']);
    Route::post('/locations/assign-products', [AdminLocationController::class, 'assignProducts']);
    Route::post('/locations/remove-products', [AdminLocationController::class, 'removeProducts']);
    Route::post('/locations/stats', [AdminLocationController::class, 'getStats']);
    Route::post('/locations/find-nearest', [AdminLocationController::class, 'findNearest']);

    // Product Delivery Location Management
    Route::post('/products/assign-delivery-locations', [AdminProductController::class, 'assignDeliveryLocations']);
    Route::post('/products/remove-delivery-locations', [AdminProductController::class, 'removeDeliveryLocations']);
    Route::post('/products/delivery-locations', [AdminProductController::class, 'getDeliveryLocations']);
    Route::post('/products/cancel-delivery', [AdminProductController::class, 'cancelDelivery']);
    Route::post('/products/restore-delivery', [AdminProductController::class, 'restoreDelivery']);
    Route::post('/products/cancelled-deliveries', [AdminProductController::class, 'getCancelledDeliveries']);
    Route::post('/products/active-deliveries', [AdminProductController::class, 'getActiveDeliveries']);

    // Delivery Issue Management
    Route::post('/delivery-issues/report', [AdminProductController::class, 'reportDeliveryIssue']);
    Route::post('/delivery-issues/list', [AdminProductController::class, 'getDeliveryIssues']);
    Route::post('/delivery-issues/resolve', [AdminProductController::class, 'resolveDeliveryIssue']);
    Route::post('/delivery-issues/cancel', [AdminProductController::class, 'cancelDeliveryIssue']);
    Route::post('/delivery-issues/stats', [AdminProductController::class, 'getDeliveryIssueStats']);

    // Delivery Schedule Management
    Route::post('/delivery-schedules/list', [AdminDeliveryScheduleController::class, 'index']);
    Route::post('/delivery-schedules/create', [AdminDeliveryScheduleController::class, 'store']);
    Route::post('/delivery-schedules/show', [AdminDeliveryScheduleController::class, 'show']);
    Route::post('/delivery-schedules/update', [AdminDeliveryScheduleController::class, 'update']);
    Route::post('/delivery-schedules/delete', [AdminDeliveryScheduleController::class, 'destroy']);
    Route::post('/delivery-schedules/toggle-availability', [AdminDeliveryScheduleController::class, 'toggleAvailability']);
    Route::post('/delivery-schedules/time-slots', [AdminDeliveryScheduleController::class, 'getAvailableTimeSlots']);
    Route::post('/delivery-schedules/book-slot', [AdminDeliveryScheduleController::class, 'bookTimeSlot']);
    Route::post('/delivery-schedules/release-slot', [AdminDeliveryScheduleController::class, 'releaseTimeSlot']);
    Route::post('/delivery-schedules/bulk-create', [AdminDeliveryScheduleController::class, 'createBulkSchedules']);
    Route::post('/delivery-schedules/stats', [AdminDeliveryScheduleController::class, 'getStats']);

    // Admin Role Management
    Route::post('/admin-roles/list', [AdminRoleController::class, 'index']);
    Route::post('/admin-roles/create', [AdminRoleController::class, 'store']);
    Route::post('/admin-roles/show', [AdminRoleController::class, 'show']);
    Route::post('/admin-roles/update', [AdminRoleController::class, 'update']);
    Route::post('/admin-roles/delete', [AdminRoleController::class, 'destroy']);
    Route::post('/admin-roles/permissions', [AdminRoleController::class, 'getPermissions']);
    Route::post('/admin-roles/assign-role', [AdminRoleController::class, 'assignRole']);
    Route::post('/admin-roles/remove-role', [AdminRoleController::class, 'removeRole']);
    Route::post('/admin-roles/stats', [AdminRoleController::class, 'getStats']);

    // User Management
    Route::post('/users/list', [AdminUserController::class, 'index']);
    Route::post('/users/create', [AdminUserController::class, 'store']);
    Route::post('/users/show', [AdminUserController::class, 'show']);
    Route::post('/users/update', [AdminUserController::class, 'update']);
    Route::post('/users/delete', [AdminUserController::class, 'destroy']);
    Route::post('/users/toggle-status', [AdminUserController::class, 'toggleStatus']);
    Route::post('/users/stats', [AdminUserController::class, 'getUserStats']);
});
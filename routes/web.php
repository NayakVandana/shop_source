<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ============================================
// WEB ROUTES - Page rendering (GET views only)
// No business logic - views only
// ============================================

// Home & Public Pages (Guest)
Route::get('/', function () {
    return Inertia::render('ui/home/Home');
})->name('home');

Route::get('/products', function () {
    return Inertia::render('ui/product/Products');
})->name('products');

Route::get('/product', function () {
    return Inertia::render('ui/product/ProductDetail');
})->name('product');

Route::get('/cart', function () {
    return Inertia::render('ui/cart/Cart');
})->name('cart');

Route::get('/checkout', function () {
    return Inertia::render('ui/checkout/Checkout');
})->name('checkout');

Route::get('/order-confirmation', function () {
    return Inertia::render('ui/checkout/OrderConfirmation');
})->name('order-confirmation');

// Authentication Pages (Guest)
Route::get('/login', function () {
    return Inertia::render('ui/auth/Login');
})->name('login');

Route::get('/register', function () {
    return Inertia::render('ui/auth/Register');
})->name('register');

// User Products - Redirect to products page
Route::get('/dashboard', function () {
    return redirect('/products');
})->name('user.products');

// Admin Authentication (Guest)
Route::get('/admin/login', function () {
    return Inertia::render('admin/auth/AdminLogin');
})->name('admin.login');

// Admin Dashboard (Protected)
Route::get('/admin/dashboard', function () {
    return Inertia::render('admin/dashboard/AdminDashboard');
})->name('admin.dashboard');

// Admin Products (Protected)
Route::get('/admin/products', function () {
    return Inertia::render('admin/products/Products');
})->name('admin.products');

Route::get('/admin/products/create', function () {
    return Inertia::render('admin/products/ProductForm');
})->name('admin.products.create');

Route::get('/admin/products/edit', function () {
    return Inertia::render('admin/products/ProductForm');
})->name('admin.products.edit');

// Admin Categories (Protected)
Route::get('/admin/categories', function () {
    return Inertia::render('admin/categories/Categories');
})->name('admin.categories');

Route::get('/admin/categories/create', function () {
    return Inertia::render('admin/categories/CategoryForm');
})->name('admin.categories.create');

Route::get('/admin/categories/edit', function () {
    return Inertia::render('admin/categories/CategoryForm');
})->name('admin.categories.edit');

// Admin Permissions (Protected)
Route::get('/admin/permissions', function () {
    return Inertia::render('admin/permissions/Permissions');
})->name('admin.permissions');

Route::get('/admin/permissions/bulk-create', function () {
    return Inertia::render('admin/permissions/PermissionBulkForm');
})->name('admin.permissions.bulk-create');

// Admin Users (Protected)
Route::get('/admin/users', function () {
    return Inertia::render('admin/users/Users');
})->name('admin.users');

// Admin Discounts (Protected)
Route::get('/admin/discounts', function () {
    return Inertia::render('admin/discounts/Discounts');
})->name('admin.discounts');

Route::get('/admin/discounts/create', function () {
    return Inertia::render('admin/discounts/DiscountForm');
})->name('admin.discounts.create');

Route::get('/admin/discounts/edit', function () {
    return Inertia::render('admin/discounts/DiscountForm');
})->name('admin.discounts.edit');

// Admin Coupon Codes (Protected)
Route::get('/admin/coupon-codes', function () {
    return Inertia::render('admin/coupon-codes/CouponCodes');
})->name('admin.coupon-codes');

Route::get('/admin/coupon-codes/create', function () {
    return Inertia::render('admin/coupon-codes/CouponCodeForm');
})->name('admin.coupon-codes.create');

Route::get('/admin/coupon-codes/edit', function () {
    return Inertia::render('admin/coupon-codes/CouponCodeForm');
})->name('admin.coupon-codes.edit');

// Fallback: render Inertia 404 page for any unknown web route
Route::fallback(function () {
    return Inertia::render('errors/PageNotFound')->toResponse(request())->setStatusCode(404);
});

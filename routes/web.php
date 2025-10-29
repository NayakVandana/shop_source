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
    return Inertia::render('guest/home/Home');
})->name('home');

Route::get('/products', function () {
    return Inertia::render('guest/product/Products');
})->name('products');

Route::get('/product', function () {
    return Inertia::render('guest/product/ProductDetail');
})->name('product');

// Authentication Pages (Guest)
Route::get('/login', function () {
    return Inertia::render('guest/auth/Login');
})->name('login');

Route::get('/register', function () {
    return Inertia::render('guest/auth/Register');
})->name('register');

// User Dashboard (Protected)
Route::get('/dashboard', function () {
    return Inertia::render('user/dashboard/Dashboard');
})->name('dashboard');

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

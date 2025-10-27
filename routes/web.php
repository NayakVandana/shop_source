<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ============================================
// WEB ROUTES - Page rendering (GET views only)
// No business logic - views only
// ============================================

// Home & Public Pages
Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

Route::get('/products', function () {
    return Inertia::render('Products');
})->name('products');

Route::get('/product', function () {
    return Inertia::render('ProductDetail');
})->name('product');

// Authentication Pages
Route::get('/login', function () {
    return Inertia::render('Login');
})->name('login');

Route::get('/register', function () {
    return Inertia::render('Register');
})->name('register');

// Dashboard (Protected)
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

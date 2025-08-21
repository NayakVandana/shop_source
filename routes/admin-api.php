<?php

use App\Http\Controllers\auth\AuthController;
use App\Http\Controllers\admin\AuthController as AdminAuthController;
use App\Http\Controllers\admin\ProductController as AdminProductController;
use App\Http\Controllers\user\ProductController as UserProductController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AdminAuthController::class, 'adminLogin']);



Route::middleware(['user.verify','admin.verify',])->group(function () {
    Route::get('/products', [AdminProductController::class, 'index']);
    // Route::post('/products', [AdminProductController::class, 'store'])->middleware('role:admin');
    Route::post('/products', [AdminProductController::class, 'store']);
    Route::post('/products/manage', [AdminProductController::class, 'manage'])->middleware('role:admin');
});
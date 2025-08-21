<?php

use App\Http\Controllers\auth\AuthController;
use App\Http\Controllers\admin\AuthController as AdminAuthController;
use App\Http\Controllers\admin\ProductController as AdminProductController;
use App\Http\Controllers\user\ProductController as UserProductController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware(['user.verify'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/products', [UserProductController::class, 'index']);
});


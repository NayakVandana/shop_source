<?php

use App\Http\Controllers\auth\AuthController;
use App\Http\Controllers\admin\AuthController as AdminAuthController;
use App\Http\Controllers\admin\ProductController as AdminProductController;
use App\Http\Controllers\user\ProductController as UserProductController;
use Illuminate\Support\Facades\Route;

Route::post('/products', [UserProductController::class, 'index']);

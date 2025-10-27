<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use Illuminate\Support\Facades\Route;

// Public Product Routes
Route::post('/products/index', [ProductController::class, 'index']);
Route::post('/products/store', [ProductController::class, 'store']);
Route::post('/products/show', [ProductController::class, 'show']);
Route::post('/products/update', [ProductController::class, 'update']);
Route::post('/products/destroy', [ProductController::class, 'destroy']);

// Public Category Routes
Route::post('/categories/index', [CategoryController::class, 'index']);
Route::post('/categories/store', [CategoryController::class, 'store']);
Route::post('/categories/show', [CategoryController::class, 'show']);
Route::post('/categories/update', [CategoryController::class, 'update']);
Route::post('/categories/destroy', [CategoryController::class, 'destroy']);
Route::post('/categories/products', [CategoryController::class, 'products']);

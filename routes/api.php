<?php

use Illuminate\Support\Facades\Route;

// Public Product & Category Routes
Route::post('/products/list', [App\Http\Controllers\user\ProductController::class, 'index']);

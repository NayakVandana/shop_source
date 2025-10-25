<?php

use App\Http\Controllers\admin\AuthController;
use Illuminate\Support\Facades\Route;

// Admin Authentication
Route::post('/admin-login', [AuthController::class, 'adminLogin']);

// Admin Routes (Protected) - All POST methods
Route::middleware(['user.verify', 'admin.verify', 'uuid.validate'])->group(function () {
    
    // Authentication
    Route::post('/admin-logout', [AuthController::class, 'adminLogout']);
    Route::post('/admin-profile', [AuthController::class, 'adminProfile']);
    
});

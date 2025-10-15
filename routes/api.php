<?php

use Illuminate\Support\Facades\Route;

// API Routes are organized in separate files:
// - admin-api.php for admin panel APIs
// - user-api.php for customer/user APIs

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API is running',
        'timestamp' => now(),
        'version' => '1.0.0'
    ]);
});

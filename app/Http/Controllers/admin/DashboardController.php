<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Exception;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function stats(Request $request)
    {
        try {
            // Products counts
            $totalProducts = Product::count();
            $activeProducts = Product::where('is_active', true)->count();
            $inactiveProducts = Product::where('is_active', false)->count();
            $featuredProducts = Product::where('is_featured', true)->count();
            $lowStockProducts = Product::where('manage_stock', true)
                ->where('stock_quantity', '<', 10)
                ->where('stock_quantity', '>', 0)
                ->count();
            $outOfStockProducts = Product::where('manage_stock', true)
                ->where(function($q) {
                    $q->where('in_stock', false)
                      ->orWhere('stock_quantity', '<=', 0);
                })
                ->count();
            
            // Categories count
            $totalCategories = Category::count();
            
            // Users counts
            $totalUsers = User::count();
            $activeUsers = User::where('is_active', true)->count();
            $inactiveUsers = User::where('is_active', false)->count();
            $totalAdminUsers = User::where('is_admin', true)->count();
            $registeredUsers = User::where('is_registered', true)->count();
            
            $stats = [
                'total_products' => $totalProducts,
                'active_products' => $activeProducts,
                'inactive_products' => $inactiveProducts,
                'total_categories' => $totalCategories,
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'inactive_users' => $inactiveUsers,
                'total_admin_users' => $totalAdminUsers,
                'registered_users' => $registeredUsers,
                'featured_products' => $featuredProducts,
                'low_stock_products' => $lowStockProducts,
                'out_of_stock_products' => $outOfStockProducts,
            ];

            $recent_products = Product::with(['category', 'media'])
                ->latest()
                ->limit(5)
                ->get()
                ->map(function ($product) {
                    unset($product->password);
                    return $product;
                });

            $top_categories = Category::withCount('products')
                ->orderBy('products_count', 'desc')
                ->limit(5)
                ->get();

            $recent_users = User::latest()
                ->limit(5)
                ->get()
                ->map(function ($user) {
                    unset($user->password);
                    return $user;
                });

            return $this->sendJsonResponse(true, 'Dashboard stats retrieved successfully', [
                'stats' => $stats,
                'recent_products' => $recent_products,
                'top_categories' => $top_categories,
                'recent_users' => $recent_users,
            ]);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}


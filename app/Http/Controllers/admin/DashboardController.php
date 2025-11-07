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
            $stats = [
                'total_products' => Product::count(),
                'total_categories' => Category::count(),
                'total_users' => User::count(),
                'featured_products' => Product::where('is_featured', true)->count(),
                'low_stock_products' => Product::where('stock_quantity', '<', 10)->count(),
            ];

            $recent_products = Product::with(['category', 'media'])
                ->latest()
                ->limit(5)
                ->get();

            $top_categories = Category::withCount('products')
                ->orderBy('products_count', 'desc')
                ->limit(5)
                ->get();

            return $this->sendJsonResponse(true, 'Dashboard stats retrieved successfully', [
                'stats' => $stats,
                'recent_products' => $recent_products,
                'top_categories' => $top_categories,
            ]);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}


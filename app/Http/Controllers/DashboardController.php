<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Exception;

class DashboardController extends Controller
{
    /**
     * Single Action Controller - Handle dashboard data
     */
    public function __invoke(): JsonResponse
    {
        try {
            $stats = [
                'total_products' => Product::count(),
                'total_categories' => Category::count(),
                'total_users' => User::count(),
                'featured_products' => Product::where('is_featured', true)->count(),
                'low_stock_products' => Product::where('stock_quantity', '<', 10)->count(),
            ];

            $recent_products = Product::with('category')
                ->latest()
                ->limit(5)
                ->get();

            $top_categories = Category::withCount('products')
                ->orderBy('products_count', 'desc')
                ->limit(5)
                ->get();

            return $this->sendJsonResponse(true, 'Dashboard data retrieved successfully', [
                'stats' => $stats,
                'recent_products' => $recent_products,
                'top_categories' => $top_categories
            ]);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}

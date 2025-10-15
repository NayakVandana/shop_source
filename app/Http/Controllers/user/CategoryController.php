<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Exception;

class CategoryController extends Controller
{
    public function index()
    {
        try {
            $categories = Category::where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            return $this->sendJsonResponse(true, 'Categories retrieved successfully', $categories);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function show($id)
    {
        try {
            $category = Category::with(['products' => function($query) {
                $query->where('is_active', true);
            }])->where('is_active', true)->findOrFail($id);

            return $this->sendJsonResponse(true, 'Category retrieved successfully', $category);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function products(Request $request, $id)
    {
        try {
            $category = Category::findOrFail($id);
            
            $query = $category->products()->where('is_active', true);

            // Search within category
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Filter by price range
            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->get('min_price'));
            }

            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->get('max_price'));
            }

            // Filter by stock status
            if ($request->has('in_stock')) {
                $query->where('in_stock', $request->get('in_stock'));
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            if ($sortBy === 'price') {
                $query->orderByRaw('CASE WHEN sale_price IS NOT NULL THEN sale_price ELSE price END ' . $sortOrder);
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 12);
            $products = $query->paginate($perPage);

            return $this->sendJsonResponse(true, 'Category products retrieved successfully', [
                'category' => $category,
                'products' => $products
            ]);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}
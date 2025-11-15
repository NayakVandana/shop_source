<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Exception;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Product::with(['category', 'media', 'discounts'])->where('is_active', true);

            // Search
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            // Filter by category
            if ($request->has('category_id')) {
                $query->where('category_id', $request->get('category_id'));
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

            // Filter featured products
            if ($request->has('featured')) {
                $query->where('is_featured', true);
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

            // Media URLs are automatically available via model attributes
            // No need to manually transform - they're accessed via accessors

            return $this->sendJsonResponse(true, 'Products retrieved successfully', $products);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $product = Product::with(['category', 'media', 'discounts'])
                ->where('is_active', true)
                ->where('uuid', $data['id'])
                ->firstOrFail();
            
            // Media URLs are automatically available via model attributes
            // image_urls, primary_image_url, video_urls, primary_video_url
            
            return $this->sendJsonResponse(true, 'Product retrieved successfully', $product);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function featured(Request $request)
    {
        try {
            $products = Product::with(['category', 'media', 'discounts'])
                ->where('is_active', true)
                ->where('is_featured', true)
                ->orderBy('created_at', 'desc')
                ->limit(8)
                ->get();
            
            return $this->sendJsonResponse(true, 'Featured products retrieved successfully', $products);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function related(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $product = Product::where('uuid', $data['id'])->firstOrFail();
            
            $relatedProducts = Product::with(['category', 'media', 'discounts'])
                ->where('is_active', true)
                ->where('uuid', '!=', $data['id'])
                ->where('category_id', $product->category_id)
                ->limit(4)
                ->get();
            
            return $this->sendJsonResponse(true, 'Related products retrieved successfully', $relatedProducts);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}

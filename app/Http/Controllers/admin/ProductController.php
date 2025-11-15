<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Exception;

class ProductController extends Controller
{
    /**
     * Display a listing of products
     */
    public function index(Request $request): Response
    {
        try {
            $query = Product::with(['category', 'media', 'discounts']);
            
            // Search functionality
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%')
                      ->orWhere('sku', 'like', '%' . $request->search . '%');
            }
            
            // Category filter
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }
            
            // Status filter
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }
            
            // Featured filter
            if ($request->has('is_featured')) {
                $query->where('is_featured', $request->is_featured);
            }
            
            // Price range filter
            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }
            
            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }
            
            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);
            
            // Pagination
            $perPage = $request->get('per_page', 15);
            $products = $query->paginate($perPage);
            
            // Get counts for filtered query (before pagination)
            $countsQuery = Product::query();
            
            // Apply same filters as main query
            if ($request->has('search')) {
                $countsQuery->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%')
                      ->orWhere('sku', 'like', '%' . $request->search . '%');
            }
            
            if ($request->has('category_id')) {
                $countsQuery->where('category_id', $request->category_id);
            }
            
            if ($request->has('is_active')) {
                $countsQuery->where('is_active', $request->is_active);
            }
            
            if ($request->has('is_featured')) {
                $countsQuery->where('is_featured', $request->is_featured);
            }
            
            if ($request->has('min_price')) {
                $countsQuery->where('price', '>=', $request->min_price);
            }
            
            if ($request->has('max_price')) {
                $countsQuery->where('price', '<=', $request->max_price);
            }
            
            // Get counts
            $totalCount = $countsQuery->count();
            $activeCount = (clone $countsQuery)->where('is_active', true)->count();
            $inactiveCount = (clone $countsQuery)->where('is_active', false)->count();
            $inStockCount = (clone $countsQuery)->where(function($q) {
                $q->where(function($q2) {
                    $q2->where('manage_stock', false)
                       ->orWhere(function($q3) {
                           $q3->where('manage_stock', true)
                              ->where('in_stock', true)
                              ->where('stock_quantity', '>', 0);
                       });
                });
            })->count();
            $outOfStockCount = (clone $countsQuery)->where(function($q) {
                $q->where('manage_stock', true)
                  ->where(function($q2) {
                      $q2->where('in_stock', false)
                         ->orWhere('stock_quantity', '<=', 0);
                  });
            })->count();
            
            // Add counts to pagination response
            $products->appends([
                'counts' => [
                    'total' => $totalCount,
                    'active' => $activeCount,
                    'inactive' => $inactiveCount,
                    'in_stock' => $inStockCount,
                    'out_of_stock' => $outOfStockCount,
                ]
            ]);
            
            // Return response with counts in data
            $responseData = $products->toArray();
            $responseData['counts'] = [
                'total' => $totalCount,
                'active' => $activeCount,
                'inactive' => $inactiveCount,
                'in_stock' => $inStockCount,
                'out_of_stock' => $outOfStockCount,
            ];
            
            return $this->sendJsonResponse(true, 'Products retrieved successfully', $responseData);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request): Response
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'short_description' => 'nullable|string|max:500',
                'price' => 'required|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0',
                'category_id' => 'required|exists:categories,id',
                'stock_quantity' => 'required|integer|min:0',
                'manage_stock' => 'boolean',
                'in_stock' => 'boolean',
                'is_featured' => 'boolean',
                'is_active' => 'boolean',
                'weight' => 'nullable|numeric|min:0',
                'dimensions' => 'nullable|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'images' => 'nullable|array',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'video' => 'nullable|mimes:mp4,avi,mov,wmv,flv,webm|max:10240',
                'videos' => 'nullable|array',
                'videos.*' => 'mimes:mp4,avi,mov,wmv,flv,webm|max:10240',
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', ['errors' => $validator->errors()], 422);
            }

            $data = $request->except(['images', 'image', 'videos', 'video']);
            
            $product = Product::create($data);
            
            // Handle single image upload
            if ($request->hasFile('image')) {
                $product->storeImages($request->file('image'));
            }
            
            // Handle multiple images upload
            if ($request->hasFile('images')) {
                $product->storeImages($request->file('images'));
            }

            // Handle single video upload
            if ($request->hasFile('video')) {
                $product->storeVideos($request->file('video'));
            }
            
            // Handle multiple videos upload
            if ($request->hasFile('videos')) {
                $product->storeVideos($request->file('videos'));
            }

            $product->load(['category', 'media', 'discounts']);

            return $this->sendJsonResponse(true, 'Product created successfully', $product, 201);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Display the specified product
     */
    public function show(Request $request): Response
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $product = Product::with(['category', 'media', 'discounts'])->where('uuid', $data['id'])->firstOrFail();
            
            return $this->sendJsonResponse(true, 'Product retrieved successfully', $product);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Update the specified product
     */
    public function update(Request $request): Response
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $product = Product::where('uuid', $data['id'])->firstOrFail();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'short_description' => 'nullable|string|max:500',
                'price' => 'sometimes|required|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0',
                'category_id' => 'sometimes|required|exists:categories,id',
                'stock_quantity' => 'sometimes|required|integer|min:0',
                'manage_stock' => 'boolean',
                'in_stock' => 'boolean',
                'is_featured' => 'boolean',
                'is_active' => 'boolean',
                'weight' => 'nullable|numeric|min:0',
                'dimensions' => 'nullable|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'images' => 'nullable|array',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'video' => 'nullable|mimes:mp4,avi,mov,wmv,flv,webm|max:10240',
                'videos' => 'nullable|array',
                'videos.*' => 'mimes:mp4,avi,mov,wmv,flv,webm|max:10240',
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', ['errors' => $validator->errors()], 422);
            }

            $updateData = $request->except(['id', 'images', 'image', 'videos', 'video']);
            
            // Handle single image upload
            if ($request->hasFile('image')) {
                // Delete existing images
                $product->deleteImages();
                $product->storeImages($request->file('image'));
            }
            
            // Handle multiple images upload
            if ($request->hasFile('images')) {
                // Delete existing images
                $product->deleteImages();
                $product->storeImages($request->file('images'));
            }

            // Handle single video upload
            if ($request->hasFile('video')) {
                // Delete existing videos
                $product->deleteVideos();
                $product->storeVideos($request->file('video'));
            }
            
            // Handle multiple videos upload
            if ($request->hasFile('videos')) {
                // Delete existing videos
                $product->deleteVideos();
                $product->storeVideos($request->file('videos'));
            }

            $product->update($updateData);
            $product->load(['category', 'media', 'discounts']);

            return $this->sendJsonResponse(true, 'Product updated successfully', $product);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Remove the specified product
     */
    public function destroy(Request $request): Response
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $product = Product::where('uuid', $data['id'])->firstOrFail();

            // Delete associated media (images and videos)
            // This is handled automatically by the model's boot method,
            // but we can call explicitly if needed
            $product->deleteImages();
            $product->deleteVideos();
            
            $product->delete();

            return $this->sendJsonResponse(true, 'Product deleted successfully', null);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}

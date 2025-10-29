<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Exception;

class ProductController extends Controller
{
    /**
     * Display a listing of products
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Product::with('category');
            
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
            
            return $this->sendJsonResponse(true, 'Products retrieved successfully', $products);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request): JsonResponse
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
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', ['errors' => $validator->errors()], 422);
            }

            $data = $request->except(['images', 'image']);
            
            // Handle single image upload
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('products', 'public');
                $data['images'] = [$imagePath];
            }
            
            // Handle multiple images upload
            if ($request->hasFile('images')) {
                $images = [];
                foreach ($request->file('images') as $image) {
                    $images[] = $image->store('products', 'public');
                }
                $data['images'] = $images;
            }

            $product = Product::create($data);
            $product->load('category');

            return $this->sendJsonResponse(true, 'Product created successfully', $product, 201);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Display the specified product
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $product = Product::with('category')->where('uuid', $data['id'])->firstOrFail();
            
            return $this->sendJsonResponse(true, 'Product retrieved successfully', $product);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Update the specified product
     */
    public function update(Request $request): JsonResponse
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
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', ['errors' => $validator->errors()], 422);
            }

            $updateData = $request->except(['id', 'images', 'image']);
            
            // Handle single image upload
            if ($request->hasFile('image')) {
                // Delete old images if exists
                if ($product->images) {
                    foreach ($product->images as $oldImage) {
                        Storage::disk('public')->delete($oldImage);
                    }
                }
                $imagePath = $request->file('image')->store('products', 'public');
                $updateData['images'] = [$imagePath];
            }
            
            // Handle multiple images upload
            if ($request->hasFile('images')) {
                // Delete old images if exists
                if ($product->images) {
                    foreach ($product->images as $oldImage) {
                        Storage::disk('public')->delete($oldImage);
                    }
                }
                $images = [];
                foreach ($request->file('images') as $image) {
                    $images[] = $image->store('products', 'public');
                }
                $updateData['images'] = $images;
            }

            $product->update($updateData);
            $product->load('category');

            return $this->sendJsonResponse(true, 'Product updated successfully', $product);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Remove the specified product
     */
    public function destroy(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $product = Product::where('uuid', $data['id'])->firstOrFail();

            // Delete associated images
            if ($product->images && is_array($product->images)) {
                foreach ($product->images as $imagePath) {
                    if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                        Storage::disk('public')->delete($imagePath);
                    }
                }
            }
            
            $product->delete();

            return $this->sendJsonResponse(true, 'Product deleted successfully', null);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}

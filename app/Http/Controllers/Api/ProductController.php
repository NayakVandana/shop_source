<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Exception;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Product::with('category');
            
            // Search functionality
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }
            
            // Category filter
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
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
            
            return response()->json([
                'status' => true,
                'message' => 'Products retrieved successfully',
                'data' => $products
            ]);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Store a newly created resource
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'category_id' => 'required|exists:categories,id',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'is_featured' => 'boolean',
                'stock_quantity' => 'required|integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->all();
            
            // Handle image upload
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('products', 'public');
                $data['image'] = $imagePath;
            }

            $product = Product::create($data);
            $product->load('category');

            return response()->json([
                'status' => true,
                'message' => 'Product created successfully',
                'data' => $product
            ], 201);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Display the specified resource
     */
    public function show(Product $product): JsonResponse
    {
        try {
            $product->load('category');
            
            return response()->json([
                'status' => true,
                'message' => 'Product retrieved successfully',
                'data' => $product
            ]);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Update the specified resource
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'sometimes|required|numeric|min:0',
                'category_id' => 'sometimes|required|exists:categories,id',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'is_featured' => 'boolean',
                'stock_quantity' => 'sometimes|required|integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->all();
            
            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($product->image) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($product->image);
                }
                
                $imagePath = $request->file('image')->store('products', 'public');
                $data['image'] = $imagePath;
            }

            $product->update($data);
            $product->load('category');

            return response()->json([
                'status' => true,
                'message' => 'Product updated successfully',
                'data' => $product
            ]);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Remove the specified resource
     */
    public function destroy(Product $product): JsonResponse
    {
        try {
            // Delete associated image
            if ($product->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($product->image);
            }
            
            $product->delete();

            return response()->json([
                'status' => true,
                'message' => 'Product deleted successfully'
            ]);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Exception;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Category::query();
            
            // Search functionality
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }
            
            // Sorting
            $sortBy = $request->get('sort_by', 'name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);
            
            // Pagination
            $perPage = $request->get('per_page', 15);
            $categories = $query->paginate($perPage);
            
            return response()->json([
                'status' => true,
                'message' => 'Categories retrieved successfully',
                'data' => $categories
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
                'slug' => 'nullable|string|unique:categories,slug',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->all();
            
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
            }
            
            // Handle image upload
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('categories', 'public');
                $data['image'] = $imagePath;
            }

            $category = Category::create($data);

            return response()->json([
                'status' => true,
                'message' => 'Category created successfully',
                'data' => $category
            ], 201);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Display the specified resource
     */
    public function show(Category $category): JsonResponse
    {
        try {
            $category->loadCount('products');
            
            return response()->json([
                'status' => true,
                'message' => 'Category retrieved successfully',
                'data' => $category
            ]);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Update the specified resource
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'slug' => 'sometimes|string|unique:categories,slug,' . $category->id,
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'is_active' => 'boolean'
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
                if ($category->image) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($category->image);
                }
                
                $imagePath = $request->file('image')->store('categories', 'public');
                $data['image'] = $imagePath;
            }

            $category->update($data);

            return response()->json([
                'status' => true,
                'message' => 'Category updated successfully',
                'data' => $category
            ]);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Remove the specified resource
     */
    public function destroy(Category $category): JsonResponse
    {
        try {
            // Check if category has products
            if ($category->products()->count() > 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Cannot delete category with existing products'
                ], 422);
            }
            
            // Delete associated image
            if ($category->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($category->image);
            }
            
            $category->delete();

            return response()->json([
                'status' => true,
                'message' => 'Category deleted successfully'
            ]);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Get products for a specific category
     */
    public function products(Category $category, Request $request): JsonResponse
    {
        try {
            $query = $category->products();
            
            // Search functionality
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
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
                'message' => 'Category products retrieved successfully',
                'data' => [
                    'category' => $category,
                    'products' => $products
                ]
            ]);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}

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
    public function index(Request $request)
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
            
            return $this->sendJsonResponse(true, 'Categories retrieved successfully', $categories);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Store a newly created resource
     */
    public function store(Request $request)
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
                return $this->sendJsonResponse(false, 'Validation failed', $validator->errors(), 422);
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

            return $this->sendJsonResponse(true, 'Category created successfully', $category, 201);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Display the specified resource
     */
    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $category = Category::where('uuid', $data['id'])->firstOrFail();
            $category->loadCount('products');
            
            return $this->sendJsonResponse(true, 'Category retrieved successfully', $category);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Update the specified resource
     */
    public function update(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $category = Category::where('uuid', $data['id'])->firstOrFail();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'slug' => 'sometimes|string|unique:categories,slug,' . $category->id,
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', $validator->errors(), 422);
            }

            $updateData = $request->except(['id']);
            
            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($category->image) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($category->image);
                }
                
                $imagePath = $request->file('image')->store('categories', 'public');
                $updateData['image'] = $imagePath;
            }

            $category->update($updateData);

            return $this->sendJsonResponse(true, 'Category updated successfully', $category);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Remove the specified resource
     */
    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $category = Category::where('uuid', $data['id'])->firstOrFail();

            // Check if category has products
            if ($category->products()->count() > 0) {
                return $this->sendJsonResponse(false, 'Cannot delete category with existing products', null, 422);
            }
            
            // Delete associated image
            if ($category->image) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($category->image);
            }
            
            $category->delete();

            return $this->sendJsonResponse(true, 'Category deleted successfully', null);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Get products for a specific category
     */
    public function products(Request $request)
    {
        try {
            $data = $request->validate([
                'category_id' => 'required|string'
            ]);

            $category = Category::where('uuid', $data['category_id'])->firstOrFail();
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
            
            return $this->sendJsonResponse(true, 'Category products retrieved successfully', [
                'category' => $category,
                'products' => $products
            ]);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}

<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Category::query();

            // Search
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Filter by status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->get('is_active'));
            }

            // Sort
            $sortBy = $request->get('sort_by', 'sort_order');
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

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'image' => 'nullable|string',
                'is_active' => 'boolean',
                'sort_order' => 'integer|min:0'
            ]);

            // Generate slug
            $data['slug'] = Str::slug($data['name']);

            $category = Category::create($data);

            return $this->sendJsonResponse(true, 'Category created successfully', $category, 201);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function show($id)
    {
        try {
            $category = Category::with('products')->findOrFail($id);
            return $this->sendJsonResponse(true, 'Category retrieved successfully', $category);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $category = Category::findOrFail($id);

            $data = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'image' => 'nullable|string',
                'is_active' => 'boolean',
                'sort_order' => 'integer|min:0'
            ]);

            // Update slug if name changed
            if (isset($data['name'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $category->update($data);

            return $this->sendJsonResponse(true, 'Category updated successfully', $category);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function destroy($id)
    {
        try {
            $category = Category::findOrFail($id);
            
            // Check if category has products
            if ($category->products()->count() > 0) {
                return $this->sendJsonResponse(false, 'Cannot delete category with products', null, 400);
            }

            $category->delete();

            return $this->sendJsonResponse(true, 'Category deleted successfully');
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $category = Category::findOrFail($id);
            $category->update(['is_active' => !$category->is_active]);

            return $this->sendJsonResponse(true, 'Category status updated successfully', $category);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}
<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Exception;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Category::withCount('products');
            
            // Search functionality
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%')
                      ->orWhere('slug', 'like', '%' . $request->search . '%');
            }
            
            // Status filter
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }
            
            // Sorting
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

    /**
     * Store a newly created category
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'slug' => 'nullable|string|unique:categories,slug',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'video' => 'nullable|mimes:mp4,avi,mov,wmv,flv,webm|max:10240',
                'is_active' => 'boolean',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', ['errors' => $validator->errors()], 422);
            }

            $data = $request->except(['image', 'video']);
            
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
            }
            
            $category = Category::create($data);
            
            // Handle image upload
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('categories', 'public');
                $category->update(['image' => $imagePath]);
            }
            
            // Handle video upload
            if ($request->hasFile('video')) {
                $videoPath = $request->file('video')->store('categories/videos', 'public');
                $category->update(['video' => $videoPath]);
            }

            return $this->sendJsonResponse(true, 'Category created successfully', $category, 201);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Display the specified category
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $category = Category::withCount('products')->where('uuid', $data['id'])->firstOrFail();
            
            return $this->sendJsonResponse(true, 'Category retrieved successfully', $category);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Update the specified category
     */
    public function update(Request $request): JsonResponse
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
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'video' => 'nullable|mimes:mp4,avi,mov,wmv,flv,webm|max:10240',
                'is_active' => 'boolean',
                'sort_order' => 'nullable|integer|min:0',
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', ['errors' => $validator->errors()], 422);
            }

            $updateData = $request->except(['id', 'image', 'video']);
            
            // Generate slug if name changed and slug not provided
            if (isset($updateData['name']) && empty($updateData['slug'])) {
                $updateData['slug'] = \Illuminate\Support\Str::slug($updateData['name']);
            }
            
            // Handle image upload
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($category->image) {
                    Storage::disk('public')->delete($category->image);
                }
                
                $imagePath = $request->file('image')->store('categories', 'public');
                $updateData['image'] = $imagePath;
            }
            
            // Handle video upload
            if ($request->hasFile('video')) {
                // Delete old video if exists
                if ($category->video) {
                    Storage::disk('public')->delete($category->video);
                }
                
                $videoPath = $request->file('video')->store('categories/videos', 'public');
                $updateData['video'] = $videoPath;
            }

            $category->update($updateData);

            return $this->sendJsonResponse(true, 'Category updated successfully', $category);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Remove the specified category
     */
    public function destroy(Request $request): JsonResponse
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
                Storage::disk('public')->delete($category->image);
            }
            
            // Delete associated video
            if ($category->video) {
                Storage::disk('public')->delete($category->video);
            }
            
            $category->delete();

            return $this->sendJsonResponse(true, 'Category deleted successfully', null);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}


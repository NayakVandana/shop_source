<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductMedia;
use App\Helpers\MediaStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\UploadedFile;
use Exception;

class ProductController extends Controller
{
    /**
     * Store product images
     */
    protected function storeProductImages(Product $product, $uploadedImages, $setFirstAsPrimary = true)
    {
        if (!$uploadedImages) {
            return [];
        }

        if ($uploadedImages instanceof UploadedFile) {
            $uploadedImages = [$uploadedImages];
        }

        $productSlug = $product->slug ?: 'products';
        $mediaRecords = [];
        
        foreach ($uploadedImages as $index => $image) {
            if ($image && $image->isValid()) {
                $mediaData = MediaStorageService::storeFile(
                    $image,
                    'image',
                    'products',
                    $productSlug
                );

                $mediaRecord = $product->media()->create([
                    'type' => 'image',
                    'file_path' => $mediaData['file_path'],
                    'file_name' => $mediaData['file_name'],
                    'mime_type' => $mediaData['mime_type'],
                    'file_size' => $mediaData['file_size'],
                    'disk' => $mediaData['disk'],
                    'url' => $mediaData['url'],
                    'sort_order' => $index,
                    'is_primary' => $setFirstAsPrimary && $index === 0,
                ]);

                $mediaRecords[] = $mediaRecord;
            }
        }

        return $mediaRecords;
    }

    /**
     * Store product videos
     */
    protected function storeProductVideos(Product $product, $uploadedVideos, $setFirstAsPrimary = true)
    {
        if (!$uploadedVideos) {
            return [];
        }

        if ($uploadedVideos instanceof UploadedFile) {
            $uploadedVideos = [$uploadedVideos];
        }

        $productSlug = $product->slug ?: 'products';
        $mediaRecords = [];
        
        foreach ($uploadedVideos as $index => $video) {
            if ($video && $video->isValid()) {
                $mediaData = MediaStorageService::storeFile(
                    $video,
                    'video',
                    'products',
                    $productSlug
                );

                $mediaRecord = $product->media()->create([
                    'type' => 'video',
                    'file_path' => $mediaData['file_path'],
                    'file_name' => $mediaData['file_name'],
                    'mime_type' => $mediaData['mime_type'],
                    'file_size' => $mediaData['file_size'],
                    'disk' => $mediaData['disk'],
                    'url' => $mediaData['url'],
                    'sort_order' => $index,
                    'is_primary' => $setFirstAsPrimary && $index === 0,
                ]);

                $mediaRecords[] = $mediaRecord;
            }
        }

        return $mediaRecords;
    }

    /**
     * Delete product images
     */
    protected function deleteProductImages(Product $product)
    {
        $mediaImages = $product->imagesMedia()->get();
        foreach ($mediaImages as $media) {
            MediaStorageService::deleteFile($media->file_path, $media->disk);
            $media->delete();
        }
    }

    /**
     * Delete product videos
     */
    protected function deleteProductVideos(Product $product)
    {
        $mediaVideos = $product->videosMedia()->get();
        foreach ($mediaVideos as $media) {
            MediaStorageService::deleteFile($media->file_path, $media->disk);
            $media->delete();
        }
    }

    /**
     * Display a listing of the resource
     */
    public function index(Request $request)
    {
        try {
            $query = Product::with(['category', 'media', 'discounts']);
            
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
            
            return $this->sendJsonResponse(true, 'Products retrieved successfully', $products);
            
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
                'price' => 'required|numeric|min:0',
                'category_id' => 'required|exists:categories,id',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'images' => 'nullable|array',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'video' => 'nullable|mimes:mp4,avi,mov,wmv,flv,webm|max:10240',
                'videos' => 'nullable|array',
                'videos.*' => 'mimes:mp4,avi,mov,wmv,flv,webm|max:10240',
                'is_featured' => 'boolean',
                'stock_quantity' => 'required|integer|min:0'
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', $validator->errors(), 422);
            }

            $data = $request->except(['image', 'images', 'video', 'videos']);
            
            $product = Product::create($data);
            
            // Handle image upload
            if ($request->hasFile('image')) {
                $this->storeProductImages($product, $request->file('image'));
            }
            
            // Handle multiple images
            if ($request->hasFile('images')) {
                $this->storeProductImages($product, $request->file('images'));
            }
            
            // Handle video upload
            if ($request->hasFile('video')) {
                $this->storeProductVideos($product, $request->file('video'));
            }
            
            // Handle multiple videos
            if ($request->hasFile('videos')) {
                $this->storeProductVideos($product, $request->file('videos'));
            }

            $product->load(['category', 'media']);

            return $this->sendJsonResponse(true, 'Product created successfully', $product, 201);
            
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

            $product = Product::with(['category', 'media', 'discounts'])->where('uuid', $data['id'])->firstOrFail();
            
            return $this->sendJsonResponse(true, 'Product retrieved successfully', $product);
            
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

            $product = Product::where('uuid', $data['id'])->firstOrFail();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'sometimes|required|numeric|min:0',
                'category_id' => 'sometimes|required|exists:categories,id',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'images' => 'nullable|array',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'video' => 'nullable|mimes:mp4,avi,mov,wmv,flv,webm|max:10240',
                'videos' => 'nullable|array',
                'videos.*' => 'mimes:mp4,avi,mov,wmv,flv,webm|max:10240',
                'is_featured' => 'boolean',
                'stock_quantity' => 'sometimes|required|integer|min:0'
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', $validator->errors(), 422);
            }

            $updateData = $request->except(['id', 'image', 'images', 'video', 'videos']);
            
            // Handle image upload
            if ($request->hasFile('image')) {
                $this->deleteProductImages($product);
                $this->storeProductImages($product, $request->file('image'));
            }
            
            // Handle multiple images
            if ($request->hasFile('images')) {
                $this->deleteProductImages($product);
                $this->storeProductImages($product, $request->file('images'));
            }
            
            // Handle video upload
            if ($request->hasFile('video')) {
                $this->deleteProductVideos($product);
                $this->storeProductVideos($product, $request->file('video'));
            }
            
            // Handle multiple videos
            if ($request->hasFile('videos')) {
                $this->deleteProductVideos($product);
                $this->storeProductVideos($product, $request->file('videos'));
            }

            $product->update($updateData);
            $product->load(['category', 'media']);

            return $this->sendJsonResponse(true, 'Product updated successfully', $product);
            
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

            $product = Product::where('uuid', $data['id'])->firstOrFail();

            // Delete associated media
            $this->deleteProductImages($product);
            $this->deleteProductVideos($product);
            
            $product->delete();

            return $this->sendJsonResponse(true, 'Product deleted successfully', null);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}

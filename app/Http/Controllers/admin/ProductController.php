<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductMedia;
use App\Models\ProductVariation;
use App\Helpers\MediaStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Exception;

class ProductController extends Controller
{

    /**
     * Store product images
     */
    protected function storeProductImages(Product $product, $uploadedImages, $setFirstAsPrimary = true, $color = null)
    {
        if (!$uploadedImages) {
            return [];
        }

        // Convert single file to array
        if ($uploadedImages instanceof UploadedFile) {
            $uploadedImages = [$uploadedImages];
        }

        $productSlug = $product->slug ?: 'products';
        $mediaRecords = [];
        
        foreach ($uploadedImages as $index => $image) {
            if ($image && $image->isValid()) {
                // Store file using MediaStorageService
                $mediaData = MediaStorageService::storeFile(
                    $image,
                    'image',
                    'products',
                    $productSlug
                );

                // Create media record
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
                    'color' => $color,
                ]);

                $mediaRecords[] = $mediaRecord;
            }
        }

        return $mediaRecords;
    }

    /**
     * Store product videos
     */
    protected function storeProductVideos(Product $product, $uploadedVideos, $setFirstAsPrimary = true, $color = null)
    {
        if (!$uploadedVideos) {
            return [];
        }

        // Convert single file to array
        if ($uploadedVideos instanceof UploadedFile) {
            $uploadedVideos = [$uploadedVideos];
        }

        $productSlug = $product->slug ?: 'products';
        $mediaRecords = [];
        
        foreach ($uploadedVideos as $index => $video) {
            if ($video && $video->isValid()) {
                // Store file using MediaStorageService
                $mediaData = MediaStorageService::storeFile(
                    $video,
                    'video',
                    'products',
                    $productSlug
                );

                // Create media record
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
                    'color' => $color,
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
     * Get available sizes based on category name
     * Logic is in controller, not model
     */
    protected function getSizesByCategory($categoryName)
    {
        if (!$categoryName) {
            return null;
        }

        $text = strtolower($categoryName);

        // Kids sizes
        if (strpos($text, 'kid') !== false || strpos($text, 'child') !== false || strpos($text, 'toddler') !== false) {
            return ['2T', '3T', '4T', '5T', '6T', 'XS', 'S', 'M', 'L', 'XL', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '16'];
        }
        
        // Women sizes
        if (strpos($text, 'women') !== false || strpos($text, 'woman') !== false || strpos($text, 'ladies') !== false) {
            return ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '0', '2', '4', '6', '8', '10', '12', '14', '16', '18', '20', '22', '24'];
        }
        
        // Men sizes
        if (strpos($text, 'men') !== false || strpos($text, 'man') !== false || strpos($text, 'gentlemen') !== false) {
            return ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '28', '30', '32', '34', '36', '38', '40', '42', '44', '46', '48', '50', '52'];
        }
        
        // Default sizes for generic clothing
        return ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
    }

    /**
     * Process sizes and colors data
     */
    protected function processSizesAndColors($request, $categoryId)
    {
        $sizes = null;
        $colors = null;

        // Get category to determine default sizes
        if ($categoryId) {
            $category = Category::find($categoryId);
            if ($category) {
                // If sizes are provided in request, use them; otherwise use default based on category
                if ($request->has('sizes') && is_array($request->sizes) && count($request->sizes) > 0) {
                    $sizes = $request->sizes;
                } else {
                    // Auto-determine sizes based on category name
                    $defaultSizes = $this->getSizesByCategory($category->name);
                    if ($defaultSizes) {
                        $sizes = $defaultSizes;
                    }
                }
            }
        } else {
            // If sizes are provided without category, use them
            if ($request->has('sizes') && is_array($request->sizes) && count($request->sizes) > 0) {
                $sizes = $request->sizes;
            }
        }

        // Process colors
        if ($request->has('colors') && is_array($request->colors) && count($request->colors) > 0) {
            $colors = $request->colors;
        }

        return [
            'sizes' => $sizes,
            'colors' => $colors
        ];
    }

    /**
     * Sync product's general stock_quantity with sum of all variations
     * 
     * This method calculates the total stock from all product variations
     * and updates the product's general stock_quantity field.
     * 
     * For products with variations:
     * - products.stock_quantity = sum of all product_variations.stock_quantity
     * - This represents total available stock across all size-color combinations
     * - Used as fallback when no specific variation is found
     * 
     * For products without variations:
     * - products.stock_quantity = direct stock quantity
     * 
     * @param Product $product
     * @return void
     */
    protected function syncProductStockFromVariations(Product $product): void
    {
        // Check if product has variations
        $variations = ProductVariation::where('product_id', $product->id)->get();
        
        if ($variations->isNotEmpty()) {
            // Calculate total stock from all variations
            $totalStock = $variations->sum('stock_quantity');
            $hasInStock = $variations->where('in_stock', true)->isNotEmpty();
            
            // Update product's general stock
            $product->update([
                'stock_quantity' => $totalStock,
                'in_stock' => $hasInStock && $totalStock > 0,
            ]);
        }
    }

    /**
     * Display a listing of products
     */
    public function index(Request $request)
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
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'short_description' => 'nullable|string|max:500',
                'price' => 'required|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0',
                'category_id' => 'required|exists:categories,id',
                'stock_quantity' => 'required|integer|min:1',
                'manage_stock' => 'boolean',
                'in_stock' => 'boolean',
                'is_featured' => 'boolean',
                'is_active' => 'boolean',
                'weight' => 'nullable|numeric|min:0',
                'dimensions' => 'nullable|string',
                'sizes' => 'nullable|array',
                'sizes.*' => 'string|max:50',
                'colors' => 'nullable|array',
                'colors.*' => 'string|max:50',
                'color_sizes' => 'nullable|array',
                'color_sizes.*' => 'nullable|array',
                'color_sizes.*.*' => 'string|max:50',
                'variation_stock' => 'nullable|array',
                'variation_stock.*' => 'nullable|integer|min:1',
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

            $data = $request->except(['images', 'image', 'videos', 'video', 'sizes', 'colors', 'color_images', 'color_videos', 'color_sizes', 'variation_stock']);
            
            // Ensure description is always present (required field)
            if (!isset($data['description']) || $data['description'] === null || $data['description'] === '') {
                $data['description'] = $data['short_description'] ?? $data['name'] ?? 'No description provided';
            }
            
            // Process colors
            $colors = null;
            if ($request->has('colors') && is_array($request->colors) && count($request->colors) > 0) {
                $colors = $request->colors;
                $data['colors'] = $colors;
            }
            
            // Collect all unique sizes from color_sizes for product.sizes field
            $allSizes = [];
            if ($request->has('color_sizes') && is_array($request->color_sizes)) {
                foreach ($request->color_sizes as $color => $sizes) {
                    if (is_array($sizes)) {
                        foreach ($sizes as $size) {
                            if (!in_array($size, $allSizes)) {
                                $allSizes[] = $size;
                            }
                        }
                    }
                }
            }
            if (!empty($allSizes)) {
                $data['sizes'] = $allSizes;
            }
            
            DB::beginTransaction();
            try {
                $product = Product::create($data);
                
                // Create product variations with stock quantities if color_sizes and colors are provided
                if ($request->has('color_sizes') && is_array($request->color_sizes) && !empty($colors) && $request->has('variation_stock')) {
                    $colorSizes = $request->input('color_sizes', []);
                    $variationStock = $request->input('variation_stock', []);
                    $variations = [];
                    
                    foreach ($colors as $color) {
                        if (isset($colorSizes[$color]) && is_array($colorSizes[$color])) {
                            foreach ($colorSizes[$color] as $size) {
                                $key = "{$size}_{$color}";
                                $stockQuantity = isset($variationStock[$key]) ? (int)$variationStock[$key] : 0;
                                
                                // Only create variation if stock quantity is greater than 0
                                if ($stockQuantity > 0) {
                                    $variations[] = [
                                        'product_id' => $product->id,
                                        'size' => $size,
                                        'color' => $color,
                                        'stock_quantity' => $stockQuantity,
                                        'in_stock' => true,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ];
                                }
                            }
                        }
                    }
                    
                    if (!empty($variations)) {
                        ProductVariation::insert($variations);
                        // Sync general stock from variations
                        $this->syncProductStockFromVariations($product);
                    }
                }
                
                // Handle single image upload (general images without color)
                if ($request->hasFile('image')) {
                    $this->storeProductImages($product, $request->file('image'));
                }
                
                // Handle multiple images upload (general images without color)
                if ($request->hasFile('images')) {
                    $this->storeProductImages($product, $request->file('images'));
                }
                
                // Handle color-specific main images
                if ($request->has('color_main_image') && is_array($request->color_main_image)) {
                    foreach ($request->color_main_image as $color => $image) {
                        if ($request->hasFile("color_main_image.{$color}")) {
                            $mainImage = $request->file("color_main_image.{$color}");
                            $this->storeProductImages($product, $mainImage, true, $color);
                        }
                    }
                }

                // Handle color-specific images
                if ($request->has('color_images') && is_array($request->color_images)) {
                    foreach ($request->color_images as $color => $images) {
                        if ($request->hasFile("color_images.{$color}")) {
                            $colorImages = $request->file("color_images.{$color}");
                            if (is_array($colorImages)) {
                                $this->storeProductImages($product, $colorImages, false, $color);
                            } else {
                                $this->storeProductImages($product, [$colorImages], false, $color);
                            }
                        }
                    }
                }

                // Handle single video upload (general videos without color)
                if ($request->hasFile('video')) {
                    $this->storeProductVideos($product, $request->file('video'));
                }
                
                // Handle multiple videos upload (general videos without color)
                if ($request->hasFile('videos')) {
                    $this->storeProductVideos($product, $request->file('videos'));
                }
                
                // Handle color-specific main videos
                if ($request->has('color_main_video') && is_array($request->color_main_video)) {
                    foreach ($request->color_main_video as $color => $video) {
                        if ($request->hasFile("color_main_video.{$color}")) {
                            $mainVideo = $request->file("color_main_video.{$color}");
                            $this->storeProductVideos($product, $mainVideo, true, $color);
                        }
                    }
                }

                // Handle color-specific videos
                if ($request->has('color_videos') && is_array($request->color_videos)) {
                    foreach ($request->color_videos as $color => $videos) {
                        if ($request->hasFile("color_videos.{$color}")) {
                            $colorVideos = $request->file("color_videos.{$color}");
                            if (is_array($colorVideos)) {
                                $this->storeProductVideos($product, $colorVideos, false, $color);
                            } else {
                                $this->storeProductVideos($product, [$colorVideos], false, $color);
                            }
                        }
                    }
                }

                DB::commit();
                $product->load(['category', 'media', 'discounts', 'variations']);

                return $this->sendJsonResponse(true, 'Product created successfully', $product, 201);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Display the specified product
     */
    public function show(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $product = Product::with(['category', 'media', 'discounts', 'variations'])->where('uuid', $data['id'])->firstOrFail();
            
            return $this->sendJsonResponse(true, 'Product retrieved successfully', $product);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Update the specified product
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
                'short_description' => 'nullable|string|max:500',
                'price' => 'sometimes|required|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0',
                'category_id' => 'sometimes|required|exists:categories,id',
                'stock_quantity' => 'sometimes|required|integer|min:1',
                'manage_stock' => 'boolean',
                'in_stock' => 'boolean',
                'is_featured' => 'boolean',
                'is_active' => 'boolean',
                'weight' => 'nullable|numeric|min:0',
                'dimensions' => 'nullable|string',
                'sizes' => 'nullable|array',
                'sizes.*' => 'string|max:50',
                'colors' => 'nullable|array',
                'colors.*' => 'string|max:50',
                'color_sizes' => 'nullable|array',
                'color_sizes.*' => 'nullable|array',
                'color_sizes.*.*' => 'string|max:50',
                'variation_stock' => 'nullable|array',
                'variation_stock.*' => 'nullable|integer|min:1',
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

            $updateData = $request->except(['id', 'images', 'image', 'videos', 'video', 'sizes', 'colors', 'color_images', 'color_videos', 'color_sizes', 'variation_stock']);
            
            // Ensure description is always present (required field) - only update if not provided
            if (!isset($updateData['description']) || $updateData['description'] === null || $updateData['description'] === '') {
                // Don't update description if it's not in the request (preserve existing)
                if ($request->has('description')) {
                    $updateData['description'] = $updateData['short_description'] ?? $product->description ?? 'No description provided';
                }
            }
            
            // Process colors
            $colors = null;
            if ($request->has('colors')) {
                if (is_array($request->colors) && count($request->colors) > 0) {
                    $colors = $request->colors;
                    $updateData['colors'] = $colors;
                } else {
                    $updateData['colors'] = null;
                }
            }
            
            // Collect all unique sizes from color_sizes for product.sizes field
            $allSizes = [];
            if ($request->has('color_sizes') && is_array($request->color_sizes)) {
                foreach ($request->color_sizes as $color => $sizes) {
                    if (is_array($sizes)) {
                        foreach ($sizes as $size) {
                            if (!in_array($size, $allSizes)) {
                                $allSizes[] = $size;
                            }
                        }
                    }
                }
            }
            if (!empty($allSizes)) {
                $updateData['sizes'] = $allSizes;
            } else if ($request->has('color_sizes')) {
                $updateData['sizes'] = null;
            }
            
            DB::beginTransaction();
            try {
                // Update product variations with stock quantities if color_sizes, colors, and variation_stock are provided
                $finalColors = $updateData['colors'] ?? $product->colors ?? [];
                
                if ($request->has('color_sizes') && is_array($request->color_sizes) && !empty($finalColors) && $request->has('variation_stock')) {
                    // Delete existing variations
                    ProductVariation::where('product_id', $product->id)->delete();
                    
                    $colorSizes = $request->input('color_sizes', []);
                    $variationStock = $request->input('variation_stock', []);
                    $variations = [];
                    
                    foreach ($finalColors as $color) {
                        if (isset($colorSizes[$color]) && is_array($colorSizes[$color])) {
                            foreach ($colorSizes[$color] as $size) {
                                $key = "{$size}_{$color}";
                                $stockQuantity = isset($variationStock[$key]) ? (int)$variationStock[$key] : 0;
                                
                                // Only create variation if stock quantity is greater than 0
                                if ($stockQuantity > 0) {
                                    $variations[] = [
                                        'product_id' => $product->id,
                                        'size' => $size,
                                        'color' => $color,
                                        'stock_quantity' => $stockQuantity,
                                        'in_stock' => true,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ];
                                }
                            }
                        }
                    }
                    
                    if (!empty($variations)) {
                        ProductVariation::insert($variations);
                    }
                } else if (empty($finalColors) || !$request->has('color_sizes')) {
                    // If colors are removed or no color_sizes provided, delete all variations
                    ProductVariation::where('product_id', $product->id)->delete();
                }
                // Handle single image upload (general images without color)
                if ($request->hasFile('image')) {
                    // Delete existing general images (without color)
                    $product->media()->where('type', 'image')->whereNull('color')->delete();
                    $this->storeProductImages($product, $request->file('image'));
                }
                
                // Handle multiple images upload (general images without color)
                if ($request->hasFile('images')) {
                    // Delete existing general images (without color)
                    $product->media()->where('type', 'image')->whereNull('color')->delete();
                    $this->storeProductImages($product, $request->file('images'));
                }
                
                // Handle color-specific main images
                if ($request->has('color_main_image') && is_array($request->color_main_image)) {
                    foreach ($request->color_main_image as $color => $image) {
                        if ($request->hasFile("color_main_image.{$color}")) {
                            // Delete existing main image for this color
                            $product->media()->where('type', 'image')->where('color', $color)->where('is_primary', true)->delete();
                            $mainImage = $request->file("color_main_image.{$color}");
                            $this->storeProductImages($product, $mainImage, true, $color);
                        }
                    }
                }

                // Handle color-specific images
                if ($request->has('color_images') && is_array($request->color_images)) {
                    foreach ($request->color_images as $color => $images) {
                        if ($request->hasFile("color_images.{$color}")) {
                            // Delete existing non-primary images for this color
                            $product->media()->where('type', 'image')->where('color', $color)->where('is_primary', false)->delete();
                            
                            $colorImages = $request->file("color_images.{$color}");
                            if (is_array($colorImages)) {
                                $this->storeProductImages($product, $colorImages, false, $color);
                            } else {
                                $this->storeProductImages($product, [$colorImages], false, $color);
                            }
                        }
                    }
                }

                // Handle single video upload (general videos without color)
                if ($request->hasFile('video')) {
                    // Delete existing general videos (without color)
                    $product->media()->where('type', 'video')->whereNull('color')->delete();
                    $this->storeProductVideos($product, $request->file('video'));
                }
                
                // Handle multiple videos upload (general videos without color)
                if ($request->hasFile('videos')) {
                    // Delete existing general videos (without color)
                    $product->media()->where('type', 'video')->whereNull('color')->delete();
                    $this->storeProductVideos($product, $request->file('videos'));
                }
                
                // Handle color-specific main videos
                if ($request->has('color_main_video') && is_array($request->color_main_video)) {
                    foreach ($request->color_main_video as $color => $video) {
                        if ($request->hasFile("color_main_video.{$color}")) {
                            // Delete existing main video for this color
                            $product->media()->where('type', 'video')->where('color', $color)->where('is_primary', true)->delete();
                            $mainVideo = $request->file("color_main_video.{$color}");
                            $this->storeProductVideos($product, $mainVideo, true, $color);
                        }
                    }
                }

                // Handle color-specific videos
                if ($request->has('color_videos') && is_array($request->color_videos)) {
                    foreach ($request->color_videos as $color => $videos) {
                        if ($request->hasFile("color_videos.{$color}")) {
                            // Delete existing non-primary videos for this color
                            $product->media()->where('type', 'video')->where('color', $color)->where('is_primary', false)->delete();
                            
                            $colorVideos = $request->file("color_videos.{$color}");
                            if (is_array($colorVideos)) {
                                $this->storeProductVideos($product, $colorVideos, false, $color);
                            } else {
                                $this->storeProductVideos($product, [$colorVideos], false, $color);
                            }
                        }
                    }
                }

                $product->update($updateData);
                
                // Sync general stock from variations if variations exist
                if (!empty($finalColors) && $request->has('color_sizes')) {
                    $this->syncProductStockFromVariations($product);
                }
                
                DB::commit();
                $product->load(['category', 'media', 'discounts', 'variations']);

                return $this->sendJsonResponse(true, 'Product updated successfully', $product);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Remove the specified product
     */
    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $product = Product::where('uuid', $data['id'])->firstOrFail();

            // Delete associated media (images and videos)
            $this->deleteProductImages($product);
            $this->deleteProductVideos($product);
            
            $product->delete();

            return $this->sendJsonResponse(true, 'Product deleted successfully', null);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}

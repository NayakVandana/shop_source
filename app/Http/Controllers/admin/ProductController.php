<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\DeliveryLocation;
use App\Models\DeliveryIssue;
use App\Traits\AdminPermissionTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Exception;

class ProductController extends Controller
{
    use AdminPermissionTrait;
    public function index(Request $request)
    {
        // Check permission
        $permissionCheck = $this->checkPermission('products.list');
        if ($permissionCheck) return $permissionCheck;

        try {
            $query = Product::with('category');

            // Search
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Filter by category
            if ($request->has('category_id')) {
                $query->where('category_id', $request->get('category_id'));
            }

            // Filter by status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->get('is_active'));
            }

            // Filter by stock status
            if ($request->has('in_stock')) {
                $query->where('in_stock', $request->get('in_stock'));
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $products = $query->paginate($perPage);

            // Add image URLs to each product
            $products->getCollection()->transform(function ($product) {
                $product->image_urls = $product->image_urls;
                $product->primary_image_url = $product->primary_image_url;
                $product->video_urls = $product->video_urls;
                $product->primary_video_url = $product->primary_video_url;
                return $product;
            });

            return $this->sendJsonResponse(true, 'Products retrieved successfully', $products);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function store(Request $request)
    {
        // Check permission
        $permissionCheck = $this->checkPermission('products.create');
        if ($permissionCheck) return $permissionCheck;

        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'short_description' => 'nullable|string|max:500',
                'price' => 'required|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0',
                'sku' => 'required|string|unique:products,sku',
                'stock_quantity' => 'required|integer|min:0',
                'manage_stock' => 'boolean',
                'in_stock' => 'boolean',
                'weight' => 'nullable|numeric|min:0',
                'dimensions' => 'nullable|string',
                'images' => 'nullable|array|max:10',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'videos' => 'nullable|array|max:5',
                'videos.*' => 'file|mimes:mp4,avi,mov,wmv,flv,webm|max:10240',
                'is_featured' => 'boolean',
                'is_active' => 'boolean',
                'category_id' => 'nullable|exists:categories,id'
            ]);

            // Generate slug
            $data['slug'] = Str::slug($data['name']);

            // Handle image uploads
            if ($request->hasFile('images')) {
                $imagePaths = Product::storeImages($request->file('images'), $data['slug']);
                $data['images'] = $imagePaths;
            } else {
                $data['images'] = [];
            }

            // Handle video uploads
            if ($request->hasFile('videos')) {
                $videoPaths = Product::storeVideos($request->file('videos'), $data['slug']);
                $data['videos'] = $videoPaths;
            } else {
                $data['videos'] = [];
            }

            $product = Product::create($data);
            $product->load('category');

            // Add image URLs to response
            $product->image_urls = $product->image_urls;
            $product->primary_image_url = $product->primary_image_url;
            $product->video_urls = $product->video_urls;
            $product->primary_video_url = $product->primary_video_url;

            return $this->sendJsonResponse(true, 'Product created successfully', $product, 201);
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

            $product = Product::with('category')->where('uuid', $data['id'])->firstOrFail();
            
            // Add image URLs to response
            $product->image_urls = $product->image_urls;
            $product->primary_image_url = $product->primary_image_url;
            $product->video_urls = $product->video_urls;
            $product->primary_video_url = $product->primary_video_url;
            
            return $this->sendJsonResponse(true, 'Product retrieved successfully', $product);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function update(Request $request)
    {
        // Check permission
        $permissionCheck = $this->checkPermission('products.update');
        if ($permissionCheck) return $permissionCheck;

        try {
            $data = $request->validate([
                'id' => 'required|string',
                'name' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string',
                'short_description' => 'nullable|string|max:500',
                'price' => 'sometimes|required|numeric|min:0',
                'sale_price' => 'nullable|numeric|min:0',
                'sku' => 'sometimes|required|string',
                'stock_quantity' => 'sometimes|required|integer|min:0',
                'manage_stock' => 'boolean',
                'in_stock' => 'boolean',
                'weight' => 'nullable|numeric|min:0',
                'dimensions' => 'nullable|string',
                'images' => 'nullable|array|max:10',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'videos' => 'nullable|array|max:5',
                'videos.*' => 'file|mimes:mp4,avi,mov,wmv,flv,webm|max:10240',
                'is_featured' => 'boolean',
                'is_active' => 'boolean',
                'category_id' => 'nullable|exists:categories,id'
            ]);

            $product = Product::where('uuid', $data['id'])->firstOrFail();
            unset($data['id']); // Remove id from update data

            // Update slug if name changed
            if (isset($data['name'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Handle image uploads
            if ($request->hasFile('images')) {
                // Delete old images
                $product->deleteImages();
                
                // Store new images
                $imagePaths = Product::storeImages($request->file('images'), $data['slug'] ?? $product->slug);
                $data['images'] = $imagePaths;
            }

            // Handle video uploads
            if ($request->hasFile('videos')) {
                // Delete old videos
                $product->deleteVideos();
                
                // Store new videos
                $videoPaths = Product::storeVideos($request->file('videos'), $data['slug'] ?? $product->slug);
                $data['videos'] = $videoPaths;
            }

            $product->update($data);
            $product->load('category');

            // Add image URLs to response
            $product->image_urls = $product->image_urls;
            $product->primary_image_url = $product->primary_image_url;
            $product->video_urls = $product->video_urls;
            $product->primary_video_url = $product->primary_video_url;

            return $this->sendJsonResponse(true, 'Product updated successfully', $product);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function destroy(Request $request)
    {
        // Check permission
        $permissionCheck = $this->checkPermission('products.delete');
        if ($permissionCheck) return $permissionCheck;

        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $product = Product::where('uuid', $data['id'])->firstOrFail();
            
            // Delete associated images and videos
            $product->deleteImages();
            $product->deleteVideos();
            
            $product->delete();

            return $this->sendJsonResponse(true, 'Product deleted successfully');
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function toggleStatus(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $product = Product::where('uuid', $data['id'])->firstOrFail();
            $product->update(['is_active' => !$product->is_active]);

            return $this->sendJsonResponse(true, 'Product status updated successfully', $product);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function updateStock(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|string',
                'stock_quantity' => 'required|integer|min:0',
                'manage_stock' => 'boolean'
            ]);

            $product = Product::where('uuid', $data['id'])->firstOrFail();
            unset($data['id']); // Remove id from update data
            $product->update($data);

            return $this->sendJsonResponse(true, 'Stock updated successfully', $product);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function assignDeliveryLocations(Request $request)
    {
        try {
            $data = $request->validate([
                'product_id' => 'required|string',
                'location_ids' => 'required|array',
                'location_ids.*' => 'exists:delivery_locations,id',
                'delivery_fee' => 'nullable|numeric|min:0',
                'estimated_delivery_days' => 'nullable|integer|min:1|max:30',
                'is_available' => 'boolean'
            ]);

            $product = Product::where('uuid', $data['product_id'])->firstOrFail();

            $syncData = [];
            foreach ($data['location_ids'] as $locationId) {
                $location = DeliveryLocation::find($locationId);
                $syncData[$locationId] = [
                    'delivery_fee' => $data['delivery_fee'] ?? $location->delivery_fee,
                    'estimated_delivery_days' => $data['estimated_delivery_days'] ?? $location->estimated_delivery_days,
                    'is_available' => $data['is_available'] ?? true
                ];
            }

            $product->deliveryLocations()->sync($syncData);

            return $this->sendJsonResponse(true, 'Delivery locations assigned to product successfully');
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function removeDeliveryLocations(Request $request)
    {
        try {
            $data = $request->validate([
                'product_id' => 'required|string',
                'location_ids' => 'required|array',
                'location_ids.*' => 'exists:delivery_locations,id'
            ]);

            $product = Product::where('uuid', $data['product_id'])->firstOrFail();
            $product->deliveryLocations()->detach($data['location_ids']);

            return $this->sendJsonResponse(true, 'Delivery locations removed from product successfully');
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getDeliveryLocations(Request $request)
    {
        try {
            $data = $request->validate([
                'product_id' => 'required|string'
            ]);

            $product = Product::where('uuid', $data['product_id'])->firstOrFail();
            $locations = $product->deliveryLocations()->where('is_active', true)->get();

            return $this->sendJsonResponse(true, 'Product delivery locations retrieved successfully', $locations);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function cancelDelivery(Request $request)
    {
        try {
            $data = $request->validate([
                'product_id' => 'required|string',
                'location_id' => 'required|integer|exists:delivery_locations,id',
                'reason' => 'required|string|max:500',
                'notes' => 'nullable|string|max:1000'
            ]);

            $product = Product::where('uuid', $data['product_id'])->firstOrFail();
            $admin = auth()->user();

            $product->cancelDeliveryToLocation(
                $data['location_id'],
                $data['reason'],
                $data['notes'],
                $admin
            );

            return $this->sendJsonResponse(true, 'Product delivery cancelled successfully');
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function restoreDelivery(Request $request)
    {
        try {
            $data = $request->validate([
                'product_id' => 'required|string',
                'location_id' => 'required|integer|exists:delivery_locations,id'
            ]);

            $product = Product::where('uuid', $data['product_id'])->firstOrFail();
            $admin = auth()->user();

            $product->restoreDeliveryToLocation($data['location_id'], $admin);

            return $this->sendJsonResponse(true, 'Product delivery restored successfully');
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function reportDeliveryIssue(Request $request)
    {
        try {
            $data = $request->validate([
                'product_id' => 'required|string',
                'order_id' => 'required|string',
                'location_id' => 'required|integer|exists:delivery_locations,id',
                'issue_type' => 'required|string|in:product_unavailable,delivery_location_issue,logistics_problem,weather_issue,address_issue,customer_unavailable,other',
                'title' => 'required|string|max:255',
                'description' => 'required|string|max:1000',
                'metadata' => 'nullable|array'
            ]);

            $product = Product::where('uuid', $data['product_id'])->firstOrFail();
            $order = Order::where('uuid', $data['order_id'])->firstOrFail();
            $admin = auth()->user();

            $issue = $product->reportDeliveryIssue(
                $order->id,
                $data['location_id'],
                $data['issue_type'],
                $data['title'],
                $data['description'],
                $admin,
                $data['metadata'] ?? null
            );

            $issue->load(['order', 'deliveryLocation']);

            return $this->sendJsonResponse(true, 'Delivery issue reported successfully', $issue, 201);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getDeliveryIssues(Request $request)
    {
        try {
            $data = $request->validate([
                'product_id' => 'required|string',
                'status' => 'nullable|string|in:reported,investigating,resolved,cancelled'
            ]);

            $product = Product::where('uuid', $data['product_id'])->firstOrFail();
            $issues = $product->getDeliveryIssues($data['status'] ?? null);

            return $this->sendJsonResponse(true, 'Delivery issues retrieved successfully', $issues);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function resolveDeliveryIssue(Request $request)
    {
        try {
            $data = $request->validate([
                'issue_id' => 'required|string',
                'resolution' => 'required|string|in:delivery_cancelled,delivery_delayed,delivery_rerouted,product_replaced,refund_issued,other',
                'resolution_notes' => 'required|string|max:1000'
            ]);

            $issue = DeliveryIssue::where('uuid', $data['issue_id'])->firstOrFail();
            $admin = auth()->user();

            $issue->resolve(
                $data['resolution'],
                $data['resolution_notes'],
                $admin
            );

            $issue->load(['order', 'product', 'deliveryLocation']);

            return $this->sendJsonResponse(true, 'Delivery issue resolved successfully', $issue);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function cancelDeliveryIssue(Request $request)
    {
        try {
            $data = $request->validate([
                'issue_id' => 'required|string',
                'reason' => 'required|string|max:500'
            ]);

            $issue = DeliveryIssue::where('uuid', $data['issue_id'])->firstOrFail();
            $admin = auth()->user();

            $issue->cancel($data['reason'], $admin);

            $issue->load(['order', 'product', 'deliveryLocation']);

            return $this->sendJsonResponse(true, 'Delivery issue cancelled successfully', $issue);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getDeliveryIssueStats()
    {
        try {
            $stats = [
                'total_issues' => DeliveryIssue::count(),
                'reported_issues' => DeliveryIssue::where('status', 'reported')->count(),
                'investigating_issues' => DeliveryIssue::where('status', 'investigating')->count(),
                'resolved_issues' => DeliveryIssue::where('status', 'resolved')->count(),
                'cancelled_issues' => DeliveryIssue::where('status', 'cancelled')->count(),
                'issue_types' => DeliveryIssue::selectRaw('issue_type, count(*) as count')
                    ->groupBy('issue_type')
                    ->get(),
                'resolutions' => DeliveryIssue::selectRaw('resolution, count(*) as count')
                    ->whereNotNull('resolution')
                    ->groupBy('resolution')
                    ->get(),
            ];

            return $this->sendJsonResponse(true, 'Delivery issue statistics retrieved successfully', $stats);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getCancelledDeliveries(Request $request)
    {
        try {
            $data = $request->validate([
                'product_id' => 'required|string'
            ]);

            $product = Product::where('uuid', $data['product_id'])->firstOrFail();
            $cancelledLocations = $product->getCancelledDeliveryLocations();

            return $this->sendJsonResponse(true, 'Cancelled delivery locations retrieved successfully', $cancelledLocations);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getActiveDeliveries(Request $request)
    {
        try {
            $data = $request->validate([
                'product_id' => 'required|string'
            ]);

            $product = Product::where('uuid', $data['product_id'])->firstOrFail();
            $activeLocations = $product->getActiveDeliveryLocations();

            return $this->sendJsonResponse(true, 'Active delivery locations retrieved successfully', $activeLocations);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}
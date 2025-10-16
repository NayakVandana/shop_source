<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use App\Models\Product;
use App\Models\User;
use App\Traits\AdminPermissionTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception;

class AdminReviewController extends Controller
{
    use AdminPermissionTrait;

    public function index(Request $request)
    {
        // Check permission
        $permissionCheck = $this->checkPermission('reviews.list');
        if ($permissionCheck) return $permissionCheck;

        try {
            $query = ProductReview::with(['product', 'user', 'order']);

            // Search
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('comment', 'like', "%{$search}%")
                      ->orWhereHas('product', function ($productQuery) use ($search) {
                          $productQuery->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('user', function ($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Filter by product
            if ($request->has('product_id')) {
                $query->where('product_id', $request->get('product_id'));
            }

            // Filter by rating
            if ($request->has('rating')) {
                $query->where('rating', $request->get('rating'));
            }

            // Filter by approval status
            if ($request->has('is_approved')) {
                $query->where('is_approved', $request->get('is_approved'));
            }

            // Filter by verified purchase
            if ($request->has('is_verified_purchase')) {
                $query->where('is_verified_purchase', $request->get('is_verified_purchase'));
            }

            // Filter by featured status
            if ($request->has('is_featured')) {
                $query->where('is_featured', $request->get('is_featured'));
            }

            // Sort
            $sortBy = $request->get('sort_by', 'reviewed_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $reviews = $query->paginate($perPage);

            // Add image URLs to each review
            $reviews->getCollection()->transform(function ($review) {
                $review->image_urls = $review->image_urls;
                return $review;
            });

            return $this->sendJsonResponse(true, 'Reviews retrieved successfully', $reviews);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function show(Request $request)
    {
        // Check permission
        $permissionCheck = $this->checkPermission('reviews.view');
        if ($permissionCheck) return $permissionCheck;

        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $review = ProductReview::with(['product', 'user', 'order', 'helpfulVotes.user'])
                ->where('uuid', $data['id'])
                ->firstOrFail();

            // Add image URLs to response
            $review->image_urls = $review->image_urls;

            return $this->sendJsonResponse(true, 'Review retrieved successfully', $review);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function update(Request $request)
    {
        // Check permission
        $permissionCheck = $this->checkPermission('reviews.update');
        if ($permissionCheck) return $permissionCheck;

        try {
            $data = $request->validate([
                'id' => 'required|string',
                'rating' => 'sometimes|required|integer|min:1|max:5',
                'title' => 'nullable|string|max:255',
                'comment' => 'nullable|string|max:2000',
                'is_approved' => 'boolean',
                'is_featured' => 'boolean',
                'images' => 'nullable|array|max:5',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048'
            ]);

            $review = ProductReview::where('uuid', $data['id'])->firstOrFail();
            unset($data['id']); // Remove id from update data

            // Handle image uploads
            if ($request->hasFile('images')) {
                // Delete old images
                $review->deleteImages();
                
                // Store new images
                $imagePaths = ProductReview::storeImages($request->file('images'), $review->product->slug);
                $data['images'] = $imagePaths;
            }

            $review->update($data);

            // Add image URLs to response
            $review->image_urls = $review->image_urls;

            return $this->sendJsonResponse(true, 'Review updated successfully', $review);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function destroy(Request $request)
    {
        // Check permission
        $permissionCheck = $this->checkPermission('reviews.delete');
        if ($permissionCheck) return $permissionCheck;

        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $review = ProductReview::where('uuid', $data['id'])->firstOrFail();
            
            // Delete associated images
            $review->deleteImages();
            
            $review->delete();

            return $this->sendJsonResponse(true, 'Review deleted successfully');
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function approve(Request $request)
    {
        // Check permission
        $permissionCheck = $this->checkPermission('reviews.approve');
        if ($permissionCheck) return $permissionCheck;

        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $review = ProductReview::where('uuid', $data['id'])->firstOrFail();
            $review->update(['is_approved' => true]);

            return $this->sendJsonResponse(true, 'Review approved successfully', $review);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function reject(Request $request)
    {
        // Check permission
        $permissionCheck = $this->checkPermission('reviews.approve');
        if ($permissionCheck) return $permissionCheck;

        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $review = ProductReview::where('uuid', $data['id'])->firstOrFail();
            $review->update(['is_approved' => false]);

            return $this->sendJsonResponse(true, 'Review rejected successfully', $review);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function toggleFeatured(Request $request)
    {
        // Check permission
        $permissionCheck = $this->checkPermission('reviews.feature');
        if ($permissionCheck) return $permissionCheck;

        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $review = ProductReview::where('uuid', $data['id'])->firstOrFail();
            $review->update(['is_featured' => !$review->is_featured]);

            return $this->sendJsonResponse(true, 'Review featured status updated successfully', $review);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function bulkApprove(Request $request)
    {
        // Check permission
        $permissionCheck = $this->checkPermission('reviews.approve');
        if ($permissionCheck) return $permissionCheck;

        try {
            $data = $request->validate([
                'review_ids' => 'required|array',
                'review_ids.*' => 'string|exists:product_reviews,uuid'
            ]);

            $updated = ProductReview::whereIn('uuid', $data['review_ids'])
                ->update(['is_approved' => true]);

            return $this->sendJsonResponse(true, "{$updated} reviews approved successfully");
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function bulkReject(Request $request)
    {
        // Check permission
        $permissionCheck = $this->checkPermission('reviews.approve');
        if ($permissionCheck) return $permissionCheck;

        try {
            $data = $request->validate([
                'review_ids' => 'required|array',
                'review_ids.*' => 'string|exists:product_reviews,uuid'
            ]);

            $updated = ProductReview::whereIn('uuid', $data['review_ids'])
                ->update(['is_approved' => false]);

            return $this->sendJsonResponse(true, "{$updated} reviews rejected successfully");
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function bulkDelete(Request $request)
    {
        // Check permission
        $permissionCheck = $this->checkPermission('reviews.delete');
        if ($permissionCheck) return $permissionCheck;

        try {
            $data = $request->validate([
                'review_ids' => 'required|array',
                'review_ids.*' => 'string|exists:product_reviews,uuid'
            ]);

            $reviews = ProductReview::whereIn('uuid', $data['review_ids'])->get();
            
            foreach ($reviews as $review) {
                $review->deleteImages();
                $review->delete();
            }

            return $this->sendJsonResponse(true, count($reviews) . " reviews deleted successfully");
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getStats()
    {
        // Check permission
        $permissionCheck = $this->checkPermission('reviews.stats');
        if ($permissionCheck) return $permissionCheck;

        try {
            $stats = [
                'total_reviews' => ProductReview::count(),
                'approved_reviews' => ProductReview::where('is_approved', true)->count(),
                'pending_reviews' => ProductReview::where('is_approved', false)->count(),
                'featured_reviews' => ProductReview::where('is_featured', true)->count(),
                'verified_purchases' => ProductReview::where('is_verified_purchase', true)->count(),
                'average_rating' => round(ProductReview::where('is_approved', true)->avg('rating'), 1),
                'rating_distribution' => ProductReview::selectRaw('rating, count(*) as count')
                    ->where('is_approved', true)
                    ->groupBy('rating')
                    ->orderBy('rating', 'desc')
                    ->get(),
                'recent_reviews' => ProductReview::where('is_approved', true)
                    ->orderBy('reviewed_at', 'desc')
                    ->limit(5)
                    ->with(['product:id,name', 'user:id,name'])
                    ->get(),
            ];

            return $this->sendJsonResponse(true, 'Review statistics retrieved successfully', $stats);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getProductReviews(Request $request)
    {
        // Check permission
        $permissionCheck = $this->checkPermission('reviews.list');
        if ($permissionCheck) return $permissionCheck;

        try {
            $data = $request->validate([
                'product_id' => 'required|string'
            ]);

            $product = Product::where('uuid', $data['product_id'])->firstOrFail();
            
            $query = $product->reviews()->with(['user', 'order']);

            // Filter by rating
            if ($request->has('rating')) {
                $query->where('rating', $request->get('rating'));
            }

            // Filter by approval status
            if ($request->has('is_approved')) {
                $query->where('is_approved', $request->get('is_approved'));
            }

            // Sort
            $sortBy = $request->get('sort_by', 'reviewed_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $reviews = $query->paginate($perPage);

            // Add image URLs to each review
            $reviews->getCollection()->transform(function ($review) {
                $review->image_urls = $review->image_urls;
                return $review;
            });

            return $this->sendJsonResponse(true, 'Product reviews retrieved successfully', $reviews);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}
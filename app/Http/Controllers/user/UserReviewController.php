<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\ProductReview;
use App\Models\Product;
use App\Models\ReviewHelpfulVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class UserReviewController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = Auth::user();
            $query = $user->reviews()->with(['product', 'order']);

            // Search
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('comment', 'like', "%{$search}%")
                      ->orWhereHas('product', function ($productQuery) use ($search) {
                          $productQuery->where('name', 'like', "%{$search}%");
                      });
                });
            }

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

            return $this->sendJsonResponse(true, 'Your reviews retrieved successfully', $reviews);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = Auth::user();
            
            $data = $request->validate([
                'product_id' => 'required|string|exists:products,uuid',
                'order_id' => 'nullable|string|exists:orders,uuid',
                'rating' => 'required|integer|min:1|max:5',
                'title' => 'nullable|string|max:255',
                'comment' => 'nullable|string|max:2000',
                'images' => 'nullable|array|max:5',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'metadata' => 'nullable|array'
            ]);

            $product = Product::where('uuid', $data['product_id'])->firstOrFail();
            
            // Check if user can review this product
            if (!$product->canUserReview($user->id)) {
                return $this->sendJsonResponse(false, 'You cannot review this product. You must have purchased it and not already reviewed it.', null, 403);
            }

            // Check if user already reviewed this product
            if ($product->hasUserReviewed($user->id)) {
                return $this->sendJsonResponse(false, 'You have already reviewed this product.', null, 409);
            }

            $data['user_id'] = $user->id;
            $data['product_id'] = $product->id;
            
            // Set order_id if provided
            if (isset($data['order_id'])) {
                $order = \App\Models\Order::where('uuid', $data['order_id'])->first();
                if ($order) {
                    $data['order_id'] = $order->id;
                    $data['is_verified_purchase'] = true;
                }
            }

            // Handle image uploads
            if ($request->hasFile('images')) {
                $imagePaths = ProductReview::storeImages($request->file('images'), $product->slug);
                $data['images'] = $imagePaths;
            } else {
                $data['images'] = [];
            }

            $review = ProductReview::create($data);
            $review->load(['product', 'order']);

            // Add image URLs to response
            $review->image_urls = $review->image_urls;

            return $this->sendJsonResponse(true, 'Review submitted successfully', $review, 201);
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

            $user = Auth::user();
            $review = $user->reviews()
                ->with(['product', 'order', 'helpfulVotes.user'])
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
        try {
            $data = $request->validate([
                'id' => 'required|string',
                'rating' => 'sometimes|required|integer|min:1|max:5',
                'title' => 'nullable|string|max:255',
                'comment' => 'nullable|string|max:2000',
                'images' => 'nullable|array|max:5',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'metadata' => 'nullable|array'
            ]);

            $user = Auth::user();
            $review = $user->reviews()->where('uuid', $data['id'])->firstOrFail();

            // Check if user can edit this review
            if (!$review->canEdit($user->id)) {
                return $this->sendJsonResponse(false, 'You can only edit reviews within 30 days of posting.', null, 403);
            }

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
            $review->load(['product', 'order']);

            // Add image URLs to response
            $review->image_urls = $review->image_urls;

            return $this->sendJsonResponse(true, 'Review updated successfully', $review);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $user = Auth::user();
            $review = $user->reviews()->where('uuid', $data['id'])->firstOrFail();

            // Check if user can delete this review
            if (!$review->canDelete($user->id)) {
                return $this->sendJsonResponse(false, 'You can only delete reviews within 7 days of posting.', null, 403);
            }

            // Delete associated images
            $review->deleteImages();
            
            $review->delete();

            return $this->sendJsonResponse(true, 'Review deleted successfully');
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getProductReviews(Request $request)
    {
        try {
            $data = $request->validate([
                'product_id' => 'required|string'
            ]);

            $product = Product::where('uuid', $data['product_id'])->firstOrFail();
            
            $query = $product->approvedReviews()->with(['user:id,name']);

            // Filter by rating
            if ($request->has('rating')) {
                $query->where('rating', $request->get('rating'));
            }

            // Filter by verified purchase
            if ($request->has('verified_only')) {
                $query->where('is_verified_purchase', true);
            }

            // Sort options
            $sortBy = $request->get('sort_by', 'reviewed_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            switch ($sortBy) {
                case 'most_helpful':
                    $query->orderByRaw('(helpful_count - not_helpful_count) DESC');
                    break;
                case 'highest_rated':
                    $query->orderBy('rating', 'desc');
                    break;
                case 'lowest_rated':
                    $query->orderBy('rating', 'asc');
                    break;
                case 'newest':
                    $query->orderBy('reviewed_at', 'desc');
                    break;
                case 'oldest':
                    $query->orderBy('reviewed_at', 'asc');
                    break;
                default:
                    $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 10);
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

    public function voteHelpful(Request $request)
    {
        try {
            $data = $request->validate([
                'review_id' => 'required|string|exists:product_reviews,uuid',
                'is_helpful' => 'required|boolean'
            ]);

            $user = Auth::user();
            $review = ProductReview::where('uuid', $data['review_id'])->firstOrFail();

            // Check if user can vote (not their own review)
            if ($review->user_id === $user->id) {
                return $this->sendJsonResponse(false, 'You cannot vote on your own review.', null, 403);
            }

            // Check if user already voted
            $existingVote = ReviewHelpfulVote::where('user_id', $user->id)
                ->where('review_id', $review->id)
                ->first();

            if ($existingVote) {
                // Update existing vote
                $existingVote->update(['is_helpful' => $data['is_helpful']]);
            } else {
                // Create new vote
                ReviewHelpfulVote::create([
                    'user_id' => $user->id,
                    'review_id' => $review->id,
                    'is_helpful' => $data['is_helpful']
                ]);
            }

            // Update review counts
            $helpfulCount = ReviewHelpfulVote::where('review_id', $review->id)
                ->where('is_helpful', true)
                ->count();
            
            $notHelpfulCount = ReviewHelpfulVote::where('review_id', $review->id)
                ->where('is_helpful', false)
                ->count();

            $review->update([
                'helpful_count' => $helpfulCount,
                'not_helpful_count' => $notHelpfulCount
            ]);

            return $this->sendJsonResponse(true, 'Vote recorded successfully', [
                'helpful_count' => $helpfulCount,
                'not_helpful_count' => $notHelpfulCount,
                'helpful_percentage' => $review->helpful_percentage
            ]);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function removeVote(Request $request)
    {
        try {
            $data = $request->validate([
                'review_id' => 'required|string|exists:product_reviews,uuid'
            ]);

            $user = Auth::user();
            $review = ProductReview::where('uuid', $data['review_id'])->firstOrFail();

            // Remove user's vote
            ReviewHelpfulVote::where('user_id', $user->id)
                ->where('review_id', $review->id)
                ->delete();

            // Update review counts
            $helpfulCount = ReviewHelpfulVote::where('review_id', $review->id)
                ->where('is_helpful', true)
                ->count();
            
            $notHelpfulCount = ReviewHelpfulVote::where('review_id', $review->id)
                ->where('is_helpful', false)
                ->count();

            $review->update([
                'helpful_count' => $helpfulCount,
                'not_helpful_count' => $notHelpfulCount
            ]);

            return $this->sendJsonResponse(true, 'Vote removed successfully', [
                'helpful_count' => $helpfulCount,
                'not_helpful_count' => $notHelpfulCount,
                'helpful_percentage' => $review->helpful_percentage
            ]);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getProductRatingSummary(Request $request)
    {
        try {
            $data = $request->validate([
                'product_id' => 'required|string'
            ]);

            $product = Product::where('uuid', $data['product_id'])->firstOrFail();

            $summary = [
                'average_rating' => $product->average_rating,
                'total_reviews' => $product->total_reviews,
                'rating_distribution' => $product->rating_distribution,
                'recent_reviews' => $product->getRecentReviews(3),
                'featured_reviews' => $product->getFeaturedReviews(2),
                'can_review' => $product->canUserReview(Auth::id()),
                'user_review' => $product->getUserReview(Auth::id())
            ];

            return $this->sendJsonResponse(true, 'Product rating summary retrieved successfully', $summary);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}
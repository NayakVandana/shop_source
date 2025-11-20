<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Discount;
use App\Models\RecentlyViewedProduct;
use App\Models\UserToken;
use App\Helpers\SessionService;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    /**
     * Format product data with calculated fields
     */
    protected function formatProductData($product)
    {
        $productArray = $product->toArray();
        
        // Add image URLs
        if ($product->relationLoaded('media')) {
            $images = $product->media->where('type', 'image');
            $productArray['image_urls'] = $images->pluck('url')->toArray();
            $primaryImage = $images->where('is_primary', true)->first() ?? $images->first();
            $productArray['primary_image_url'] = $primaryImage ? $primaryImage->url : asset('images/placeholder.svg');
            
            $videos = $product->media->where('type', 'video');
            $productArray['video_urls'] = $videos->pluck('url')->toArray();
            $primaryVideo = $videos->where('is_primary', true)->first() ?? $videos->first();
            $productArray['primary_video_url'] = $primaryVideo ? $primaryVideo->url : null;
        } else {
            $productArray['image_urls'] = [];
            $productArray['primary_image_url'] = asset('images/placeholder.svg');
            $productArray['video_urls'] = [];
            $productArray['primary_video_url'] = null;
        }
        
        // Calculate discount info
        $productArray['discount_info'] = $this->calculateProductDiscountInfo($product);
        $productArray['final_price'] = $productArray['discount_info'] 
            ? $productArray['discount_info']['final_price'] 
            : ($product->sale_price ?? $product->price);
        
        return $productArray;
    }

    /**
     * Calculate discount information for a product
     */
    protected function calculateProductDiscountInfo(Product $product)
    {
        if (!$product->relationLoaded('discounts')) {
            $product->load('discounts');
        }

        $validDiscounts = $product->discounts->filter(function ($discount) {
            if (!$discount->is_active) {
                return false;
            }

            $now = now();
            if ($discount->start_date && $now->lt($discount->start_date)) {
                return false;
            }

            if ($discount->end_date && $now->gt($discount->end_date)) {
                return false;
            }

            if ($discount->usage_limit && $discount->usage_count >= $discount->usage_limit) {
                return false;
            }

            return true;
        });

        if ($validDiscounts->isEmpty()) {
            return null;
        }

        $basePrice = $product->sale_price ?? $product->price;
        $bestDiscount = $validDiscounts->sortByDesc(function ($discount) use ($basePrice) {
            return $this->calculateDiscountAmount($discount, $basePrice);
        })->first();

        $discountAmount = $this->calculateDiscountAmount($bestDiscount, $basePrice);
        $finalPrice = max(0, $basePrice - $discountAmount);

        return [
            'discount_id' => $bestDiscount->id,
            'discount_uuid' => $bestDiscount->uuid,
            'discount_name' => $bestDiscount->name,
            'discount_type' => $bestDiscount->type,
            'discount_value' => (float) $bestDiscount->value,
            'discount_amount' => (float) $discountAmount,
            'original_price' => (float) $basePrice,
            'final_price' => (float) $finalPrice,
            'discount_percentage' => $basePrice > 0 ? round(($discountAmount / $basePrice) * 100, 2) : 0,
            'display_text' => $bestDiscount->type === 'percentage' 
                ? number_format($bestDiscount->value, 2) . "% OFF" 
                : "$" . number_format($bestDiscount->value, 2) . " OFF",
        ];
    }

    /**
     * Calculate discount amount for a discount and price
     */
    protected function calculateDiscountAmount(Discount $discount, $price): float
    {
        if ($discount->min_purchase_amount && $price < $discount->min_purchase_amount) {
            return 0;
        }

        $discountAmount = 0;
        
        if ($discount->type === 'percentage') {
            $discountAmount = ($price * $discount->value) / 100;
        } else {
            $discountAmount = $discount->value;
        }

        if ($discount->max_discount_amount && $discountAmount > $discount->max_discount_amount) {
            $discountAmount = $discount->max_discount_amount;
        }

        return min($discountAmount, $price);
    }

    public function index(Request $request)
    {
        try {
            $query = Product::with(['category', 'media', 'discounts'])->where('is_active', true);

            // Search
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            // Filter by category
            if ($request->has('category_id')) {
                $query->where('category_id', $request->get('category_id'));
            }

            // Filter by price range
            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->get('min_price'));
            }

            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->get('max_price'));
            }

            // Filter by stock status
            if ($request->has('in_stock')) {
                $query->where('in_stock', $request->get('in_stock'));
            }

            // Filter featured products
            if ($request->has('featured')) {
                $query->where('is_featured', true);
            }


            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            if ($sortBy === 'price') {
                $query->orderByRaw('CASE WHEN sale_price IS NOT NULL THEN sale_price ELSE price END ' . $sortOrder);
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }

            // Pagination
            $perPage = $request->get('per_page', 12);
            $products = $query->paginate($perPage);

            // Format products with calculated fields
            $products->getCollection()->transform(function ($product) {
                return $this->formatProductData($product);
            });

            return $this->sendJsonResponse(true, 'Products retrieved successfully', $products);
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

            $product = Product::with(['category', 'media', 'discounts', 'variations'])
                ->where('is_active', true)
                ->where('uuid', $data['id'])
                ->firstOrFail();
            
            $formattedProduct = $this->formatProductData($product);
            
            return $this->sendJsonResponse(true, 'Product retrieved successfully', $formattedProduct);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Track a product view
     */
    public function trackView(Request $request)
    {
        try {
            $data = $request->validate([
                'product_id' => 'required|string'
            ]);

            // Manually authenticate user if token is present (since route is public)
            $user = $request->user();
            if (!$user) {
                $token = $request->bearerToken() ?? $request->get('Authorization');
                if ($token) {
                    // Remove 'Bearer ' prefix if present
                    $token = str_replace('Bearer ', '', $token);
                    
                    $userToken = UserToken::where(function ($q) use ($token) {
                        $q->where('web_access_token', $token);
                        $q->orWhere('app_access_token', $token);
                    })->first();
                    
                    if ($userToken && $userToken->user) {
                        $user = $userToken->user;
                        Auth::login($user);
                    }
                }
            }
            
            // Get or create session ID using centralized SessionService
            $sessionId = SessionService::getOrCreateSessionId($request);

            // Find product by UUID
            $product = Product::where('uuid', $data['product_id'])->first();
            if (!$product) {
                return $this->sendJsonResponse(false, 'Product not found', null, 404);
            }

            // Use updateOrCreate to handle duplicates - updates existing or creates new
            // Match unique constraints: ['user_id', 'product_id'] for authenticated users
            // or ['session_id', 'product_id'] for guest users
            $recentlyViewed = DB::transaction(function () use ($user, $sessionId, $product) {
                if ($user) {
                    // For authenticated users, match on user_id and product_id
                    return RecentlyViewedProduct::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'product_id' => $product->id,
                        ],
                        [
                            'session_id' => null,
                            'viewed_at' => now(),
                        ]
                    );
                } else {
                    // For guest users, match on session_id and product_id
                    return RecentlyViewedProduct::updateOrCreate(
                        [
                            'session_id' => $sessionId,
                            'product_id' => $product->id,
                        ],
                        [
                            'user_id' => null,
                            'viewed_at' => now(),
                        ]
                    );
                }
            });

            // Limit to 20 most recent views per user/session (across all products)
            $query = RecentlyViewedProduct::query();
            if ($user) {
                $query->where('user_id', $user->id);
            } else {
                if ($sessionId) {
                    $query->where('session_id', $sessionId);
                }
            }
            
            $totalViews = $query->count();
            if ($totalViews > 20) {
                // Delete oldest views
                $oldestViews = $query->orderBy('viewed_at', 'asc')
                    ->limit($totalViews - 20)
                    ->pluck('id');
                RecentlyViewedProduct::whereIn('id', $oldestViews)->delete();
            }

            // Add session_id to response data (for mobile apps)
            $responseData = $recentlyViewed->toArray();
            $responseData['session_id'] = $sessionId;
            
            $response = $this->sendJsonResponse(true, 'Product view tracked successfully', $responseData);
            
            // Always set/update session cookie to maintain persistence (for web browsers)
            return SessionService::setSessionCookie($response, $sessionId);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Get recently viewed products
     */
    public function recentlyViewed(Request $request)
    {
        try {
            $user = $request->user();
            
            // Get session ID using centralized SessionService
            $sessionId = SessionService::getSessionId($request);

            $query = RecentlyViewedProduct::with(['product.category', 'product.media', 'product.discounts'])
                ->whereHas('product', function($q) {
                    $q->where('is_active', true);
                })
                ->orderBy('viewed_at', 'desc');

            if ($user) {
                $query->where('user_id', $user->id);
            } else {
                if ($sessionId) {
                    $query->where('session_id', $sessionId);
                } else {
                    // No session ID, return empty
                    return $this->sendJsonResponse(true, 'No recently viewed products', []);
                }
            }

            $limit = $request->get('limit', 10);
            $recentlyViewed = $query->limit($limit)->get();

            // Format products
            $formattedProducts = $recentlyViewed->map(function ($item) {
                return $this->formatProductData($item->product);
            });

            return $this->sendJsonResponse(true, 'Recently viewed products retrieved successfully', $formattedProducts);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Remove a product from recently viewed
     */
    public function removeRecentlyViewed(Request $request)
    {
        try {
            $data = $request->validate([
                'product_id' => 'required|string'
            ]);

            $user = $request->user();
            
            // Get session ID using centralized SessionService
            $sessionId = SessionService::getSessionId($request);

            // Find product by UUID
            $product = Product::where('uuid', $data['product_id'])->first();
            if (!$product) {
                return $this->sendJsonResponse(false, 'Product not found', null, 404);
            }

            $query = RecentlyViewedProduct::where('product_id', $product->id);
            
            if ($user) {
                $query->where('user_id', $user->id);
            } else {
                if ($sessionId) {
                    $query->where('session_id', $sessionId);
                } else {
                    return $this->sendJsonResponse(false, 'Session not found', null, 404);
                }
            }

            $deleted = $query->delete();

            if ($deleted) {
                return $this->sendJsonResponse(true, 'Product removed from recently viewed successfully', null);
            } else {
                return $this->sendJsonResponse(false, 'Product not found in recently viewed', null, 404);
            }
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Clear all recently viewed products
     */
    public function clearRecentlyViewed(Request $request)
    {
        try {
            $user = $request->user();
            
            // Get session ID using centralized SessionService
            $sessionId = SessionService::getSessionId($request);

            $query = RecentlyViewedProduct::query();
            
            if ($user) {
                $query->where('user_id', $user->id);
            } else {
                if ($sessionId) {
                    $query->where('session_id', $sessionId);
                } else {
                    return $this->sendJsonResponse(false, 'Session not found', null, 404);
                }
            }

            $deleted = $query->delete();

            return $this->sendJsonResponse(true, 'All recently viewed products cleared successfully', ['deleted_count' => $deleted]);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function featured(Request $request)
    {
        try {
            $products = Product::with(['category', 'media', 'discounts'])
                ->where('is_active', true)
                ->where('is_featured', true)
                ->orderBy('created_at', 'desc')
                ->limit(8)
                ->get();
            
            $formattedProducts = $products->map(function ($product) {
                return $this->formatProductData($product);
            });
            
            return $this->sendJsonResponse(true, 'Featured products retrieved successfully', $formattedProducts);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function related(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $product = Product::where('uuid', $data['id'])->firstOrFail();
            
            $relatedProducts = Product::with(['category', 'media', 'discounts'])
                ->where('is_active', true)
                ->where('uuid', '!=', $data['id'])
                ->where('category_id', $product->category_id)
                ->limit(4)
                ->get();
            
            $formattedProducts = $relatedProducts->map(function ($product) {
                return $this->formatProductData($product);
            });
            
            return $this->sendJsonResponse(true, 'Related products retrieved successfully', $formattedProducts);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}

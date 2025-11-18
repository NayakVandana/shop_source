<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Discount;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
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

        // Apply max discount limit if set
        if ($discount->max_discount_amount && $discountAmount > $discount->max_discount_amount) {
            $discountAmount = $discount->max_discount_amount;
        }

        // Don't exceed the price
        return min($discountAmount, $price);
    }

    /**
     * Format cart data for JSON response
     */
    protected function formatCartResponse(Cart $cart): array
    {
        $cart->load(['items.product.category', 'items.product.media']);
        $cartData = $cart->toArray();
        
        // Ensure items is always an array in the response
        if (!isset($cartData['items']) || !is_array($cartData['items'])) {
            $cartData['items'] = $cart->items ? $cart->items->toArray() : [];
        }
        
        // Calculate totals in controller (business logic)
        $items = $cart->items;
        $totalItems = $items->sum('quantity');
        $subtotal = $items->sum(function ($item) {
            return (($item->price ?? 0) - ($item->discount_amount ?? 0)) * ($item->quantity ?? 0);
        });
        $totalDiscount = $items->sum(function ($item) {
            return ($item->discount_amount ?? 0) * ($item->quantity ?? 0);
        });
        
        return [
            'cart' => $cartData,
            'total_items' => $totalItems,
            'subtotal' => $subtotal,
            'total_discount' => $totalDiscount,
        ];
    }

    /**
     * Get or create cart for current user/guest
     */
    protected function getOrCreateCart(Request $request): Cart
    {
        $user = $request->user();
        
        // For guest users, prioritize cookie over session for better persistence
        // For authenticated users, we can use session or cookie
        $sessionId = null;
        
        // First, try to get from cookie (most reliable for guest persistence)
        $sessionId = $request->cookie('cart_session_id');
        
        // If no cookie, try session
        if (!$sessionId) {
            try {
                if ($request->hasSession()) {
                    $sessionId = $request->session()->getId();
                }
            } catch (\Exception $e) {
                // Session not available
            }
        }
        
        // If still no session ID, generate a new one for guest
        if (!$sessionId) {
            $sessionId = 'guest_' . uniqid() . '_' . time();
        }

        if ($user) {
            // Get or create cart for authenticated user
            $userCart = Cart::firstOrCreate(
                ['user_id' => $user->id],
                ['session_id' => $sessionId]
            );

            // If there's a guest cart with items, merge it into user cart
            if ($sessionId) {
                $guestCart = Cart::with('items')->where('session_id', $sessionId)
                    ->whereNull('user_id')
                    ->where('id', '!=', $userCart->id)
                    ->first();

                if ($guestCart && $guestCart->items && $guestCart->items->isNotEmpty()) {
                    // Merge guest cart items into user cart
                    foreach ($guestCart->items as $guestItem) {
                        $existingItem = CartItem::where('cart_id', $userCart->id)
                            ->where('product_id', $guestItem->product_id)
                            ->first();

                        if ($existingItem) {
                            // Update quantity if product already exists
                            $existingItem->update([
                                'quantity' => $existingItem->quantity + $guestItem->quantity,
                                'price' => $guestItem->price, // Use latest price
                                'discount_amount' => $guestItem->discount_amount,
                            ]);
                        } else {
                            // Move item to user cart
                            $guestItem->update(['cart_id' => $userCart->id]);
                        }
                    }

                    // Delete empty guest cart
                    $guestCart->items()->delete();
                    $guestCart->delete();
                }
            }

            return $userCart;
        } else {
            // Get or create cart for guest user
            $cart = Cart::firstOrCreate(
                ['session_id' => $sessionId],
                ['user_id' => null]
            );
            
            // Update session_id if it was generated new (to ensure it's saved)
            if ($cart->session_id !== $sessionId) {
                $cart->update(['session_id' => $sessionId]);
            }
        }

        return $cart;
    }

    /**
     * Get cart with items
     */
    public function index(Request $request)
    {
        try {
            $cart = $this->getOrCreateCart($request);
            $cartData = $this->formatCartResponse($cart);

            $response = $this->sendJsonResponse(true, 'Cart retrieved successfully', $cartData);

            // Always set/update cookie for guest session to maintain cart persistence
            if (!$request->user() && $cart->session_id) {
                $response->cookie('cart_session_id', $cart->session_id, 60 * 24 * 30, '/', null, false, false); // 30 days, httpOnly false for JS access
            }

            return $response;
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Add item to cart
     */
    public function add(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id' => 'required',
                'quantity' => 'required|integer|min:1',
                'size' => 'nullable|string|max:50',
                'color' => 'nullable|string|max:50',
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', ['errors' => $validator->errors()], 422);
            }

            // Find product by UUID (product_id can be UUID string or numeric ID)
            // Try UUID first, then fall back to numeric ID
            $product = Product::with(['discounts'])
                ->where(function($query) use ($request) {
                    $query->where('uuid', $request->product_id)
                          ->orWhere('id', $request->product_id);
                })
                ->firstOrFail();

            // Check if product is active
            if (!$product->is_active) {
                return $this->sendJsonResponse(false, 'Product is not available', null, 400);
            }

            // Check stock - prioritize variation stock if size/color provided
            if ($product->manage_stock) {
                if ($request->size || $request->color) {
                    // Check variation stock
                    $variation = ProductVariation::where('product_id', $product->id)
                        ->where('size', $request->size ?? null)
                        ->where('color', $request->color ?? null)
                        ->first();
                    
                    if ($variation) {
                        // Check variation stock
                        if ($variation->stock_quantity < $request->quantity) {
                            return $this->sendJsonResponse(false, 'Insufficient stock available for selected size/color', null, 400);
                        }
                        if (!$variation->in_stock) {
                            return $this->sendJsonResponse(false, 'Selected size/color combination is out of stock', null, 400);
                        }
                    } else {
                        // Variation doesn't exist, check general product stock
                        if ($product->stock_quantity < $request->quantity) {
                            return $this->sendJsonResponse(false, 'Insufficient stock available', null, 400);
                        }
                    }
                } else {
                    // No size/color specified, check general product stock
                    if ($product->stock_quantity < $request->quantity) {
                        return $this->sendJsonResponse(false, 'Insufficient stock available', null, 400);
                    }
                }
            }

            $cart = $this->getOrCreateCart($request);

            // Calculate price and discount
            $basePrice = $product->sale_price ?? $product->price;
            $discountInfo = $this->calculateProductDiscountInfo($product);
            $discountAmount = $discountInfo ? $discountInfo['discount_amount'] : 0;
            $finalPrice = $discountInfo ? $discountInfo['final_price'] : $basePrice;

            // Check if item already exists in cart (same product, size, and color)
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->where('size', $request->size ?? null)
                ->where('color', $request->color ?? null)
                ->first();

            if ($cartItem) {
                // Update quantity
                $newQuantity = $cartItem->quantity + $request->quantity;
                
                // Check stock again - prioritize variation stock if size/color provided
                if ($product->manage_stock) {
                    if ($request->size || $request->color) {
                        $variation = ProductVariation::where('product_id', $product->id)
                            ->where('size', $request->size ?? null)
                            ->where('color', $request->color ?? null)
                            ->first();
                        
                        if ($variation) {
                            if ($variation->stock_quantity < $newQuantity) {
                                return $this->sendJsonResponse(false, 'Insufficient stock available for selected size/color', null, 400);
                            }
                        } else {
                            if ($product->stock_quantity < $newQuantity) {
                                return $this->sendJsonResponse(false, 'Insufficient stock available', null, 400);
                            }
                        }
                    } else {
                        if ($product->stock_quantity < $newQuantity) {
                            return $this->sendJsonResponse(false, 'Insufficient stock available', null, 400);
                        }
                    }
                }

                $cartItem->update([
                    'quantity' => $newQuantity,
                    'price' => $finalPrice,
                    'discount_amount' => $discountAmount,
                ]);
            } else {
                // Create new cart item
                $cartItem = CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'quantity' => $request->quantity,
                    'price' => $finalPrice,
                    'discount_amount' => $discountAmount,
                    'size' => $request->size ?? null,
                    'color' => $request->color ?? null,
                ]);
            }

            $cartData = $this->formatCartResponse($cart);

            $response = $this->sendJsonResponse(true, 'Item added to cart successfully', $cartData);

            // Always set/update cookie for guest session to maintain cart persistence
            if (!$request->user() && $cart->session_id) {
                $response->cookie('cart_session_id', $cart->session_id, 60 * 24 * 30, '/', null, false, false); // 30 days
            }

            return $response;
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Update cart item quantity
     */
    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'cart_item_id' => 'required|exists:cart_items,id',
                'quantity' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', ['errors' => $validator->errors()], 422);
            }

            $cartItem = CartItem::with('product')->findOrFail($request->cart_item_id);
            $cart = $cartItem->cart;

            // Verify cart belongs to current user/guest
            // Use the same session ID retrieval logic as getOrCreateCart
            $user = $request->user();
            $sessionId = null;
            
            // First, try to get from cookie (most reliable for guest persistence)
            $sessionId = $request->cookie('cart_session_id');
            
            // If no cookie, try session
            if (!$sessionId) {
                try {
                    if ($request->hasSession()) {
                        $sessionId = $request->session()->getId();
                    }
                } catch (\Exception $e) {
                    // Session not available
                }
            }
            
            // For authenticated users, check user_id match
            if ($user) {
                if ($cart->user_id !== $user->id) {
                    return $this->sendJsonResponse(false, 'Unauthorized', null, 403);
                }
            } else {
                // For guests, check session_id match
                // Allow if: cart has no user_id AND (session_id matches OR cart has no session_id yet)
                if ($cart->user_id !== null) {
                    // Cart belongs to a user, guest cannot access
                    return $this->sendJsonResponse(false, 'Unauthorized', null, 403);
                }
                
                // If we have a session_id, it must match
                if ($sessionId && $cart->session_id && $cart->session_id !== $sessionId) {
                    return $this->sendJsonResponse(false, 'Unauthorized', null, 403);
                }
            }

            // Check stock
            $product = $cartItem->product;
            
            // Check stock - prioritize variation stock if size/color provided
            if ($product->manage_stock) {
                if ($cartItem->size || $cartItem->color) {
                    // Check variation stock
                    $variation = ProductVariation::where('product_id', $product->id)
                        ->where('size', $cartItem->size ?? null)
                        ->where('color', $cartItem->color ?? null)
                        ->first();
                    
                    if ($variation) {
                        if ($variation->stock_quantity < $request->quantity) {
                            return $this->sendJsonResponse(false, 'Insufficient stock available for selected size/color', null, 400);
                        }
                        if (!$variation->in_stock) {
                            return $this->sendJsonResponse(false, 'Selected size/color combination is out of stock', null, 400);
                        }
                    } else {
                        if ($product->stock_quantity < $request->quantity) {
                            return $this->sendJsonResponse(false, 'Insufficient stock available', null, 400);
                        }
                    }
                } else {
                    if ($product->stock_quantity < $request->quantity) {
                        return $this->sendJsonResponse(false, 'Insufficient stock available', null, 400);
                    }
                }
            }

            // Update quantity and recalculate price/discount
            $basePrice = $product->sale_price ?? $product->price;
            $discountInfo = $this->calculateProductDiscountInfo($product);
            $discountAmount = $discountInfo ? $discountInfo['discount_amount'] : 0;
            $finalPrice = $discountInfo ? $discountInfo['final_price'] : $basePrice;

            $cartItem->update([
                'quantity' => $request->quantity,
                'price' => $finalPrice,
                'discount_amount' => $discountAmount,
            ]);

            $cartData = $this->formatCartResponse($cart);

            $response = $this->sendJsonResponse(true, 'Cart item updated successfully', $cartData);

            // Always set/update cookie for guest session to maintain cart persistence
            if (!$request->user() && $cart->session_id) {
                $response->cookie('cart_session_id', $cart->session_id, 60 * 24 * 30, '/', null, false, false); // 30 days
            }

            return $response;
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Remove item from cart
     */
    public function remove(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'cart_item_id' => 'required|exists:cart_items,id',
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', ['errors' => $validator->errors()], 422);
            }

            $cartItem = CartItem::findOrFail($request->cart_item_id);
            $cart = $cartItem->cart;

            // Verify cart belongs to current user/guest
            // Use the same session ID retrieval logic as getOrCreateCart
            $user = $request->user();
            $sessionId = null;
            
            // First, try to get from cookie (most reliable for guest persistence)
            $sessionId = $request->cookie('cart_session_id');
            
            // If no cookie, try session
            if (!$sessionId) {
                try {
                    if ($request->hasSession()) {
                        $sessionId = $request->session()->getId();
                    }
                } catch (\Exception $e) {
                    // Session not available
                }
            }
            
            // For authenticated users, check user_id match
            if ($user) {
                if ($cart->user_id !== $user->id) {
                    return $this->sendJsonResponse(false, 'Unauthorized', null, 403);
                }
            } else {
                // For guests, check session_id match
                // Allow if: cart has no user_id AND (session_id matches OR cart has no session_id yet)
                if ($cart->user_id !== null) {
                    // Cart belongs to a user, guest cannot access
                    return $this->sendJsonResponse(false, 'Unauthorized', null, 403);
                }
                
                // If we have a session_id, it must match
                if ($sessionId && $cart->session_id && $cart->session_id !== $sessionId) {
                    return $this->sendJsonResponse(false, 'Unauthorized', null, 403);
                }
            }

            $cartItem->delete();

            $cartData = $this->formatCartResponse($cart);

            $response = $this->sendJsonResponse(true, 'Item removed from cart successfully', $cartData);

            // Always set/update cookie for guest session to maintain cart persistence
            if (!$request->user() && $cart->session_id) {
                $response->cookie('cart_session_id', $cart->session_id, 60 * 24 * 30, '/', null, false, false); // 30 days
            }

            return $response;
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Clear cart
     */
    public function clear(Request $request)
    {
        try {
            $cart = $this->getOrCreateCart($request);
            $cart->items()->delete();
            
            // Reload cart to get fresh state
            $cart->refresh();
            $cartData = $this->formatCartResponse($cart);

            $response = $this->sendJsonResponse(true, 'Cart cleared successfully', $cartData);

            // Always set/update cookie for guest session to maintain cart persistence
            if (!$request->user() && $cart->session_id) {
                $response->cookie('cart_session_id', $cart->session_id, 60 * 24 * 30, '/', null, false, false); // 30 days
            }

            return $response;
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}

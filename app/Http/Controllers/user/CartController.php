<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
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
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', ['errors' => $validator->errors()], 422);
            }

            $product = Product::with('discounts')->findOrFail($request->product_id);

            // Check if product is active and in stock
            if (!$product->is_active) {
                return $this->sendJsonResponse(false, 'Product is not available', null, 400);
            }

            if ($product->manage_stock && $product->stock_quantity < $request->quantity) {
                return $this->sendJsonResponse(false, 'Insufficient stock available', null, 400);
            }

            $cart = $this->getOrCreateCart($request);

            // Calculate price and discount
            $basePrice = $product->sale_price ?? $product->price;
            $discountInfo = $product->discount_info;
            $discountAmount = $discountInfo ? $discountInfo['discount_amount'] : 0;
            $finalPrice = $discountInfo ? $discountInfo['final_price'] : $basePrice;

            // Check if item already exists in cart
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();

            if ($cartItem) {
                // Update quantity
                $newQuantity = $cartItem->quantity + $request->quantity;
                
                // Check stock again
                if ($product->manage_stock && $product->stock_quantity < $newQuantity) {
                    return $this->sendJsonResponse(false, 'Insufficient stock available', null, 400);
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
            $user = $request->user();
            $sessionId = null;
            try {
                if ($request->hasSession()) {
                    $sessionId = $request->session()->getId();
                }
            } catch (\Exception $e) {
                // Session not available
            }
            if (!$sessionId) {
                $sessionId = $request->cookie('cart_session_id');
            }
            
            if ($user && $cart->user_id !== $user->id) {
                return $this->sendJsonResponse(false, 'Unauthorized', null, 403);
            }
            if (!$user && $cart->session_id !== $sessionId) {
                return $this->sendJsonResponse(false, 'Unauthorized', null, 403);
            }

            // Check stock
            $product = $cartItem->product;
            if ($product->manage_stock && $product->stock_quantity < $request->quantity) {
                return $this->sendJsonResponse(false, 'Insufficient stock available', null, 400);
            }

            // Update quantity and recalculate price/discount
            $basePrice = $product->sale_price ?? $product->price;
            $discountInfo = $product->discount_info;
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
            $user = $request->user();
            $sessionId = null;
            try {
                if ($request->hasSession()) {
                    $sessionId = $request->session()->getId();
                }
            } catch (\Exception $e) {
                // Session not available
            }
            if (!$sessionId) {
                $sessionId = $request->cookie('cart_session_id');
            }
            
            if ($user && $cart->user_id !== $user->id) {
                return $this->sendJsonResponse(false, 'Unauthorized', null, 403);
            }
            if (!$user && $cart->session_id !== $sessionId) {
                return $this->sendJsonResponse(false, 'Unauthorized', null, 403);
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

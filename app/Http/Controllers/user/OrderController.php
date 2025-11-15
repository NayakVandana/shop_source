<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CouponCode;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Get or create cart for current user/guest
     */
    protected function getOrCreateCart(Request $request): Cart
    {
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

        if ($user) {
            $cart = Cart::where('user_id', $user->id)->first();
        } else {
            $cart = Cart::where('session_id', $sessionId)->first();
        }

        return $cart;
    }

    /**
     * Create order from cart
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'shipping_name' => 'required|string|max:255',
                'shipping_email' => 'required|email|max:255',
                'shipping_phone' => 'required|string|max:20',
                'shipping_address' => 'required|string',
                'shipping_city' => 'required|string|max:100',
                'shipping_state' => 'nullable|string|max:100',
                'shipping_postal_code' => 'nullable|string|max:20',
                'shipping_country' => 'nullable|string|max:100',
                'coupon_code' => 'nullable|string|exists:coupon_codes,code',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', ['errors' => $validator->errors()], 422);
            }

            $cart = $this->getOrCreateCart($request);

            if (!$cart || $cart->items->isEmpty()) {
                return $this->sendJsonResponse(false, 'Cart is empty', null, 400);
            }

            // Validate all cart items are still available
            foreach ($cart->items as $item) {
                $product = $item->product;
                if (!$product->is_active) {
                    return $this->sendJsonResponse(false, "Product '{$product->name}' is no longer available", null, 400);
                }
                if ($product->manage_stock && $product->stock_quantity < $item->quantity) {
                    return $this->sendJsonResponse(false, "Insufficient stock for '{$product->name}'", null, 400);
                }
            }

            DB::beginTransaction();

            try {
                // Calculate totals in controller (business logic)
                $items = $cart->items;
                $subtotal = $items->sum(function ($item) {
                    return (($item->price ?? 0) - ($item->discount_amount ?? 0)) * ($item->quantity ?? 0);
                });
                $discountAmount = $items->sum(function ($item) {
                    return ($item->discount_amount ?? 0) * ($item->quantity ?? 0);
                });
                $couponDiscount = 0;
                $couponCode = null;

                // Apply coupon code if provided
                if ($request->has('coupon_code') && $request->coupon_code) {
                    $coupon = CouponCode::where('code', $request->coupon_code)->first();
                    if ($coupon && $coupon->isValid()) {
                        // Check if coupon can be used by user
                        $user = $request->user();
                        if (!$user || $coupon->canBeUsedByUser($user)) {
                            $couponDiscount = $coupon->calculateDiscount($subtotal);
                            $couponCode = $coupon->code;
                            $coupon->incrementUsage();
                        }
                    }
                }

                $taxAmount = 0; // Can be calculated based on tax rate
                $shippingAmount = 0; // Can be calculated based on shipping rules
                $total = $subtotal - $discountAmount - $couponDiscount + $taxAmount + $shippingAmount;

                // Create order
                $user = $request->user();
                $sessionId = $request->session()->getId();

                $order = Order::create([
                    'user_id' => $user ? $user->id : null,
                    'session_id' => $sessionId,
                    'status' => 'pending',
                    'subtotal' => $subtotal,
                    'discount_amount' => $discountAmount,
                    'tax_amount' => $taxAmount,
                    'shipping_amount' => $shippingAmount,
                    'total' => $total,
                    'coupon_code' => $couponCode,
                    'coupon_discount' => $couponDiscount,
                    'shipping_name' => $request->shipping_name,
                    'shipping_email' => $request->shipping_email,
                    'shipping_phone' => $request->shipping_phone,
                    'shipping_address' => $request->shipping_address,
                    'shipping_city' => $request->shipping_city,
                    'shipping_state' => $request->shipping_state,
                    'shipping_postal_code' => $request->shipping_postal_code,
                    'shipping_country' => $request->shipping_country,
                    'notes' => $request->notes,
                ]);

                // Create order items and update stock
                foreach ($cart->items as $cartItem) {
                    $product = $cartItem->product;
                    
                    // Calculate item subtotal in controller (business logic)
                    $itemSubtotal = (($cartItem->price ?? 0) - ($cartItem->discount_amount ?? 0)) * ($cartItem->quantity ?? 0);
                    
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_sku' => $product->sku,
                        'quantity' => $cartItem->quantity,
                        'price' => $cartItem->price,
                        'discount_amount' => $cartItem->discount_amount,
                        'subtotal' => $itemSubtotal,
                    ]);

                    // Update product stock
                    if ($product->manage_stock) {
                        $product->decrement('stock_quantity', $cartItem->quantity);
                        if ($product->stock_quantity <= 0) {
                            $product->update(['in_stock' => false]);
                        }
                    }
                }

                // Clear cart
                $cart->items()->delete();

                DB::commit();

                $order->load(['items.product', 'user']);

                return $this->sendJsonResponse(true, 'Order created successfully', $order, 201);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Get user orders
     */
    public function index(Request $request)
    {
        try {
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

            $query = Order::with(['items.product.media']);

            if ($user) {
                $query->where('user_id', $user->id);
            } else {
                $query->where('session_id', $sessionId);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $orders = $query->paginate($perPage);

            return $this->sendJsonResponse(true, 'Orders retrieved successfully', $orders);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Get single order
     */
    public function show(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', ['errors' => $validator->errors()], 422);
            }

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

            $order = Order::with(['items.product.media', 'user'])
                ->where('uuid', $request->id)
                ->firstOrFail();

            // Verify ownership
            if ($user && $order->user_id !== $user->id) {
                return $this->sendJsonResponse(false, 'Unauthorized', null, 403);
            }
            if (!$user && $order->session_id !== $sessionId) {
                return $this->sendJsonResponse(false, 'Unauthorized', null, 403);
            }

            return $this->sendJsonResponse(true, 'Order retrieved successfully', $order);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}

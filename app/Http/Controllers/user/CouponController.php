<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Exception;

class CouponController extends Controller
{
    public function index()
    {
        try {
            $coupons = Discount::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('starts_at')
                          ->orWhere('starts_at', '<=', now());
                })
                ->where(function($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>=', now());
                })
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->sendJsonResponse(true, 'Available coupons retrieved successfully', $coupons);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function validateCode(Request $request)
    {
        try {
            $data = $request->validate([
                'code' => 'required|string',
                'amount' => 'required|numeric|min:0',
                'products' => 'nullable|array'
            ]);

            $coupon = Discount::where('code', $data['code'])->first();

            if (!$coupon) {
                return $this->sendJsonResponse(false, 'Invalid coupon code', null, 400);
            }

            if (!$coupon->isValid()) {
                return $this->sendJsonResponse(false, 'Coupon is not valid or has expired', null, 400);
            }

            $user = auth()->user();
            $discountAmount = $coupon->calculateDiscount(
                $data['amount'], 
                $user ? $user->id : null, 
                $data['products'] ?? []
            );

            if ($discountAmount <= 0) {
                return $this->sendJsonResponse(false, 'Coupon does not apply to this order', null, 400);
            }

            return $this->sendJsonResponse(true, 'Coupon is valid', [
                'coupon' => [
                    'uuid' => $coupon->uuid,
                    'name' => $coupon->name,
                    'code' => $coupon->code,
                    'type' => $coupon->type,
                    'value' => $coupon->value,
                    'description' => $coupon->description,
                ],
                'discount_amount' => $discountAmount,
                'final_amount' => $data['amount'] - $discountAmount
            ]);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function validateCartCoupon(Request $request)
    {
        try {
            $data = $request->validate([
                'code' => 'required|string'
            ]);

            $user = auth()->user();
            $cartItems = CartItem::with('product')->where('user_id', $user->id)->get();

            if ($cartItems->isEmpty()) {
                return $this->sendJsonResponse(false, 'Cart is empty', null, 400);
            }

            $subtotal = 0;
            $products = [];

            foreach ($cartItems as $item) {
                $subtotal += $item->product->current_price * $item->quantity;
                $products[] = $item->product;
            }

            $coupon = Discount::where('code', $data['code'])->first();

            if (!$coupon) {
                return $this->sendJsonResponse(false, 'Invalid coupon code', null, 400);
            }

            if (!$coupon->isValid()) {
                return $this->sendJsonResponse(false, 'Coupon is not valid or has expired', null, 400);
            }

            $discountAmount = $coupon->calculateDiscount($subtotal, $user->id, $products);

            if ($discountAmount <= 0) {
                return $this->sendJsonResponse(false, 'Coupon does not apply to your cart', null, 400);
            }

            $taxAmount = $subtotal * 0.1; // 10% tax
            $shippingAmount = 0; // Free shipping
            $totalAmount = $subtotal - $discountAmount + $taxAmount + $shippingAmount;

            return $this->sendJsonResponse(true, 'Coupon applied successfully', [
                'coupon' => [
                    'uuid' => $coupon->uuid,
                    'name' => $coupon->name,
                    'code' => $coupon->code,
                    'type' => $coupon->type,
                    'value' => $coupon->value,
                    'description' => $coupon->description,
                ],
                'cart_summary' => [
                    'subtotal' => $subtotal,
                    'discount_amount' => $discountAmount,
                    'tax_amount' => $taxAmount,
                    'shipping_amount' => $shippingAmount,
                    'total_amount' => $totalAmount,
                ],
                'cart_items' => $cartItems
            ]);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getApplicableCoupons(Request $request)
    {
        try {
            $user = auth()->user();
            $cartItems = CartItem::with('product')->where('user_id', $user->id)->get();

            if ($cartItems->isEmpty()) {
                return $this->sendJsonResponse(true, 'No cart items', []);
            }

            $subtotal = 0;
            $productIds = [];
            $categoryIds = [];

            foreach ($cartItems as $item) {
                $subtotal += $item->product->current_price * $item->quantity;
                $productIds[] = $item->product->id;
                if ($item->product->category_id) {
                    $categoryIds[] = $item->product->category_id;
                }
            }

            $applicableCoupons = Discount::where('is_active', true)
                ->where(function($query) {
                    $query->whereNull('starts_at')
                          ->orWhere('starts_at', '<=', now());
                })
                ->where(function($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>=', now());
                })
                ->where(function($query) use ($subtotal) {
                    $query->whereNull('minimum_amount')
                          ->orWhere('minimum_amount', '<=', $subtotal);
                })
                ->get()
                ->filter(function($coupon) use ($user, $productIds, $categoryIds) {
                    // Check if coupon applies to this user and cart
                    $discountAmount = $coupon->calculateDiscount($subtotal, $user->id, $productIds);
                    return $discountAmount > 0;
                })
                ->values();

            return $this->sendJsonResponse(true, 'Applicable coupons retrieved successfully', [
                'coupons' => $applicableCoupons,
                'cart_subtotal' => $subtotal
            ]);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}
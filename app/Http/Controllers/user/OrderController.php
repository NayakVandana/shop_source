<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\CartItem;
use App\Models\Discount;
use Illuminate\Http\Request;
use Exception;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $query = Order::with('orderItems.product')->where('user_id', $user->id);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->get('status'));
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 10);
            $orders = $query->paginate($perPage);

            return $this->sendJsonResponse(true, 'Orders retrieved successfully', $orders);
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

            $user = auth()->user();
            $order = Order::with(['orderItems.product', 'user'])
                ->where('user_id', $user->id)
                ->where('uuid', $data['id'])
                ->firstOrFail();

            return $this->sendJsonResponse(true, 'Order retrieved successfully', $order);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'shipping_address' => 'required|string',
                'billing_address' => 'nullable|string',
                'payment_method' => 'required|string',
                'discount_code' => 'nullable|string',
                'notes' => 'nullable|string'
            ]);

            $user = auth()->user();
            $cartItems = CartItem::with('product')->where('user_id', $user->id)->get();

            if ($cartItems->isEmpty()) {
                return $this->sendJsonResponse(false, 'Cart is empty', null, 400);
            }

            // Calculate totals
            $subtotal = 0;
            $discountAmount = 0;

            foreach ($cartItems as $item) {
                $subtotal += $item->product->current_price * $item->quantity;
            }

            // Apply discount if provided
            if (!empty($data['discount_code'])) {
                $discount = Discount::where('code', $data['discount_code'])->first();
                
                if ($discount && $discount->isValid()) {
                    $products = $cartItems->pluck('product')->toArray();
                    $discountAmount = $discount->calculateDiscount($subtotal, $user->id, $products);
                    
                    if ($discountAmount > 0) {
                        // Update discount usage
                        $discount->incrementUsage();
                    }
                }
            }

            $taxAmount = $subtotal * 0.1; // 10% tax - you can make this configurable
            $shippingAmount = 0; // Free shipping for now - you can add logic here
            $totalAmount = $subtotal - $discountAmount + $taxAmount + $shippingAmount;

            // Create order
            $order = Order::create([
                'order_number' => 'ORD-' . time() . '-' . rand(1000, 9999),
                'user_id' => $user->id,
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'discount_code' => $data['discount_code'] ?? null,
                'tax_amount' => $taxAmount,
                'shipping_amount' => $shippingAmount,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $data['payment_method'],
                'shipping_address' => $data['shipping_address'],
                'billing_address' => $data['billing_address'] ?? $data['shipping_address'],
                'notes' => $data['notes']
            ]);

            // Create order items
            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->product->current_price,
                    'total_price' => $item->product->current_price * $item->quantity
                ]);

                // Update product stock
                if ($item->product->manage_stock) {
                    $item->product->decrement('stock_quantity', $item->quantity);
                    
                    // Update in_stock status
                    if ($item->product->stock_quantity <= 0) {
                        $item->product->update(['in_stock' => false]);
                    }
                }
            }

            // Clear cart
            CartItem::where('user_id', $user->id)->delete();

            $order->load('orderItems.product');

            return $this->sendJsonResponse(true, 'Order created successfully', $order, 201);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function cancel(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $user = auth()->user();
            $order = Order::where('user_id', $user->id)
                ->where('uuid', $data['id'])
                ->whereIn('status', ['pending', 'confirmed'])
                ->firstOrFail();

            $order->update(['status' => 'cancelled']);

            // Restore stock
            foreach ($order->orderItems as $item) {
                if ($item->product->manage_stock) {
                    $item->product->increment('stock_quantity', $item->quantity);
                    $item->product->update(['in_stock' => true]);
                }
            }

            return $this->sendJsonResponse(true, 'Order cancelled successfully', $order);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getOrderStats()
    {
        try {
            $user = auth()->user();
            
            $stats = [
                'total_orders' => Order::where('user_id', $user->id)->count(),
                'pending_orders' => Order::where('user_id', $user->id)->where('status', 'pending')->count(),
                'confirmed_orders' => Order::where('user_id', $user->id)->where('status', 'confirmed')->count(),
                'shipped_orders' => Order::where('user_id', $user->id)->where('status', 'shipped')->count(),
                'delivered_orders' => Order::where('user_id', $user->id)->where('status', 'delivered')->count(),
                'cancelled_orders' => Order::where('user_id', $user->id)->where('status', 'cancelled')->count(),
                'total_spent' => Order::where('user_id', $user->id)->where('payment_status', 'paid')->sum('total_amount'),
            ];

            return $this->sendJsonResponse(true, 'Order statistics retrieved successfully', $stats);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getOrderTimeline(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $user = auth()->user();
            $order = Order::where('uuid', $data['id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            $timeline = $order->getCustomerTimeline();

            return $this->sendJsonResponse(true, 'Order timeline retrieved successfully', $timeline);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function requestReturn(Request $request)
    {
        try {
            $data = $request->validate([
                'order_id' => 'required|string',
                'order_item_id' => 'required|integer|exists:order_items,id',
                'type' => 'required|string|in:return,exchange,refund',
                'reason' => 'required|string',
                'description' => 'nullable|string|max:1000',
                'quantity' => 'required|integer|min:1',
                'images' => 'nullable|array|max:5',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048'
            ]);

            $user = auth()->user();
            $order = Order::where('uuid', $data['order_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            // Check if order can be returned
            if (!$order->canBeReturned()) {
                return $this->sendJsonResponse(false, 'This order cannot be returned', null, 400);
            }

            // Check if order item belongs to this order
            $orderItem = OrderItem::where('id', $data['order_item_id'])
                ->where('order_id', $order->id)
                ->firstOrFail();

            // Check quantity
            if ($data['quantity'] > $orderItem->quantity) {
                return $this->sendJsonResponse(false, 'Return quantity cannot exceed ordered quantity', null, 400);
            }

            // Handle image uploads
            $imagePaths = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $path = $image->store('returns', 'public');
                    $imagePaths[] = $path;
                }
            }

            // Calculate refund amount
            $refundAmount = ($orderItem->price * $data['quantity']) - ($orderItem->discount_amount ?? 0);

            $return = OrderReturn::create([
                'order_id' => $order->id,
                'order_item_id' => $orderItem->id,
                'type' => $data['type'],
                'reason' => $data['reason'],
                'description' => $data['description'],
                'quantity' => $data['quantity'],
                'refund_amount' => $refundAmount,
                'images' => $imagePaths,
                'customer_notes' => $data['description'],
            ]);

            $return->load(['order', 'orderItem.product']);

            return $this->sendJsonResponse(true, 'Return request submitted successfully', $return, 201);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getReturns(Request $request)
    {
        try {
            $user = auth()->user();
            $query = OrderReturn::whereHas('order', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->with(['order', 'orderItem.product']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->get('status'));
            }

            // Filter by type
            if ($request->has('type')) {
                $query->where('type', $request->get('type'));
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $returns = $query->paginate($perPage);

            return $this->sendJsonResponse(true, 'Returns retrieved successfully', $returns);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getReturnReasons()
    {
        try {
            $reasons = OrderReturn::getReturnReasons();

            return $this->sendJsonResponse(true, 'Return reasons retrieved successfully', $reasons);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function cancelOrder(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|string',
                'reason' => 'required|string|max:500'
            ]);

            $user = auth()->user();
            $order = Order::where('uuid', $data['id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            if (!$order->canBeCancelled()) {
                return $this->sendJsonResponse(false, 'This order cannot be cancelled', null, 400);
            }

            $order->cancel($data['reason'], $user);
            $order->load(['orderItems.product', 'orderStatus']);

            return $this->sendJsonResponse(true, 'Order cancelled successfully', $order);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function trackOrder(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $user = auth()->user();
            $order = Order::where('uuid', $data['id'])
                ->where('user_id', $user->id)
                ->with(['orderItems.product', 'orderStatus', 'deliveryLocation'])
                ->firstOrFail();

            // Add delivery tracking info
            $order->delivery_status = $order->delivery_status;
            $order->estimated_delivery_date = $order->estimated_delivery_date;
            $order->timeline = $order->getCustomerTimeline();

            return $this->sendJsonResponse(true, 'Order tracking information retrieved successfully', $order);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}
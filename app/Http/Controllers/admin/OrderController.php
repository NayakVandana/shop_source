<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Exception;

class OrderController extends Controller
{
    /**
     * Display a listing of orders
     */
    public function index(Request $request): Response
    {
        try {
            $query = Order::with(['user', 'items.product']);

            // Search
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                      ->orWhere('shipping_name', 'like', "%{$search}%")
                      ->orWhere('shipping_email', 'like', "%{$search}%")
                      ->orWhere('shipping_phone', 'like', "%{$search}%");
                });
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by date range
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $orders = $query->paginate($perPage);

            // Get counts
            $totalCount = Order::count();
            $pendingCount = Order::where('status', 'pending')->count();
            $processingCount = Order::where('status', 'processing')->count();
            $shippedCount = Order::where('status', 'shipped')->count();
            $deliveredCount = Order::where('status', 'delivered')->count();
            $cancelledCount = Order::where('status', 'cancelled')->count();

            return $this->sendJsonResponse(true, 'Orders retrieved successfully', [
                'orders' => $orders,
                'counts' => [
                    'total' => $totalCount,
                    'pending' => $pendingCount,
                    'processing' => $processingCount,
                    'shipped' => $shippedCount,
                    'delivered' => $deliveredCount,
                    'cancelled' => $cancelledCount,
                ],
            ]);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Display single order
     */
    public function show(Request $request): Response
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', ['errors' => $validator->errors()], 422);
            }

            $order = Order::with(['user', 'items.product.media', 'items.product.category'])
                ->where('uuid', $request->id)
                ->firstOrFail();

            return $this->sendJsonResponse(true, 'Order retrieved successfully', $order);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request): Response
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|string',
                'status' => 'required|in:pending,processing,shipped,delivered,cancelled,refunded',
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', ['errors' => $validator->errors()], 422);
            }

            $order = Order::where('uuid', $request->id)->firstOrFail();
            $order->update(['status' => $request->status]);

            $order->load(['user', 'items.product']);

            return $this->sendJsonResponse(true, 'Order status updated successfully', $order);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Update order details
     */
    public function update(Request $request): Response
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|string',
                'shipping_name' => 'sometimes|string|max:255',
                'shipping_email' => 'sometimes|email|max:255',
                'shipping_phone' => 'sometimes|string|max:20',
                'shipping_address' => 'sometimes|string',
                'shipping_city' => 'sometimes|string|max:100',
                'shipping_state' => 'nullable|string|max:100',
                'shipping_postal_code' => 'nullable|string|max:20',
                'shipping_country' => 'nullable|string|max:100',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', ['errors' => $validator->errors()], 422);
            }

            $order = Order::where('uuid', $request->id)->firstOrFail();

            $updateData = $request->only([
                'shipping_name', 'shipping_email', 'shipping_phone',
                'shipping_address', 'shipping_city', 'shipping_state',
                'shipping_postal_code', 'shipping_country', 'notes'
            ]);

            $order->update($updateData);
            $order->load(['user', 'items.product']);

            return $this->sendJsonResponse(true, 'Order updated successfully', $order);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}

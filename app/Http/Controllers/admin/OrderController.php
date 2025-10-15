<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatus;
use App\Models\OrderTimeline;
use App\Models\OrderReturn;
use Illuminate\Http\Request;
use Exception;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Order::with(['user', 'orderItems.product']);

            // Search
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                      ->orWhereHas('user', function($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->get('status'));
            }

            // Filter by payment status
            if ($request->has('payment_status')) {
                $query->where('payment_status', $request->get('payment_status'));
            }

            // Filter by date range
            if ($request->has('date_from')) {
                $query->whereDate('created_at', '>=', $request->get('date_from'));
            }

            if ($request->has('date_to')) {
                $query->whereDate('created_at', '<=', $request->get('date_to'));
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

    public function show($id)
    {
        try {
            $order = Order::with(['user', 'orderItems.product'])->findOrFail($id);
            return $this->sendJsonResponse(true, 'Order retrieved successfully', $order);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
                'notes' => 'nullable|string'
            ]);

            $order = Order::findOrFail($id);
            $order->update($data);

            return $this->sendJsonResponse(true, 'Order status updated successfully', $order);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function updatePaymentStatus(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'payment_status' => 'required|in:pending,paid,failed,refunded',
                'payment_method' => 'nullable|string'
            ]);

            $order = Order::findOrFail($id);
            $order->update($data);

            return $this->sendJsonResponse(true, 'Payment status updated successfully', $order);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function destroy($id)
    {
        try {
            $order = Order::findOrFail($id);
            
            // Only allow deletion of pending orders
            if ($order->status !== 'pending') {
                return $this->sendJsonResponse(false, 'Only pending orders can be deleted', null, 400);
            }

            $order->delete();

            return $this->sendJsonResponse(true, 'Order deleted successfully');
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getOrderStats()
    {
        try {
            $stats = [
                'total_orders' => Order::count(),
                'pending_orders' => Order::where('status', 'pending')->count(),
                'confirmed_orders' => Order::where('status', 'confirmed')->count(),
                'processing_orders' => Order::where('status', 'processing')->count(),
                'shipped_orders' => Order::where('status', 'shipped')->count(),
                'delivered_orders' => Order::where('status', 'delivered')->count(),
                'cancelled_orders' => Order::where('status', 'cancelled')->count(),
                'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount'),
                'pending_payments' => Order::where('payment_status', 'pending')->count(),
                'paid_orders' => Order::where('payment_status', 'paid')->count(),
            ];

            return $this->sendJsonResponse(true, 'Order statistics retrieved successfully', $stats);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function updateStatus(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|string',
                'status' => 'required|string|in:pending,confirmed,processing,shipped,delivered,cancelled',
                'title' => 'nullable|string',
                'description' => 'nullable|string',
                'metadata' => 'nullable|array'
            ]);

            $order = Order::where('uuid', $data['id'])->firstOrFail();
            $admin = auth()->user();

            $title = $data['title'] ?? ucfirst($data['status']);
            $description = $data['description'] ?? "Order status updated to {$data['status']}";

            $order->updateStatus(
                $data['status'],
                $title,
                $description,
                $data['metadata'] ?? null,
                $admin
            );

            // Load relationships for response
            $order->load(['user', 'orderItems.product', 'orderStatus', 'deliveryLocation']);

            return $this->sendJsonResponse(true, 'Order status updated successfully', $order);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function shipOrder(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|string',
                'tracking_number' => 'required|string',
                'delivery_company' => 'nullable|string',
                'estimated_delivery_date' => 'nullable|date|after:today'
            ]);

            $order = Order::where('uuid', $data['id'])->firstOrFail();
            $admin = auth()->user();

            $order->markAsShipped(
                $data['tracking_number'],
                $data['delivery_company'] ?? null,
                $admin
            );

            if ($data['estimated_delivery_date']) {
                $order->update(['estimated_delivery_date' => $data['estimated_delivery_date']]);
            }

            $order->load(['user', 'orderItems.product', 'orderStatus', 'deliveryLocation']);

            return $this->sendJsonResponse(true, 'Order shipped successfully', $order);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function deliverOrder(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $order = Order::where('uuid', $data['id'])->firstOrFail();
            $admin = auth()->user();

            $order->markAsDelivered($admin);
            $order->load(['user', 'orderItems.product', 'orderStatus', 'deliveryLocation']);

            return $this->sendJsonResponse(true, 'Order marked as delivered successfully', $order);
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

            $order = Order::where('uuid', $data['id'])->firstOrFail();
            $admin = auth()->user();

            $order->cancel($data['reason'], $admin);
            $order->load(['user', 'orderItems.product', 'orderStatus', 'deliveryLocation']);

            return $this->sendJsonResponse(true, 'Order cancelled successfully', $order);
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

            $order = Order::where('uuid', $data['id'])->firstOrFail();
            $timeline = $order->getAdminTimeline();

            return $this->sendJsonResponse(true, 'Order timeline retrieved successfully', $timeline);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getReturns(Request $request)
    {
        try {
            $query = OrderReturn::with(['order', 'orderItem.product']);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->get('status'));
            }

            // Filter by type
            if ($request->has('type')) {
                $query->where('type', $request->get('type'));
            }

            // Filter by order
            if ($request->has('order_id')) {
                $query->where('order_id', $request->get('order_id'));
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

    public function processReturn(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|string',
                'status' => 'required|string|in:approved,rejected,processing,completed',
                'admin_notes' => 'nullable|string|max:1000',
                'refund_amount' => 'nullable|numeric|min:0',
                'return_tracking_number' => 'nullable|string'
            ]);

            $return = OrderReturn::where('uuid', $data['id'])->firstOrFail();
            $admin = auth()->user();

            $return->update([
                'status' => $data['status'],
                'admin_notes' => $data['admin_notes'],
                'refund_amount' => $data['refund_amount'] ?? $return->refund_amount,
                'return_tracking_number' => $data['return_tracking_number'] ?? $return->return_tracking_number,
                'processed_at' => now(),
                'processed_by_type' => get_class($admin),
                'processed_by_id' => $admin->id,
            ]);

            if ($data['status'] === 'completed') {
                $return->update(['completed_at' => now()]);
            }

            $return->load(['order', 'orderItem.product']);

            return $this->sendJsonResponse(true, 'Return processed successfully', $return);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getReturnStats()
    {
        try {
            $stats = [
                'total_returns' => OrderReturn::count(),
                'pending_returns' => OrderReturn::where('status', 'pending')->count(),
                'approved_returns' => OrderReturn::where('status', 'approved')->count(),
                'rejected_returns' => OrderReturn::where('status', 'rejected')->count(),
                'completed_returns' => OrderReturn::where('status', 'completed')->count(),
                'total_refund_amount' => OrderReturn::where('status', 'completed')->sum('refund_amount'),
                'return_types' => OrderReturn::selectRaw('type, count(*) as count')
                    ->groupBy('type')
                    ->get(),
                'return_reasons' => OrderReturn::selectRaw('reason, count(*) as count')
                    ->groupBy('reason')
                    ->orderBy('count', 'desc')
                    ->get(),
            ];

            return $this->sendJsonResponse(true, 'Return statistics retrieved successfully', $stats);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}
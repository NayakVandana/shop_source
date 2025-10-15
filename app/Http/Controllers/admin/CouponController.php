<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Discount::query();

            // Search
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Filter by status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->get('is_active'));
            }

            // Filter by type
            if ($request->has('type')) {
                $query->where('type', $request->get('type'));
            }

            // Filter by expiration
            if ($request->has('expired')) {
                if ($request->get('expired')) {
                    $query->where('expires_at', '<', now());
                } else {
                    $query->where(function($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>=', now());
                    });
                }
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $coupons = $query->paginate($perPage);

            // Add additional data to each coupon
            $coupons->getCollection()->transform(function ($coupon) {
                $coupon->remaining_uses = $coupon->getRemainingUses();
                $coupon->usage_percentage = $coupon->getUsagePercentage();
                $coupon->is_expired = $coupon->expires_at && $coupon->expires_at < now();
                return $coupon;
            });

            return $this->sendJsonResponse(true, 'Coupons retrieved successfully', $coupons);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'code' => 'nullable|string|unique:discounts,code',
                'description' => 'nullable|string',
                'type' => 'required|in:percentage,fixed',
                'value' => 'required|numeric|min:0',
                'minimum_amount' => 'nullable|numeric|min:0',
                'max_discount_amount' => 'nullable|numeric|min:0',
                'usage_limit' => 'nullable|integer|min:1',
                'user_limit' => 'nullable|integer|min:1',
                'first_time_only' => 'boolean',
                'stackable' => 'boolean',
                'applicable_products' => 'nullable|array',
                'applicable_products.*' => 'exists:products,id',
                'applicable_categories' => 'nullable|array',
                'applicable_categories.*' => 'exists:categories,id',
                'starts_at' => 'nullable|date',
                'expires_at' => 'nullable|date|after:starts_at',
                'is_active' => 'boolean'
            ]);

            // Generate code if not provided
            if (empty($data['code'])) {
                $data['code'] = strtoupper(Str::random(8));
            }

            $coupon = Discount::create($data);

            return $this->sendJsonResponse(true, 'Coupon created successfully', $coupon, 201);
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

            $coupon = Discount::where('uuid', $data['id'])->firstOrFail();
            
            // Add additional data
            $coupon->remaining_uses = $coupon->getRemainingUses();
            $coupon->usage_percentage = $coupon->getUsagePercentage();
            $coupon->is_expired = $coupon->expires_at && $coupon->expires_at < now();

            return $this->sendJsonResponse(true, 'Coupon retrieved successfully', $coupon);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function update(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|string',
                'name' => 'sometimes|required|string|max:255',
                'code' => 'nullable|string',
                'description' => 'nullable|string',
                'type' => 'sometimes|required|in:percentage,fixed',
                'value' => 'sometimes|required|numeric|min:0',
                'minimum_amount' => 'nullable|numeric|min:0',
                'max_discount_amount' => 'nullable|numeric|min:0',
                'usage_limit' => 'nullable|integer|min:1',
                'user_limit' => 'nullable|integer|min:1',
                'first_time_only' => 'boolean',
                'stackable' => 'boolean',
                'applicable_products' => 'nullable|array',
                'applicable_products.*' => 'exists:products,id',
                'applicable_categories' => 'nullable|array',
                'applicable_categories.*' => 'exists:categories,id',
                'starts_at' => 'nullable|date',
                'expires_at' => 'nullable|date|after:starts_at',
                'is_active' => 'boolean'
            ]);

            $coupon = Discount::where('uuid', $data['id'])->firstOrFail();
            unset($data['id']); // Remove id from update data

            $coupon->update($data);

            return $this->sendJsonResponse(true, 'Coupon updated successfully', $coupon);
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

            $coupon = Discount::where('uuid', $data['id'])->firstOrFail();
            $coupon->delete();

            return $this->sendJsonResponse(true, 'Coupon deleted successfully');
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function toggleStatus(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $coupon = Discount::where('uuid', $data['id'])->firstOrFail();
            $coupon->update(['is_active' => !$coupon->is_active]);

            return $this->sendJsonResponse(true, 'Coupon status updated successfully', $coupon);
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
                'user_id' => 'nullable|integer|exists:users,id',
                'products' => 'nullable|array'
            ]);

            $coupon = Discount::where('code', $data['code'])->first();

            if (!$coupon) {
                return $this->sendJsonResponse(false, 'Invalid coupon code', null, 400);
            }

            if (!$coupon->isValid()) {
                return $this->sendJsonResponse(false, 'Coupon is not valid or has expired', null, 400);
            }

            $discountAmount = $coupon->calculateDiscount(
                $data['amount'], 
                $data['user_id'] ?? null, 
                $data['products'] ?? []
            );

            if ($discountAmount <= 0) {
                return $this->sendJsonResponse(false, 'Coupon does not apply to this order', null, 400);
            }

            return $this->sendJsonResponse(true, 'Coupon is valid', [
                'coupon' => $coupon,
                'discount_amount' => $discountAmount,
                'final_amount' => $data['amount'] - $discountAmount
            ]);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getStats()
    {
        try {
            $stats = [
                'total_coupons' => Discount::count(),
                'active_coupons' => Discount::where('is_active', true)->count(),
                'expired_coupons' => Discount::where('expires_at', '<', now())->count(),
                'total_usage' => Discount::sum('used_count'),
                'most_used_coupon' => Discount::orderBy('used_count', 'desc')->first(),
                'recent_coupons' => Discount::orderBy('created_at', 'desc')->limit(5)->get(),
            ];

            return $this->sendJsonResponse(true, 'Coupon statistics retrieved successfully', $stats);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function generateCode()
    {
        try {
            $code = strtoupper(Str::random(8));
            
            // Ensure uniqueness
            while (Discount::where('code', $code)->exists()) {
                $code = strtoupper(Str::random(8));
            }

            return $this->sendJsonResponse(true, 'Coupon code generated', ['code' => $code]);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}
<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\CouponCode;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Exception;

class CouponCodeController extends Controller
{
    /**
     * Display a listing of coupon codes
     */
    public function index(Request $request): Response
    {
        try {
            $query = CouponCode::query();
            
            // Search functionality
            if ($request->has('search')) {
                $query->where('code', 'like', '%' . $request->search . '%')
                      ->orWhere('name', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%');
            }
            
            // Status filter
            if ($request->has('is_active')) {
                $query->where('is_active', $request->is_active);
            }
            
            // Type filter
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }
            
            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);
            
            // Pagination
            $perPage = $request->get('per_page', 15);
            $coupons = $query->paginate($perPage);
            
            // Get counts
            $countsQuery = CouponCode::query();
            if ($request->has('search')) {
                $countsQuery->where('code', 'like', '%' . $request->search . '%')
                      ->orWhere('name', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%');
            }
            if ($request->has('is_active')) {
                $countsQuery->where('is_active', $request->is_active);
            }
            if ($request->has('type')) {
                $countsQuery->where('type', $request->type);
            }
            
            $totalCount = $countsQuery->count();
            $activeCount = (clone $countsQuery)->where('is_active', true)->count();
            $inactiveCount = (clone $countsQuery)->where('is_active', false)->count();
            
            $responseData = $coupons->toArray();
            $responseData['counts'] = [
                'total' => $totalCount,
                'active' => $activeCount,
                'inactive' => $inactiveCount,
            ];
            
            return $this->sendJsonResponse(true, 'Coupon codes retrieved successfully', $responseData);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Store a newly created coupon code
     */
    public function store(Request $request): Response
    {
        try {
            $validator = Validator::make($request->all(), [
                'code' => 'nullable|string|max:50|unique:coupon_codes,code',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'required|in:percentage,fixed',
                'value' => 'required|numeric|min:0',
                'min_purchase_amount' => 'nullable|numeric|min:0',
                'max_discount_amount' => 'nullable|numeric|min:0',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'usage_limit' => 'nullable|integer|min:0',
                'usage_limit_per_user' => 'nullable|integer|min:0',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', ['errors' => $validator->errors()], 422);
            }

            $data = $request->all();
            
            // Convert date strings to datetime if provided
            if ($request->has('start_date') && $request->start_date) {
                $data['start_date'] = date('Y-m-d H:i:s', strtotime($request->start_date));
            }
            if ($request->has('end_date') && $request->end_date) {
                $data['end_date'] = date('Y-m-d H:i:s', strtotime($request->end_date));
            }
            
            $coupon = CouponCode::create($data);

            return $this->sendJsonResponse(true, 'Coupon code created successfully', $coupon, 201);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Display the specified coupon code
     */
    public function show(Request $request): Response
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $coupon = CouponCode::where('uuid', $data['id'])->firstOrFail();
            
            return $this->sendJsonResponse(true, 'Coupon code retrieved successfully', $coupon);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Update the specified coupon code
     */
    public function update(Request $request): Response
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $coupon = CouponCode::where('uuid', $data['id'])->firstOrFail();

            $validator = Validator::make($request->all(), [
                'code' => 'sometimes|string|max:50|unique:coupon_codes,code,' . $coupon->id,
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'sometimes|required|in:percentage,fixed',
                'value' => 'sometimes|required|numeric|min:0',
                'min_purchase_amount' => 'nullable|numeric|min:0',
                'max_discount_amount' => 'nullable|numeric|min:0',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'usage_limit' => 'nullable|integer|min:0',
                'usage_limit_per_user' => 'nullable|integer|min:0',
                'is_active' => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', ['errors' => $validator->errors()], 422);
            }

            $updateData = $request->except(['id']);
            
            // Convert date strings to datetime if provided
            if ($request->has('start_date') && $request->start_date) {
                $updateData['start_date'] = date('Y-m-d H:i:s', strtotime($request->start_date));
            }
            if ($request->has('end_date') && $request->end_date) {
                $updateData['end_date'] = date('Y-m-d H:i:s', strtotime($request->end_date));
            }

            $coupon->update($updateData);

            return $this->sendJsonResponse(true, 'Coupon code updated successfully', $coupon);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Remove the specified coupon code
     */
    public function destroy(Request $request): Response
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $coupon = CouponCode::where('uuid', $data['id'])->firstOrFail();
            $coupon->delete();

            return $this->sendJsonResponse(true, 'Coupon code deleted successfully', null);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}

<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\Request;
use Exception;

class DiscountController extends Controller
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
                      ->orWhere('code', 'like', "%{$search}%");
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

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $discounts = $query->paginate($perPage);

            return $this->sendJsonResponse(true, 'Discounts retrieved successfully', $discounts);
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
                'type' => 'required|in:percentage,fixed',
                'value' => 'required|numeric|min:0',
                'minimum_amount' => 'nullable|numeric|min:0',
                'usage_limit' => 'nullable|integer|min:1',
                'starts_at' => 'nullable|date',
                'expires_at' => 'nullable|date|after:starts_at',
                'is_active' => 'boolean'
            ]);

            $discount = Discount::create($data);

            return $this->sendJsonResponse(true, 'Discount created successfully', $discount, 201);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function show($id)
    {
        try {
            $discount = Discount::findOrFail($id);
            return $this->sendJsonResponse(true, 'Discount retrieved successfully', $discount);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $discount = Discount::findOrFail($id);

            $data = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'code' => 'nullable|string|unique:discounts,code,' . $id,
                'type' => 'sometimes|required|in:percentage,fixed',
                'value' => 'sometimes|required|numeric|min:0',
                'minimum_amount' => 'nullable|numeric|min:0',
                'usage_limit' => 'nullable|integer|min:1',
                'starts_at' => 'nullable|date',
                'expires_at' => 'nullable|date|after:starts_at',
                'is_active' => 'boolean'
            ]);

            $discount->update($data);

            return $this->sendJsonResponse(true, 'Discount updated successfully', $discount);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function destroy($id)
    {
        try {
            $discount = Discount::findOrFail($id);
            $discount->delete();

            return $this->sendJsonResponse(true, 'Discount deleted successfully');
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $discount = Discount::findOrFail($id);
            $discount->update(['is_active' => !$discount->is_active]);

            return $this->sendJsonResponse(true, 'Discount status updated successfully', $discount);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function validateCode(Request $request)
    {
        try {
            $data = $request->validate([
                'code' => 'required|string',
                'amount' => 'required|numeric|min:0'
            ]);

            $discount = Discount::where('code', $data['code'])->first();

            if (!$discount) {
                return $this->sendJsonResponse(false, 'Invalid discount code', null, 400);
            }

            if (!$discount->isValid()) {
                return $this->sendJsonResponse(false, 'Discount code is not valid', null, 400);
            }

            $discountAmount = $discount->calculateDiscount($data['amount']);

            return $this->sendJsonResponse(true, 'Discount code is valid', [
                'discount' => $discount,
                'discount_amount' => $discountAmount,
                'final_amount' => $data['amount'] - $discountAmount
            ]);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}
<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Exception;

class DiscountController extends Controller
{
    /**
     * Display a listing of discounts
     */
    public function index(Request $request): Response
    {
        try {
            $query = Discount::with('products');
            
            // Search functionality
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%')
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
            $discounts = $query->paginate($perPage);
            
            // Get counts
            $countsQuery = Discount::query();
            if ($request->has('search')) {
                $countsQuery->where('name', 'like', '%' . $request->search . '%')
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
            
            $responseData = $discounts->toArray();
            $responseData['counts'] = [
                'total' => $totalCount,
                'active' => $activeCount,
                'inactive' => $inactiveCount,
            ];
            
            return $this->sendJsonResponse(true, 'Discounts retrieved successfully', $responseData);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Store a newly created discount
     */
    public function store(Request $request): Response
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'required|in:percentage,fixed',
                'value' => 'required|numeric|min:0',
                'min_purchase_amount' => 'nullable|numeric|min:0',
                'max_discount_amount' => 'nullable|numeric|min:0',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'usage_limit' => 'nullable|integer|min:0',
                'is_active' => 'boolean',
                'product_ids' => 'nullable|array',
                'product_ids.*' => 'exists:products,id',
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', ['errors' => $validator->errors()], 422);
            }

            $data = $request->except(['product_ids']);
            
            // Convert date strings to datetime if provided
            if ($request->has('start_date') && $request->start_date) {
                $data['start_date'] = date('Y-m-d H:i:s', strtotime($request->start_date));
            }
            if ($request->has('end_date') && $request->end_date) {
                $data['end_date'] = date('Y-m-d H:i:s', strtotime($request->end_date));
            }
            
            $discount = Discount::create($data);
            
            // Attach products if provided
            if ($request->has('product_ids') && is_array($request->product_ids)) {
                $discount->products()->sync($request->product_ids);
            }

            $discount->load('products');

            return $this->sendJsonResponse(true, 'Discount created successfully', $discount, 201);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Display the specified discount
     */
    public function show(Request $request): Response
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $discount = Discount::with('products')->where('uuid', $data['id'])->firstOrFail();
            
            return $this->sendJsonResponse(true, 'Discount retrieved successfully', $discount);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Update the specified discount
     */
    public function update(Request $request): Response
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $discount = Discount::where('uuid', $data['id'])->firstOrFail();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'type' => 'sometimes|required|in:percentage,fixed',
                'value' => 'sometimes|required|numeric|min:0',
                'min_purchase_amount' => 'nullable|numeric|min:0',
                'max_discount_amount' => 'nullable|numeric|min:0',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'usage_limit' => 'nullable|integer|min:0',
                'is_active' => 'boolean',
                'product_ids' => 'nullable|array',
                'product_ids.*' => 'exists:products,id',
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', ['errors' => $validator->errors()], 422);
            }

            $updateData = $request->except(['id', 'product_ids']);
            
            // Convert date strings to datetime if provided
            if ($request->has('start_date') && $request->start_date) {
                $updateData['start_date'] = date('Y-m-d H:i:s', strtotime($request->start_date));
            }
            if ($request->has('end_date') && $request->end_date) {
                $updateData['end_date'] = date('Y-m-d H:i:s', strtotime($request->end_date));
            }

            $discount->update($updateData);
            
            // Sync products if provided
            if ($request->has('product_ids')) {
                $discount->products()->sync($request->product_ids ?? []);
            }

            $discount->load('products');

            return $this->sendJsonResponse(true, 'Discount updated successfully', $discount);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Remove the specified discount
     */
    public function destroy(Request $request): Response
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $discount = Discount::where('uuid', $data['id'])->firstOrFail();
            $discount->delete();

            return $this->sendJsonResponse(true, 'Discount deleted successfully', null);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}

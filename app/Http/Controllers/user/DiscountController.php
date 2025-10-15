<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use Illuminate\Http\Request;
use Exception;

class DiscountController extends Controller
{
    public function index()
    {
        try {
            $discounts = Discount::where('is_active', true)
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

            return $this->sendJsonResponse(true, 'Active discounts retrieved successfully', $discounts);
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
                return $this->sendJsonResponse(false, 'Discount code is not valid or has expired', null, 400);
            }

            $discountAmount = $discount->calculateDiscount($data['amount']);

            if ($discountAmount <= 0) {
                return $this->sendJsonResponse(false, 'Discount code does not apply to this amount', null, 400);
            }

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
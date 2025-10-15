<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Models\DeliveryLocation;
use App\Models\Product;
use Illuminate\Http\Request;
use Exception;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = DeliveryLocation::where('is_active', true);

            // Search
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('city', 'like', "%{$search}%")
                      ->orWhere('state', 'like', "%{$search}%");
                });
            }

            // Filter by state
            if ($request->has('state')) {
                $query->where('state', $request->get('state'));
            }

            // Filter by city
            if ($request->has('city')) {
                $query->where('city', $request->get('city'));
            }

            // Sort by name
            $query->orderBy('name', 'asc');

            $locations = $query->get();

            return $this->sendJsonResponse(true, 'Delivery locations retrieved successfully', $locations);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function findNearest(Request $request)
    {
        try {
            $data = $request->validate([
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'radius' => 'nullable|integer|min:1|max:100'
            ]);

            $nearestLocation = DeliveryLocation::findNearest(
                $data['latitude'], 
                $data['longitude'], 
                $data['radius'] ?? null
            );

            if (!$nearestLocation) {
                return $this->sendJsonResponse(false, 'No delivery location found within the specified radius', null, 404);
            }

            return $this->sendJsonResponse(true, 'Nearest delivery location found', $nearestLocation);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function checkDelivery(Request $request)
    {
        try {
            $data = $request->validate([
                'product_id' => 'required|string',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180'
            ]);

            $product = Product::where('uuid', $data['product_id'])->firstOrFail();
            
            $deliveryInfo = $product->getDeliveryInfo(
                $data['latitude'], 
                $data['longitude']
            );

            if (!$deliveryInfo) {
                return $this->sendJsonResponse(false, 'Product is not deliverable to this location', [
                    'is_deliverable' => false,
                    'delivery_info' => null
                ], 400);
            }

            return $this->sendJsonResponse(true, 'Delivery information retrieved successfully', [
                'is_deliverable' => true,
                'delivery_info' => $deliveryInfo
            ]);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getStates()
    {
        try {
            $states = DeliveryLocation::where('is_active', true)
                ->distinct()
                ->pluck('state')
                ->sort()
                ->values();

            return $this->sendJsonResponse(true, 'States retrieved successfully', $states);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getCities(Request $request)
    {
        try {
            $data = $request->validate([
                'state' => 'nullable|string'
            ]);

            $query = DeliveryLocation::where('is_active', true);

            if ($data['state']) {
                $query->where('state', $data['state']);
            }

            $cities = $query->distinct()
                ->pluck('city')
                ->sort()
                ->values();

            return $this->sendJsonResponse(true, 'Cities retrieved successfully', $cities);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}
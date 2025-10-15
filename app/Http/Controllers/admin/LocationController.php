<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryLocation;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = DeliveryLocation::query();

            // Search
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('city', 'like', "%{$search}%")
                      ->orWhere('state', 'like', "%{$search}%")
                      ->orWhere('postal_code', 'like', "%{$search}%");
                });
            }

            // Filter by status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->get('is_active'));
            }

            // Filter by state
            if ($request->has('state')) {
                $query->where('state', $request->get('state'));
            }

            // Filter by city
            if ($request->has('city')) {
                $query->where('city', $request->get('city'));
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $locations = $query->paginate($perPage);

            return $this->sendJsonResponse(true, 'Delivery locations retrieved successfully', $locations);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'state' => 'required|string|max:255',
                'country' => 'nullable|string|max:255',
                'postal_code' => 'required|string|max:20',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'address' => 'nullable|string',
                'is_active' => 'boolean',
                'delivery_radius_km' => 'nullable|integer|min:1|max:100',
                'delivery_fee' => 'nullable|numeric|min:0',
                'estimated_delivery_days' => 'nullable|integer|min:1|max:30'
            ]);

            $location = DeliveryLocation::create($data);

            return $this->sendJsonResponse(true, 'Delivery location created successfully', $location, 201);
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

            $location = DeliveryLocation::where('uuid', $data['id'])->firstOrFail();
            $location->load('products');

            return $this->sendJsonResponse(true, 'Delivery location retrieved successfully', $location);
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
                'city' => 'sometimes|required|string|max:255',
                'state' => 'sometimes|required|string|max:255',
                'country' => 'nullable|string|max:255',
                'postal_code' => 'sometimes|required|string|max:20',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'address' => 'nullable|string',
                'is_active' => 'boolean',
                'delivery_radius_km' => 'nullable|integer|min:1|max:100',
                'delivery_fee' => 'nullable|numeric|min:0',
                'estimated_delivery_days' => 'nullable|integer|min:1|max:30'
            ]);

            $location = DeliveryLocation::where('uuid', $data['id'])->firstOrFail();
            unset($data['id']); // Remove id from update data

            $location->update($data);

            return $this->sendJsonResponse(true, 'Delivery location updated successfully', $location);
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

            $location = DeliveryLocation::where('uuid', $data['id'])->firstOrFail();
            $location->delete();

            return $this->sendJsonResponse(true, 'Delivery location deleted successfully');
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

            $location = DeliveryLocation::where('uuid', $data['id'])->firstOrFail();
            $location->update(['is_active' => !$location->is_active]);

            return $this->sendJsonResponse(true, 'Delivery location status updated successfully', $location);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function assignProducts(Request $request)
    {
        try {
            $data = $request->validate([
                'location_id' => 'required|string',
                'product_ids' => 'required|array',
                'product_ids.*' => 'exists:products,id',
                'delivery_fee' => 'nullable|numeric|min:0',
                'estimated_delivery_days' => 'nullable|integer|min:1|max:30',
                'is_available' => 'boolean'
            ]);

            $location = DeliveryLocation::where('uuid', $data['location_id'])->firstOrFail();

            $syncData = [];
            foreach ($data['product_ids'] as $productId) {
                $syncData[$productId] = [
                    'delivery_fee' => $data['delivery_fee'] ?? $location->delivery_fee,
                    'estimated_delivery_days' => $data['estimated_delivery_days'] ?? $location->estimated_delivery_days,
                    'is_available' => $data['is_available'] ?? true
                ];
            }

            $location->products()->sync($syncData);

            return $this->sendJsonResponse(true, 'Products assigned to delivery location successfully');
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function removeProducts(Request $request)
    {
        try {
            $data = $request->validate([
                'location_id' => 'required|string',
                'product_ids' => 'required|array',
                'product_ids.*' => 'exists:products,id'
            ]);

            $location = DeliveryLocation::where('uuid', $data['location_id'])->firstOrFail();
            $location->products()->detach($data['product_ids']);

            return $this->sendJsonResponse(true, 'Products removed from delivery location successfully');
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getStats()
    {
        try {
            $stats = [
                'total_locations' => DeliveryLocation::count(),
                'active_locations' => DeliveryLocation::where('is_active', true)->count(),
                'locations_with_coordinates' => DeliveryLocation::whereNotNull('latitude')->whereNotNull('longitude')->count(),
                'total_products_assigned' => DeliveryLocation::withCount('products')->get()->sum('products_count'),
                'states_covered' => DeliveryLocation::distinct('state')->count('state'),
                'cities_covered' => DeliveryLocation::distinct('city')->count('city'),
            ];

            return $this->sendJsonResponse(true, 'Location statistics retrieved successfully', $stats);
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
}
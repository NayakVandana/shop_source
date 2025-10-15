<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\DeliverySchedule;
use App\Models\Product;
use App\Models\DeliveryLocation;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;

class DeliveryScheduleController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = DeliverySchedule::with(['product', 'deliveryLocation']);

            // Filter by product
            if ($request->has('product_id')) {
                $query->where('product_id', $request->get('product_id'));
            }

            // Filter by delivery location
            if ($request->has('delivery_location_id')) {
                $query->where('delivery_location_id', $request->get('delivery_location_id'));
            }

            // Filter by delivery date
            if ($request->has('delivery_date')) {
                $query->whereDate('delivery_date', $request->get('delivery_date'));
            }

            // Filter by delivery type
            if ($request->has('delivery_type')) {
                $query->where('delivery_type', $request->get('delivery_type'));
            }

            // Filter by availability
            if ($request->has('is_available')) {
                $query->where('is_available', $request->get('is_available'));
            }

            // Filter by express delivery
            if ($request->has('is_express')) {
                $query->where('is_express', $request->get('is_express'));
            }

            // Filter by date range
            if ($request->has('date_from')) {
                $query->whereDate('delivery_date', '>=', $request->get('date_from'));
            }

            if ($request->has('date_to')) {
                $query->whereDate('delivery_date', '<=', $request->get('date_to'));
            }

            // Special filters
            if ($request->has('today')) {
                $query->today();
            }

            if ($request->has('tomorrow')) {
                $query->tomorrow();
            }

            if ($request->has('available')) {
                $query->available();
            }

            // Sort
            $sortBy = $request->get('sort_by', 'delivery_date');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $schedules = $query->paginate($perPage);

            return $this->sendJsonResponse(true, 'Delivery schedules retrieved successfully', $schedules);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'product_id' => 'required|string|exists:products,uuid',
                'delivery_location_id' => 'required|integer|exists:delivery_locations,id',
                'delivery_date' => 'required|date|after_or_equal:today',
                'start_time' => 'nullable|date_format:H:i',
                'end_time' => 'nullable|date_format:H:i|after:start_time',
                'max_orders' => 'nullable|integer|min:1',
                'delivery_fee' => 'nullable|numeric|min:0',
                'is_available' => 'boolean',
                'is_express' => 'boolean',
                'delivery_type' => 'required|string|in:standard,express,scheduled,same_day,next_day',
                'notes' => 'nullable|string|max:1000',
                'time_slots' => 'nullable|array',
                'time_slots.*.time' => 'required|date_format:H:i',
                'time_slots.*.max_orders' => 'nullable|integer|min:1',
                'cutoff_time' => 'nullable|date|after:now'
            ]);

            // Get product ID from UUID
            $product = Product::where('uuid', $data['product_id'])->firstOrFail();
            $data['product_id'] = $product->id;

            // Check for duplicate schedule
            $existingSchedule = DeliverySchedule::where('product_id', $product->id)
                ->where('delivery_location_id', $data['delivery_location_id'])
                ->whereDate('delivery_date', $data['delivery_date'])
                ->first();

            if ($existingSchedule) {
                return $this->sendJsonResponse(false, 'Delivery schedule already exists for this product, location, and date', null, 400);
            }

            $schedule = DeliverySchedule::create($data);

            return $this->sendJsonResponse(true, 'Delivery schedule created successfully', $schedule->load(['product', 'deliveryLocation']), 201);
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

            $schedule = DeliverySchedule::where('uuid', $data['id'])
                ->with(['product', 'deliveryLocation', 'orders'])
                ->firstOrFail();

            return $this->sendJsonResponse(true, 'Delivery schedule retrieved successfully', $schedule);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function update(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|string',
                'delivery_date' => 'sometimes|required|date|after_or_equal:today',
                'start_time' => 'nullable|date_format:H:i',
                'end_time' => 'nullable|date_format:H:i|after:start_time',
                'max_orders' => 'nullable|integer|min:1',
                'delivery_fee' => 'nullable|numeric|min:0',
                'is_available' => 'boolean',
                'is_express' => 'boolean',
                'delivery_type' => 'sometimes|required|string|in:standard,express,scheduled,same_day,next_day',
                'notes' => 'nullable|string|max:1000',
                'time_slots' => 'nullable|array',
                'time_slots.*.time' => 'required|date_format:H:i',
                'time_slots.*.max_orders' => 'nullable|integer|min:1',
                'cutoff_time' => 'nullable|date|after:now'
            ]);

            $schedule = DeliverySchedule::where('uuid', $data['id'])->firstOrFail();
            unset($data['id']); // Remove id from update data

            $schedule->update($data);

            return $this->sendJsonResponse(true, 'Delivery schedule updated successfully', $schedule->load(['product', 'deliveryLocation']));
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

            $schedule = DeliverySchedule::where('uuid', $data['id'])->firstOrFail();
            $schedule->delete();

            return $this->sendJsonResponse(true, 'Delivery schedule deleted successfully');
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function toggleAvailability(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $schedule = DeliverySchedule::where('uuid', $data['id'])->firstOrFail();
            $schedule->update(['is_available' => !$schedule->is_available]);

            return $this->sendJsonResponse(true, 'Delivery schedule availability updated successfully', $schedule);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getAvailableTimeSlots(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $schedule = DeliverySchedule::where('uuid', $data['id'])->firstOrFail();
            $timeSlots = $schedule->getAvailableTimeSlots();

            return $this->sendJsonResponse(true, 'Available time slots retrieved successfully', $timeSlots);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function bookTimeSlot(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|string',
                'time' => 'required|date_format:H:i'
            ]);

            $schedule = DeliverySchedule::where('uuid', $data['id'])->firstOrFail();
            $schedule->bookTimeSlot($data['time']);

            return $this->sendJsonResponse(true, 'Time slot booked successfully', $schedule->fresh());
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function releaseTimeSlot(Request $request)
    {
        try {
            $data = $request->validate([
                'id' => 'required|string',
                'time' => 'required|date_format:H:i'
            ]);

            $schedule = DeliverySchedule::where('uuid', $data['id'])->firstOrFail();
            $schedule->releaseTimeSlot($data['time']);

            return $this->sendJsonResponse(true, 'Time slot released successfully', $schedule->fresh());
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getStats()
    {
        try {
            $stats = [
                'total_schedules' => DeliverySchedule::count(),
                'available_schedules' => DeliverySchedule::available()->count(),
                'today_schedules' => DeliverySchedule::today()->count(),
                'tomorrow_schedules' => DeliverySchedule::tomorrow()->count(),
                'express_schedules' => DeliverySchedule::express()->count(),
                'schedules_by_type' => DeliverySchedule::selectRaw('delivery_type, count(*) as count')
                    ->groupBy('delivery_type')
                    ->get(),
                'schedules_by_location' => DeliverySchedule::with('deliveryLocation')
                    ->selectRaw('delivery_location_id, count(*) as count')
                    ->groupBy('delivery_location_id')
                    ->get(),
                'upcoming_schedules' => DeliverySchedule::whereDate('delivery_date', '>=', today())
                    ->where('is_available', true)
                    ->count(),
            ];

            return $this->sendJsonResponse(true, 'Delivery schedule statistics retrieved successfully', $stats);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function createBulkSchedules(Request $request)
    {
        try {
            $data = $request->validate([
                'product_id' => 'required|string|exists:products,uuid',
                'delivery_location_id' => 'required|integer|exists:delivery_locations,id',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after_or_equal:start_date',
                'delivery_type' => 'required|string|in:standard,express,scheduled,same_day,next_day',
                'time_slots' => 'nullable|array',
                'max_orders' => 'nullable|integer|min:1',
                'delivery_fee' => 'nullable|numeric|min:0',
                'exclude_weekends' => 'boolean',
                'exclude_dates' => 'nullable|array',
                'exclude_dates.*' => 'date'
            ]);

            $product = Product::where('uuid', $data['product_id'])->firstOrFail();
            $excludeDates = $data['exclude_dates'] ?? [];
            $excludeWeekends = $data['exclude_weekends'] ?? false;

            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);
            $createdSchedules = [];

            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                // Skip weekends if requested
                if ($excludeWeekends && $date->isWeekend()) {
                    continue;
                }

                // Skip excluded dates
                if (in_array($date->format('Y-m-d'), $excludeDates)) {
                    continue;
                }

                // Check if schedule already exists
                $existingSchedule = DeliverySchedule::where('product_id', $product->id)
                    ->where('delivery_location_id', $data['delivery_location_id'])
                    ->whereDate('delivery_date', $date)
                    ->first();

                if ($existingSchedule) {
                    continue;
                }

                $scheduleData = [
                    'product_id' => $product->id,
                    'delivery_location_id' => $data['delivery_location_id'],
                    'delivery_date' => $date,
                    'delivery_type' => $data['delivery_type'],
                    'time_slots' => $data['time_slots'],
                    'max_orders' => $data['max_orders'],
                    'delivery_fee' => $data['delivery_fee'],
                    'is_available' => true,
                    'is_express' => $data['delivery_type'] === 'express',
                ];

                $schedule = DeliverySchedule::create($scheduleData);
                $createdSchedules[] = $schedule;
            }

            return $this->sendJsonResponse(true, 'Bulk delivery schedules created successfully', $createdSchedules, 201);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}
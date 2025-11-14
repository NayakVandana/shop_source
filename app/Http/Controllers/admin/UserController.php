<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index(Request $request): Response
    {
        try {
            $query = User::query();
            
            // Get all request data for debugging
            $allRequest = $request->all();
            
            // Search functionality
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('email', 'like', '%' . $search . '%')
                      ->orWhere('mobile', 'like', '%' . $search . '%');
                });
            }
            
            // Role filter
            if ($request->filled('role')) {
                $role = $request->input('role');
                $query->where('role', $role);
            }
            
            // Admin filter - check if parameter exists in request (even if false)
            if (array_key_exists('is_admin', $allRequest)) {
                $isAdmin = $request->input('is_admin');
                // Handle boolean, string, or numeric values
                if (is_bool($isAdmin)) {
                    $query->where('is_admin', $isAdmin);
                } else {
                    $isAdminBool = filter_var($isAdmin, FILTER_VALIDATE_BOOLEAN);
                    $query->where('is_admin', $isAdminBool);
                }
            }
            
            // Active status filter - check if parameter exists in request (even if false)
            if (array_key_exists('is_active', $allRequest)) {
                $isActive = $request->input('is_active');
                // Handle boolean, string, or numeric values
                if (is_bool($isActive)) {
                    $query->where('is_active', $isActive);
                } else {
                    $isActiveBool = filter_var($isActive, FILTER_VALIDATE_BOOLEAN);
                    $query->where('is_active', $isActiveBool);
                }
            }
            
            // Registered status filter
            if (array_key_exists('is_registered', $allRequest)) {
                $isRegistered = $request->input('is_registered');
                if (is_bool($isRegistered)) {
                    $query->where('is_registered', $isRegistered);
                } else {
                    $isRegisteredBool = filter_var($isRegistered, FILTER_VALIDATE_BOOLEAN);
                    $query->where('is_registered', $isRegisteredBool);
                }
            }
            
            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);
            
            // Pagination
            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);
            
            $users = $query->paginate($perPage, ['*'], 'page', $page);
            
            // Remove sensitive data from response
            $users->getCollection()->transform(function ($user) {
                unset($user->password);
                return $user;
            });
            
            return $this->sendJsonResponse(true, 'Users retrieved successfully', $users);
            
        } catch (Exception $e) {
            \Log::error('UserController index error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            return $this->sendError($e);
        }
    }

    /**
     * Display the specified user
     */
    public function show(Request $request): Response
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $user = User::where('uuid', $data['id'])->firstOrFail();
            
            // Remove sensitive data
            unset($user->password);
            
            return $this->sendJsonResponse(true, 'User retrieved successfully', $user);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Update the specified user
     */
    public function update(Request $request): Response
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $user = User::where('uuid', $data['id'])->firstOrFail();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|max:191|unique:users,email,' . $user->id,
                'mobile' => 'nullable|string|max:20|unique:users,mobile,' . $user->id,
                'role' => 'sometimes|required|string',
                'is_registered' => 'boolean',
                'is_active' => 'boolean',
                'is_admin' => 'boolean',
                'password' => 'nullable|string|min:8',
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', ['errors' => $validator->errors()], 422);
            }

            $updateData = $request->except(['id', 'password']);
            
            // Handle password update separately if provided
            if ($request->has('password') && !empty($request->password)) {
                $updateData['password'] = bcrypt($request->password);
            }

            $user->update($updateData);
            
            // Remove sensitive data
            unset($user->password);

            return $this->sendJsonResponse(true, 'User updated successfully', $user);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Remove the specified user
     */
    public function destroy(Request $request): Response
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $user = User::where('uuid', $data['id'])->firstOrFail();

            // Prevent deleting yourself
            $currentUser = Auth::guard('admin')->user() ?? $request->user();
            if ($currentUser && $user->id === $currentUser->id) {
                return $this->sendJsonResponse(false, 'You cannot delete your own account', null, 422);
            }

            $user->delete();

            return $this->sendJsonResponse(true, 'User deleted successfully', null);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}


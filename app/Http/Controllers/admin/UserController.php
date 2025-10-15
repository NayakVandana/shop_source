<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Exception;

class UserController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = User::query();

            // Search
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('mobile', 'like', "%{$search}%");
                });
            }

            // Filter by role
            if ($request->has('role')) {
                $query->where('role', $request->get('role'));
            }

            // Filter by status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->get('is_active'));
            }

            // Filter by admin status
            if ($request->has('is_admin')) {
                $query->where('is_admin', $request->get('is_admin'));
            }

            // Sort
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $users = $query->paginate($perPage);

            return $this->sendJsonResponse(true, 'Users retrieved successfully', $users);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'mobile' => 'required|numeric|digits_between:10,12|unique:users,mobile',
                'password' => 'required|string|min:8',
                'role' => 'required|in:user,admin',
                'is_active' => 'boolean'
            ]);

            $data['password'] = Hash::make($data['password']);
            $data['is_registered'] = true;
            $data['is_admin'] = $data['role'] === 'admin';

            $user = User::create($data);

            return $this->sendJsonResponse(true, 'User created successfully', $user, 201);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function show($id)
    {
        try {
            $user = User::with(['orders', 'cartItems.product'])->findOrFail($id);
            return $this->sendJsonResponse(true, 'User retrieved successfully', $user);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $data = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:users,email,' . $id,
                'mobile' => 'sometimes|required|numeric|digits_between:10,12|unique:users,mobile,' . $id,
                'password' => 'sometimes|required|string|min:8',
                'role' => 'sometimes|required|in:user,admin',
                'is_active' => 'boolean'
            ]);

            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            if (isset($data['role'])) {
                $data['is_admin'] = $data['role'] === 'admin';
            }

            $user->update($data);

            return $this->sendJsonResponse(true, 'User updated successfully', $user);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function destroy($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Prevent deletion of admin users
            if ($user->is_admin) {
                return $this->sendJsonResponse(false, 'Cannot delete admin users', null, 400);
            }

            $user->delete();

            return $this->sendJsonResponse(true, 'User deleted successfully');
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->update(['is_active' => !$user->is_active]);

            return $this->sendJsonResponse(true, 'User status updated successfully', $user);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getUserStats()
    {
        try {
            $stats = [
                'total_users' => User::count(),
                'active_users' => User::where('is_active', true)->count(),
                'inactive_users' => User::where('is_active', false)->count(),
                'admin_users' => User::where('is_admin', true)->count(),
                'regular_users' => User::where('is_admin', false)->count(),
                'new_users_this_month' => User::whereMonth('created_at', now()->month)->count(),
            ];

            return $this->sendJsonResponse(true, 'User statistics retrieved successfully', $stats);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}
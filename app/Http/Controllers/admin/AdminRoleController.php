<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use App\Models\AdminPermission;
use App\Models\User;
use App\Traits\AdminPermissionTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Exception;

class AdminRoleController extends Controller
{
    use AdminPermissionTrait;

    public function index(Request $request)
    {
        // Check permission
        $permissionCheck = $this->checkPermission('admin.roles');
        if ($permissionCheck) return $permissionCheck;

        try {
            $query = AdminRole::with('permissions');

            // Search
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', $request->get('is_active'));
            }

            // Filter by system roles
            if ($request->has('is_system')) {
                $query->where('is_system', $request->get('is_system'));
            }

            // Sort
            $sortBy = $request->get('sort_by', 'sort_order');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $roles = $query->paginate($perPage);

            return $this->sendJsonResponse(true, 'Admin roles retrieved successfully', $roles);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function store(Request $request)
    {
        // Check permission
        $permissionCheck = $this->checkPermission('admin.roles');
        if ($permissionCheck) return $permissionCheck;

        try {
            $data = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'permissions' => 'nullable|array',
                'permissions.*' => 'exists:admin_permissions,slug',
                'is_active' => 'boolean',
                'sort_order' => 'nullable|integer|min:0'
            ]);

            $data['uuid'] = Str::uuid();
            $data['slug'] = Str::slug($data['name']);
            $data['is_system'] = false; // Custom roles are not system roles

            $role = AdminRole::create($data);

            // Assign permissions
            if (isset($data['permissions'])) {
                $permissions = AdminPermission::whereIn('slug', $data['permissions'])->get();
                $role->permissions()->sync($permissions->pluck('id')->toArray());
            }

            return $this->sendJsonResponse(true, 'Admin role created successfully', $role->load('permissions'), 201);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function show(Request $request)
    {
        // Check permission
        $permissionCheck = $this->checkPermission('admin.roles');
        if ($permissionCheck) return $permissionCheck;

        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $role = AdminRole::where('uuid', $data['id'])
                ->with(['permissions', 'users'])
                ->firstOrFail();

            return $this->sendJsonResponse(true, 'Admin role retrieved successfully', $role);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function update(Request $request)
    {
        // Check permission
        $permissionCheck = $this->checkPermission('admin.roles');
        if ($permissionCheck) return $permissionCheck;

        try {
            $data = $request->validate([
                'id' => 'required|string',
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string|max:1000',
                'permissions' => 'nullable|array',
                'permissions.*' => 'exists:admin_permissions,slug',
                'is_active' => 'boolean',
                'sort_order' => 'nullable|integer|min:0'
            ]);

            $role = AdminRole::where('uuid', $data['id'])->firstOrFail();

            // Prevent modification of system roles
            if ($role->is_system) {
                return $this->sendJsonResponse(false, 'Cannot modify system roles', null, 403);
            }

            unset($data['id']); // Remove id from update data

            if (isset($data['name'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $role->update($data);

            // Update permissions
            if (isset($data['permissions'])) {
                $permissions = AdminPermission::whereIn('slug', $data['permissions'])->get();
                $role->permissions()->sync($permissions->pluck('id')->toArray());
            }

            return $this->sendJsonResponse(true, 'Admin role updated successfully', $role->load('permissions'));
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function destroy(Request $request)
    {
        // Check permission
        $permissionCheck = $this->checkPermission('admin.roles');
        if ($permissionCheck) return $permissionCheck;

        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $role = AdminRole::where('uuid', $data['id'])->firstOrFail();

            // Prevent deletion of system roles
            if ($role->is_system) {
                return $this->sendJsonResponse(false, 'Cannot delete system roles', null, 403);
            }

            // Check if role has users
            if ($role->users()->count() > 0) {
                return $this->sendJsonResponse(false, 'Cannot delete role with assigned users', null, 403);
            }

            $role->delete();

            return $this->sendJsonResponse(true, 'Admin role deleted successfully');
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getPermissions(Request $request)
    {
        // Check permission
        $permissionCheck = $this->checkPermission('admin.permissions');
        if ($permissionCheck) return $permissionCheck;

        try {
            $permissions = AdminPermission::where('is_active', true)
                ->orderBy('module')
                ->orderBy('name')
                ->get()
                ->groupBy('module');

            return $this->sendJsonResponse(true, 'Permissions retrieved successfully', $permissions);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function assignRole(Request $request)
    {
        // Check permission
        $permissionCheck = $this->checkPermission('admin.roles');
        if ($permissionCheck) return $permissionCheck;

        try {
            $data = $request->validate([
                'user_id' => 'required|string|exists:users,uuid',
                'role_id' => 'required|string|exists:admin_roles,uuid'
            ]);

            $user = User::where('uuid', $data['user_id'])->firstOrFail();
            $role = AdminRole::where('uuid', $data['role_id'])->firstOrFail();

            $user->update(['admin_role_id' => $role->id]);

            return $this->sendJsonResponse(true, 'Role assigned to user successfully', $user->load('adminRole'));
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function removeRole(Request $request)
    {
        // Check permission
        $permissionCheck = $this->checkPermission('admin.roles');
        if ($permissionCheck) return $permissionCheck;

        try {
            $data = $request->validate([
                'user_id' => 'required|string|exists:users,uuid'
            ]);

            $user = User::where('uuid', $data['user_id'])->firstOrFail();
            $user->update(['admin_role_id' => null]);

            return $this->sendJsonResponse(true, 'Role removed from user successfully', $user->load('adminRole'));
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    public function getStats()
    {
        // Check permission
        $permissionCheck = $this->checkPermission('admin.roles');
        if ($permissionCheck) return $permissionCheck;

        try {
            $stats = [
                'total_roles' => AdminRole::count(),
                'active_roles' => AdminRole::where('is_active', true)->count(),
                'system_roles' => AdminRole::where('is_system', true)->count(),
                'custom_roles' => AdminRole::where('is_system', false)->count(),
                'roles_with_users' => AdminRole::has('users')->count(),
                'total_permissions' => AdminPermission::count(),
                'active_permissions' => AdminPermission::where('is_active', true)->count(),
                'permissions_by_module' => AdminPermission::selectRaw('module, count(*) as count')
                    ->groupBy('module')
                    ->get(),
            ];

            return $this->sendJsonResponse(true, 'Admin role statistics retrieved successfully', $stats);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}
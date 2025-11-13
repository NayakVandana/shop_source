<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions
     */
    public function index(Request $request): Response
    {
        try {
            $query = Permission::query();
            
            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('slug', 'like', '%' . $search . '%')
                      ->orWhere('description', 'like', '%' . $search . '%')
                      ->orWhere('module', 'like', '%' . $search . '%')
                      ->orWhere('action', 'like', '%' . $search . '%');
                });
            }
            
            // Filter by module
            if ($request->has('module')) {
                $query->where('module', $request->module);
            }
            
            // Filter by action
            if ($request->has('action')) {
                $query->where('action', $request->action);
            }
            
            // Sorting
            $sortBy = $request->get('sort_by', 'name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);
            
            // Pagination
            $perPage = $request->get('per_page', 15);
            $permissions = $query->paginate($perPage);
            
            // Add roles for each permission
            $permissions->getCollection()->transform(function ($permission) {
                $permission->roles = DB::table('role_permissions')
                    ->where('permission_id', $permission->id)
                    ->pluck('role')
                    ->toArray();
                return $permission;
            });
            
            return $this->sendJsonResponse(true, 'Permissions retrieved successfully', $permissions);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Store a newly created permission
     */
    public function store(Request $request): Response
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'module' => 'nullable|string|max:255',
                'action' => 'nullable|string|max:255',
                'slug' => 'nullable|string|unique:permissions,slug',
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', ['errors' => $validator->errors()], 422);
            }

            $data = $request->except(['roles']);
            
            // Generate slug if not provided
            if (empty($data['slug']) && !empty($data['name'])) {
                $data['slug'] = \Illuminate\Support\Str::slug($data['name']);
            }
            
            $permission = Permission::create($data);
            
            // Assign to roles if provided
            if ($request->has('roles') && is_array($request->roles)) {
                foreach ($request->roles as $role) {
                    if (in_array($role, UserRole::values())) {
                        $permission->assignToRole($role);
                    }
                }
            }
            
            // Load roles for response
            $permission->roles = DB::table('role_permissions')
                ->where('permission_id', $permission->id)
                ->pluck('role')
                ->toArray();

            return $this->sendJsonResponse(true, 'Permission created successfully', $permission, 201);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Display the specified permission
     */
    public function show(Request $request): Response
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $permission = Permission::where('id', $data['id'])
                ->orWhere('slug', $data['id'])
                ->orWhere('uuid', $data['id'])
                ->firstOrFail();
            
            // Load roles
            $permission->roles = DB::table('role_permissions')
                ->where('permission_id', $permission->id)
                ->pluck('role')
                ->toArray();
            
            return $this->sendJsonResponse(true, 'Permission retrieved successfully', $permission);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Update the specified permission
     */
    public function update(Request $request): Response
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $permission = Permission::where('id', $data['id'])
                ->orWhere('slug', $data['id'])
                ->firstOrFail();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'module' => 'nullable|string|max:255',
                'action' => 'nullable|string|max:255',
                'slug' => 'sometimes|string|unique:permissions,slug,' . $permission->id,
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', ['errors' => $validator->errors()], 422);
            }

            $updateData = $request->except(['id', 'roles']);
            $permission->update($updateData);
            
            // Update role assignments if provided
            if ($request->has('roles') && is_array($request->roles)) {
                // Get all valid roles
                $validRoles = UserRole::values();
                
                // Remove all existing role assignments
                DB::table('role_permissions')->where('permission_id', $permission->id)->delete();
                
                // Assign new roles
                foreach ($request->roles as $role) {
                    if (in_array($role, $validRoles)) {
                        $permission->assignToRole($role);
                    }
                }
            }
            
            // Load roles for response
            $permission->roles = DB::table('role_permissions')
                ->where('permission_id', $permission->id)
                ->pluck('role')
                ->toArray();

            return $this->sendJsonResponse(true, 'Permission updated successfully', $permission);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Remove the specified permission
     */
    public function destroy(Request $request): Response
    {
        try {
            $data = $request->validate([
                'id' => 'required|string'
            ]);

            $permission = Permission::where('id', $data['id'])
                ->orWhere('slug', $data['id'])
                ->firstOrFail();

            $permission->delete();

            return $this->sendJsonResponse(true, 'Permission deleted successfully', null);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Get all available roles
     */
    public function roles(): Response|JsonResponse
    {
        try {
            $roles = array_map(function ($role) {
                return [
                    'value' => $role,
                    'label' => UserRole::from($role)->label(),
                ];
            }, UserRole::values());
            
            return $this->sendJsonResponse(true, 'Roles retrieved successfully', $roles);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Get all modules
     */
    public function modules(): Response|JsonResponse
    {
        try {
            $modules = Permission::distinct()
                ->whereNotNull('module')
                ->pluck('module')
                ->toArray();
            
            return $this->sendJsonResponse(true, 'Modules retrieved successfully', $modules);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Update roles for a permission
     */
    public function updateRoles(Request $request): Response|JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|string',
                'roles' => 'required|array',
                'roles.*' => 'string',
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', ['errors' => $validator->errors()], 422);
            }

            $permission = Permission::where('id', $request->id)
                ->orWhere('slug', $request->id)
                ->firstOrFail();

            $roles = $request->roles;
            $validRoles = UserRole::values();
            
            // Remove all existing role assignments
            DB::table('role_permissions')->where('permission_id', $permission->id)->delete();
            
            // Assign new roles
            foreach ($roles as $role) {
                if (in_array($role, $validRoles)) {
                    $permission->assignToRole($role);
                }
            }
            
            // Load roles for response
            $permission->roles = DB::table('role_permissions')
                ->where('permission_id', $permission->id)
                ->pluck('role')
                ->toArray();

            return $this->sendJsonResponse(true, 'Permission roles updated successfully', $permission);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Get all actions
     */
    public function actions(): Response|JsonResponse
    {
        try {
            $actions = Permission::distinct()
                ->whereNotNull('action')
                ->pluck('action')
                ->toArray();
            
            return $this->sendJsonResponse(true, 'Actions retrieved successfully', $actions);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Get permissions grouped by module
     */
    public function groupedByModule(): Response|JsonResponse
    {
        try {
            $permissions = Permission::all();
            
            // Group by module
            $grouped = [];
            foreach ($permissions as $permission) {
                $module = $permission->module ?: 'other';
                if (!isset($grouped[$module])) {
                    $grouped[$module] = [];
                }
                
                // Get roles for this permission
                $roles = DB::table('role_permissions')
                    ->where('permission_id', $permission->id)
                    ->pluck('role')
                    ->toArray();
                
                $grouped[$module][] = [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'slug' => $permission->slug,
                    'action' => $permission->action,
                    'description' => $permission->description,
                    'roles' => $roles,
                ];
            }
            
            return $this->sendJsonResponse(true, 'Permissions grouped by module', $grouped);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Get permissions grouped by role
     */
    public function groupedByRole(): Response|JsonResponse
    {
        try {
            $permissions = Permission::all();
            $allRoles = UserRole::values();
            
            // Group by role
            $grouped = [];
            foreach ($allRoles as $role) {
                if ($role === 'user') continue; // Skip user role
                
                $grouped[$role] = [];
                
                // Get permissions for this role
                foreach ($permissions as $permission) {
                    $hasRole = DB::table('role_permissions')
                        ->where('permission_id', $permission->id)
                        ->where('role', $role)
                        ->exists();
                    
                    if ($hasRole) {
                        $grouped[$role][] = [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'slug' => $permission->slug,
                            'module' => $permission->module,
                            'action' => $permission->action,
                            'description' => $permission->description,
                        ];
                    }
                }
            }
            
            return $this->sendJsonResponse(true, 'Permissions grouped by role', $grouped);
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Create multiple permissions for a module at once
     */
    public function createBulk(Request $request): Response|JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'module' => 'required|string|max:255',
                'actions' => 'required|array',
                'actions.*' => 'required|string|max:255',
                'roles' => 'nullable|array',
                'roles.*' => 'string',
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', ['errors' => $validator->errors()], 422);
            }

            $module = $request->module;
            $actions = $request->actions;
            $roles = $request->roles ?? [];
            
            // Standard action labels
            $actionLabels = [
                'view' => 'View',
                'create' => 'Create',
                'update' => 'Update',
                'edit' => 'Edit',
                'delete' => 'Delete',
                'manage' => 'Manage',
                'statistics' => 'Statistics',
            ];

            $createdPermissions = [];
            foreach ($actions as $action) {
                // Check if permission already exists
                $existing = Permission::where('module', $module)
                    ->where('action', $action)
                    ->first();

                if ($existing) {
                    // Update roles if provided
                    if (!empty($roles)) {
                        DB::table('role_permissions')
                            ->where('permission_id', $existing->id)
                            ->delete();
                        
                        foreach ($roles as $role) {
                            if (in_array($role, UserRole::values())) {
                                $existing->assignToRole($role);
                            }
                        }
                    }
                    $createdPermissions[] = $existing;
                } else {
                    // Create new permission
                    $actionLabel = $actionLabels[$action] ?? ucfirst($action);
                    $name = $actionLabel . ' ' . ucfirst($module);
                    
                    $permission = Permission::create([
                        'name' => $name,
                        'slug' => \Illuminate\Support\Str::slug($name),
                        'module' => $module,
                        'action' => $action,
                        'description' => "Can {$action} {$module}",
                    ]);

                    // Assign to roles if provided
                    foreach ($roles as $role) {
                        if (in_array($role, UserRole::values())) {
                            $permission->assignToRole($role);
                        }
                    }

                    $createdPermissions[] = $permission;
                }
            }

            return $this->sendJsonResponse(true, 'Permissions created successfully', [
                'permissions' => $createdPermissions,
                'count' => count($createdPermissions)
            ], 201);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}


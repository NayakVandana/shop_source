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
                    // Check if role assignment already exists
                    $exists = DB::table('role_permissions')
                        ->where('permission_id', $permission->id)
                        ->where('role', $role)
                        ->exists();
                    
                    if (!$exists) {
                        DB::table('role_permissions')->insert([
                            'permission_id' => $permission->id,
                            'role' => $role,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
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

            // Ensure super_admin is always included in roles (super admin should have all permissions by default)
            $superAdminRole = UserRole::SUPER_ADMIN->value;
            if (!in_array($superAdminRole, $roles)) {
                $roles[] = $superAdminRole;
            }

            $createdPermissions = [];
            foreach ($actions as $action) {
                // Check if permission already exists
                $existing = Permission::where('module', $module)
                    ->where('action', $action)
                    ->first();

                if ($existing) {
                    // Add roles additively (don't remove existing role assignments, especially super_admin)
                    if (!empty($roles)) {
                        foreach ($roles as $role) {
                            if (in_array($role, UserRole::values())) {
                                // Check if role assignment already exists
                                $exists = DB::table('role_permissions')
                                    ->where('permission_id', $existing->id)
                                    ->where('role', $role)
                                    ->exists();
                                
                                if (!$exists) {
                                    DB::table('role_permissions')->insert([
                                        'permission_id' => $existing->id,
                                        'role' => $role,
                                        'created_at' => now(),
                                        'updated_at' => now(),
                                    ]);
                                }
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

                    // Assign to roles (super_admin is already included in the roles array)
                    foreach ($roles as $role) {
                        if (in_array($role, UserRole::values())) {
                            // Check if role assignment already exists
                            $exists = DB::table('role_permissions')
                                ->where('permission_id', $permission->id)
                                ->where('role', $role)
                                ->exists();
                            
                            if (!$exists) {
                                DB::table('role_permissions')->insert([
                                    'permission_id' => $permission->id,
                                    'role' => $role,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
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


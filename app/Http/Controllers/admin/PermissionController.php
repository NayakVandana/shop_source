<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserPermission;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Exception;

class PermissionController extends Controller
{
    /**
     * Get default modules and actions
     * This should match the Modules constant in AdminPermission.tsx
     */
    private static function getDefaultModules(): array
    {
        return [
            'products' => ['view', 'create', 'update', 'delete'],
            'categories' => ['view', 'create', 'update', 'delete'],
            'users' => ['view', 'create', 'update', 'delete'],
            'permissions' => ['view', 'create', 'update', 'delete', 'manage'],
            'dashboard' => ['view', 'statistics'],
            'orders' => ['view', 'create', 'update', 'delete'],
            'account' => ['view', 'create', 'update', 'delete'],
        ];
    }

    /**
     * Generate all default permission objects
     * Returns array of objects: [{"module": "products", "action": "view", "permission": "products:view"}, ...]
     */
    private static function generateAllDefaultPermissions(): array
    {
        $permissions = [];
        $modules = self::getDefaultModules();
        
        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $permissionKey = "{$module}:{$action}";
                $permissions[] = [
                    'module' => $module,
                    'action' => $action,
                    'permission' => $permissionKey,
                ];
            }
        }
        
        return $permissions;
    }

    /**
     * Get permissions for a user as array of objects
     * Format: [{"module": "products", "action": "view", "permission": "products:view"}, ...]
     * Super admin has all permissions by default (generates all possible permissions)
     */
    public static function getUserPermissions(User $user): array
    {
        if ($user->isSuperAdmin()) {
            // Super admin always has all permissions - generate from default modules
            $dbPermissions = self::getAllPermissionObjects();
            if (!empty($dbPermissions)) {
                return $dbPermissions;
            }
            // If no permissions in DB, return all default permissions
            return self::generateAllDefaultPermissions();
        }

        $userPermission = UserPermission::where('user_id', $user->id)
            ->where('role', $user->role ?? 'user')
            ->first();

        if (!$userPermission || !$userPermission->permissions) {
            return [];
        }

        return $userPermission->permissions ?? [];
    }

    /**
     * Get permission keys as simple array (for backward compatibility)
     */
    public static function getUserPermissionKeys(User $user): array
    {
        $permissions = self::getUserPermissions($user);
        $keys = [];
        foreach ($permissions as $perm) {
            if (is_string($perm)) {
                $keys[] = $perm;
            } elseif (is_array($perm) && isset($perm['permission'])) {
                $keys[] = $perm['permission'];
            }
        }
        return $keys;
    }

    /**
     * Check if user has a specific permission
     * Super admin always returns true (has all permissions by default)
     */
    public static function userHasPermission(User $user, string $permission): bool
    {
        // Super admin has ALL permissions by default
        if ($user->isSuperAdmin()) {
            return true;
        }

        $userPermissions = self::getUserPermissions($user);
        foreach ($userPermissions as $perm) {
            if (is_string($perm) && $perm === $permission) {
                return true;
            } elseif (is_array($perm) && isset($perm['permission']) && $perm['permission'] === $permission) {
                return true;
            }
        }
        return false;
    }

    /**
     * Set permissions for user
     * Accepts array of objects: [{"module": "products", "action": "view", "permission": "products:view"}, ...]
     */
    public static function setUserPermissions(User $user, array $permissions): void
    {
        $userPermission = UserPermission::where('user_id', $user->id)
            ->where('role', $user->role ?? 'user')
            ->first();
        
        if ($userPermission) {
            $userPermission->update([
                'permissions' => $permissions,
            ]);
        } else {
            UserPermission::create([
                'user_id' => $user->id,
                'role' => $user->role ?? 'user',
                'permissions' => $permissions,
            ]);
        }
    }

    /**
     * Add permission to user
     * Creates object: {"module": "products", "action": "view", "permission": "products:view"}
     */
    public static function addUserPermission(User $user, string $permission): void
    {
        $parts = explode(':', $permission);
        $module = $parts[0] ?? '';
                $action = $parts[1] ?? '';
                
        $permissions = self::getUserPermissions($user);
        
        // Check if permission already exists
        $exists = false;
        foreach ($permissions as $perm) {
            if (is_string($perm) && $perm === $permission) {
                $exists = true;
                break;
            } elseif (is_array($perm) && isset($perm['permission']) && $perm['permission'] === $permission) {
                $exists = true;
                break;
            }
        }
        
        if (!$exists) {
            $permissions[] = [
                'module' => $module,
                'action' => $action,
                'permission' => $permission,
            ];
            self::setUserPermissions($user, $permissions);
        }
    }

    /**
     * Remove permission from user
     */
    public static function removeUserPermission(User $user, string $permission): void
    {
        $permissions = self::getUserPermissions($user);
        $filtered = [];
        
        foreach ($permissions as $perm) {
            if (is_string($perm) && $perm !== $permission) {
                $filtered[] = $perm;
            } elseif (is_array($perm) && isset($perm['permission']) && $perm['permission'] !== $permission) {
                $filtered[] = $perm;
            }
        }
        
        self::setUserPermissions($user, array_values($filtered));
    }

    /**
     * Convert permission key to object format
     */
    private static function permissionKeyToObject(string $permissionKey): array
    {
        $parts = explode(':', $permissionKey);
        return [
            'module' => $parts[0] ?? '',
            'action' => $parts[1] ?? '',
            'permission' => $permissionKey,
        ];
    }

    /**
     * Get all available permission objects from user_permissions table
     * Returns array of objects: [{"module": "products", "action": "view", "permission": "products:view"}, ...]
     */
    public static function getAllPermissionObjects(): array
    {
        $objects = [];
        $validRoles = UserRole::values();
        $adminRoles = array_filter($validRoles, fn($r) => $r !== 'user');
        
        foreach ($adminRoles as $role) {
            $userPermissions = UserPermission::where('role', $role)->get();
            foreach ($userPermissions as $userPermission) {
                if ($userPermission->permissions) {
                    foreach ($userPermission->permissions as $perm) {
                        if (is_string($perm)) {
                            $obj = self::permissionKeyToObject($perm);
                            $exists = false;
                            foreach ($objects as $existing) {
                                if ($existing['permission'] === $obj['permission']) {
                                    $exists = true;
                                    break;
                                }
                            }
                            if (!$exists) {
                                $objects[] = $obj;
                            }
                        } elseif (is_array($perm) && isset($perm['permission'])) {
                            $exists = false;
                            foreach ($objects as $existing) {
                                if (isset($existing['permission']) && $existing['permission'] === $perm['permission']) {
                                    $exists = true;
                                    break;
                                }
                            }
                            if (!$exists) {
                                $objects[] = $perm;
                            }
                        }
                    }
                }
            }
        }
        
        return $objects;
    }

    /**
     * Get all available permission keys
     * Returns all unique permission keys from database, or generates default ones if DB is empty
     * (For backward compatibility - extracts keys from permission objects)
     */
    public static function getAllPermissionKeys(): array
    {
        $objects = self::getAllPermissionObjects();
        
        // If no permissions in DB, generate from default modules
        if (empty($objects)) {
            $defaultPerms = self::generateAllDefaultPermissions();
            $keys = [];
            foreach ($defaultPerms as $perm) {
                if (isset($perm['permission'])) {
                    $keys[] = $perm['permission'];
                }
            }
            return $keys;
        }
        
        $keys = [];
        foreach ($objects as $obj) {
            if (isset($obj['permission'])) {
                $keys[] = $obj['permission'];
            }
        }
        return array_unique($keys);
    }


    /**
     * Get all available roles (excluding super_admin - super admin has all permissions by default)
     */
    public function roles(): Response|JsonResponse
    {
        try {
            $allRoles = UserRole::values();
            // Filter out super_admin and user roles
            $roles = array_filter($allRoles, function ($role) {
                return $role !== 'super_admin' && $role !== 'user';
            });
            
            $roles = array_map(function ($role) {
                return [
                    'value' => $role,
                    'label' => UserRole::from($role)->label(),
                ];
            }, $roles);
            
            return $this->sendJsonResponse(true, 'Roles retrieved successfully', array_values($roles));
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Get permissions grouped by role
     * Shows all default permissions for each role, with indicators of which are assigned
     * Returns permissions in format: { role: [{ id, slug, module, action }] }
     * By default shows all permissions from default modules
     */
    public function groupedByRole(): Response|JsonResponse
    {
        try {
            $allRoles = UserRole::values();
            $grouped = [];
            
            // Get all default permissions
            $allDefaultPermissions = self::generateAllDefaultPermissions();
            
            // Build a map of permission key to object for quick lookup
            $defaultPermsMap = [];
            foreach ($allDefaultPermissions as $perm) {
                $defaultPermsMap[$perm['permission']] = $perm;
            }
            
            // Get all permissions from database grouped by role
            // Exclude super_admin and user roles
            $dbPermissionsByRole = [];
            foreach ($allRoles as $role) {
                if ($role === 'user' || $role === 'super_admin') continue;
                
                $dbPermissionsByRole[$role] = [];
                $userPermissions = UserPermission::where('role', $role)->get();
                
                foreach ($userPermissions as $userPermission) {
                    if ($userPermission->permissions) {
                        foreach ($userPermission->permissions as $perm) {
                            $permKey = null;
                            
                            if (is_string($perm)) {
                                $permKey = $perm;
                            } elseif (is_array($perm) && isset($perm['permission'])) {
                                $permKey = $perm['permission'];
                            }
                            
                            if ($permKey && !in_array($permKey, $dbPermissionsByRole[$role])) {
                                $dbPermissionsByRole[$role][] = $permKey;
                            }
                        }
                    }
                }
            }
            
            // For each role, show all default permissions (all available permissions)
            // Exclude super_admin and user roles (super admin has all permissions by default)
            foreach ($allRoles as $role) {
                if ($role === 'user' || $role === 'super_admin') continue;
                
                $grouped[$role] = [];
                
                // Get permissions assigned to this role from database
                $assignedPermissions = $dbPermissionsByRole[$role] ?? [];
                
                // Show ALL default permissions for each role
                // This allows super admin to see all available permissions and manage them
                foreach ($allDefaultPermissions as $permObj) {
                    $permKey = $permObj['permission'];
                    $permId = crc32($permKey);
                    
                    // Only include permissions that are assigned to this role
                    if (in_array($permKey, $assignedPermissions)) {
                        $grouped[$role][] = [
                            'id' => $permId,
                            'slug' => $permKey,
                            'module' => $permObj['module'],
                            'action' => $permObj['action'],
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
     * Saves permissions to user_permissions table as array of objects
     * Format: [{"module": "products", "action": "view", "permission": "products:view"}, ...]
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
            
            // Remove super_admin from roles if present (super admin has all permissions by default)
            $roles = array_filter($roles, function ($role) {
                return $role !== 'super_admin' && $role !== 'user';
            });

            $createdPermissions = [];
            foreach ($actions as $action) {
                $permissionKey = "{$module}:{$action}";

                    foreach ($roles as $role) {
                    if (!in_array($role, UserRole::values())) {
                        continue;
                    }

                    $users = User::where('role', $role)->get();
                    foreach ($users as $user) {
                        self::addUserPermission($user, $permissionKey);
                    }
                }

                    $createdPermissions[] = [
                        'id' => crc32($permissionKey),
                        'slug' => $permissionKey,
                        'module' => $module,
                        'action' => $action,
                    ];
            }

            return $this->sendJsonResponse(true, 'Permissions created successfully', [
                'permissions' => $createdPermissions,
                'count' => count($createdPermissions)
            ], 201);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Update roles for a permission
     * Updates user_permissions table by adding/removing permissions from users
     * Removes permission from all admin roles, then adds to specified roles
     * Permission key format: "module:action"
     */
    public function updateRoles(Request $request): Response|JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'roles' => 'required|array',
                'roles.*' => 'string',
            ]);

            if ($validator->fails()) {
                return $this->sendJsonResponse(false, 'Validation failed', ['errors' => $validator->errors()], 422);
            }

            $permissionId = $request->id;
            $roles = $request->roles;
            $validRoles = UserRole::values();
            // Exclude super_admin and user roles
            $allAdminRoles = array_filter($validRoles, fn($r) => $r !== 'user' && $r !== 'super_admin');
            
            // Remove super_admin from roles if present (super admin has all permissions by default)
            $roles = array_filter($roles, function ($role) {
                return $role !== 'super_admin' && $role !== 'user';
            });
            
            $permissionKey = null;
            foreach ($allAdminRoles as $role) {
                $userPermissions = UserPermission::where('role', $role)->get();
                foreach ($userPermissions as $userPermission) {
                    if ($userPermission->permissions) {
                        foreach ($userPermission->permissions as $perm) {
                            $permKey = null;
                            if (is_string($perm)) {
                                $permKey = $perm;
                            } elseif (is_array($perm) && isset($perm['permission'])) {
                                $permKey = $perm['permission'];
                            }
                            
                            if ($permKey && crc32($permKey) == $permissionId) {
                                $permissionKey = $permKey;
                                break 3;
                            }
                        }
                    }
                }
                if ($permissionKey) break;
            }

            if (!$permissionKey) {
                return $this->sendJsonResponse(false, 'Permission not found', [], 404);
            }
            
            foreach ($allAdminRoles as $role) {
                $users = User::where('role', $role)->get();
                foreach ($users as $user) {
                    self::removeUserPermission($user, $permissionKey);
                }
            }
            
            foreach ($roles as $role) {
                if (in_array($role, $validRoles) && $role !== 'user' && $role !== 'super_admin') {
                    $users = User::where('role', $role)->get();
                    foreach ($users as $user) {
                        self::addUserPermission($user, $permissionKey);
                    }
                }
            }
            
            $parts = explode(':', $permissionKey);
            return $this->sendJsonResponse(true, 'Permission roles updated successfully', [
                'id' => $permissionId,
                'slug' => $permissionKey,
                'module' => $parts[0] ?? '',
                'action' => $parts[1] ?? '',
                'roles' => $roles,
            ]);
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }

    /**
     * Remove the specified permission from all users
     * Removes permission from user_permissions table for all admin users
     */
    public function destroy(Request $request): Response|JsonResponse
    {
        try {
            $data = $request->validate([
                'id' => 'required'
            ]);

            $permissionId = $data['id'];
            $validRoles = UserRole::values();
            $adminRoles = array_filter($validRoles, fn($r) => $r !== 'user');
            
            $permissionKey = null;
            foreach ($adminRoles as $role) {
                $userPermissions = UserPermission::where('role', $role)->get();
                foreach ($userPermissions as $userPermission) {
                    if ($userPermission->permissions) {
                        foreach ($userPermission->permissions as $perm) {
                            $permKey = null;
                            if (is_string($perm)) {
                                $permKey = $perm;
                            } elseif (is_array($perm) && isset($perm['permission'])) {
                                $permKey = $perm['permission'];
                            }
                            
                            if ($permKey && crc32($permKey) == $permissionId) {
                                $permissionKey = $permKey;
                                break 3;
                            }
                        }
                    }
                }
                if ($permissionKey) break;
            }

            if (!$permissionKey) {
                return $this->sendJsonResponse(false, 'Permission not found', [], 404);
            }

            foreach ($adminRoles as $role) {
                $users = User::where('role', $role)->get();
            foreach ($users as $user) {
                    self::removeUserPermission($user, $permissionKey);
                }
            }

            return $this->sendJsonResponse(true, 'Permission removed from all users successfully');
            
        } catch (Exception $e) {
            return $this->sendError($e);
        }
    }
}

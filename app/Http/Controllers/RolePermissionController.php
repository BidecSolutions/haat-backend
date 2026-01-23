<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;
use App\Models\Admin;
use App\Models\User;

class RolePermissionController extends Controller
{
    //Role list
    public function listRoles()
    {
        try {
            $roles = Role::with('permissions')->get();

            return response()->json([
                'success' => true,
                'message' => 'Roles fetched successfully',
                'data' => $roles,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching roles: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Create Role
    // public function createRole(Request $request)
    // {
    //     try {
    //         $request->validate([
    //             'name' => 'required|string',
    //             'guard' => 'required|string',
    //         ]);

    //         $role = Role::create([
    //             'name' => $request->name,
    //             'guard_name' => $request->guard,
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Role created successfully',
    //             'data' => $role,
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error creating role: ' . $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function createRole(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string',
                'guard' => 'required|string',
                'permissions' => 'array', // Optional, but expected
                'permissions.*' => 'string|exists:permissions,name', // Validate each permission
            ]);

            // Create the role
            $role = Role::create([
                'name' => $request->name,
                'guard_name' => $request->guard,
            ]);

            // Assign permissions if provided
            if ($request->has('permissions') && !empty($request->permissions)) {
                $role->syncPermissions($request->permissions);
            }

            return response()->json([
                'success' => true,
                'message' => 'Role created and permissions assigned successfully',
                'data' => $role->load('permissions'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating role: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Update Role
    public function updateRole(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'required|string',
                'permissions' => 'array', // Optional
                'permissions.*' => 'string|exists:permissions,name',
            ]);

            $role = Role::findOrFail($id);
            $role->name = $request->name;
            $role->save();

            // Update permissions if provided
            if ($request->has('permissions')) {
                $role->syncPermissions($request->permissions);
            }

            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully',
                'data' => $role->load('permissions'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating role: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List all permissions, grouped by module and permission_name.
     */
    public function listPermissionsOnDemand(Request $request)
    {
        try {
            // Fetch permissions, optionally filter by guard
            $query = Permission::query();
            if ($request->has('guard')) {
                $query->where('guard_name', $request->guard);
            }
            $permissions = $query->get();

            if ($permissions->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No permissions found',
                    'data' => [],
                ], 200);
            }

            // Group permissions by module (prefix of permission_name) and then by permission_name
            $groupedPermissions = $permissions->groupBy(function ($permission) {
                // Extract the module (prefix before the first dot in permission_name)
                return explode('.', $permission->module_name ?? 'Unknown')[0] ?? 'Unknown';
            })->map(function ($moduleGroup) {
                // Within each module, group by permission_name and map to name arrays
                return $moduleGroup->groupBy('permission_name')->map(function ($permissionGroup) {
                    return $permissionGroup->pluck('name')->sort()->values()->toArray();
                })->sortKeys()->toArray();
            })->sortKeys()->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Permissions fetched successfully',
                'data' => $groupedPermissions,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching permissions: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function listPermissions(Request $request)
    {
        try {
            // Fetch permissions, optionally filter by guard
            $query = Permission::query();
            if ($request->has('guard')) {
                $query->where('guard_name', $request->guard);
            }
            $permissions = $query->get();

            if ($permissions->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No permissions found',
                    'data' => [],
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Permissions fetched successfully',
                'data' => $permissions,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching permissions: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $permission = Permission::findOrFail($id);

            $permission->delete();

            return response()->json([
                'success' => true,
                'message' => 'Permission deleted successfully',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Permission not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting permission: ' . $e->getMessage(),
            ], 500);
        }
    }


    // Create Permission
   public function createPermission(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|unique:permissions,name',
                'permission_name' => 'required|string',
                'module_name' => 'required|string',
                'guard' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $permission = Permission::create([
                'name' => $request->name,
                'permission_name' => $request->permission_name,
                'module_name' => $request->module_name,
                'guard_name' => $request->guard,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permission created successfully',
                'data' => $permission,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating permission: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Update Permission
    public function updatePermission(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'required|string',
                'permission_name' => 'required|string',
                'module_name' => 'required|string',
            ]);

            $permission = Permission::findOrFail($id);
            $permission->name = $request->name;
            $permission->permission_name = $request->permission_name;
            $permission->module_name = $request->module_name;
            $permission->save();

            return response()->json([
                'success' => true,
                'message' => 'Permission updated successfully',
                'data' => $permission,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating permission: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Admin Role list
    public function listAdminRoles()
    {
        try 
        {
            $admins = Admin::with('roles')->get()->map(function ($admin) {
                return [
                    'admin_id' => $admin->id,
                    'admin_name' => $admin->name,
                    'roles' => $admin->roles->pluck('name'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Admin roles fetched successfully',
                'data' => $admins,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching admin roles: ' . $e->getMessage(),
            ], 500);
        }
    }


    // Assign Role to Admin
    public function assignRoleToAdmin(Request $request)
    {
        try {
            $admin = Admin::findOrFail($request->admin_id);
            $admin->assignRole($request->role);

            return response()->json([
                'success' => true,
                'message' => 'Role assigned to admin',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error assigning role to admin: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Admin list ROle permission
    public function listRolePermissions()
    {
        try {
            $roles = Role::with('permissions')->get()->map(function ($role) {
                return [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'permissions' => $role->permissions->pluck('name'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Role permissions fetched successfully',
                'data' => $roles,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching role permissions: ' . $e->getMessage(),
            ], 500);
        }
    }
 

    // Assign Permission to Role Admin
    public function assignPermissionToRole(Request $request)
    {
        try {
            $role = Role::findByName($request->role, 'admin-api');
            $role->givePermissionTo($request->permission);

            return response()->json([
                'success' => true,
                'message' => 'Permission assigned to role',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error assigning permission to role: ' . $e->getMessage(),
            ], 500);
        }
    }



    
    // User Role list
    public function listUserRoles()
    {
        try {
            $users = User::with('roles')->get()->map(function ($user) {
                return [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'roles' => $user->roles->pluck('name'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'User roles fetched successfully',
                'data' => $users,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching user roles: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Assign Role to User
    public function assignRoleToUser(Request $request)
    {
        try {
            $user = User::findOrFail($request->user_id);
            $user->assignRole($request->role);

            return response()->json([
                'success' => true,
                'message' => 'Role assigned to user',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error assigning role to user: ' . $e->getMessage(),
            ], 500);
        }
    }

    // User list Role Permission
    public function listUserPermissions()
    {
        try {
            $users = User::with('permissions')->get()->map(function ($user) {
                return [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'permissions' => $user->permissions->pluck('name'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'User permissions fetched successfully',
                'data' => $users,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching user permissions: ' . $e->getMessage(),
            ], 500);
        }
    }


    // Assign Permission to Role User
    public function assignPermissionToUser(Request $request)
    {
        try {
            $user = User::findOrFail($request->user_id);
            $user->givePermissionTo($request->permission);

            return response()->json([
                'success' => true,
                'message' => 'Permission assigned to user',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error assigning permission to user: ' . $e->getMessage(),
            ], 500);
        }
    }


}

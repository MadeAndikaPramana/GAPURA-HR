<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RolePermissionMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return $this->unauthorizedResponse($request);
        }

        $user = Auth::user();

        // If no roles specified, just check if authenticated
        if (empty($roles)) {
            return $next($request);
        }

        // Check if user has any of the required roles
        if ($this->hasAnyRole($user, $roles)) {
            return $next($request);
        }

        return $this->forbiddenResponse($request);
    }

    /**
     * Check if user has any of the specified roles
     */
    protected function hasAnyRole($user, array $roles): bool
    {
        // Get user role based on position level or department
        $userRoles = $this->getUserRoles($user);

        foreach ($roles as $role) {
            if (in_array($role, $userRoles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine user roles based on employee data
     */
    protected function getUserRoles($user): array
    {
        $roles = ['employee']; // Default role

        // Check if user is in IT department (system admin)
        if ($user->department?->code === 'IT') {
            $roles[] = 'admin';
            $roles[] = 'system_admin';
        }

        // Check if user is in HR department
        if ($user->department?->code === 'HR') {
            $roles[] = 'hr';
            $roles[] = 'hr_admin';
        }

        // Check position level
        switch ($user->position_level) {
            case 'executive':
                $roles[] = 'executive';
                $roles[] = 'admin';
                break;
            case 'manager':
                $roles[] = 'manager';
                break;
            case 'supervisor':
                $roles[] = 'supervisor';
                break;
        }

        // Check if user manages a department
        if (\App\Models\Department::where('manager_id', $user->id)->exists()) {
            $roles[] = 'department_manager';
            $roles[] = 'manager';
        }

        // Check for specific training admin role
        if ($this->isTrainingAdmin($user)) {
            $roles[] = 'training_admin';
            $roles[] = 'admin';
        }

        return array_unique($roles);
    }

    /**
     * Check if user is a training administrator
     */
    protected function isTrainingAdmin($user): bool
    {
        // Check if user has training-related positions
        $trainingPositions = [
            'training coordinator',
            'training manager',
            'training specialist',
            'hr training',
            'learning and development'
        ];

        $userPosition = strtolower($user->position ?? '');

        foreach ($trainingPositions as $position) {
            if (str_contains($userPosition, $position)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return unauthorized response
     */
    protected function unauthorizedResponse(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required',
                'error_code' => 'UNAUTHORIZED'
            ], 401);
        }

        return redirect()->guest(route('login'))
            ->with('error', 'Please log in to access this resource.');
    }

    /**
     * Return forbidden response
     */
    protected function forbiddenResponse(Request $request): Response
    {
        $user = Auth::user();
        $userRoles = $this->getUserRoles($user);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions to access this resource',
                'error_code' => 'FORBIDDEN',
                'user_roles' => $userRoles
            ], 403);
        }

        return redirect()->back()
            ->with('error', 'You do not have permission to access this resource.');
    }

    /**
     * Check specific permission for a user
     */
    public static function userHasPermission($user, string $permission): bool
    {
        $permissions = self::getUserPermissions($user);
        return in_array($permission, $permissions);
    }

    /**
     * Get all permissions for a user
     */
    public static function getUserPermissions($user): array
    {
        $middleware = new self();
        $roles = $middleware->getUserRoles($user);
        $permissions = [];

        foreach ($roles as $role) {
            $permissions = array_merge($permissions, self::getRolePermissions($role));
        }

        return array_unique($permissions);
    }

    /**
     * Get permissions for a specific role
     */
    protected static function getRolePermissions(string $role): array
    {
        $permissions = [
            'employee' => [
                'view_own_profile',
                'view_own_certificates',
                'view_own_training_schedule',
                'update_own_profile'
            ],
            'supervisor' => [
                'view_team_training',
                'view_team_compliance',
                'assign_team_training',
                'view_department_reports'
            ],
            'manager' => [
                'view_department_analytics',
                'manage_department_training',
                'approve_training_requests',
                'view_department_compliance',
                'export_department_data'
            ],
            'hr' => [
                'manage_employees',
                'manage_training_records',
                'manage_certificates',
                'view_all_compliance',
                'generate_reports',
                'import_export_data',
                'manage_training_providers',
                'send_notifications'
            ],
            'hr_admin' => [
                'manage_training_types',
                'manage_training_categories',
                'system_maintenance',
                'view_analytics',
                'manage_notification_templates'
            ],
            'training_admin' => [
                'manage_all_training',
                'approve_all_training',
                'manage_training_calendar',
                'training_effectiveness_analysis',
                'provider_management'
            ],
            'admin' => [
                'manage_system_settings',
                'manage_users',
                'view_audit_logs',
                'system_backup_restore',
                'manage_integrations'
            ],
            'system_admin' => [
                'full_system_access',
                'database_management',
                'server_management',
                'security_management'
            ],
            'executive' => [
                'view_executive_dashboard',
                'view_strategic_analytics',
                'approve_budget',
                'view_roi_analysis'
            ]
        ];

        return $permissions[$role] ?? [];
    }

    /**
     * Check if user can manage specific employee
     */
    public static function canManageEmployee($user, $targetEmployee): bool
    {
        $userRoles = (new self())->getUserRoles($user);

        // Admin and HR can manage anyone
        if (array_intersect(['admin', 'hr', 'hr_admin'], $userRoles)) {
            return true;
        }

        // Users can manage themselves (limited)
        if ($user->id === $targetEmployee->id) {
            return true;
        }

        // Managers can manage their department employees
        if (in_array('manager', $userRoles)) {
            $managedDepartments = \App\Models\Department::where('manager_id', $user->id)
                ->pluck('id')
                ->toArray();

            if (in_array($targetEmployee->department_id, $managedDepartments)) {
                return true;
            }
        }

        // Supervisors can manage their direct reports
        if (in_array('supervisor', $userRoles) && $targetEmployee->supervisor_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can access department data
     */
    public static function canAccessDepartment($user, $departmentId): bool
    {
        $userRoles = (new self())->getUserRoles($user);

        // Admin and HR can access all departments
        if (array_intersect(['admin', 'hr', 'executive'], $userRoles)) {
            return true;
        }

        // Users can access their own department
        if ($user->department_id === $departmentId) {
            return true;
        }

        // Department managers can access their managed departments
        if (in_array('manager', $userRoles)) {
            return \App\Models\Department::where('id', $departmentId)
                ->where('manager_id', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Get accessible departments for user
     */
    public static function getAccessibleDepartments($user): array
    {
        $userRoles = (new self())->getUserRoles($user);

        // Admin and HR can access all departments
        if (array_intersect(['admin', 'hr', 'executive'], $userRoles)) {
            return \App\Models\Department::pluck('id')->toArray();
        }

        $accessibleDepartments = [];

        // Add user's own department
        if ($user->department_id) {
            $accessibleDepartments[] = $user->department_id;
        }

        // Add managed departments
        if (in_array('manager', $userRoles)) {
            $managedDepartments = \App\Models\Department::where('manager_id', $user->id)
                ->pluck('id')
                ->toArray();
            $accessibleDepartments = array_merge($accessibleDepartments, $managedDepartments);
        }

        return array_unique($accessibleDepartments);
    }
}

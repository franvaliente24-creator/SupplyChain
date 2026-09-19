<?php
/**
 * Role-Based Access Control (RBAC) Configuration
 * Defines user roles, permissions, and access control functions
 */

// Load environment variables from .env file
require_once __DIR__ . '/load_env.php';

/**
 * Define user roles with their permissions
 */
$ROLES = [
    'admin' => [
        'name' => 'Administrator',
        'permissions' => [
            // User Management
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.manage_roles',
            
            // Purchase Orders
            'orders.view',
            'orders.create',
            'orders.edit',
            'orders.delete',
            'orders.approve',
            'orders.reject',
            
            // Inventory
            'inventory.view',
            'inventory.create',
            'inventory.edit',
            'inventory.delete',
            'inventory.adjust',
            
            // Suppliers
            'suppliers.view',
            'suppliers.create',
            'suppliers.edit',
            'suppliers.delete',
            
            // Reports
            'reports.view',
            'reports.export',
            
            // System
            'system.settings',
            'system.logs',
            'system.backup'
        ]
    ],
    'manager' => [
        'name' => 'Supply Chain Manager',
        'permissions' => [
            // User Management (limited)
            'users.view',
            
            // Purchase Orders
            'orders.view',
            'orders.create',
            'orders.edit',
            'orders.approve',
            
            // Inventory
            'inventory.view',
            'inventory.create',
            'inventory.edit',
            'inventory.adjust',
            
            // Suppliers
            'suppliers.view',
            'suppliers.create',
            'suppliers.edit',
            
            // Reports
            'reports.view',
            'reports.export'
        ]
    ],
    'procurement' => [
        'name' => 'Procurement Specialist',
        'permissions' => [
            // Purchase Orders
            'orders.view',
            'orders.create',
            'orders.edit',
            
            // Suppliers
            'suppliers.view',
            'suppliers.create',
            'suppliers.edit',
            
            // Reports
            'reports.view'
        ]
    ],
    'warehouse' => [
        'name' => 'Warehouse Manager',
        'permissions' => [
            // Inventory
            'inventory.view',
            'inventory.create',
            'inventory.edit',
            'inventory.adjust',
            
            // Purchase Orders (receiving)
            'orders.view',
            'orders.receive',
            
            // Reports
            'reports.view'
        ]
    ],
    'viewer' => [
        'name' => 'Viewer',
        'permissions' => [
            // Read-only access
            'orders.view',
            'inventory.view',
            'suppliers.view',
            'reports.view'
        ]
    ]
];

/**
 * Get all permissions for a specific role
 */
function getRolePermissions($role) {
    global $ROLES;
    
    $role = strtolower(trim($role));
    return $ROLES[$role]['permissions'] ?? [];
}

/**
 * Check if user has a specific permission
 */
function hasPermission($permission, $userRole = null) {
    if ($userRole === null) {
        if (!isset($_SESSION)) {
            session_start();
        }
        $userRole = $_SESSION['role'] ?? null;
    }
    
    if (!$userRole) {
        return false;
    }
    
    $role = strtolower(trim($userRole));
    $permissions = getRolePermissions($role);
    
    return in_array($permission, $permissions, true);
}

/**
 * Check if user has any of the specified permissions
 */
function hasAnyPermission($permissions, $userRole = null) {
    foreach ($permissions as $permission) {
        if (hasPermission($permission, $userRole)) {
            return true;
        }
    }
    return false;
}

/**
 * Check if user has all of the specified permissions
 */
function hasAllPermissions($permissions, $userRole = null) {
    foreach ($permissions as $permission) {
        if (!hasPermission($permission, $userRole)) {
            return false;
        }
    }
    return true;
}

/**
 * Require a specific permission (exit if not authorized)
 */
function requirePermission($permission, $userRole = null) {
    if (!hasPermission($permission, $userRole)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['message' => 'You do not have permission to perform this action.']);
        exit;
    }
}

/**
 * Require any of the specified permissions (exit if not authorized)
 */
function requireAnyPermission($permissions, $userRole = null) {
    if (!hasAnyPermission($permissions, $userRole)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['message' => 'You do not have permission to perform this action.']);
        exit;
    }
}

/**
 * Get user role display name
 */
function getRoleName($role) {
    global $ROLES;
    
    $role = strtolower(trim($role));
    return $ROLES[$role]['name'] ?? 'Unknown Role';
}

/**
 * Check if user is an administrator
 */
function isAdmin($userRole = null) {
    if ($userRole === null) {
        if (!isset($_SESSION)) {
            session_start();
        }
        $userRole = $_SESSION['role'] ?? null;
    }
    
    return strtolower(trim($userRole)) === 'admin';
}

/**
 * Check if user can access a specific page/section
 */
function canAccessPage($page, $userRole = null) {
    $pagePermissions = [
        'user_management' => ['users.view'],
        'orders' => ['orders.view'],
        'orders_create' => ['orders.create'],
        'orders_edit' => ['orders.edit'],
        'orders_delete' => ['orders.delete'],
        'orders_approve' => ['orders.approve'],
        'inventory' => ['inventory.view'],
        'inventory_edit' => ['inventory.edit'],
        'suppliers' => ['suppliers.view'],
        'suppliers_edit' => ['suppliers.edit'],
        'reports' => ['reports.view'],
        'settings' => ['system.settings'],
        // Add additional pages that should be accessible
        'item_master' => ['inventory.view'],
        'stock_levels' => ['inventory.view'],
        'utilization_overview' => ['inventory.view'],
        'adjustments' => ['inventory.edit'],
        'asset_disposition' => ['inventory.edit'],
        'requisitions' => ['orders.view'],
        'rfqs' => ['orders.view'],
        'sourcing' => ['suppliers.view'],
        'spend' => ['reports.view'],
        'po_approvals' => ['orders.approve'],
        'goods_receipt' => ['orders.view'],
        'order_history' => ['orders.view'],
        'po_scanner' => ['orders.view'],
        'dtrs' => ['orders.view'],
        'pod' => ['orders.view'],
        'document_repository' => ['reports.view'],
        'document_tracking' => ['orders.view'],
        'carriers' => ['suppliers.view'],
        'customs_records' => ['orders.view'],
        'zone_map' => ['inventory.view'],
        'bin_lookup' => ['inventory.view'],
        'task_queues' => ['inventory.view'],
        'cycle_count' => ['inventory.view'],
        'activity_log' => ['system.logs'],
        'login_history' => ['system.logs']
    ];
    
    $requiredPermissions = $pagePermissions[$page] ?? [];
    
    if (empty($requiredPermissions)) {
        return true; // No specific permissions required
    }
    
    return hasAnyPermission($requiredPermissions, $userRole);
}

/**
 * Require access to a specific page (exit if not authorized)
 */
function requirePageAccess($page, $userRole = null) {
    if (!canAccessPage($page, $userRole)) {
        http_response_code(403);
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['message' => 'You do not have permission to access this page.']);
        } else {
            header('Location: dashboard.html?error=no_permission');
        }
        exit;
    }
}

/**
 * Get all available roles
 */
function getAllRoles() {
    global $ROLES;
    
    $roles = [];
    foreach ($ROLES as $key => $role) {
        $roles[$key] = $role['name'];
    }
    
    return $roles;
}
?>

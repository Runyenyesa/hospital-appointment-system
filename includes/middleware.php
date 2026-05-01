<?php
/**
 * RBAC Middleware
 * Role-Based Access Control for Hospital Management System
 */

require_once __DIR__ . '/auth.php';

/**
 * Role middleware - checks if user has required role
 */
function requireRole($allowedRoles) {
    requireAuth();
    
    if (!is_array($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    
    $userRole = $_SESSION['role_slug'] ?? '';
    $userRoleId = $_SESSION['role_id'] ?? 0;
    
    // Check by slug or ID
    $hasAccess = false;
    foreach ($allowedRoles as $role) {
        if (is_numeric($role) && $userRoleId == $role) {
            $hasAccess = true;
            break;
        }
        if ($userRole === $role) {
            $hasAccess = true;
            break;
        }
    }
    
    if (!$hasAccess) {
        http_response_code(403);
        flashMessage('Access denied. You do not have permission to access this page.', 'error');
        
        // Redirect to appropriate dashboard
        $dashboard = match($userRole) {
            'admin' => '/pages/admin/dashboard.php',
            'doctor' => '/pages/doctor/dashboard.php',
            'receptionist' => '/pages/receptionist/dashboard.php',
            'patient' => '/pages/patient/dashboard.php',
            default => '/index.php'
        };
        redirect($dashboard);
    }
}

/**
 * Check permission for specific action
 */
function hasPermission($action, $scope = null) {
    if (!isLoggedIn()) return false;
    
    $role = $_SESSION['role_slug'] ?? '';
    
    // Admin has all permissions
    if ($role === 'admin') return true;
    
    // Define permissions per role
    $permissions = [
        'doctor' => [
            'view_appointments', 'update_appointments', 'view_patients', 
            'create_medical_record', 'view_medical_history', 'complete_appointment',
            'add_consultation_notes', 'cancel_appointment'
        ],
        'receptionist' => [
            'view_all_appointments', 'approve_appointment', 'reject_appointment',
            'reschedule_appointment', 'create_walk_in', 'manage_patients',
            'assign_doctor', 'register_patient'
        ],
        'patient' => [
            'book_appointment', 'view_own_appointments', 'cancel_own_appointment',
            'reschedule_own_appointment', 'view_own_medical_history', 'update_profile'
        ]
    ];
    
    $rolePermissions = $permissions[$role] ?? [];
    
    // Check if permission exists
    return in_array($action, $rolePermissions);
}

/**
 * Middleware shorthand functions
 */
function requireAdmin() {
    requireRole(['admin', ROLE_ADMIN]);
}

function requireDoctor() {
    requireRole(['doctor', ROLE_DOCTOR]);
}

function requireReceptionist() {
    requireRole(['receptionist', ROLE_RECEPTIONIST]);
}

function requireStaff() {
    requireRole(['admin', 'doctor', 'receptionist', ROLE_ADMIN, ROLE_DOCTOR, ROLE_RECEPTIONIST]);
}

function requirePatient() {
    requireRole(['patient', ROLE_PATIENT]);
}

/**
 * API auth middleware for AJAX requests
 */
function apiAuth() {
    if (!validateSession()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit();
    }
}

/**
 * API role check
 */
function apiRequireRole($allowedRoles) {
    apiAuth();
    
    if (!is_array($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    
    $userRole = $_SESSION['role_slug'] ?? '';
    $hasAccess = false;
    
    foreach ($allowedRoles as $role) {
        if ($userRole === $role || (is_numeric($role) && $_SESSION['role_id'] == $role)) {
            $hasAccess = true;
            break;
        }
    }
    
    if (!$hasAccess) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Forbidden']);
        exit();
    }
}
?>
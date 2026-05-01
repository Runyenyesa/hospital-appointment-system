<?php
/**
 * Global Helper Functions
 * Hospital Appointment Management System
 */

require_once __DIR__ . '/config.php';

/**
 * Sanitize input to prevent XSS
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header("Location: " . APP_URL . "/" . ltrim($url, '/'));
    exit();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user ID
 */
function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 */
function currentUserRole() {
    return $_SESSION['role_id'] ?? null;
}

/**
 * Get current user data
 */
function currentUser() {
    if (!isLoggedIn()) return null;
    
    static $user = null;
    if ($user === null) {
        $db = getDB();
        $stmt = $db->prepare("SELECT u.*, r.role_name, r.role_slug FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }
    return $user;
}

/**
 * Check if current user has a specific role
 */
function hasRole($role) {
    if (!isLoggedIn()) return false;
    
    if (is_numeric($role)) {
        return $_SESSION['role_id'] == $role;
    }
    
    return isset($_SESSION['role_slug']) && $_SESSION['role_slug'] === $role;
}

/**
 * Display flash message
 */
function flashMessage($message = null, $type = 'info') {
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return;
    }
    
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render flash message HTML
 */
function showFlash() {
    $flash = flashMessage();
    if ($flash) {
        $alertClass = match($flash['type']) {
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            default => 'alert-info'
        };
        echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
        echo e($flash['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
    }
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Format date
 */
function formatDate($date, $format = 'M d, Y') {
    if (!$date) return 'N/A';
    return date($format, strtotime($date));
}

/**
 * Format time
 */
function formatTime($time, $format = 'h:i A') {
    if (!$time) return 'N/A';
    return date($format, strtotime($time));
}

/**
 * Get appointment status badge
 */
function statusBadge($status) {
    $classes = [
        'pending' => 'bg-warning text-dark',
        'approved' => 'bg-success',
        'rejected' => 'bg-danger',
        'completed' => 'bg-primary',
        'cancelled' => 'bg-secondary',
        'no_show' => 'bg-dark'
    ];
    $class = $classes[$status] ?? 'bg-info';
    $label = ucfirst(str_replace('_', ' ', $status));
    return '<span class="badge ' . $class . '">' . $label . '</span>';
}

/**
 * Log activity
 */
function logActivity($action, $entityType = null, $entityId = null, $oldValues = null, $newValues = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'] ?? null,
            $action,
            $entityType,
            $entityId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
    }
}

/**
 * Send notification to user
 */
function sendNotification($userId, $type, $title, $message, $relatedId = null, $relatedType = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO notifications (user_id, type, title, message, related_id, related_type) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $type, $title, $message, $relatedId, $relatedType]);
        return true;
    } catch (Exception $e) {
        error_log("Failed to send notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Count unread notifications
 */
function unreadNotificationCount($userId = null) {
    if (!$userId) $userId = currentUserId();
    if (!$userId) return 0;
    
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get system setting
 */
function getSetting($key, $default = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value, setting_type FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        
        if (!$row) return $default;
        
        return match($row['setting_type']) {
            'integer' => (int) $row['setting_value'],
            'boolean' => (bool) $row['setting_value'],
            'json' => json_decode($row['setting_value'], true),
            default => $row['setting_value']
        };
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Upload file helper
 */
function uploadFile($file, $subdirectory = '') {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload failed'];
    }
    
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'error' => 'File too large'];
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }
    
    $uploadDir = UPLOAD_PATH . ($subdirectory ? $subdirectory . '/' : '');
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filename = generateToken(16) . '_' . basename($file['name']);
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'path' => 'uploads/' . ($subdirectory ? $subdirectory . '/' : '') . $filename];
    }
    
    return ['success' => false, 'error' => 'Failed to save file'];
}

/**
 * Pagination helper
 */
function paginate($query, $params = [], $perPage = 10) {
    $db = getDB();
    
    // Get total count
    $countQuery = preg_replace('/SELECT\s+.*?\s+FROM\s+/is', 'SELECT COUNT(*) FROM ', $query, 1);
    $countQuery = preg_replace('/\s+ORDER\s+BY\s+.*$/i', '', $countQuery);
    $countQuery = preg_replace('/\s+LIMIT\s+.*$/i', '', $countQuery);
    
    $stmt = $db->prepare($countQuery);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();
    
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $offset = ($page - 1) * $perPage;
    $totalPages = (int) ceil($total / $perPage);
    
    // Get paginated results
    $query .= " LIMIT $perPage OFFSET $offset";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll();
    
    return [
        'data' => $results,
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage,
        'totalPages' => $totalPages,
        'hasNext' => $page < $totalPages,
        'hasPrev' => $page > 1
    ];
}

/**
 * Get readable time ago
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    
    return formatDate($datetime);
}
?>
<?php
/**
 * Authentication Functions
 * Hospital Appointment Management System
 */

require_once __DIR__ . '/functions.php';

/**
 * Register a new user
 */
function registerUser($data) {
    $db = getDB();
    
    // Validate required fields
    $required = ['email', 'password', 'first_name', 'last_name', 'role_id'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'error' => ucfirst(str_replace('_', ' ', $field)) . ' is required'];
        }
    }
    
    // Validate email
    if (!isValidEmail($data['email'])) {
        return ['success' => false, 'error' => 'Invalid email address'];
    }
    
    // Check if email exists
    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Email already registered'];
    }
    
    // Validate password strength
    if (strlen($data['password']) < 6) {
        return ['success' => false, 'error' => 'Password must be at least 6 characters'];
    }
    
    // Hash password
    $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
    
    // Build insert query dynamically
    $fields = ['role_id', 'email', 'password_hash', 'first_name', 'last_name'];
    $values = [$data['role_id'], $data['email'], $passwordHash, $data['first_name'], $data['last_name']];
    $placeholders = ['?', '?', '?', '?', '?'];
    
    // Optional fields
    $optionalFields = ['phone', 'date_of_birth', 'gender', 'address', 'city', 'country', 'zip_code', 
                       'dept_id', 'specialization', 'license_number', 'qualification', 'experience_years', 'consultation_fee'];
    
    foreach ($optionalFields as $field) {
        if (isset($data[$field]) && $data[$field] !== '') {
            $fields[] = $field;
            $values[] = $data[$field];
            $placeholders[] = '?';
        }
    }
    
    try {
        $sql = "INSERT INTO users (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        $stmt = $db->prepare($sql);
        $stmt->execute($values);
        $userId = $db->lastInsertId();
        
        // Send welcome notification
        sendNotification($userId, 'welcome', 'Welcome to ' . APP_NAME, 
            'Your account has been created successfully. You can now book appointments and manage your health records.');
        
        logActivity('user_registered', 'user', $userId);
        
        return ['success' => true, 'user_id' => $userId, 'message' => 'Registration successful'];
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        return ['success' => false, 'error' => 'Registration failed. Please try again.'];
    }
}

/**
 * Login user
 */
function loginUser($email, $password) {
    $db = getDB();
    
    // Get user by email
    $stmt = $db->prepare("SELECT u.*, r.role_name, r.role_slug, r.role_id as role_id_ref 
                         FROM users u 
                         JOIN roles r ON u.role_id = r.role_id 
                         WHERE u.email = ? AND u.is_active = 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        return ['success' => false, 'error' => 'Invalid email or password'];
    }
    
    // Check if account is locked
    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
        $remaining = ceil((strtotime($user['locked_until']) - time()) / 60);
        return ['success' => false, 'error' => "Account locked. Try again in $remaining minutes."];
    }
    
    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        // Increment failed attempts
        $newAttempts = $user['failed_login_attempts'] + 1;
        $lockedUntil = null;
        
        if ($newAttempts >= MAX_LOGIN_ATTEMPTS) {
            $lockedUntil = date('Y-m-d H:i:s', time() + LOCKOUT_DURATION);
        }
        
        $stmt = $db->prepare("UPDATE users SET failed_login_attempts = ?, locked_until = ? WHERE user_id = ?");
        $stmt->execute([$newAttempts, $lockedUntil, $user['user_id']]);
        
        return ['success' => false, 'error' => 'Invalid email or password'];
    }
    
    // Check if password needs rehash
    if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT, ['cost' => BCRYPT_COST])) {
        $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        $stmt->execute([$newHash, $user['user_id']]);
    }
    
    // Clear failed attempts and update last login
    $stmt = $db->prepare("UPDATE users SET failed_login_attempts = 0, locked_until = NULL, 
                          last_login = NOW(), last_login_ip = ? WHERE user_id = ?");
    $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? null, $user['user_id']]);
    
    // Set session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role_id'] = $user['role_id'];
    $_SESSION['role_slug'] = $user['role_slug'];
    $_SESSION['role_name'] = $user['role_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
    $_SESSION['login_time'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? null;
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    logActivity('user_login', 'user', $user['user_id']);
    
    return ['success' => true, 'user' => $user];
}

/**
 * Logout user
 */
function logoutUser() {
    if (isset($_SESSION['user_id'])) {
        logActivity('user_logout', 'user', $_SESSION['user_id']);
    }
    
    // Clear session
    $_SESSION = [];
    
    // Destroy session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Strict'
        ]);
    }
    
    session_destroy();
}

/**
 * Check session validity
 */
function validateSession() {
    if (!isLoggedIn()) return false;
    
    // Check session timeout
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > SESSION_TIMEOUT) {
        logoutUser();
        return false;
    }
    
    // Check IP binding (optional - enable in production for extra security)
    // if ($_SESSION['ip_address'] !== ($_SERVER['REMOTE_ADDR'] ?? null)) {
    //     logoutUser();
    //     return false;
    // }
    
    // Update activity time
    $_SESSION['login_time'] = time();
    
    return true;
}

/**
 * Require authentication
 */
function requireAuth() {
    if (!validateSession()) {
        flashMessage('Please login to continue', 'warning');
        redirect('/pages/auth/login.php');
    }
}

/**
 * Require guest (not logged in)
 */
function requireGuest() {
    if (isLoggedIn()) {
        $dashboard = match($_SESSION['role_slug']) {
            'admin' => '/pages/admin/dashboard.php',
            'doctor' => '/pages/doctor/dashboard.php',
            'receptionist' => '/pages/receptionist/dashboard.php',
            'patient' => '/pages/patient/dashboard.php',
            default => '/'
        };
        redirect($dashboard);
    }
}
?>
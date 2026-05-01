<?php
/**
 * Database Configuration
 * Hospital Appointment Management System
 */

// Error reporting - disable in production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.gc_maxlifetime', 3600);
ini_set('session.use_strict_mode', 1);

// Timezone
 date_default_timezone_set('America/New_York');

// Database credentials
 define('DB_HOST', 'localhost');
 define('DB_USERNAME', 'root');
 define('DB_PASSWORD', ''); // Change in production
 define('DB_NAME', 'hospital_db');
 define('DB_CHARSET', 'utf8mb4');

// Application settings
 define('APP_NAME', 'City General Hospital');
 define('APP_URL', 'http://localhost/hospital_appointment_system');
 define('APP_VERSION', '1.0.0');
 define('UPLOAD_PATH', __DIR__ . '/../uploads/');
 define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// Security settings
 define('SESSION_TIMEOUT', 3600); // 1 hour
 define('MAX_LOGIN_ATTEMPTS', 5);
 define('LOCKOUT_DURATION', 900); // 15 minutes
 define('BCRYPT_COST', 12);

// Email settings
 define('SMTP_HOST', 'smtp.gmail.com');
 define('SMTP_PORT', 587);
 define('SMTP_USER', 'noreply@citygeneral.com');
 define('SMTP_PASS', '');

// Role constants
 define('ROLE_ADMIN', 1);
 define('ROLE_DOCTOR', 2);
 define('ROLE_RECEPTIONIST', 3);
 define('ROLE_PATIENT', 4);

// Appointment status constants
 define('STATUS_PENDING', 'pending');
 define('STATUS_APPROVED', 'approved');
 define('STATUS_REJECTED', 'rejected');
 define('STATUS_COMPLETED', 'completed');
 define('STATUS_CANCELLED', 'cancelled');
 define('STATUS_NO_SHOW', 'no_show');

// Database connection
 class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->connection = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed. Please try again later.");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
 }

// Helper function to get DB connection
function getDB() {
    return Database::getInstance()->getConnection();
}
?>
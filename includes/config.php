<?php
ini_set('display_errors', 0);
error_reporting(0);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.gc_maxlifetime', 3600);
date_default_timezone_set('Africa/Kampala');
define('DB_HOST',     getenv('DB_HOST')     ?: 'localhost');
define('DB_PORT',     getenv('DB_PORT')     ?: '3306');
define('DB_USERNAME', getenv('DB_USERNAME') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');
define('DB_NAME',     getenv('DB_NAME')     ?: 'defaultdb');
define('DB_CHARSET',  'utf8mb4');
define('APP_NAME',    'City General Hospital');
define('APP_URL',     getenv('APP_URL') ?: 'http://localhost');
define('APP_VERSION', '1.0.0');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 5242880);
define('SESSION_TIMEOUT', 3600);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900);
define('BCRYPT_COST', 12);
define('ROLE_ADMIN', 1);
define('ROLE_DOCTOR', 2);
define('ROLE_RECEPTIONIST', 3);
define('ROLE_PATIENT', 4);
define('STATUS_PENDING', 'pending');
define('STATUS_APPROVED', 'approved');
define('STATUS_REJECTED', 'rejected');
define('STATUS_COMPLETED', 'completed');
define('STATUS_CANCELLED', 'cancelled');
define('STATUS_NO_SHOW', 'no_show');
class Database {
    private static $instance = null;
    private $connection;
    private function __construct() {
        try {
            $dsn = "mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME.";charset=".DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
                PDO::MYSQL_ATTR_SSL_CA => true,
            ];
            $this->connection = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
        } catch (PDOException $e) {
            error_log("DB error: ".$e->getMessage());
            die("Database connection failed.");
        }
    }
    public static function getInstance() {
        if (self::$instance === null) self::$instance = new self();
        return self::$instance;
    }
    public function getConnection() { return $this->connection; }
}
function getDB() { return Database::getInstance()->getConnection(); }
?>

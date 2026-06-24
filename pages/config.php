<?php
// =============================================
// config.php - AgriConnect System Configuration
// =============================================

// =============================================
// 1. ERROR REPORTING (Development Mode)
// =============================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// =============================================
// 2. SESSION CONFIGURATION
// =============================================
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
    
    // Session timeout (30 minutes)
    ini_set('session.gc_maxlifetime', 1800);
    session_start();
}

// =============================================
// 3. DATABASE CONFIGURATION
// =============================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'password');
define('DB_NAME', 'Agri_Project');

// =============================================
// 4. APPLICATION CONFIGURATION
// =============================================
define('APP_NAME', 'AgriConnect');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/AgriConnect');
define('APP_ENV', 'development'); // development, staging, production

// =============================================
// 5. FILE PATHS
// =============================================
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/uploads/');
define('PHOTO_PATH', UPLOAD_PATH . 'photos/');
define('TEMP_PATH', ROOT_PATH . '/temp/');

// =============================================
// 6. M-PESA API CONFIGURATION
// =============================================
// Sandbox credentials (replace with production when live)
define('MPESA_CONSUMER_KEY', 'YOUR_CONSUMER_KEY');
define('MPESA_CONSUMER_SECRET', 'YOUR_CONSUMER_SECRET');
define('MPESA_PASSKEY', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919');
define('MPESA_SHORTCODE', '174379'); // Sandbox default
define('MPESA_INITIATOR_USERNAME', 'YOUR_INITIATOR_USERNAME');
define('MPESA_INITIATOR_PASSWORD', 'YOUR_INITIATOR_PASSWORD');

// Environment URLs
if (APP_ENV === 'production') {
    define('MPESA_API_URL', 'https://api.safaricom.co.ke');
    define('MPESA_CALLBACK_URL', APP_URL . '/api/mpesa/callback.php');
} else {
    define('MPESA_API_URL', 'https://sandbox.safaricom.co.ke');
    define('MPESA_CALLBACK_URL', APP_URL . '/api/mpesa/callback.php');
}

// =============================================
// 7. DATABASE CONNECTION
// =============================================
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($this->conn->connect_error) {
                throw new Exception("Connection failed: " . $this->conn->connect_error);
            }
            
            // Set charset to UTF-8
            $this->conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }
    
    public function query($sql) {
        return $this->conn->query($sql);
    }
    
    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }
    
    public function lastInsertId() {
        return $this->conn->insert_id;
    }
    
    public function affectedRows() {
        return $this->conn->affected_rows;
    }
    
    public function close() {
        $this->conn->close();
    }
}

// =============================================
// 8. AUTHENTICATION FUNCTIONS
// =============================================

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return hasRole('admin');
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 */
function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Get current user full name
 */
function getCurrentUserName() {
    return $_SESSION['full_name'] ?? null;
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

/**
 * Require admin role - redirect if not admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . APP_URL . '/dashboard.php');
        exit;
    }
}

/**
 * Require specific role
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: ' . APP_URL . '/dashboard.php');
        exit;
    }
}

// =============================================
// 9. SECURITY FUNCTIONS
// =============================================

/**
 * Hash password for storage
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password against hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token;
}

/**
 * Generate random string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Sanitize input for display
 */
function sanitizeOutput($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize input for database
 */
function sanitizeInput($input) {
    $db = Database::getInstance();
    return $db->escape($input);
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Kenyan format)
 */
function isValidPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return preg_match('/^(254|0)[7-9][0-9]{8}$/', $phone);
}

/**
 * Format phone number to international format
 */
function formatPhoneNumber($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($phone, 0, 1) === '0') {
        $phone = '254' . substr($phone, 1);
    } elseif (substr($phone, 0, 4) !== '254') {
        $phone = '254' . $phone;
    }
    return $phone;
}

// =============================================
// 10. ACTIVITY LOGGING FUNCTIONS
// =============================================

/**
 * Log user activity
 */
function logActivity($userId, $activityType, $description, $entityType = null, $entityId = null) {
    $db = Database::getInstance();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $stmt = $db->prepare("INSERT INTO activity_logs 
        (user_id, activity_type, description, entity_type, entity_id, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("sssssss", $userId, $activityType, $description, $entityType, $entityId, $ip, $userAgent);
    return $stmt->execute();
}

/**
 * Log login attempt
 */
function logLoginAttempt($userId, $loginType, $ip, $userAgent, $location = null, $reason = null) {
    $db = Database::getInstance();
    
    $city = $location['city'] ?? null;
    $country = $location['country'] ?? null;
    
    $stmt = $db->prepare("INSERT INTO login_history 
        (user_id, login_type, ip_address, user_agent, location_city, location_country, failure_reason) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("sssssss", $userId, $loginType, $ip, $userAgent, $city, $country, $reason);
    return $stmt->execute();
}

// =============================================
// 11. USER SESSION FUNCTIONS
// =============================================

/**
 * Create user session
 */
function createUserSession($userId, $fullName, $role) {
    $token = generateRandomString(32);
    
    $db = Database::getInstance();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $deviceInfo = getDeviceInfo($userAgent);
    
    $stmt = $db->prepare("INSERT INTO user_sessions 
        (user_id, session_token, ip_address, user_agent, device_type, browser, os) 
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("sssssss", $userId, $token, $ip, $userAgent, 
        $deviceInfo['device'], $deviceInfo['browser'], $deviceInfo['os']);
    $stmt->execute();
    
    $_SESSION['user_id'] = $userId;
    $_SESSION['full_name'] = $fullName;
    $_SESSION['role'] = $role;
    $_SESSION['session_token'] = $token;
    $_SESSION['login_time'] = date('Y-m-d H:i:s');
    $_SESSION['ip'] = $ip;
}

/**
 * Get device info from user agent
 */
function getDeviceInfo($userAgent) {
    $device = 'Desktop';
    $browser = 'Unknown';
    $os = 'Unknown';
    
    // Detect device
    if (strpos($userAgent, 'Mobile') !== false) $device = 'Mobile';
    if (strpos($userAgent, 'Tablet') !== false) $device = 'Tablet';
    
    // Detect browser
    if (strpos($userAgent, 'Chrome') !== false) $browser = 'Chrome';
    elseif (strpos($userAgent, 'Firefox') !== false) $browser = 'Firefox';
    elseif (strpos($userAgent, 'Safari') !== false) $browser = 'Safari';
    elseif (strpos($userAgent, 'Edge') !== false) $browser = 'Edge';
    elseif (strpos($userAgent, 'Opera') !== false) $browser = 'Opera';
    
    // Detect OS
    if (strpos($userAgent, 'Windows') !== false) $os = 'Windows';
    elseif (strpos($userAgent, 'Mac') !== false) $os = 'macOS';
    elseif (strpos($userAgent, 'Linux') !== false) $os = 'Linux';
    elseif (strpos($userAgent, 'Android') !== false) $os = 'Android';
    elseif (strpos($userAgent, 'iOS') !== false) $os = 'iOS';
    
    return ['device' => $device, 'browser' => $browser, 'os' => $os];
}

/**
 * Get geolocation from IP
 */
function getLocationFromIP($ip) {
    // Use free IP geolocation API
    $url = "http://ip-api.com/json/$ip";
    
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        return [
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'region' => $data['regionName'] ?? null,
            'lat' => $data['lat'] ?? null,
            'lon' => $data['lon'] ?? null,
            'isp' => $data['isp'] ?? null
        ];
    } catch (Exception $e) {
        return ['city' => null, 'country' => null];
    }
}

// =============================================
// 12. FILE UPLOAD FUNCTIONS
// =============================================

/**
 * Upload file
 */
function uploadFile($file, $targetDir, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif']) {
    // Create directory if not exists
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error'];
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB
        return ['success' => false, 'error' => 'File too large'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = time() . '_' . generateRandomString(8) . '.' . $extension;
    $filepath = $targetDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'path' => $filepath];
    }
    
    return ['success' => false, 'error' => 'Failed to move file'];
}

// =============================================
// 13. M-PESA FUNCTIONS
// =============================================

/**
 * Get M-Pesa access token
 */
function getMpesaAccessToken() {
    $consumerKey = MPESA_CONSUMER_KEY;
    $consumerSecret = MPESA_CONSUMER_SECRET;
    
    $auth = base64_encode("$consumerKey:$consumerSecret");
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, MPESA_API_URL . '/oauth/v1/generate?grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $auth]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return null;
    }
    
    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

/**
 * Initiate STK Push
 */
function initiateSTKPush($phone, $amount, $accountReference, $transactionDesc = 'Payment to AgriConnect') {
    $accessToken = getMpesaAccessToken();
    if (!$accessToken) {
        return ['success' => false, 'error' => 'Failed to get access token'];
    }
    
    $phone = formatPhoneNumber($phone);
    $timestamp = date('YmdHis');
    $password = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);
    
    $data = [
        'BusinessShortCode' => MPESA_SHORTCODE,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $amount,
        'PartyA' => $phone,
        'PartyB' => MPESA_SHORTCODE,
        'PhoneNumber' => $phone,
        'CallBackURL' => MPESA_CALLBACK_URL,
        'AccountReference' => $accountReference,
        'TransactionDesc' => $transactionDesc
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, MPESA_API_URL . '/mpesa/stkpush/v1/processrequest');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['success' => false, 'error' => $error];
    }
    
    $result = json_decode($response, true);
    
    if ($result['ResponseCode'] === '0') {
        return ['success' => true, 'data' => $result];
    } else {
        return ['success' => false, 'error' => $result['ResponseDescription'] ?? 'Unknown error'];
    }
}

// =============================================
// 14. NOTIFICATION FUNCTIONS
// =============================================

/**
 * Create notification for user
 */
function createNotification($userId, $type, $title, $message, $relatedEntityId = null, $relatedEntityType = null) {
    $db = Database::getInstance();
    
    $stmt = $db->prepare("INSERT INTO notifications 
        (user_id, type, title, message, related_entity_id, related_entity_type) 
        VALUES (?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("ssssss", $userId, $type, $title, $message, $relatedEntityId, $relatedEntityType);
    return $stmt->execute();
}

// =============================================
// 15. RESPONSE HELPERS
// =============================================

/**
 * Send JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Send success response
 */
function successResponse($message, $data = null) {
    $response = ['success' => true, 'message' => $message];
    if ($data) {
        $response['data'] = $data;
    }
    jsonResponse($response);
}

/**
 * Send error response
 */
function errorResponse($message, $statusCode = 400) {
    jsonResponse(['success' => false, 'error' => $message], $statusCode);
}

// =============================================
// 16. SESSION MANAGEMENT FUNCTIONS
// =============================================

/**
 * Destroy user session
 */
function destroyUserSession() {
    if (isset($_SESSION['user_id'])) {
        $db = Database::getInstance();
        $userId = $_SESSION['user_id'];
        
        // Update session as inactive
        $stmt = $db->prepare("UPDATE user_sessions SET is_active = FALSE, logout_time = NOW() WHERE user_id = ? AND is_active = TRUE");
        $stmt->bind_param("s", $userId);
        $stmt->execute();
    }
    
    $_SESSION = array();
    session_destroy();
}

// =============================================
// 17. APPLICATION INITIALIZATION
// =============================================

// Auto-login check for session validation
function validateSession() {
    if (isLoggedIn()) {
        // Check if session is still valid in database
        $db = Database::getInstance();
        $token = $_SESSION['session_token'] ?? null;
        $userId = $_SESSION['user_id'];
        
        if ($token) {
            $stmt = $db->prepare("SELECT session_token, is_active FROM user_sessions WHERE user_id = ? AND is_active = TRUE ORDER BY login_time DESC LIMIT 1");
            $stmt->bind_param("s", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                if ($row['session_token'] !== $token) {
                    // Session invalid - logout
                    destroyUserSession();
                    return false;
                }
                
                // Update last activity
                $stmt = $db->prepare("UPDATE user_sessions SET last_activity = NOW() WHERE session_token = ?");
                $stmt->bind_param("s", $token);
                $stmt->execute();
                
                return true;
            } else {
                // Session not found - logout
                destroyUserSession();
                return false;
            }
        }
    }
    return true;
}

// Validate session on every request
validateSession();

// =============================================
// 18. DATE AND TIME HELPERS
// =============================================

/**
 * Get current date/time in Kenya timezone
 */
function getKenyaTime() {
    $timezone = new DateTimeZone('Africa/Nairobi');
    $date = new DateTime('now', $timezone);
    return $date->format('Y-m-d H:i:s');
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'Y-m-d H:i:s') {
    $timezone = new DateTimeZone('Africa/Nairobi');
    $datetime = new DateTime($date, $timezone);
    return $datetime->format($format);
}

/**
 * Get time ago string
 */
function timeAgo($datetime) {
    $timezone = new DateTimeZone('Africa/Nairobi');
    $now = new DateTime('now', $timezone);
    $past = new DateTime($datetime, $timezone);
    $diff = $now->diff($past);
    
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'just now';
}

// =============================================
// 19. RATE LIMITING
// =============================================

/**
 * Check rate limit for IP address
 */
function checkRateLimit($ip, $limit = 10, $timeWindow = 60) {
    $db = Database::getInstance();
    
    // Clean old entries
    $stmt = $db->prepare("DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)");
    $stmt->bind_param("i", $timeWindow);
    $stmt->execute();
    
    // Check current count
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM rate_limits WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)");
    $stmt->bind_param("si", $ip, $timeWindow);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] >= $limit) {
        return false;
    }
    
    // Log this request
    $stmt = $db->prepare("INSERT INTO rate_limits (ip_address, created_at) VALUES (?, NOW())");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    
    return true;
}

// =============================================
// 20. HELPER FUNCTIONS FOR YOUR SYSTEM
// =============================================

/**
 * Get user details by ID
 */
function getUserById($userId) {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Get farmer's subscription status
 */
function getSubscriptionStatus($farmerId) {
    $db = Database::getInstance();
    $stmt = $db->prepare("SELECT * FROM subscriptions WHERE farmer_id = ? AND status = 'active' ORDER BY expires_at DESC LIMIT 1");
    $stmt->bind_param("s", $farmerId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Check if farmer has active subscription
 */
function hasActiveSubscription($farmerId) {
    $subscription = getSubscriptionStatus($farmerId);
    if (!$subscription) return false;
    
    $now = new DateTime();
    $expires = new DateTime($subscription['expires_at']);
    return $now < $expires;
}

// =============================================
// 21. ENVIRONMENT CHECK
// =============================================

// Verify required extensions are loaded
$requiredExtensions = ['mysqli', 'curl', 'json', 'session'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

if (!empty($missingExtensions)) {
    die('Missing required PHP extensions: ' . implode(', ', $missingExtensions));
}

// =============================================
// 22. APPLICATION START
// =============================================

// Initialize database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Set timezone
date_default_timezone_set('Africa/Nairobi');

// Set default character encoding
mb_internal_encoding('UTF-8');

// =============================================
// 23. ADD RATE_LIMITS TABLE IF NOT EXISTS
// =============================================

$createRateLimits = "
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rate_ip (ip_address),
    INDEX idx_rate_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

$conn->query($createRateLimits);

// =============================================
// END OF CONFIGURATION
// =============================================

// Define constant to indicate config is loaded
define('CONFIG_LOADED', true);
?>
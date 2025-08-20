<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

// Define base URL if not defined
defined('BASE_URL') or define('BASE_URL', '/inventory_system');

// Sanitize input data
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Improved IP detection with proxy support and validation
function getClientIP() {
    $ip_keys = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];
    
    $ip = 'UNKNOWN';
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip_candidate) {
                $ip_candidate = trim($ip_candidate);
                
                // Validate IP address
                if (filter_var($ip_candidate, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    $ip = $ip_candidate;
                    break 2; // Break both loops
                }
            }
        }
    }
    
    // Fallback to REMOTE_ADDR if no valid IP found
    if ($ip === 'UNKNOWN' && isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    // Convert IPv6 localhost to IPv4 for consistency
    if ($ip === '::1') {
        $ip = '127.0.0.1';
    }
    
    return $ip;
}

// Enhanced geolocation function with caching
function getIPLocation($ip) {
    // Skip for local IPs
    if ($ip === '127.0.0.1' || $ip === '::1') {
        return 'Localhost';
    }
    
    // Initialize cache if not exists
    static $ipCache = [];
    if (isset($ipCache[$ip])) {
        return $ipCache[$ip];
    }
    
    $location = 'Unknown';
    
    try {
        // Use ip-api.com (free tier)
        $url = "http://ip-api.com/json/$ip?fields=status,message,country,regionName,city";
        $response = @file_get_contents($url);
        
        if ($response !== false) {
            $data = json_decode($response, true);
            
            if ($data && $data['status'] === 'success') {
                $locationParts = [];
                if (!empty($data['city'])) $locationParts[] = $data['city'];
                if (!empty($data['regionName'])) $locationParts[] = $data['regionName'];
                if (!empty($data['country'])) $locationParts[] = $data['country'];
                
                $location = implode(', ', $locationParts);
            }
        }
    } catch (Exception $e) {
        // Silent fail - don't break the application
        error_log("Geolocation error: " . $e->getMessage());
    }
    
    // Cache the result
    $ipCache[$ip] = $location;
    return $location;
}

// includes/auth_functions.php

function completeLogin($user, $rememberMe) {
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['email'] = $user['email'];
    
    if ($rememberMe) {
        $conn = getDBConnection();
        $session_token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $ip_address = getClientIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        
        $stmt = $conn->prepare("INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $user['id'], $session_token, $ip_address, $user_agent, $expires_at);
        $stmt->execute();
        
        setcookie('remember_token', $session_token, [
            'expires' => strtotime('+3 days'),
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Strict',
            'secure' => isset($_SERVER['HTTPS'])
        ]);
    }
    
    $ip_address = getClientIP();
    $location = getIPLocation($ip_address);
    
    logAction("user_login", "users", $user['id'], null, json_encode([
        'ip_address' => $ip_address,
        'location' => $location,
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ], JSON_PRETTY_PRINT));
    
    header("Location: " . getDashboardLink());
    exit();
}
// Get dashboard link based on user role
function getDashboardLink() {
    if (!isLoggedIn()) return BASE_URL;
    
    $role = getCurrentUserRole();
    switch ($role) {
        case 'admin':
            return BASE_URL . '/admin/dashboard.php';
        case 'inventory_manager':
            return BASE_URL . '/manager/dashboard.php';
        case 'staff':
            return BASE_URL . '/staff/dashboard.php';
        default:
            return BASE_URL;
    }
}

// Enhanced action logger with geolocation
function logAction($action, $tableName = null, $recordId = null, $oldValue = null, $newValue = null, $ipAddress = null, $location = null) {
    if (!isLoggedIn()) return false;
    
    $conn = getDBConnection();
    
    if ($ipAddress === null) {
        $ipAddress = getClientIP();
    }
    
    if ($location === null) {
        $location = getIPLocation($ipAddress);
    }
    
    // Prepare values for JSON storage if they're arrays/objects
    if (is_array($oldValue) || is_object($oldValue)) {
        $oldValue = json_encode($oldValue, JSON_PRETTY_PRINT);
    }
    
    if (is_array($newValue) || is_object($newValue)) {
        $newValue = json_encode($newValue, JSON_PRETTY_PRINT);
    }
    
    $stmt = $conn->prepare("INSERT INTO audit_logs 
                          (user_id, action, table_name, record_id, old_value, new_value, ip_address, location, created_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("ississss", $_SESSION['user_id'], $action, $tableName, $recordId, $oldValue, $newValue, $ipAddress, $location);
    $result = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return $result;
}

// Get user by ID
function getUserById($id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $user;
}

// Get all permissions
function getAllPermissions() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT * FROM permissions ORDER BY category, name");
    $permissions = [];
    
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row;
    }
    
    $conn->close();
    return $permissions;
}

// Get permissions for a specific role
function getPermissionsForRole($role) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT p.* FROM role_permissions rp 
                           JOIN permissions p ON rp.permission_id = p.id 
                           WHERE rp.role = ?");
    $stmt->bind_param("s", $role);
    $stmt->execute();
    $result = $stmt->get_result();
    $permissions = [];
    
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $permissions;
}
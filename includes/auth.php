<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user has specific permission
function hasPermission($permissionName) {
    if (!isLoggedIn()) return false;
    
    $conn = getDBConnection();
    $userId = $_SESSION['user_id'];
    
    // Check user-specific permissions first
    $stmt = $conn->prepare("SELECT p.name FROM user_permissions up 
                           JOIN permissions p ON up.permission_id = p.id 
                           WHERE up.user_id = ? AND p.name = ?");
    $stmt->bind_param("is", $userId, $permissionName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        $conn->close();
        return true;
    }
    
    // Check role permissions
    $stmt = $conn->prepare("SELECT p.name FROM users u 
                           JOIN role_permissions rp ON u.role = rp.role 
                           JOIN permissions p ON rp.permission_id = p.id 
                           WHERE u.id = ? AND p.name = ?");
    $stmt->bind_param("is", $userId, $permissionName);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $hasPermission = $result->num_rows > 0;
    
    $stmt->close();
    $conn->close();
    
    return $hasPermission;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: " . BASE_URL . "/login.php");
        exit();
    }
}

// Redirect if user doesn't have required permission
function requirePermission($permissionName) {
    requireLogin();
    
    if (!hasPermission($permissionName)) {
        $_SESSION['error'] = "You don't have permission to access this page.";
        header("Location: " . getDashboardLink());
        exit();
    }
}

// Get current user's role
function getCurrentUserRole() {
    if (!isLoggedIn()) return null;
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $role = $result->fetch_assoc()['role'];
    
    $stmt->close();
    $conn->close();
    
    return $role;
}
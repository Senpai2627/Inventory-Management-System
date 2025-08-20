<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_operations.php';
require_once __DIR__ . '/../includes/functions.php';

requirePermission('delete_inventory');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Get client IP for audit logging
    $ipAddress = getClientIP();
    
    if (deleteInventoryItem($id)) {
        $_SESSION['success'] = "Item deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting item";
    }
}

header("Location: items.php");
exit();
?>
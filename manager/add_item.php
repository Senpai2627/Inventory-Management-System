<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_operations.php';
require_once __DIR__ . '/../includes/functions.php';

requirePermission('add_inventory');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => sanitizeInput($_POST['name']),
        'description' => sanitizeInput($_POST['description']),
        'category_id' => intval($_POST['category_id']),
        'supplier_id' => isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : null,
        'quantity' => intval($_POST['quantity']),
        'price' => floatval($_POST['price']),
        'reorder_level' => intval($_POST['reorder_level']),
        'location' => sanitizeInput($_POST['location']),
        'barcode' => sanitizeInput($_POST['barcode'])
    ];
    
    // Get client IP for audit logging
    $ipAddress = getClientIP();
    
    $itemId = addInventoryItem($data);
    
    if ($itemId) {
        // Log the action with IP address
        logAction("Added inventory item", "inventory_items", $itemId, null, json_encode($data), $ipAddress);
        
        $_SESSION['success'] = "Item added successfully";
        header("Location: items.php");
        exit();
    } else {
        $_SESSION['error'] = "Error adding item";
        header("Location: items.php");
        exit();
    }
} else {
    header("Location: items.php");
    exit();
}
?>
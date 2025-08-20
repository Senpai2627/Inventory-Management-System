<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_operations.php';
require_once __DIR__ . '/../includes/functions.php';

requirePermission('edit_inventory');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
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
    
    if (updateInventoryItem($id, $data)) {
        $_SESSION['success'] = "Item updated successfully";
        header("Location: items.php");
        exit();
    } else {
        $_SESSION['error'] = "Error updating item";
        header("Location: items.php");
        exit();
    }
} else {
    header("Location: items.php");
    exit();
}
?>
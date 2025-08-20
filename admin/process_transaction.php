<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_operations.php';

requirePermission('process_transactions');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $itemId = intval($_POST['item_id']);
    $userId = $_SESSION['user_id'];
    $type = sanitizeInput($_POST['transaction_type']);
    $quantity = intval($_POST['quantity']);
    $notes = sanitizeInput($_POST['notes']);
    $reference = sanitizeInput($_POST['reference_number']);
    
    $transactionId = addTransaction($itemId, $userId, $type, $quantity, $notes, $reference);
    
    if ($transactionId) {
        $_SESSION['success'] = "Transaction processed successfully";
        header("Location: transactions.php");
        exit();
    } else {
        $_SESSION['error'] = "Error processing transaction";
        header("Location: transactions.php");
        exit();
    }
} else {
    header("Location: transactions.php");
    exit();
}
?>
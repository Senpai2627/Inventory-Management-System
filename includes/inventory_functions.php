<?php
require_once __DIR__ . '/db_operations.php';
require_once __DIR__ . '/functions.php'; // Include functions.php for shared functions

// Check for low stock items
function getLowStockItems($threshold = null) {
    $conn = getDBConnection();
    
    if ($threshold === null) {
        // Get items below their reorder level
        $sql = "SELECT i.*, c.name as category_name 
                FROM inventory_items i 
                LEFT JOIN categories c ON i.category_id = c.id 
                WHERE i.quantity <= i.reorder_level 
                ORDER BY i.quantity ASC";
        $stmt = $conn->prepare($sql);
    } else {
        // Get items below specified threshold
        $sql = "SELECT i.*, c.name as category_name 
                FROM inventory_items i 
                LEFT JOIN categories c ON i.category_id = c.id 
                WHERE i.quantity <= ? 
                ORDER BY i.quantity ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $threshold);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return $items;
}

// Generate inventory report data
function generateInventoryReport($type = 'summary', $params = []) {
    $conn = getDBConnection();
    $reportData = [];
    
    switch ($type) {
        case 'summary':
            // Total items, total value, low stock count
            $result = $conn->query("
                SELECT 
                    COUNT(*) as total_items,
                    SUM(quantity) as total_quantity,
                    SUM(quantity * price) as total_value,
                    SUM(CASE WHEN quantity <= reorder_level THEN 1 ELSE 0 END) as low_stock_items
                FROM inventory_items
            ");
            $reportData = $result->fetch_assoc();
            break;
            
        case 'category':
            // Items by category
            $stmt = $conn->prepare("
                SELECT c.name as category, 
                       COUNT(i.id) as item_count,
                       SUM(i.quantity) as total_quantity,
                       SUM(i.quantity * i.price) as total_value
                FROM categories c
                LEFT JOIN inventory_items i ON c.id = i.category_id
                GROUP BY c.id, c.name
                ORDER BY c.name
            ");
            $stmt->execute();
            $result = $stmt->get_result();
            $reportData = [];
            
            while ($row = $result->fetch_assoc()) {
                $reportData[] = $row;
            }
            break;
            
        case 'transactions':
            // Transaction summary by date range
            $startDate = $params['start_date'] ?? date('Y-m-01');
            $endDate = $params['end_date'] ?? date('Y-m-t');
            
            $stmt = $conn->prepare("
                SELECT 
                    DATE(t.created_at) as transaction_date,
                    t.transaction_type,
                    COUNT(t.id) as transaction_count,
                    SUM(t.quantity) as total_quantity,
                    SUM(t.quantity * i.price) as total_value
                FROM inventory_transactions t
                JOIN inventory_items i ON t.item_id = i.id
                WHERE DATE(t.created_at) BETWEEN ? AND ?
                GROUP BY DATE(t.created_at), t.transaction_type
                ORDER BY DATE(t.created_at), t.transaction_type
            ");
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            $result = $stmt->get_result();
            $reportData = [];
            
            while ($row = $result->fetch_assoc()) {
                $reportData[] = $row;
            }
            break;
    }
    
    $conn->close();
    return $reportData;
}
?>
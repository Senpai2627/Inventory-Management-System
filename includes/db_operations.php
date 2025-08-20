<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';


// Inventory Item Operations
function getInventoryItems($search = '', $categoryId = null, $page = 1, $perPage = 10) {
    $conn = getDBConnection();
    $offset = ($page - 1) * $perPage;
    
    $sql = "SELECT i.*, c.name as category_name, s.name as supplier_name 
            FROM inventory_items i 
            LEFT JOIN categories c ON i.category_id = c.id 
            LEFT JOIN suppliers s ON i.supplier_id = s.id 
            WHERE (i.name LIKE ? OR i.description LIKE ?)";
    
    $params = ["%$search%", "%$search%"];
    $types = "ss";
    
    if ($categoryId) {
        $sql .= " AND i.category_id = ?";
        $params[] = $categoryId;
        $types .= "i";
    }
    
    $sql .= " LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total FROM inventory_items i WHERE (i.name LIKE ? OR i.description LIKE ?)";
    $countParams = ["%$search%", "%$search%"];
    $countTypes = "ss";
    
    if ($categoryId) {
        $countSql .= " AND i.category_id = ?";
        $countParams[] = $categoryId;
        $countTypes .= "i";
    }
    
    $countStmt = $conn->prepare($countSql);
    $countStmt->bind_param($countTypes, ...$countParams);
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    
    $stmt->close();
    $countStmt->close();
    $conn->close();
    
    return [
        'items' => $items,
        'total' => $total,
        'pages' => ceil($total / $perPage)
    ];
}

function getInventoryItemById($id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT i.*, c.name as category_name, s.name as supplier_name 
                           FROM inventory_items i 
                           LEFT JOIN categories c ON i.category_id = c.id 
                           LEFT JOIN suppliers s ON i.supplier_id = s.id 
                           WHERE i.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $item;
}

function addInventoryItem($data) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO inventory_items 
                           (name, description, category_id, supplier_id, quantity, price, reorder_level, location, barcode) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiiddiss", 
        $data['name'], $data['description'], $data['category_id'], $data['supplier_id'], 
        $data['quantity'], $data['price'], $data['reorder_level'], $data['location'], $data['barcode']);
    
    $result = $stmt->execute();
    $insertId = $conn->insert_id;
    
    $stmt->close();
    $conn->close();
    
    if ($result) {
        $ipAddress = getClientIP();
        logAction("Added inventory item", "inventory_items", $insertId, null, json_encode($data), $ipAddress);
        return $insertId;
    }
    
    return false;
}

function updateInventoryItem($id, $data) {
    $oldItem = getInventoryItemById($id);
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE inventory_items SET 
                           name = ?, description = ?, category_id = ?, supplier_id = ?, 
                           quantity = ?, price = ?, reorder_level = ?, location = ?, barcode = ? 
                           WHERE id = ?");
    $stmt->bind_param("ssiiddissi", 
        $data['name'], $data['description'], $data['category_id'], $data['supplier_id'], 
        $data['quantity'], $data['price'], $data['reorder_level'], $data['location'], $data['barcode'], $id);
    
    $result = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    if ($result) {
        $ipAddress = getClientIP();
        logAction("Updated inventory item", "inventory_items", $id, json_encode($oldItem), json_encode($data), $ipAddress);
        return true;
    }
    
    return false;
}

function deleteInventoryItem($id) {
    $oldItem = getInventoryItemById($id);
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("DELETE FROM inventory_items WHERE id = ?");
    $stmt->bind_param("i", $id);
    $result = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    if ($result) {
        $ipAddress = getClientIP();
        logAction("Deleted inventory item", "inventory_items", $id, json_encode($oldItem), null, $ipAddress);
        return true;
    }
    
    return false;
}

// Category Operations
function getAllCategories() {
    $conn = getDBConnection();
    $result = $conn->query("SELECT * FROM categories ORDER BY name");
    $categories = [];
    
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    
    $conn->close();
    return $categories;
}

function getCategoryById($id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $category = $result->fetch_assoc();
    
    $stmt->close();
    $conn->close();
    
    return $category;
}

function addCategory($name, $description) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $description);
    $result = $stmt->execute();
    $insertId = $conn->insert_id;
    
    $stmt->close();
    $conn->close();
    
    if ($result) {
        $ipAddress = getClientIP();
        logAction("Added category", "categories", $insertId, null, json_encode(['name' => $name, 'description' => $description]), $ipAddress);
        return $insertId;
    }
    
    return false;
}

function updateCategory($id, $name, $description) {
    $oldCategory = getCategoryById($id);
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $description, $id);
    $result = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    if ($result) {
        $ipAddress = getClientIP();
        logAction("Updated category", "categories", $id, json_encode($oldCategory), json_encode(['name' => $name, 'description' => $description]), $ipAddress);
        return true;
    }
    
    return false;
}

function deleteCategory($id) {
    // Check if category is used by any items
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM inventory_items WHERE category_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    
    if ($count > 0) {
        $stmt->close();
        $conn->close();
        return false; // Category is in use
    }
    
    $oldCategory = getCategoryById($id);
    
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    $result = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    if ($result) {
        $ipAddress = getClientIP();
        logAction("Deleted category", "categories", $id, json_encode($oldCategory), null, $ipAddress);
        return true;
    }
    
    return false;
}

// Supplier Operations
function getSupplierById($id) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT * FROM suppliers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $supplier = $result->fetch_assoc();

    $stmt->close();
    $conn->close();
    
    return $supplier;
}

// Transaction Operations
function getTransactions($page = 1, $perPage = 10, $itemId = null, $userId = null) {
    $conn = getDBConnection();
    $offset = ($page - 1) * $perPage;

    $sql = "SELECT t.*, i.name as item_name, u.username as user_name 
            FROM inventory_transactions t 
            JOIN inventory_items i ON t.item_id = i.id 
            JOIN users u ON t.user_id = u.id";

    $where = [];
    $params = [];
    $types = "";

    if ($itemId !== null) {
        $where[] = "t.item_id = ?";
        $params[] = $itemId;
        $types .= "i";
    }

    if ($userId !== null) {
        $where[] = "t.user_id = ?";
        $params[] = $userId;
        $types .= "i";
    }

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY t.created_at DESC LIMIT ? OFFSET ?";
    $paramsWithPagination = $params;
    $typesWithPagination = $types;
    $paramsWithPagination[] = $perPage;
    $paramsWithPagination[] = $offset;
    $typesWithPagination .= "ii";

    $stmt = $conn->prepare($sql);
    if (!empty($paramsWithPagination)) {
        $stmt->bind_param($typesWithPagination, ...$paramsWithPagination);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $transactions = [];

    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }

    // Count total without LIMIT and OFFSET
    $countSql = "SELECT COUNT(*) as total FROM inventory_transactions t";
    if (!empty($where)) {
        $countSql .= " WHERE " . implode(" AND ", $where);
    }

    $countStmt = $conn->prepare($countSql);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];

    $stmt->close();
    $countStmt->close();
    $conn->close();

    return [
        'transactions' => $transactions,
        'total' => $total,
        'pages' => ceil($total / $perPage)
    ];
}

function addTransaction($itemId, $userId, $type, $quantity, $notes = null, $reference = null) {
    $conn = getDBConnection();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Add the transaction record
        $stmt = $conn->prepare("INSERT INTO inventory_transactions 
                              (item_id, user_id, transaction_type, quantity, notes, reference_number) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisiss", $itemId, $userId, $type, $quantity, $notes, $reference);
        $stmt->execute();
        $transactionId = $conn->insert_id;
        
        // Update inventory quantity based on transaction type
        $operator = ($type == 'purchase') ? '+' : '-';
        $updateStmt = $conn->prepare("UPDATE inventory_items SET quantity = quantity $operator ? WHERE id = ?");
        $updateStmt->bind_param("ii", $quantity, $itemId);
        $updateStmt->execute();
        
        $conn->commit();
        
        $ipAddress = getClientIP();
        logAction("Added transaction", "inventory_transactions", $transactionId, null, json_encode([
            'item_id' => $itemId,
            'type' => $type,
            'quantity' => $quantity,
            'notes' => $notes
        ]), $ipAddress);
        
        return $transactionId;
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    } finally {
        $stmt->close();
        if (isset($updateStmt)) $updateStmt->close();
        $conn->close();
    }
}




function logPageView($page, $userId = null, $ipAddress = null) {
    $conn = getDBConnection();
    
    if ($ipAddress === null) {
        $ipAddress = getClientIP();
    }
    
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $referrer = $_SERVER['HTTP_REFERER'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO page_views 
                          (user_id, page, ip_address, user_agent, referrer) 
                          VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $userId, $page, $ipAddress, $userAgent, $referrer);
    $result = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return $result;
}
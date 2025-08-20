<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_operations.php';
require_once __DIR__ . '/../includes/inventory_functions.php';

// Check permissions for both dashboard and inventory
requirePermission('view_dashboard');
requirePermission('view_inventory');

// Get client IP for logging purposes
$clientIP = getClientIP();
logPageView('inventory_dashboard', $_SESSION['user_id'] ?? null, $clientIP);

// Get dashboard statistics
$conn = getDBConnection();
$stats = [
    'total_items' => $conn->query("SELECT COUNT(*) as count FROM inventory_items")->fetch_assoc()['count'],
    'total_value' => $conn->query("SELECT SUM(quantity * price) as total FROM inventory_items")->fetch_assoc()['total'],
    'low_stock' => $conn->query("SELECT COUNT(*) as count FROM inventory_items WHERE quantity <= reorder_level")->fetch_assoc()['count'],
    'total_users' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
    'recent_transactions' => $conn->query("SELECT t.*, i.name as item_name, u.username as user_name 
                                          FROM inventory_transactions t 
                                          JOIN inventory_items i ON t.item_id = i.id 
                                          JOIN users u ON t.user_id = u.id 
                                          ORDER BY t.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC)
];
$conn->close();

$lowStockItems = getLowStockItems();

// Get inventory data for the management section
$search = $_GET['search'] ?? '';
$categoryId = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;

$inventoryData = getInventoryItems($search, $categoryId, $page, $perPage);
$categories = getAllCategories();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<style>
    :root {
        --primary-blue: #1a73e8;
        --white: #ffffff;
        --light-grey: #f8f9fa;
        --border-color: #e0e0e0;
    }
    
    body {
        background-color: var(--white);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .dashboard-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem;
    }
    
    .dashboard-header {
        background-color: var(--white);
        padding: 1.5rem 0;
        margin-bottom: 1.5rem;
        border-bottom: 1px solid var(--border-color);
    }
    
    .dashboard-title {
        color: var(--primary-blue);
        font-weight: 600;
        margin-bottom: 0;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background-color: var(--white);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 1.5rem;
        text-align: center;
    }
    
    .stat-icon {
        color: var(--primary-blue);
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }
    
    .stat-value {
        font-size: 1.75rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 0.25rem;
    }
    
    .stat-label {
        color: #666;
        font-size: 0.9rem;
    }
    
    .card {
        background-color: var(--white);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        margin-bottom: 1.5rem;
    }
    
    .card-header {
        background-color: var(--white);
        border-bottom: 1px solid var(--border-color);
        padding: 1rem 1.5rem;
    }
    
    .card-header h5 {
        color: var(--primary-blue);
        font-weight: 600;
        margin: 0;
    }
    
    .btn {
        border-radius: 6px;
        padding: 0.75rem 1.5rem;
        font-weight: 500;
    }
    
    .btn-primary {
        background-color: var(--primary-blue);
        border: none;
    }
    
    .form-control, .form-select {
        border: 1px solid var(--border-color);
        border-radius: 6px;
        padding: 0.75rem;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.1);
    }
    
    .table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .table th {
        background-color: var(--light-grey);
        color: #333;
        font-weight: 600;
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid var(--border-color);
    }
    
    .table td {
        padding: 1rem;
        border-bottom: 1px solid var(--border-color);
        vertical-align: middle;
    }
    
    .low-stock {
        background-color: rgba(255, 193, 7, 0.1);
    }
    
    .critical-stock {
        background-color: rgba(220, 53, 69, 0.1);
    }
    
    .badge {
        border-radius: 6px;
        font-weight: 500;
        padding: 0.35em 0.65em;
    }
    
    .badge-primary {
        background-color: rgba(26, 115, 232, 0.1);
        color: var(--primary-blue);
    }
    
    .badge-warning {
        background-color: rgba(255, 193, 7, 0.1);
        color: #ffc107;
    }
    
    .badge-danger {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }
    
    .action-btn {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
    }
    
    .pagination .page-item.active .page-link {
        background-color: var(--primary-blue);
        border-color: var(--primary-blue);
    }
    
    .pagination .page-link {
        color: var(--primary-blue);
    }
    
    .alert {
        border-radius: 6px;
        padding: 1rem;
    }
    
    .alert-success {
        background-color: #d4edda;
        color: #155724;
    }
    
    .alert-info {
        background-color: #d1ecf1;
        color: #0c5460;
    }
    
    .status-indicator {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 6px;
    }
    
    .status-active {
        background-color: #28a745;
    }
    
    .tab-content {
        padding: 1.5rem 0;
    }
    
    .nav-tabs .nav-link {
        color: #495057;
        font-weight: 500;
    }
    
    .nav-tabs .nav-link.active {
        color: var(--primary-blue);
        border-bottom: 2px solid var(--primary-blue);
    }
    
    @media (max-width: 768px) {
        .dashboard-container {
            padding: 1rem;
        }
        
        .stats-grid {
            grid-template-columns: 1fr 1fr;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .action-btns {
            white-space: nowrap;
        }
    }
</style>

<div class="dashboard-container">
    <div class="dashboard-header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="dashboard-title">Inventory Dashboard</h1>
            <div class="text-muted">
                <?php echo date('F j, Y'); ?>
            </div>
        </div>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="stat-value"><?php echo $stats['total_items']; ?></div>
            <div class="stat-label">Total Items</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value">₱<?php echo number_format($stats['total_value'], 2); ?></div>
            <div class="stat-icon">
                <i class="fas fa-peso-sign"></i>
            </div>
            <div class="stat-label">Total Value</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-value"><?php echo $stats['low_stock']; ?></div>
            <div class="stat-label">Low Stock Items</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-value"><?php echo $stats['total_users']; ?></div>
            <div class="stat-label">Total Users</div>
        </div>
    </div>
    
    <ul class="nav nav-tabs" id="inventoryTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">
                <i class="fas fa-chart-pie me-2"></i>Overview
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="management-tab" data-bs-toggle="tab" data-bs-target="#management" type="button" role="tab">
                <i class="fas fa-boxes me-2"></i>Inventory Management
            </button>
        </li>
    </ul>
    
    <div class="tab-content" id="inventoryTabsContent">
        <!-- Overview Tab -->
        <div class="tab-pane fade show active" id="overview" role="tabpanel">
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-exclamation-circle me-2"></i>Low Stock Items</h5>
                                <span class="badge badge-danger"><?php echo count($lowStockItems); ?> Items</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($lowStockItems)): ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Quantity</th>
                                                <th>Reorder Level</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($lowStockItems as $item): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                    <td><?php echo $item['quantity']; ?></td>
                                                    <td><?php echo $item['reorder_level']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i> No low stock items
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5><i class="fas fa-exchange-alt me-2"></i>Recent Transactions</h5>
                                <span class="badge badge-primary"><?php echo count($stats['recent_transactions']); ?> Transactions</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($stats['recent_transactions'])): ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Type</th>
                                                <th>Qty</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($stats['recent_transactions'] as $txn): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($txn['item_name']); ?></td>
                                                    <td>
                                                        <span style="color: <?php echo $txn['transaction_type'] === 'in' ? '#28a745' : '#dc3545'; ?>">
                                                            <?php echo ucfirst($txn['transaction_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo $txn['quantity']; ?></td>
                                                    <td><?php echo date('M j, H:i', strtotime($txn['created_at'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> No recent transactions
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Management Tab -->
        <div class="tab-pane fade" id="management" role="tabpanel">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 style="color: var(--primary-blue);">
                    <i class="fas fa-boxes me-2"></i>Inventory Management
                </h2>
                <div class="text-muted">
                    <?php echo $inventoryData['total']; ?> <?php echo $inventoryData['total'] === 1 ? 'Item' : 'Items'; ?>
                </div>
            </div>
            
<div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-filter me-2"></i>Filter Items
                </h5>
                <?php if (hasPermission('add_inventory')): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="fas fa-plus me-1"></i> Add Item
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" action="items.php" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="search" placeholder="Search items..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="category_id">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $categoryId == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Inventory Items
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Value</th>
                                    <th class="action-btns">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inventoryData['items'] as $item): ?>
                                    <tr class="<?php 
                                        echo $item['quantity'] <= ($item['reorder_level'] / 2) ? 'critical-stock' : 
                                            ($item['quantity'] <= $item['reorder_level'] ? 'low-stock' : ''); 
                                    ?>">
                                        <td><?php echo $item['id']; ?></td>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php echo $item['quantity']; ?>
                                            <?php if ($item['quantity'] <= $item['reorder_level']): ?>
                                                <span class="badge <?php 
                                                    echo $item['quantity'] <= ($item['reorder_level'] / 2) ? 'badge-danger' : 'badge-warning'; 
                                                ?>">
                                                    <?php echo $item['quantity'] <= ($item['reorder_level'] / 2) ? 'Critical' : 'Low'; ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                        <td>₱<?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                                        <td class="action-btns">
                                            <div class="d-flex gap-2">
                                                <a href="item_details.php?id=<?php echo $item['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary action-btn"
                                                   data-bs-toggle="tooltip" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                
                                                <?php if (hasPermission('edit_inventory')): ?>
                                                    <button class="btn btn-sm btn-outline-secondary action-btn edit-btn"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editItemModal<?php echo $item['id']; ?>"
                                                            data-bs-toggle="tooltip" title="Edit Item">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if (hasPermission('delete_inventory')): ?>
                                                    <a href="delete_item.php?id=<?php echo $item['id']; ?>" 
                                                       class="btn btn-sm btn-outline-danger action-btn delete-btn"
                                                       data-bs-toggle="tooltip" title="Delete Item">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    <!-- Edit Item Modal -->
                                    <div class="modal fade" id="editItemModal<?php echo $item['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-edit me-2"></i>
                                                        Edit Item: <?php echo htmlspecialchars($item['name']); ?>
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" action="update_item.php">
                                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                    
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label for="name<?php echo $item['id']; ?>" class="form-label">Item Name</label>
                                                                <input type="text" class="form-control" id="name<?php echo $item['id']; ?>" 
                                                                       name="name" value="<?php echo htmlspecialchars($item['name']); ?>" required>
                                                            </div>
                                                            
                                                            <div class="col-md-6 mb-3">
                                                                <label for="category_id<?php echo $item['id']; ?>" class="form-label">Category</label>
                                                                <select class="form-select" id="category_id<?php echo $item['id']; ?>" 
                                                                        name="category_id" required>
                                                                    <option value="">Select Category</option>
                                                                    <?php foreach ($categories as $category): ?>
                                                                        <option value="<?php echo $category['id']; ?>" 
                                                                            <?php echo $item['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                                                            <?php echo htmlspecialchars($category['name']); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label for="description<?php echo $item['id']; ?>" class="form-label">Description</label>
                                                            <textarea class="form-control" id="description<?php echo $item['id']; ?>" 
                                                                      name="description" rows="3"><?php echo htmlspecialchars($item['description']); ?></textarea>
                                                        </div>
                                                        
                                                        <div class="row">
                                                            <div class="col-md-4 mb-3">
                                                                <label for="quantity<?php echo $item['id']; ?>" class="form-label">Quantity</label>
                                                                <input type="number" class="form-control" id="quantity<?php echo $item['id']; ?>" 
                                                                       name="quantity" value="<?php echo $item['quantity']; ?>" min="0" step="1" required>
                                                            </div>
                                                            
                                                            <div class="col-md-4 mb-3">
                                                                <label for="price<?php echo $item['id']; ?>" class="form-label">Price</label>
                                                                <input type="number" class="form-control" id="price<?php echo $item['id']; ?>" 
                                                                       name="price" value="<?php echo $item['price']; ?>" min="0" step="0.01" required>
                                                            </div>
                                                            
                                                            <div class="col-md-4 mb-3">
                                                                <label for="reorder_level<?php echo $item['id']; ?>" class="form-label">Reorder Level</label>
                                                                <input type="number" class="form-control" id="reorder_level<?php echo $item['id']; ?>" 
                                                                       name="reorder_level" value="<?php echo $item['reorder_level']; ?>" min="0" step="1" required>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label for="location<?php echo $item['id']; ?>" class="form-label">Location</label>
                                                                <input type="text" class="form-control" id="location<?php echo $item['id']; ?>" 
                                                                       name="location" value="<?php echo htmlspecialchars($item['location']); ?>">
                                                            </div>
                                                            
                                                            <div class="col-md-6 mb-3">
                                                                <label for="barcode<?php echo $item['id']; ?>" class="form-label">Barcode</label>
                                                                <input type="text" class="form-control" id="barcode<?php echo $item['id']; ?>" 
                                                                       name="barcode" value="<?php echo htmlspecialchars($item['barcode']); ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                            <i class="fas fa-times me-1"></i> Close
                                                        </button>
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="fas fa-save me-1"></i> Save Changes
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($inventoryData['pages'] > 1): ?>
                        <div class="p-3 border-top">
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center mb-0">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?tab=management&page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category_id=<?php echo $categoryId; ?>">
                                                <i class="fas fa-chevron-left"></i> Previous
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php 
                                        $startPage = max(1, $page - 2);
                                        $endPage = min($inventoryData['pages'], $page + 2);
                                        
                                        if ($startPage > 1) {
                                            echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => 1, 'tab' => 'management'])) . '">1</a></li>';
                                            if ($startPage > 2) {
                                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                            }
                                        }
                                        
                                        for ($i = $startPage; $i <= $endPage; $i++): 
                                    ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i, 'tab' => 'management'])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php 
                                        endfor;
                                        
                                        if ($endPage < $inventoryData['pages']) {
                                            if ($endPage < $inventoryData['pages'] - 1) {
                                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                            }
                                            echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $inventoryData['pages'], 'tab' => 'management'])) . '">' . $inventoryData['pages'] . '</a></li>';
                                        }
                                    ?>
                                    
                                    <?php if ($page < $inventoryData['pages']): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?tab=management&page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category_id=<?php echo $categoryId; ?>">
                                                Next <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>Add New Inventory Item
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="add_item.php">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Item Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" min="0" step="1" value="0" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="price" class="form-label">Price</label>
                            <input type="number" class="form-control" id="price" name="price" min="0" step="0.01" value="0.00" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="reorder_level" class="form-label">Reorder Level</label>
                            <input type="number" class="form-control" id="reorder_level" name="reorder_level" min="0" step="1" value="10" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="barcode" class="form-label">Barcode</label>
                            <input type="text" class="form-control" id="barcode" name="barcode">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Close
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Add Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Confirm before deleting
        const deleteButtons = document.querySelectorAll('.delete-btn');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this item?')) {
                    e.preventDefault();
                }
            });
        });
        
        // Fix for edit button - ensure it only triggers the modal
        const editButtons = document.querySelectorAll('.edit-btn');
        editButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const modalId = this.getAttribute('data-bs-target');
                const modal = new bootstrap.Modal(document.querySelector(modalId));
                modal.show();
            });
        });
        
        // Handle tab persistence
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('tab');
        
        if (activeTab) {
            const tab = document.querySelector(`#${activeTab}-tab`);
            if (tab) {
                new bootstrap.Tab(tab).show();
            }
        }
    });
</script>
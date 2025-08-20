<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['otp_verified'] !== true) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/inventory_functions.php';

requirePermission('view_dashboard');

// Get dashboard statistics for staff
$conn = getDBConnection();
$stats = [
    'low_stock' => $conn->query("SELECT COUNT(*) as count FROM inventory_items WHERE quantity <= reorder_level")->fetch_assoc()['count'],
    'recent_transactions' => $conn->query("SELECT t.*, i.name as item_name 
                                         FROM inventory_transactions t 
                                         JOIN inventory_items i ON t.item_id = i.id 
                                         WHERE t.user_id = {$_SESSION['user_id']} 
                                         ORDER BY t.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC),
    'total_items' => $conn->query("SELECT COUNT(*) as count FROM inventory_items")->fetch_assoc()['count']
];
$conn->close();

$lowStockItems = getLowStockItems();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<style>
    :root {
        --primary-blue: #1a73e8;
        --dark-blue: #0d47a1;
        --light-blue: #e8f0fe;
        --hover-blue: #4285f4;
        --white: #ffffff;
        --shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        --glow: 0 0 15px rgba(26, 115, 232, 0.3);
    }
    
    body {
        background-color: #f8f9fa;
        font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    .dashboard-header {
        background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
        color: white;
        padding: 2rem 0;
        margin-bottom: 2rem;
        border-radius: 0 0 16px 16px;
        box-shadow: var(--shadow);
    }
    
    .dashboard-title {
        font-weight: 600;
        margin-bottom: 0;
        position: relative;
        display: inline-block;
    }
    
    .dashboard-title::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 0;
        width: 50px;
        height: 4px;
        background: white;
        border-radius: 2px;
    }
    
    .widget {
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow);
        transition: all 0.3s ease;
        border-left: 4px solid var(--primary-blue);
        position: relative;
        overflow: hidden;
    }
    
    .widget:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow), var(--glow);
    }
    
    .widget-icon {
        width: 50px;
        height: 50px;
        background: var(--light-blue);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-blue);
        font-size: 1.25rem;
        margin-bottom: 1rem;
    }
    
    .widget-value {
        font-size: 1.75rem;
        font-weight: 700;
        color: var(--dark-blue);
        margin-bottom: 0.25rem;
    }
    
    .widget-title {
        color: #666;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: var(--shadow);
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
    }
    
    .card:hover {
        box-shadow: var(--shadow), var(--glow);
    }
    
    .card-header {
        background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
        color: white;
        border-radius: 12px 12px 0 0 !important;
        padding: 1rem 1.5rem;
        border-bottom: none;
    }
    
    .card-header h5 {
        font-weight: 600;
        margin: 0;
    }
    
    .table {
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .table thead th {
        background-color: var(--light-blue);
        color: var(--dark-blue);
        border-top: none;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
    }
    
    .table th, .table td {
        padding: 1rem;
        vertical-align: middle;
        border-top: 1px solid #f0f0f0;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(26, 115, 232, 0.05);
    }
    
    .table-danger {
        background-color: rgba(220, 53, 69, 0.1) !important;
    }
    
    .table-warning {
        background-color: rgba(255, 193, 7, 0.1) !important;
    }
    
    .alert {
        border-radius: 8px;
        border-left: 4px solid;
    }
    
    .alert-success {
        border-left-color: #28a745;
    }
    
    .alert-info {
        border-left-color: #17a2b8;
    }
    
    .badge {
        padding: 0.35em 0.65em;
        font-weight: 500;
        border-radius: 8px;
    }
    
    .badge-in {
        background-color: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }
    
    .badge-out {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }
    
    .floating-shapes {
        position: fixed;
        width: 100%;
        height: 100%;
        overflow: hidden;
        z-index: -1;
        pointer-events: none;
    }
    
    .shape {
        position: absolute;
        opacity: 0.1;
        border-radius: 50%;
        background: var(--primary-blue);
        filter: blur(40px);
        animation: float 15s infinite linear;
    }
    
    .shape:nth-child(1) {
        width: 300px;
        height: 300px;
        top: 10%;
        left: 10%;
        animation-delay: 0s;
    }
    
    .shape:nth-child(2) {
        width: 200px;
        height: 200px;
        top: 60%;
        left: 70%;
        animation-delay: 3s;
    }
    
    .shape:nth-child(3) {
        width: 250px;
        height: 250px;
        top: 30%;
        left: 50%;
        animation-delay: 6s;
    }
    
    @keyframes float {
        0% { transform: translate(0, 0) rotate(0deg); }
        25% { transform: translate(50px, -30px) rotate(5deg); }
        50% { transform: translate(100px, 0) rotate(0deg); }
        75% { transform: translate(50px, 30px) rotate(-5deg); }
        100% { transform: translate(0, 0) rotate(0deg); }
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
    
    .quick-actions {
        display: flex;
        gap: 15px;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }
    
    .quick-action {
        flex: 1;
        min-width: 120px;
        background: white;
        border-radius: 12px;
        padding: 1.5rem;
        text-align: center;
        box-shadow: var(--shadow);
        transition: all 0.3s ease;
        color: var(--dark-blue);
        text-decoration: none;
    }
    
    .quick-action:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow), var(--glow);
        color: var(--primary-blue);
    }
    
    .quick-action-icon {
        width: 50px;
        height: 50px;
        background: var(--light-blue);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary-blue);
        font-size: 1.25rem;
        margin: 0 auto 1rem;
    }
    
    .quick-action-title {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    .quick-action-desc {
        font-size: 0.8rem;
        color: #666;
    }
    
    @media (max-width: 768px) {
        .widget-value {
            font-size: 1.5rem;
        }
        
        .widget-icon {
            width: 40px;
            height: 40px;
            font-size: 1rem;
        }
        
        .quick-actions {
            flex-direction: column;
            gap: 10px;
        }
        
        .quick-action {
            min-width: 100%;
        }
    }
</style>

<div class="floating-shapes">
    <div class="shape"></div>
    <div class="shape"></div>
    <div class="shape"></div>
</div>

<div class="dashboard-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="dashboard-title">Staff Dashboard</h1>
            <div class="text-white">
                <i class="fas fa-calendar-alt me-2"></i>
                <?php echo date('l, F j, Y'); ?>
            </div>
        </div>
    </div>
</div>

<div class="container mt-4">
    <div class="quick-actions">
        <a href="transactions.php?type=in" class="quick-action">
            <div class="quick-action-icon">
                <i class="fas fa-arrow-down"></i>
            </div>
            <div class="quick-action-title">Stock In</div>
            <div class="quick-action-desc">Add new inventory</div>
        </a>
        
        <a href="transactions.php?type=out" class="quick-action">
            <div class="quick-action-icon">
                <i class="fas fa-arrow-up"></i>
            </div>
            <div class="quick-action-title">Stock Out</div>
            <div class="quick-action-desc">Remove inventory</div>
        </a>
        
        <a href="items.php" class="quick-action">
            <div class="quick-action-icon">
                <i class="fas fa-search"></i>
            </div>
            <div class="quick-action-title">Search Items</div>
            <div class="quick-action-desc">Find inventory</div>
        </a>
        
        <a href="transactions.php" class="quick-action">
            <div class="quick-action-icon">
                <i class="fas fa-list"></i>
            </div>
            <div class="quick-action-title">View All</div>
            <div class="quick-action-desc">Transaction history</div>
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="widget">
                <div class="widget-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="widget-value"><?php echo $stats['low_stock']; ?></div>
                <div class="widget-title">Low Stock Items</div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="widget">
                <div class="widget-icon">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="widget-value"><?php echo count($stats['recent_transactions']); ?></div>
                <div class="widget-title">Your Recent Transactions</div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="widget">
                <div class="widget-icon">
                    <i class="fas fa-boxes"></i>
                </div>
                <div class="widget-value"><?php echo $stats['total_items']; ?></div>
                <div class="widget-title">Total Inventory Items</div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-exclamation-circle me-2"></i>Low Stock Items</h5>
                        <span class="badge bg-danger"><?php echo count($lowStockItems); ?> Items</span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($lowStockItems)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Current</th>
                                        <th>Reorder</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($lowStockItems as $item): ?>
                                        <tr class="<?php echo $item['quantity'] <= ($item['reorder_level'] / 2) ? 'table-danger' : 'table-warning'; ?>">
                                            <td>
                                                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                                <div class="text-muted small"><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></div>
                                            </td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td><?php echo $item['reorder_level']; ?></td>
                                            <td>
                                                <?php if ($item['quantity'] <= ($item['reorder_level'] / 2)): ?>
                                                    <span class="badge bg-danger">Critical</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Warning</span>
                                                <?php endif; ?>
                                            </td>
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
                        <h5><i class="fas fa-history me-2"></i>Your Recent Transactions</h5>
                        <span class="badge bg-primary">Last 5</span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($stats['recent_transactions'])): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Type</th>
                                        <th>Qty</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['recent_transactions'] as $txn): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($txn['item_name']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $txn['transaction_type'] === 'in' ? 'badge-in' : 'badge-out'; ?>">
                                                    <?php echo ucfirst($txn['transaction_type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $txn['quantity']; ?></td>
                                            <td>
                                                <div><?php echo date('M j', strtotime($txn['created_at'])); ?></div>
                                                <div class="text-muted small"><?php echo date('H:i', strtotime($txn['created_at'])); ?></div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end mt-2">
                            <a href="transactions.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-list me-1"></i> View All Transactions
                            </a>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
    // Add animation to widgets on page load
    document.addEventListener('DOMContentLoaded', function() {
        const widgets = document.querySelectorAll('.widget, .quick-action');
        widgets.forEach((widget, index) => {
            widget.style.opacity = '0';
            widget.style.transform = 'translateY(20px)';
            widget.style.animation = `fadeInUp 0.5s ease forwards ${index * 0.1}s`;
        });
        
        // Add animation to cards
        const cards = document.querySelectorAll('.card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.animation = `fadeInUp 0.5s ease forwards ${0.4 + index * 0.1}s`;
        });
    });
</script>
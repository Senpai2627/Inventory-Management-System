<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['otp_verified'] !== true) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/inventory_functions.php';

requirePermission('view_dashboard');

// Get dashboard statistics
$conn = getDBConnection();
$stats = [
    'total_items' => $conn->query("SELECT COUNT(*) as count FROM inventory_items")->fetch_assoc()['count'],
    'total_value' => $conn->query("SELECT SUM(quantity * price) as total FROM inventory_items")->fetch_assoc()['total'],
    'low_stock' => $conn->query("SELECT COUNT(*) as count FROM inventory_items WHERE quantity <= reorder_level")->fetch_assoc()['count'],
    'recent_transactions' => $conn->query("SELECT t.*, i.name as item_name, u.username as user_name 
                                          FROM inventory_transactions t 
                                          JOIN inventory_items i ON t.item_id = i.id 
                                          JOIN users u ON t.user_id = u.id 
                                          ORDER BY t.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC)
];
$conn->close();

$lowStockItems = getLowStockItems();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard | Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #1a73e8;
            --dark-blue: #0d47a1;
            --light-blue: #e8f0fe;
            --hover-blue: #4285f4;
            --white: #ffffff;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
            color: var(--white);
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path fill="rgba(255,255,255,0.05)" d="M0,0 L100,0 L100,100 L0,100 Z" /></svg>');
            background-size: cover;
            animation: wave 15s linear infinite;
        }
        
        .dashboard-header h2 {
            font-weight: 700;
            margin: 0;
            position: relative;
        }
        
        .widget {
            background: var(--white);
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .widget:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .widget::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-blue), var(--dark-blue), var(--primary-blue));
            background-size: 200% 100%;
            animation: gradientFlow 3s linear infinite;
        }
        
        .widget-icon {
            font-size: 2rem;
            color: var(--primary-blue);
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .widget:hover .widget-icon {
            transform: scale(1.1);
        }
        
        .widget-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--dark-blue);
            margin-bottom: 0.5rem;
        }
        
        .widget-title {
            font-size: 1rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
            color: var(--white);
            border-radius: 12px 12px 0 0 !important;
            padding: 1rem 1.5rem;
            font-weight: 600;
            position: relative;
        }
        
        .card-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: rgba(255,255,255,0.3);
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table-hover tbody tr {
            transition: all 0.2s ease;
        }
        
        .table-hover tbody tr:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .table-danger {
            background-color: rgba(220, 53, 69, 0.1) !important;
        }
        
        .table-warning {
            background-color: rgba(255, 193, 7, 0.1) !important;
        }
        
        /* Animations */
        @keyframes wave {
            0% { background-position: 0 0; }
            100% { background-position: 100% 0; }
        }
        
        @keyframes gradientFlow {
            0% { background-position: 0% 50%; }
            100% { background-position: 200% 50%; }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Widget animations */
        .widget:nth-child(1) { animation: fadeIn 0.6s ease; }
        .widget:nth-child(2) { animation: fadeIn 0.8s ease; }
        .widget:nth-child(3) { animation: fadeIn 1s ease; }
        
        /* Card animations */
        .card:nth-child(1) { animation: fadeIn 0.8s ease; }
        .card:nth-child(2) { animation: fadeIn 1s ease; }
        
        /* Responsive */
        @media (max-width: 768px) {
            .widget {
                margin-bottom: 1.5rem;
            }
            
            .widget-value {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="dashboard-header">
        <div class="container">
            <h2><i class="fas fa-tachometer-alt me-2"></i>Manager Dashboard</h2>
        </div>
    </div>
    
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="widget">
                    <div class="widget-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="widget-value"><?php echo $stats['total_items']; ?></div>
                    <div class="widget-title">Total Items</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="widget">
                    <div class="widget-icon">
                        <i class="fas fa-peso-sign"></i>
                    </div>
                    <div class="widget-value">$<?php echo number_format($stats['total_value'], 2); ?></div>
                    <div class="widget-title">Total Value</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="widget">
                    <div class="widget-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="widget-value"><?php echo $stats['low_stock']; ?></div>
                    <div class="widget-title">Low Stock Items</div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-exclamation-circle me-2"></i>Low Stock Items</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($lowStockItems)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Item</th>
                                            <th>Category</th>
                                            <th>Quantity</th>
                                            <th>Reorder Level</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($lowStockItems as $item): ?>
                                            <tr class="<?php echo $item['quantity'] <= ($item['reorder_level'] / 2) ? 'table-danger' : 'table-warning'; ?>">
                                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                <td><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></td>
                                                <td><?php echo $item['quantity']; ?></td>
                                                <td><?php echo $item['reorder_level']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>No low stock items
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Recent Transactions</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($stats['recent_transactions'])): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Item</th>
                                            <th>Type</th>
                                            <th>Qty</th>
                                            <th>User</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stats['recent_transactions'] as $txn): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($txn['item_name']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo $txn['transaction_type'] === 'in' ? 'bg-success' : 'bg-primary'; ?>">
                                                        <?php echo ucfirst($txn['transaction_type']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $txn['quantity']; ?></td>
                                                <td><?php echo htmlspecialchars($txn['user_name']); ?></td>
                                                <td><?php echo date('M j, H:i', strtotime($txn['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No recent transactions
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add hover effect to widgets
        document.querySelectorAll('.widget').forEach(widget => {
            widget.addEventListener('mouseenter', function() {
                this.querySelector('.widget-icon').style.color = 'var(--hover-blue)';
            });
            
            widget.addEventListener('mouseleave', function() {
                this.querySelector('.widget-icon').style.color = 'var(--primary-blue)';
            });
        });
        
        // Add animation to table rows when they come into view
        const animateTableRows = () => {
            document.querySelectorAll('tbody tr').forEach((row, index) => {
                const rowPosition = row.getBoundingClientRect().top;
                const screenPosition = window.innerHeight / 1.2;
                
                if (rowPosition < screenPosition) {
                    row.style.animation = `fadeIn 0.5s ease forwards ${index * 0.1}s`;
                }
            });
        };
        
        // Run on load and scroll
        window.addEventListener('load', animateTableRows);
        window.addEventListener('scroll', animateTableRows);
    </script>
</body>
</html>
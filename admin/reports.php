<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/inventory_functions.php';

requirePermission('generate_reports');

$reportType = $_GET['report'] ?? 'summary';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

$reportData = generateInventoryReport($reportType, [
    'start_date' => $startDate,
    'end_date' => $endDate
]);
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<style>
    :root {
        --primary-blue: #1a73e8;
        --white: #ffffff;
        --light-grey: #f8f9fa;
        --border-color: #e0e0e0;
    }
    
    .report-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
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
    
    .card-body {
        padding: 1.5rem;
    }
    
    .form-label {
        font-weight: 500;
        color: #333;
    }
    
    .form-select, .form-control {
        border: 1px solid var(--border-color);
        border-radius: 6px;
        padding: 0.75rem;
    }
    
    .form-select:focus, .form-control:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.1);
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
    
    .btn-success {
        background-color: #28a745;
        border: none;
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
    }
    
    .table-bordered td {
        border: 1px solid var(--border-color);
    }
    
    .text-muted {
        color: #6c757d;
    }
    
    @media (max-width: 768px) {
        .report-container {
            padding: 1rem;
        }
    }
</style>

<div class="report-container">
    <h2 class="mb-4" style="color: var(--primary-blue);">Inventory Reports</h2>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5>Report Options</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="reports.php" class="row g-3">
                <div class="col-md-4">
                    <label for="report" class="form-label">Report Type</label>
                    <select class="form-select" id="report" name="report">
                        <option value="summary" <?php echo $reportType == 'summary' ? 'selected' : ''; ?>>Summary Report</option>
                        <option value="category" <?php echo $reportType == 'category' ? 'selected' : ''; ?>>Category Report</option>
                        <option value="transactions" <?php echo $reportType == 'transactions' ? 'selected' : ''; ?>>Transactions Report</option>
                    </select>
                </div>
                
                <?php if ($reportType == 'transactions'): ?>
                    <div class="col-md-4">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                    </div>
                <?php endif; ?>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Generate Report</button>
                    <a href="export_report.php?report=<?php echo $reportType; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" 
                       class="btn btn-success" <?php echo !hasPermission('export_data') ? 'disabled' : ''; ?>>
                        Export to CSV
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5>
                <?php 
                echo $reportType == 'summary' ? 'Inventory Summary Report' : 
                     ($reportType == 'category' ? 'Inventory by Category Report' : 'Transactions Report');
                ?>
                
                <?php if ($reportType == 'transactions'): ?>
                    <small class="text-muted">(<?php echo date('M j, Y', strtotime($startDate)); ?> to <?php echo date('M j, Y', strtotime($endDate)); ?>)</small>
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body">
            <?php if ($reportType == 'summary'): ?>
                <div class="row">
                    <div class="col-md-6">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Total Items</th>
                                    <td><?php echo $reportData['total_items']; ?></td>
                                </tr>
                                <tr>
                                    <th>Total Quantity</th>
                                    <td><?php echo $reportData['total_quantity']; ?></td>
                                </tr>
                                <tr>
                                    <th>Total Inventory Value</th>
                                    <td>$<?php echo number_format($reportData['total_value'], 2); ?></td>
                                </tr>
                                <tr>
                                    <th>Low Stock Items</th>
                                    <td><?php echo $reportData['low_stock_items']; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($reportType == 'category'): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Item Count</th>
                                <th>Total Quantity</th>
                                <th>Total Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData as $category): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category['category']); ?></td>
                                    <td><?php echo $category['item_count']; ?></td>
                                    <td><?php echo $category['total_quantity']; ?></td>
                                    <td>$<?php echo number_format($category['total_value'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
            <?php elseif ($reportType == 'transactions'): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Transaction Type</th>
                                <th>Count</th>
                                <th>Total Quantity</th>
                                <th>Total Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData as $txn): ?>
                                <tr>
                                    <td><?php echo date('M j, Y', strtotime($txn['transaction_date'])); ?></td>
                                    <td><?php echo ucfirst($txn['transaction_type']); ?></td>
                                    <td><?php echo $txn['transaction_count']; ?></td>
                                    <td><?php echo $txn['total_quantity']; ?></td>
                                    <td>$<?php echo number_format($txn['total_value'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
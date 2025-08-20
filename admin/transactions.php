<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_operations.php';

requirePermission('view_transactions');

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10;

$transactionsData = getTransactions($page, $perPage);
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
    
    .transactions-container {
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
    
    .badge {
        border-radius: 6px;
        font-weight: 500;
        padding: 0.35em 0.65em;
    }
    
    .badge-purchase {
        background-color: rgba(25, 135, 84, 0.1);
        color: #198754;
    }
    
    .badge-sale {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }
    
    .badge-adjustment {
        background-color: rgba(255, 193, 7, 0.1);
        color: #ffc107;
    }
    
    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 20px;
        gap: 8px;
    }
    
    .pagination a, .pagination span {
        padding: 8px 12px;
        border: 1px solid var(--border-color);
        border-radius: 4px;
        color: var(--primary-blue);
        text-decoration: none;
    }
    
    .pagination a:hover {
        background-color: var(--light-grey);
    }
    
    .pagination .current {
        background-color: var(--primary-blue);
        color: white;
        border-color: var(--primary-blue);
    }
    
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #6b7280;
    }
    
    .empty-state-icon {
        font-size: 3rem;
        color: #d1d5db;
        margin-bottom: 1rem;
    }
    
    @media (max-width: 768px) {
        .transactions-container {
            padding: 1rem;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
    }
</style>

<div class="transactions-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 style="color: var(--primary-blue);">
            <i class="fas fa-exchange-alt me-2"></i>Transaction History
        </h2>
        <div class="text-muted">
            <?php echo $transactionsData['total']; ?> <?php echo $transactionsData['total'] === 1 ? 'Transaction' : 'Transactions'; ?>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>All Transactions
                </h5>
                <?php if (hasPermission('process_transactions')): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                        <i class="fas fa-plus me-1"></i> New Transaction
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($transactionsData['transactions'])): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-exchange-alt"></i>
                    </div>
                    <h3>No Transactions Found</h3>
                    <p>Get started by creating your first inventory transaction</p>
                    <?php if (hasPermission('process_transactions')): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTransactionModal">
                            Create Transaction
                        </button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Item</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>User</th>
                                <th>Notes</th>
                                <th>Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactionsData['transactions'] as $txn): ?>
                                <tr>
                                    <td><?php echo date('M j, Y H:i', strtotime($txn['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($txn['item_name']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $txn['transaction_type']; ?>">
                                            <?php echo ucfirst($txn['transaction_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $txn['quantity']; ?></td>
                                    <td><?php echo htmlspecialchars($txn['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars($txn['notes']); ?></td>
                                    <td><?php echo htmlspecialchars($txn['reference_number']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($transactionsData['pages'] > 1): ?>
                    <div class="p-3 border-top">
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>">
                                    <i class="fas fa-chevron-left me-1"></i> Previous
                                </a>
                            <?php endif; ?>
                            
                            <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($transactionsData['pages'], $page + 2);
                            
                            if ($startPage > 1): ?>
                                <a href="?page=1">1</a>
                                <?php if ($startPage > 2): ?>
                                    <span>...</span>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($endPage < $transactionsData['pages']): ?>
                                <?php if ($endPage < $transactionsData['pages'] - 1): ?>
                                    <span>...</span>
                                <?php endif; ?>
                                <a href="?page=<?php echo $transactionsData['pages']; ?>">
                                    <?php echo $transactionsData['pages']; ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($page < $transactionsData['pages']): ?>
                                <a href="?page=<?php echo $page + 1; ?>">
                                    Next <i class="fas fa-chevron-right ms-1"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Transaction Modal -->
<div class="modal fade" id="addTransactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle me-2"></i>New Inventory Transaction
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="process_transaction.php" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="transaction_type" class="form-label">Transaction Type</label>
                        <select class="form-select" id="transaction_type" name="transaction_type" required>
                            <option value="">Select Type</option>
                            <option value="purchase">Purchase/Stock In</option>
                            <option value="sale">Sale/Stock Out</option>
                            <option value="adjustment">Adjustment</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="item_id" class="form-label">Item</label>
                        <select class="form-select" id="item_id" name="item_id" required>
                            <option value="">Select Item</option>
                            <?php 
                            $items = getInventoryItems('', null, 1, 1000)['items'];
                            foreach ($items as $item): ?>
                                <option value="<?php echo $item['id']; ?>">
                                    <?php echo htmlspecialchars($item['name']); ?> (Current: <?php echo $item['quantity']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reference_number" class="form-label">Reference Number</label>
                        <input type="text" class="form-control" id="reference_number" name="reference_number">
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Close
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check-circle me-1"></i> Process Transaction
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
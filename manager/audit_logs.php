<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requirePermission('view_audit_logs');

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get search/filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$user = isset($_GET['user']) ? intval($_GET['user']) : 0;
$dateFrom = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$logType = isset($_GET['log_type']) ? trim($_GET['log_type']) : 'audit'; // 'audit' or 'page_views'

$conn = getDBConnection();

// Build base queries based on log type
if ($logType === 'page_views') {
    $query = "SELECT p.*, u.username 
              FROM page_views p 
              LEFT JOIN users u ON p.user_id = u.id";
    $countQuery = "SELECT COUNT(*) as total FROM page_views p";
    $defaultOrder = "p.created_at DESC";
} else {
    $query = "SELECT a.*, u.username 
              FROM audit_logs a 
              LEFT JOIN users u ON a.user_id = u.id";
    $countQuery = "SELECT COUNT(*) as total FROM audit_logs a";
    $defaultOrder = "a.created_at DESC";
}

$where = [];
$params = [];
$types = '';

// Add filters
if (!empty($search)) {
    if ($logType === 'page_views') {
        $where[] = "(p.page LIKE ? OR p.user_agent LIKE ? OR p.referrer LIKE ?)";
        $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
        $types .= 'sss';
    } else {
        $where[] = "(a.action LIKE ? OR a.table_name LIKE ? OR a.old_value LIKE ? OR a.new_value LIKE ?)";
        $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
        $types .= 'ssss';
    }
}

if (!empty($action) && $logType === 'audit') {
    $where[] = "a.action = ?";
    $params[] = $action;
    $types .= 's';
}

if ($user > 0) {
    $where[] = ($logType === 'page_views' ? "p.user_id = ?" : "a.user_id = ?");
    $params[] = $user;
    $types .= 'i';
}

if (!empty($dateFrom)) {
    $where[] = ($logType === 'page_views' ? "p.created_at >= ?" : "a.created_at >= ?");
    $params[] = $dateFrom;
    $types .= 's';
}

if (!empty($dateTo)) {
    $where[] = ($logType === 'page_views' ? "p.created_at <= ?" : "a.created_at <= ?");
    $params[] = $dateTo . ' 23:59:59';
    $types .= 's';
}

// Get total count
if (!empty($where)) {
    $countQuery .= " WHERE " . implode(" AND ", $where);
}

$stmt = $conn->prepare($countQuery);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totalResult = $stmt->get_result();
$total = $totalResult->fetch_assoc()['total'];
$pages = ceil($total / $perPage);
$stmt->close();

// Get logs with pagination and filters
if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}
$query .= " ORDER BY $defaultOrder LIMIT ? OFFSET ?";
$types .= 'ii';
$params = array_merge($params, [$perPage, $offset]);

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$logs = $result->fetch_all(MYSQLI_ASSOC);

// Get distinct actions for filter dropdown
$actions = $logType === 'audit' ? $conn->query("SELECT DISTINCT action FROM audit_logs ORDER BY action")->fetch_all(MYSQLI_ASSOC) : [];

// Get users for filter dropdown
$users = $conn->query("SELECT id, username FROM users ORDER BY username")->fetch_all(MYSQLI_ASSOC);

// Get distinct pages for page views filter
$pagesFilter = $logType === 'page_views' ? $conn->query("SELECT DISTINCT page FROM page_views ORDER BY page")->fetch_all(MYSQLI_ASSOC) : [];

$stmt->close();
$conn->close();
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
    
    .audit-logs-container {
        background-color: #f8f9fa;
        min-height: calc(100vh - 120px);
    }
    
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: var(--shadow);
        overflow: hidden;
    }
    
    .card-header {
        background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
        color: white;
        padding: 1.25rem 1.5rem;
        border-bottom: none;
    }
    
    .card-header h5 {
        font-weight: 600;
        margin: 0;
    }
    
    .filter-card {
        background-color: white;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow);
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
        position: sticky;
        top: 0;
    }
    
    .table th, .table td {
        padding: 1rem;
        vertical-align: middle;
        border-top: 1px solid #f0f0f0;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(26, 115, 232, 0.05);
    }
    
    .action-badge {
        padding: 0.35em 0.65em;
        font-weight: 500;
        border-radius: 8px;
        font-size: 0.75rem;
        text-transform: uppercase;
    }
    
    .badge-create {
        background-color: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }
    
    .badge-update {
        background-color: rgba(255, 193, 7, 0.1);
        color: #ffc107;
    }
    
    .badge-delete {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }
    
    .badge-login {
        background-color: rgba(0, 123, 255, 0.1);
        color: #007bff;
    }
    
    .badge-system {
        background-color: rgba(108, 117, 125, 0.1);
        color: #6c757d;
    }
    
    .badge-page-view {
        background-color: rgba(111, 66, 193, 0.1);
        color: #6f42c1;
    }
    
    .pagination .page-item.active .page-link {
        background-color: var(--primary-blue);
        border-color: var(--primary-blue);
    }
    
    .pagination .page-link {
        color: var(--primary-blue);
    }
    
    .view-changes-btn {
        border-radius: 20px;
        font-size: 0.75rem;
        padding: 0.25rem 0.75rem;
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
    
    @keyframes float {
        0% { transform: translate(0, 0) rotate(0deg); }
        25% { transform: translate(50px, -30px) rotate(5deg); }
        50% { transform: translate(100px, 0) rotate(0deg); }
        75% { transform: translate(50px, 30px) rotate(-5deg); }
        100% { transform: translate(0, 0) rotate(0deg); }
    }
    
    .timestamp-col {
        white-space: nowrap;
    }
    
    .user-col {
        white-space: nowrap;
    }
    
    .ip-col {
        white-space: nowrap;
    }
    
    pre {
        background-color: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
        max-height: 300px;
        overflow-y: auto;
    }
    
    .diff-modal .modal-content {
        border-radius: 12px;
    }
    
    .diff-modal .modal-header {
        background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
        color: white;
        border-bottom: none;
    }
    
    .diff-modal .modal-title {
        font-weight: 600;
    }
    
    .diff-modal .modal-body {
        padding: 0;
    }
    
    .diff-modal .col-md-6 {
        padding: 1rem;
    }
    
    .diff-modal h6 {
        color: var(--dark-blue);
        font-weight: 600;
        margin-bottom: 1rem;
    }
    
    .log-type-switcher {
        background-color: white;
        border-radius: 50px;
        padding: 0.5rem;
        display: inline-flex;
        box-shadow: var(--shadow);
    }
    
    .log-type-btn {
        border: none;
        background: none;
        padding: 0.5rem 1.5rem;
        border-radius: 50px;
        font-weight: 500;
        transition: all 0.3s;
    }
    
    .log-type-btn.active {
        background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
        color: white;
        box-shadow: var(--glow);
    }
    
    .user-agent-col {
        max-width: 200px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    @media (max-width: 768px) {
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .table td, .table th {
            white-space: nowrap;
        }
        
        .log-type-switcher {
            width: 100%;
            justify-content: center;
            margin-bottom: 1rem;
        }
    }
</style>

<div class="floating-shapes">
    <div class="shape"></div>
    <div class="shape"></div>
</div>

<div class="audit-logs-container">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="fas fa-clipboard-list me-2"></i><?php echo $logType === 'page_views' ? 'Page View Logs' : 'Audit Logs'; ?>
            </h2>
            <div class="text-muted">
                Showing <?php echo ($offset + 1); ?>-<?php echo min($offset + $perPage, $total); ?> of <?php echo $total; ?> records
            </div>
        </div>
        
        <!-- Log Type Switcher -->
        <div class="d-flex justify-content-center mb-4">
            <div class="log-type-switcher">
                <button class="log-type-btn <?php echo $logType === 'audit' ? 'active' : ''; ?>" 
                        onclick="window.location.href='?log_type=audit<?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['log_type' => ''])) : ''; ?>'">
                    <i class="fas fa-history me-2"></i>Audit Logs
                </button>
                <button class="log-type-btn <?php echo $logType === 'page_views' ? 'active' : ''; ?>" 
                        onclick="window.location.href='?log_type=page_views<?php echo !empty($_GET) ? '&' . http_build_query(array_diff_key($_GET, ['log_type' => ''])) : ''; ?>'">
                    <i class="fas fa-eye me-2"></i>Page Views
                </button>
            </div>
        </div>
        
        <!-- Filter Card -->
        <div class="filter-card">
            <form method="get" action="">
                <input type="hidden" name="log_type" value="<?php echo $logType; ?>">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="<?php echo $logType === 'page_views' ? 'Page, user agent...' : 'Action, table, details...'; ?>" 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <?php if ($logType === 'audit'): ?>
                        <div class="col-md-2">
                            <label for="action" class="form-label">Action</label>
                            <select class="form-select" id="action" name="action">
                                <option value="">All Actions</option>
                                <?php foreach ($actions as $act): ?>
                                    <option value="<?php echo htmlspecialchars($act['action']); ?>" 
                                        <?php echo $action === $act['action'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($act['action']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <div class="col-md-2">
                            <label for="page_filter" class="form-label">Page</label>
                            <select class="form-select" id="page_filter" name="page_filter">
                                <option value="">All Pages</option>
                                <?php foreach ($pagesFilter as $pg): ?>
                                    <option value="<?php echo htmlspecialchars($pg['page']); ?>" 
                                        <?php echo isset($_GET['page_filter']) && $_GET['page_filter'] === $pg['page'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($pg['page']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>
                    
                    <div class="col-md-2">
                        <label for="user" class="form-label">User</label>
                        <select class="form-select" id="user" name="user">
                            <option value="0">All Users</option>
                            <?php foreach ($users as $usr): ?>
                                <option value="<?php echo $usr['id']; ?>" 
                                    <?php echo $user == $usr['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($usr['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="date_from" class="form-label">From Date</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" 
                               value="<?php echo htmlspecialchars($dateFrom); ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="date_to" class="form-label">To Date</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" 
                               value="<?php echo htmlspecialchars($dateTo); ?>">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="<?php echo $logType === 'page_views' ? 'fas fa-eye' : 'fas fa-history'; ?> me-2"></i>
                        <?php echo $logType === 'page_views' ? 'Page View Logs' : 'System Activity Log'; ?>
                    </h5>
                    <?php if (!empty($search) || !empty($action) || $user > 0 || !empty($dateFrom) || !empty($dateTo)): ?>
                        <a href="audit_logs.php?log_type=<?php echo $logType; ?>" class="btn btn-sm btn-outline-light">
                            <i class="fas fa-times me-1"></i> Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="timestamp-col">Timestamp</th>
                                <th class="user-col">User</th>
                                <th><?php echo $logType === 'page_views' ? 'Page' : 'Action'; ?></th>
                                <th><?php echo $logType === 'page_views' ? 'Details' : 'Target'; ?></th>
                                <th class="ip-col">IP Address</th>
                                <?php if ($logType === 'page_views'): ?>
                                    <th class="user-agent-col">User Agent</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="timestamp-col">
                                        <?php echo date('M j, Y H:i', strtotime($log['created_at'])); ?>
                                    </td>
                                    <td class="user-col">
                                        <?php if ($log['username']): ?>
                                            <span class="badge bg-primary bg-opacity-10 text-primary">
                                                <?php echo htmlspecialchars($log['username']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary"><?php echo $logType === 'page_views' ? 'Guest' : 'System'; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($logType === 'page_views'): ?>
                                            <span class="action-badge badge-page-view">
                                                <?php echo htmlspecialchars($log['page']); ?>
                                            </span>
                                        <?php else: ?>
                                            <?php 
                                                $badgeClass = 'badge-system';
                                                if (strpos($log['action'], 'create') !== false) $badgeClass = 'badge-create';
                                                elseif (strpos($log['action'], 'update') !== false) $badgeClass = 'badge-update';
                                                elseif (strpos($log['action'], 'delete') !== false) $badgeClass = 'badge-delete';
                                                elseif (strpos($log['action'], 'login') !== false) $badgeClass = 'badge-login';
                                            ?>
                                            <span class="action-badge <?php echo $badgeClass; ?>">
                                                <?php echo htmlspecialchars($log['action']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($logType === 'page_views'): ?>
                                            <?php if ($log['referrer']): ?>
                                                <small class="text-muted">From: <?php echo htmlspecialchars($log['referrer']); ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <?php if ($log['table_name']): ?>
                                                <span class="fw-semibold"><?php echo htmlspecialchars($log['table_name']); ?></span>
                                                <?php if ($log['record_id']): ?>
                                                    <span class="text-muted">#<?php echo $log['record_id']; ?></span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            
                                            <?php if ($log['old_value'] && $log['new_value']): ?>
                                                <button class="btn btn-sm btn-outline-primary view-changes-btn ms-2" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#diffModal<?php echo $log['id']; ?>">
                                                    <i class="fas fa-code-compare me-1"></i> View Changes
                                                </button>
                                                
                                                <!-- Diff Modal -->
                                                <div class="modal fade diff-modal" id="diffModal<?php echo $log['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">
                                                                    <i class="fas fa-code-compare me-2"></i>
                                                                    Changes Details
                                                                </h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row">
                                                                    <div class="col-md-6 border-end">
                                                                        <h6>Old Value</h6>
                                                                        <pre><?php echo htmlspecialchars(json_encode(json_decode($log['old_value']), JSON_PRETTY_PRINT)); ?></pre>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <h6>New Value</h6>
                                                                        <pre><?php echo htmlspecialchars(json_encode(json_decode($log['new_value']), JSON_PRETTY_PRINT)); ?></pre>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                    <i class="fas fa-times me-1"></i> Close
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="ip-col">
                                        <span class="badge bg-light text-dark"><?php echo htmlspecialchars($log['ip_address']); ?></span>
                                    </td>
                                    <?php if ($logType === 'page_views'): ?>
                                        <td class="user-agent-col" title="<?php echo htmlspecialchars($log['user_agent']); ?>">
                                            <?php echo htmlspecialchars($log['user_agent']); ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($pages > 1): ?>
                    <div class="p-3 border-top">
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center mb-0">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                            <i class="fas fa-chevron-left"></i> Previous
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php 
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($pages, $page + 2);
                                    
                                    if ($startPage > 1) {
                                        echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => 1])) . '">1</a></li>';
                                        if ($startPage > 2) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                    }
                                    
                                    for ($i = $startPage; $i <= $endPage; $i++): 
                                ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php 
                                    endfor;
                                    
                                    if ($endPage < $pages) {
                                        if ($endPage < $pages - 1) {
                                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                                        }
                                        echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['page' => $pages])) . '">' . $pages . '</a></li>';
                                    }
                                ?>
                                
                                <?php if ($page < $pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
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

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
    // Initialize tooltips
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Make table rows clickable to show modal
        document.querySelectorAll('tr[data-bs-target]').forEach(row => {
            row.style.cursor = 'pointer';
            row.addEventListener('click', function() {
                const modalId = this.getAttribute('data-bs-target');
                const modal = new bootstrap.Modal(document.querySelector(modalId));
                modal.show();
            });
        });
        
        // Add tooltips to truncated user agent cells
        document.querySelectorAll('.user-agent-col').forEach(cell => {
            if (cell.scrollWidth > cell.clientWidth) {
                cell.setAttribute('data-bs-toggle', 'tooltip');
                cell.setAttribute('title', cell.textContent);
                new bootstrap.Tooltip(cell);
            }
        });
    });
</script>
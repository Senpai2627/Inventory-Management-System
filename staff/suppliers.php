<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_operations.php';

requirePermission('manage_suppliers');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_supplier'])) {
        $name = sanitizeInput($_POST['name']);
        $contact = sanitizeInput($_POST['contact_person']);
        $email = sanitizeInput($_POST['email']);
        $phone = sanitizeInput($_POST['phone']);
        $address = sanitizeInput($_POST['address']);
        
        $conn = getDBConnection();
        $stmt = $conn->prepare("INSERT INTO suppliers (name, contact_person, email, phone, address) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $contact, $email, $phone, $address);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Supplier added successfully";
        } else {
            $_SESSION['error'] = "Error adding supplier";
        }
        
        header("Location: suppliers.php");
        exit();
    }
    
    if (isset($_POST['update_supplier'])) {
        $id = intval($_POST['id']);
        $name = sanitizeInput($_POST['name']);
        $contact = sanitizeInput($_POST['contact_person']);
        $email = sanitizeInput($_POST['email']);
        $phone = sanitizeInput($_POST['phone']);
        $address = sanitizeInput($_POST['address']);
        
        $conn = getDBConnection();
        $stmt = $conn->prepare("UPDATE suppliers SET name = ?, contact_person = ?, email = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $name, $contact, $email, $phone, $address, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Supplier updated successfully";
        } else {
            $_SESSION['error'] = "Error updating supplier";
        }
        
        header("Location: suppliers.php");
        exit();
    }
}

// Handle deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Check if supplier is used in any items
    $conn = getDBConnection();
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM inventory_items WHERE supplier_id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $count = $result->fetch_assoc()['count'];
    
    if ($count > 0) {
        $_SESSION['error'] = "Cannot delete supplier - it is being used by inventory items";
    } else {
        $deleteStmt = $conn->prepare("DELETE FROM suppliers WHERE id = ?");
        $deleteStmt->bind_param("i", $id);
        
        if ($deleteStmt->execute()) {
            $_SESSION['success'] = "Supplier deleted successfully";
        } else {
            $_SESSION['error'] = "Error deleting supplier";
        }
    }
    
    header("Location: suppliers.php");
    exit();
}

// Get all suppliers
$conn = getDBConnection();
$result = $conn->query("SELECT * FROM suppliers ORDER BY name");
$suppliers = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<style>
    :root {
        --primary-blue: #1a73e8;
        --light-blue: #e8f0fe;
        --dark-blue: #0d47a1;
        --white: #ffffff;
        --light-gray: #f8f9fa;
    }
    
    body {
        background-color: var(--light-gray);
    }
    
    .supplier-card {
        border: none;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        background: var(--white);
    }
    
    .supplier-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
    }
    
    .card-header {
        background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
        color: white;
        border-radius: 10px 10px 0 0 !important;
    }
    
    .btn-primary {
        background-color: var(--primary-blue);
        border-color: var(--primary-blue);
    }
    
    .btn-primary:hover {
        background-color: var(--dark-blue);
        border-color: var(--dark-blue);
    }
    
    .btn-outline-primary {
        color: var(--primary-blue);
        border-color: var(--primary-blue);
    }
    
    .btn-outline-primary:hover {
        background-color: var(--primary-blue);
        border-color: var(--primary-blue);
    }
    
    .table th {
        background-color: var(--light-blue);
        color: var(--dark-blue);
    }
    
    .modal-header {
        background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
        color: white;
    }
    
    .supplier-badge {
        background-color: var(--light-blue);
        color: var(--primary-blue);
        padding: 8px 12px;
        border-radius: 20px;
        font-weight: 500;
    }
    
    .action-btn {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }
    
    .search-box {
        position: relative;
        max-width: 300px;
    }
    
    .search-box input {
        padding-left: 40px;
        border-radius: 20px;
        border: 1px solid #dfe1e5;
    }
    
    .search-box i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--primary-blue);
    }
    
    .supplier-details {
        background-color: var(--light-blue);
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
    }
    
    .supplier-details-label {
        font-weight: 600;
        color: var(--dark-blue);
    }
</style>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="fw-bold mb-0 text-primary">Supplier Management</h2>
                <button class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                    <i class="fas fa-plus me-2"></i> Add New Supplier
                </button>
            </div>
            <hr class="mt-2" style="border-top: 2px solid var(--primary-blue);">
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-3">
        <div class="col-md-6">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="supplierSearch" class="form-control" placeholder="Search suppliers...">
            </div>
        </div>
        <div class="col-md-6 text-end">
            <span class="supplier-badge">
                <i class="fas fa-users me-2"></i> <?php echo count($suppliers); ?> Suppliers
            </span>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card supplier-card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white"><i class="fas fa-list me-2"></i> Supplier List</h5>
                        <div>
                            <button class="btn btn-sm btn-light me-2" id="printBtn">
                                <i class="fas fa-print me-1"></i> Print
                            </button>
                            <button class="btn btn-sm btn-light" id="exportBtn">
                                <i class="fas fa-file-export me-1"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($suppliers)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="suppliersTable">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <tr class="supplier-row" data-supplier-id="<?php echo $supplier['id']; ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar me-3">
                                                        <span class="avatar-initial rounded-circle bg-primary text-white">
                                                            <?php echo strtoupper(substr($supplier['name'], 0, 1)); ?>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($supplier['name']); ?></h6>
                                                        <small class="text-muted">ID: SUP-<?php echo str_pad($supplier['id'], 4, '0', STR_PAD_LEFT); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($supplier['contact_person']); ?></td>
                                            <td>
                                                <?php if (!empty($supplier['email'])): ?>
                                                    <a href="mailto:<?php echo htmlspecialchars($supplier['email']); ?>" class="text-primary">
                                                        <?php echo htmlspecialchars($supplier['email']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($supplier['phone'])): ?>
                                                    <a href="tel:<?php echo htmlspecialchars($supplier['phone']); ?>" class="text-primary">
                                                        <?php echo htmlspecialchars($supplier['phone']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-flex justify-content-end gap-2">
                                                    <button class="btn btn-sm btn-outline-primary rounded-pill action-btn" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#viewSupplierModal<?php echo $supplier['id']; ?>"
                                                            title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-primary rounded-pill action-btn" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editSupplierModal<?php echo $supplier['id']; ?>"
                                                            title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="suppliers.php?delete=<?php echo $supplier['id']; ?>" 
                                                       class="btn btn-sm btn-outline-danger rounded-pill action-btn delete-btn"
                                                       title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-box-open fa-4x text-muted mb-4"></i>
                            <h4 class="text-muted">No Suppliers Found</h4>
                            <p class="text-muted">Get started by adding your first supplier</p>
                            <button class="btn btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                                <i class="fas fa-plus me-2"></i> Add Supplier
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1" aria-labelledby="addSupplierModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSupplierModalLabel"><i class="fas fa-plus-circle me-2"></i> Add New Supplier</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="suppliers.php" class="needs-validation" novalidate>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Supplier Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback">Please provide a supplier name.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="contact_person" class="form-label">Contact Person</label>
                            <input type="text" class="form-control" id="contact_person" name="contact_person">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_supplier" class="btn btn-primary rounded-pill">
                        <i class="fas fa-save me-2"></i> Save Supplier
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View/Edit Supplier Modals -->
<?php foreach ($suppliers as $supplier): ?>
    <!-- View Supplier Modal -->
    <div class="modal fade" id="viewSupplierModal<?php echo $supplier['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i> Supplier Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="supplier-details">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="supplier-details-label">Supplier ID</p>
                                <p>SUP-<?php echo str_pad($supplier['id'], 4, '0', STR_PAD_LEFT); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="supplier-details-label">Supplier Name</p>
                                <p><?php echo htmlspecialchars($supplier['name']); ?></p>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="supplier-details-label">Contact Person</p>
                                <p><?php echo !empty($supplier['contact_person']) ? htmlspecialchars($supplier['contact_person']) : 'N/A'; ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="supplier-details-label">Phone</p>
                                <p><?php echo !empty($supplier['phone']) ? htmlspecialchars($supplier['phone']) : 'N/A'; ?></p>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p class="supplier-details-label">Email</p>
                                <p><?php echo !empty($supplier['email']) ? htmlspecialchars($supplier['email']) : 'N/A'; ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="supplier-details-label">Date Added</p>
                                <p><?php echo date('M d, Y', strtotime($supplier['created_at'] ?? 'now')); ?></p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <p class="supplier-details-label">Address</p>
                            <p><?php echo !empty($supplier['address']) ? nl2br(htmlspecialchars($supplier['address'])) : 'N/A'; ?></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary rounded-pill" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Supplier Modal -->
    <div class="modal fade" id="editSupplierModal<?php echo $supplier['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i> Edit Supplier</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="suppliers.php">
                    <input type="hidden" name="id" value="<?php echo $supplier['id']; ?>">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name<?php echo $supplier['id']; ?>" class="form-label">Supplier Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name<?php echo $supplier['id']; ?>" 
                                       name="name" value="<?php echo htmlspecialchars($supplier['name']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="contact_person<?php echo $supplier['id']; ?>" class="form-label">Contact Person</label>
                                <input type="text" class="form-control" id="contact_person<?php echo $supplier['id']; ?>" 
                                       name="contact_person" value="<?php echo htmlspecialchars($supplier['contact_person']); ?>">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email<?php echo $supplier['id']; ?>" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email<?php echo $supplier['id']; ?>" 
                                       name="email" value="<?php echo htmlspecialchars($supplier['email']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="phone<?php echo $supplier['id']; ?>" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone<?php echo $supplier['id']; ?>" 
                                       name="phone" value="<?php echo htmlspecialchars($supplier['phone']); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="address<?php echo $supplier['id']; ?>" class="form-label">Address</label>
                            <textarea class="form-control" id="address<?php echo $supplier['id']; ?>" 
                                      name="address" rows="3"><?php echo htmlspecialchars($supplier['address']); ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_supplier" class="btn btn-primary rounded-pill">
                            <i class="fas fa-save me-2"></i> Update Supplier
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script>
$(document).ready(function() {
    // Search functionality
    $('#supplierSearch').on('keyup', function() {
        const value = $(this).val().toLowerCase();
        $('#suppliersTable tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
    
    // Delete confirmation
    $('.delete-btn').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this supplier?')) {
            e.preventDefault();
        }
    });
    
    // Form validation
    (function() {
        'use strict';
        var forms = document.querySelectorAll('.needs-validation');
        Array.prototype.slice.call(forms)
            .forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
    })();
    
    // Print button
    $('#printBtn').on('click', function() {
        window.print();
    });
    
    // Export button (placeholder functionality)
    $('#exportBtn').on('click', function() {
        alert('Export functionality will be implemented here');
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
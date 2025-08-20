<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_operations.php';

requirePermission('manage_categories');

// Handle category creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_category'])) {
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    
    if (empty($name)) {
        $_SESSION['error'] = "Category name is required";
    } else {
        $categoryId = addCategory($name, $description);
        
        if ($categoryId) {
            $_SESSION['success'] = "Category created successfully";
            header("Location: categories.php");
            exit();
        } else {
            $_SESSION['error'] = "Error creating category";
        }
    }
}

// Handle category update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $categoryId = intval($_POST['category_id']);
    $name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    
    if (empty($name)) {
        $_SESSION['error'] = "Category name is required";
    } else {
        if (updateCategory($categoryId, $name, $description)) {
            $_SESSION['success'] = "Category updated successfully";
            header("Location: categories.php");
            exit();
        } else {
            $_SESSION['error'] = "Error updating category";
        }
    }
}

// Handle category deletion
if (isset($_GET['delete'])) {
    $categoryId = intval($_GET['delete']);
    
    if (deleteCategory($categoryId)) {
        $_SESSION['success'] = "Category deleted successfully";
    } else {
        $_SESSION['error'] = "Cannot delete category - it is being used by inventory items";
    }
    
    header("Location: categories.php");
    exit();
}

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
    
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
    }
    
    .card {
        background-color: var(--white);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        margin-bottom: 2rem;
    }
    
    .card-header {
        background-color: var(--white);
        border-bottom: 1px solid var(--border-color);
        padding: 1.25rem 1.5rem;
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
    
    .form-control {
        border: 1px solid var(--border-color);
        border-radius: 6px;
        padding: 0.75rem;
    }
    
    .form-control:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.1);
    }
    
    .btn-primary {
        background-color: var(--primary-blue);
        border: none;
        border-radius: 6px;
        padding: 0.75rem 1.5rem;
        font-weight: 500;
    }
    
    .btn-outline-primary {
        border-color: var(--primary-blue);
        color: var(--primary-blue);
    }
    
    .btn-outline-danger {
        border-color: #dc3545;
        color: #dc3545;
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
    
    .table tr:last-child td {
        border-bottom: none;
    }
    
    .alert {
        border-radius: 6px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border-left: 4px solid #dc3545;
    }
    
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border-left: 4px solid #28a745;
    }
    
    .action-buttons {
        white-space: nowrap;
    }
    
    @media (max-width: 768px) {
        .container {
            padding: 1rem;
        }
        
        .card {
            margin-bottom: 1rem;
        }
    }
</style>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="fas fa-tags me-2" style="color: var(--primary-blue);"></i>
            Categories Management
        </h2>
        <div class="text-muted">
            <?php echo count($categories); ?> <?php echo count($categories) === 1 ? 'Category' : 'Categories'; ?>
        </div>
    </div>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php elseif (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i>Create New Category
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="categories.php">
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        <button type="submit" name="create_category" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Create Category
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Existing Categories
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($categories)): ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th class="action-buttons">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td>
                                                <span style="font-weight: 500;"><?php echo htmlspecialchars($category['name']); ?></span>
                                            </td>
                                            <td>
                                                <span style="color: #666;"><?php echo htmlspecialchars($category['description']); ?></span>
                                            </td>
                                            <td class="action-buttons">
                                                <div class="d-flex gap-2">
                                                    <button class="btn btn-sm btn-outline-primary edit-btn" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editCategoryModal"
                                                            data-id="<?php echo $category['id']; ?>"
                                                            data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                            data-description="<?php echo htmlspecialchars($category['description']); ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    
                                                    <a href="categories.php?delete=<?php echo $category['id']; ?>" 
                                                       class="btn btn-sm btn-outline-danger delete-btn">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info m-4">
                            <i class="fas fa-info-circle me-2"></i> No categories found. Create your first category.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>
                    Edit Category
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="categories.php">
                <input type="hidden" name="category_id" id="modal_category_id" value="">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="modal_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="modal_name" 
                               name="name" value="" required>
                    </div>
                    <div class="mb-3">
                        <label for="modal_description" class="form-label">Description</label>
                        <textarea class="form-control" id="modal_description" 
                                  name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Close
                    </button>
                    <button type="submit" name="update_category" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Confirm before deleting
        const deleteButtons = document.querySelectorAll('.delete-btn');
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this category?')) {
                    e.preventDefault();
                }
            });
        });
        
        // Handle edit button clicks
        const editButtons = document.querySelectorAll('.edit-btn');
        const editModal = document.getElementById('editCategoryModal');
        
        if (editModal) {
            const modal = new bootstrap.Modal(editModal);
            const modalCategoryId = document.getElementById('modal_category_id');
            const modalName = document.getElementById('modal_name');
            const modalDescription = document.getElementById('modal_description');
            
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const id = button.getAttribute('data-id');
                    const name = button.getAttribute('data-name');
                    const description = button.getAttribute('data-description');
                    
                    modalCategoryId.value = id;
                    modalName.value = name;
                    modalDescription.value = description;
                    
                    modal.show();
                });
            });
        }
    });
</script>
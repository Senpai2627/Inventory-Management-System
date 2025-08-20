<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requirePermission('view_roles');

$conn = getDBConnection();

// Handle role permission updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_permissions']) && hasPermission('edit_roles')) {
    $role = sanitizeInput($_POST['role']);
    
    // First, remove all existing permissions for this role
    $stmt = $conn->prepare("DELETE FROM role_permissions WHERE role = ?");
    $stmt->bind_param("s", $role);
    $stmt->execute();
    $stmt->close();
    
    // Then add the selected permissions
    if (!empty($_POST['permissions'])) {
        $stmt = $conn->prepare("INSERT INTO role_permissions (role, permission_id) VALUES (?, ?)");
        
        foreach ($_POST['permissions'] as $permissionId) {
            $permissionId = intval($permissionId);
            $stmt->bind_param("si", $role, $permissionId);
            $stmt->execute();
        }
        
        $stmt->close();
        
        logAction("Updated role permissions", "role_permissions", null, null, json_encode([
            'role' => $role,
            'permissions' => $_POST['permissions']
        ]));
        
        $_SESSION['success'] = "Permissions updated successfully";
    } else {
        $_SESSION['success'] = "All permissions removed for this role";
    }
    
    header("Location: roles.php");
    exit();
}

// Get all permissions and roles
$permissions = getAllPermissions();
$roles = ['admin', 'inventory_manager', 'staff'];
$rolePermissions = [];

foreach ($roles as $role) {
    $rolePermissions[$role] = getPermissionsForRole($role);
}

$conn->close();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container mt-4">
    <h2 class="mb-4">Role Permissions Management</h2>
    
    <div class="card">
        <div class="card-header">
            <h5>Manage Role Permissions</h5>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs" id="roleTabs" role="tablist">
                <?php foreach ($roles as $index => $role): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $index === 0 ? 'active' : ''; ?>" 
                                id="<?php echo $role; ?>-tab" data-bs-toggle="tab" 
                                data-bs-target="#<?php echo $role; ?>" type="button">
                            <?php echo ucfirst(str_replace('_', ' ', $role)); ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <div class="tab-content p-3 border border-top-0 rounded-bottom" id="roleTabsContent">
                <?php foreach ($roles as $index => $role): ?>
                    <div class="tab-pane fade <?php echo $index === 0 ? 'show active' : ''; ?>" 
                         id="<?php echo $role; ?>" role="tabpanel">
                        <form method="POST" action="roles.php">
                            <input type="hidden" name="role" value="<?php echo $role; ?>">
                            
                            <div class="row">
                                <?php 
                                $currentCategory = '';
                                foreach ($permissions as $permission): 
                                    if ($permission['category'] != $currentCategory) {
                                        if ($currentCategory != '') echo '</div></div>';
                                        $currentCategory = $permission['category'];
                                        echo '<div class="col-md-4 mb-4"><h6>' . 
                                             ucfirst(str_replace('_', ' ', $currentCategory)) . 
                                             '</h6><div class="ps-3">';
                                    }
                                    
                                    $isChecked = in_array($permission['id'], array_column($rolePermissions[$role], 'id'));
                                ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" 
                                               id="perm_<?php echo $role . '_' . $permission['id']; ?>" 
                                               name="permissions[]" value="<?php echo $permission['id']; ?>"
                                               <?php echo $isChecked ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="perm_<?php echo $role . '_' . $permission['id']; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $permission['name'])); ?>
                                        </label>
                                        <small class="d-block text-muted"><?php echo $permission['description']; ?></small>
                                    </div>
                                <?php endforeach; ?>
                                <?php if ($currentCategory != '') echo '</div></div>'; ?>
                            </div>
                            
                            <?php if (hasPermission('edit_roles')): ?>
                                <button type="submit" name="update_permissions" class="btn btn-primary mt-3">
                                    Update Permissions
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
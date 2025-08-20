<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requirePermission('view_users');

$conn = getDBConnection();

// Function to log user actions
// Function to log user actions
// Function to log user actions
function logUserAction($action, $userId, $details = null) {
    global $conn;
    
    $ip = getClientIP(); // Using the new IP detection function
    $location = ''; // You can add geolocation lookup here if needed
    
    // Handle null coalescing for older PHP versions
    $oldValue = isset($details['old']) ? $details['old'] : null;
    $newValue = isset($details['new']) ? $details['new'] : null;
    
    $query = "INSERT INTO audit_logs (user_id, action, table_name, record_id, old_value, new_value, ip_address, location) 
              VALUES (?, ?, 'users', ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isissss", 
        $_SESSION['user_id'], // The admin performing the action
        $action,
        $userId,
        $oldValue,
        $newValue,
        $ip,
        $location
    );
    $stmt->execute();
    $stmt->close();
}

// Handle user creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user']) && hasPermission('edit_users')) {
    $username = trim($conn->real_escape_string($_POST['username']));
    $email = trim($conn->real_escape_string($_POST['email']));
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $conn->real_escape_string($_POST['role']);
    
    // Check if username or email already exists
    $result = $conn->query("SELECT id FROM users WHERE username = '$username' OR email = '$email'");
    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Username or email already exists!";
        header("Location: users.php");
        exit();
    }
    
    // Insert new user
    $conn->query("INSERT INTO users (username, email, password, role) VALUES ('$username', '$email', '$password', '$role')");
    $user_id = $conn->insert_id;
    
    // Log the user creation
    logUserAction('create_user', $user_id, [
        'new' => json_encode([
            'username' => $username,
            'email' => $email,
            'role' => $role
        ])
    ]);
    
    // Handle permissions if any were selected
    if (isset($_POST['permissions']) && hasPermission('edit_roles')) {
        foreach ($_POST['permissions'] as $perm_id) {
            $perm_id = intval($perm_id);
            $conn->query("INSERT INTO user_permissions (user_id, permission_id) VALUES ($user_id, $perm_id)");
        }
        
        // Log permission changes if any
        if (!empty($_POST['permissions'])) {
            logUserAction('update_permissions', $user_id, [
                'new' => json_encode($_POST['permissions'])
            ]);
        }
    }
    
    $_SESSION['success'] = "User created successfully!";
    header("Location: users.php");
    exit();
}

// Handle user update (now includes password update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user']) && hasPermission('edit_users')) {
    $user_id = intval($_POST['user_id']);
    $username = trim($conn->real_escape_string($_POST['username']));
    $email = trim($conn->real_escape_string($_POST['email']));
    $role = $conn->real_escape_string($_POST['role']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Get current user data for logging
    $currentUser = $conn->query("SELECT username, email, role, is_active FROM users WHERE id = $user_id")->fetch_assoc();
    
    // Check if username or email already exists (excluding current user)
    $result = $conn->query("SELECT id FROM users WHERE (username = '$username' OR email = '$email') AND id != $user_id");
    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Username or email already exists!";
        header("Location: users.php");
        exit();
    }
    
    // Prepare changes for audit log
    $changes = [];
    if ($currentUser['username'] !== $username) {
        $changes['username'] = ['old' => $currentUser['username'], 'new' => $username];
    }
    if ($currentUser['email'] !== $email) {
        $changes['email'] = ['old' => $currentUser['email'], 'new' => $email];
    }
    if ($currentUser['role'] !== $role) {
        $changes['role'] = ['old' => $currentUser['role'], 'new' => $role];
    }
    if ($currentUser['is_active'] != $is_active) {
        $changes['is_active'] = ['old' => $currentUser['is_active'], 'new' => $is_active];
    }
    
    // Build the base update query
    $updateQuery = "UPDATE users SET username = '$username', email = '$email', role = '$role', is_active = $is_active";
    
    // Add password update if new password was provided
    if (!empty($_POST['new_password'])) {
        $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $updateQuery .= ", password = '$new_password'";
        $changes['password'] = ['new' => 'updated']; // We don't store old passwords
    }
    
    // Complete the query
    $updateQuery .= " WHERE id = $user_id";
    
    // Execute the update
    $conn->query($updateQuery);
    
    // Log the changes if any
    if (!empty($changes)) {
        logUserAction('update_user', $user_id, [
            'old' => json_encode(array_map(fn($c) => $c['old'], $changes)),
            'new' => json_encode(array_map(fn($c) => $c['new'], $changes))
        ]);
    }
    
    // Handle permissions if user has permission to edit roles
    if (hasPermission('edit_roles')) {
        // Get current permissions for logging
        $currentPerms = [];
        $result = $conn->query("SELECT permission_id FROM user_permissions WHERE user_id = $user_id");
        while ($row = $result->fetch_assoc()) {
            $currentPerms[] = $row['permission_id'];
        }
        
        // First remove all existing permissions
        $conn->query("DELETE FROM user_permissions WHERE user_id = $user_id");
        
        // Add new permissions if any were selected
        $newPerms = [];
        if (isset($_POST['permissions'])) {
            foreach ($_POST['permissions'] as $perm_id) {
                $perm_id = intval($perm_id);
                $conn->query("INSERT INTO user_permissions (user_id, permission_id) VALUES ($user_id, $perm_id)");
                $newPerms[] = $perm_id;
            }
        }
        
        // Log permission changes if any
        if ($currentPerms != $newPerms) {
            logUserAction('update_permissions', $user_id, [
                'old' => json_encode($currentPerms),
                'new' => json_encode($newPerms)
            ]);
        }
    }
    
    $_SESSION['success'] = "User updated successfully!";
    header("Location: users.php");
    exit();
}

// Handle user deletion
if (isset($_GET['delete']) && hasPermission('delete_users')) {
    $user_id = intval($_GET['delete']);
    
    // Prevent deleting yourself
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['error'] = "You cannot delete your own account!";
        header("Location: users.php");
        exit();
    }
    
    // Check if this is the last admin
    $result = $conn->query("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin'");
    $admin_count = $result->fetch_assoc()['admin_count'];
    
    $result = $conn->query("SELECT role FROM users WHERE id = $user_id");
    $user_role = $result->fetch_assoc()['role'];
    
    if ($user_role == 'admin' && $admin_count <= 1) {
        $_SESSION['error'] = "Cannot delete the last admin user!";
        header("Location: users.php");
        exit();
    }
    
    // Get user data for logging before deletion
    $userData = $conn->query("SELECT username, email, role FROM users WHERE id = $user_id")->fetch_assoc();
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // First delete related records in audit_logs
        $conn->query("DELETE FROM audit_logs WHERE user_id = $user_id");
        
        // Then delete user permissions
        $conn->query("DELETE FROM user_permissions WHERE user_id = $user_id");
        
        // Finally delete the user
        $conn->query("DELETE FROM users WHERE id = $user_id");
        
        // Commit transaction
        $conn->commit();
        
        // Log the deletion
        logUserAction('delete_user', $user_id, [
            'old' => json_encode($userData)
        ]);
        
        $_SESSION['success'] = "User deleted successfully!";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = "Error deleting user: " . $e->getMessage();
    }
    
    header("Location: users.php");
    exit();
}

// Get all users with unique IDs
$users = [];
$user_ids = []; // Track seen user IDs
$result = $conn->query("SELECT * FROM users ORDER BY role, username");
while ($row = $result->fetch_assoc()) {
    if (!in_array($row['id'], $user_ids)) {
        $users[] = $row;
        $user_ids[] = $row['id'];
    }
}

// Get all permissions grouped by category
$permissions = [];
$permResult = $conn->query("SELECT * FROM permissions ORDER BY category, name");
while ($row = $permResult->fetch_assoc()) {
    $permissions[$row['category']][] = $row;
}

// Get user permissions for each user without using references
foreach ($users as $key => $user) {
    $users[$key]['permissions'] = [];
    $userPerms = $conn->query("SELECT permission_id FROM user_permissions WHERE user_id = {$user['id']}");
    while ($row = $userPerms->fetch_assoc()) {
        $users[$key]['permissions'][] = $row['permission_id'];
    }
}

$conn->close();
?>

<!-- Rest of your HTML/PHP code remains exactly the same -->
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
    
    .users-container {
        background-color: #f8f9fa;
        min-height: calc(100vh - 120px);
    }
    
    .card {
        border: none;
        border-radius: 12px;
        box-shadow: var(--shadow);
        overflow: hidden;
        transition: all 0.3s ease;
    }
    
    .card:hover {
        box-shadow: var(--shadow), var(--glow);
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
    
    .btn-primary {
        background-color: var(--primary-blue);
        border: none;
        border-radius: 8px;
        padding: 0.5rem 1.25rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        background-color: var(--hover-blue);
        transform: translateY(-2px);
    }
    
    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #e0e0e0;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: var(--primary-blue);
        box-shadow: 0 0 0 0.25rem rgba(26, 115, 232, 0.15);
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
    }
    
    .table th, .table td {
        padding: 1rem;
        vertical-align: middle;
        border-top: 1px solid #f0f0f0;
    }
    
    .table-hover tbody tr:hover {
        background-color: rgba(26, 115, 232, 0.05);
    }
    
    .badge {
        border-radius: 8px;
        font-weight: 500;
        padding: 0.35em 0.65em;
    }
    
    .badge-active {
        background-color: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }
    
    .badge-inactive {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }
    
    .badge-admin {
        background-color: rgba(13, 110, 253, 0.1);
        color: #0d6efd;
    }
    
    .badge-manager {
        background-color: rgba(255, 193, 7, 0.1);
        color: #ffc107;
    }
    
    .badge-staff {
        background-color: rgba(108, 117, 125, 0.1);
        color: #6c757d;
    }
    
    .action-btn {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
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
    
    .permission-card {
        border-radius: 8px;
        margin-bottom: 1rem;
        border: 1px solid #f0f0f0;
    }
    
    .permission-card-header {
        background-color: var(--light-blue);
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .form-check-input:checked {
        background-color: var(--primary-blue);
        border-color: var(--primary-blue);
    }
    
    .form-switch .form-check-input {
        width: 3em;
        height: 1.5em;
    }
    
    .password-toggle {
        cursor: pointer;
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        z-index: 5;
    }
    
    .password-input-container {
        position: relative;
    }
    
    @media (max-width: 768px) {
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .action-btns {
            white-space: nowrap;
        }
    }
</style>

<div class="floating-shapes">
    <div class="shape"></div>
    <div class="shape"></div>
</div>

<div class="users-container">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="fas fa-users me-2"></i>User Management
            </h2>
            <div class="text-muted">
                <?php echo count($users); ?> <?php echo count($users) === 1 ? 'User' : 'Users'; ?>
            </div>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger mb-4">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php elseif (isset($_SESSION['success'])): ?>
            <div class="alert alert-success mb-4">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (hasPermission('edit_users')): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>Create New User
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="users.php">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="col-md-4">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="col-md-4">
                                <label for="password" class="form-label">Password</label>
                                <div class="password-input-container">
                                    <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                                    <i class="far fa-eye password-toggle" data-target="password"></i>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row g-3 mt-2">
                            <div class="col-md-4">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="admin">Admin</option>
                                    <option value="inventory_manager">Inventory Manager</option>
                                    <option value="staff" selected>Staff</option>
                                </select>
                            </div>
                            <div class="col-md-8 d-flex align-items-end">
                                <button type="submit" name="create_user" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Create User
                                </button>
                            </div>
                        </div>
                        
                        <?php if (hasPermission('edit_roles')): ?>
                        <div class="mt-4">
                            <h5 class="mb-3">Additional Permissions</h5>
                            <p class="text-muted">Select additional permissions beyond the role's default permissions</p>
                            
                            <?php foreach ($permissions as $category => $perms): ?>
                                <div class="permission-card">
                                    <div class="permission-card-header">
                                        <h6 class="mb-0"><?php echo ucfirst(str_replace('_', ' ', $category)); ?></h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <?php foreach ($perms as $perm): ?>
                                                <div class="col-md-4 mb-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" 
                                                            name="permissions[]" 
                                                            value="<?php echo $perm['id']; ?>" 
                                                            id="perm_<?php echo $perm['id']; ?>">
                                                        <label class="form-check-label" for="perm_<?php echo $perm['id']; ?>">
                                                            <?php echo ucfirst(str_replace('_', ' ', $perm['name'])); ?>
                                                        </label>
                                                        <?php if ($perm['description']): ?>
                                                            <small class="text-muted d-block"><?php echo $perm['description']; ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>User List
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th class="action-btns">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo $user['role'] == 'admin' ? 'badge-admin' : 
                                                ($user['role'] == 'inventory_manager' ? 'badge-manager' : 'badge-staff'); 
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $user['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td class="action-btns">
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-outline-primary action-btn edit-btn"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editUserModal<?php echo $user['id']; ?>"
                                                    data-bs-toggle="tooltip" title="Edit User">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <?php if (hasPermission('delete_users') && $user['id'] != $_SESSION['user_id']): ?>
                                                <a href="users.php?delete=<?php echo $user['id']; ?>" 
                                                class="btn btn-sm btn-outline-danger action-btn delete-btn"
                                                data-bs-toggle="tooltip" title="Delete User">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- Edit User Modal -->
                                <div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    <i class="fas fa-user-edit me-2"></i>
                                                    Edit User: <?php echo htmlspecialchars($user['username']); ?>
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST" action="users.php">
                                                <div class="modal-body">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label for="username<?php echo $user['id']; ?>" class="form-label">Username</label>
                                                            <input type="text" class="form-control" id="username<?php echo $user['id']; ?>" 
                                                                name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                                        </div>
                                                        
                                                        <div class="col-md-6">
                                                            <label for="email<?php echo $user['id']; ?>" class="form-label">Email</label>
                                                            <input type="email" class="form-control" id="email<?php echo $user['id']; ?>" 
                                                                name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row g-3 mt-2">
                                                        <div class="col-md-6">
                                                            <label for="role<?php echo $user['id']; ?>" class="form-label">Role</label>
                                                            <select class="form-select" id="role<?php echo $user['id']; ?>" name="role" required>
                                                                <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                                <option value="inventory_manager" <?php echo $user['role'] == 'inventory_manager' ? 'selected' : ''; ?>>Inventory Manager</option>
                                                                <option value="staff" <?php echo $user['role'] == 'staff' ? 'selected' : ''; ?>>Staff</option>
                                                            </select>
                                                        </div>
                                                        
                                                        <div class="col-md-6 d-flex align-items-center">
                                                            <div class="form-check form-switch">
                                                                <input type="checkbox" class="form-check-input" id="is_active<?php echo $user['id']; ?>" 
                                                                    name="is_active" <?php echo $user['is_active'] ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="is_active<?php echo $user['id']; ?>">Active User</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <hr>
                                                    
                                                    <h5 class="mb-3">Permissions</h5>
                                                    <p class="text-muted mb-3">Manage individual permissions for this user</p>
                                                    
                                                    <?php foreach ($permissions as $category => $perms): ?>
                                                        <div class="permission-card mb-3">
                                                            <div class="permission-card-header">
                                                                <h6 class="mb-0"><?php echo ucfirst(str_replace('_', ' ', $category)); ?></h6>
                                                            </div>
                                                            <div class="card-body">
                                                                <div class="row">
                                                                    <?php foreach ($perms as $perm): ?>
                                                                        <div class="col-md-4 mb-3">
                                                                            <div class="form-check">
                                                                                <input class="form-check-input" type="checkbox" 
                                                                                    name="permissions[]" 
                                                                                    value="<?php echo $perm['id']; ?>" 
                                                                                    id="perm_<?php echo $user['id']; ?>_<?php echo $perm['id']; ?>"
                                                                                    <?php echo in_array($perm['id'], $user['permissions']) ? 'checked' : ''; ?>>
                                                                                <label class="form-check-label" for="perm_<?php echo $user['id']; ?>_<?php echo $perm['id']; ?>">
                                                                                    <?php echo ucfirst(str_replace('_', ' ', $perm['name'])); ?>
                                                                                </label>
                                                                                <?php if ($perm['description']): ?>
                                                                                    <small class="text-muted d-block"><?php echo $perm['description']; ?></small>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                    
                                                    <hr>
                                                    
                                                    <h5 class="mb-3">Password Information</h5>
                                                    <div class="alert alert-info mb-3">
                                                        <i class="fas fa-info-circle me-2"></i>
                                                        Passwords are securely hashed and cannot be viewed. You can reset it below.
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="new_password<?php echo $user['id']; ?>" class="form-label">New Password</label>
                                                        <div class="password-input-container">
                                                            <input type="password" class="form-control" id="new_password<?php echo $user['id']; ?>" 
                                                                name="new_password" minlength="8" placeholder="Enter new password to reset">
                                                            <i class="far fa-eye password-toggle" data-target="new_password<?php echo $user['id']; ?>"></i>
                                                        </div>
                                                        <small class="text-muted password-change-message">Leave blank to keep current password</small>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                        <i class="fas fa-times me-1"></i> Close
                                                    </button>
                                                    <button type="submit" name="update_user" class="btn btn-primary">
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
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Confirm before deleting
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this user?')) {
                e.preventDefault();
            }
        });
    });
    
    // Fix for modal close issue
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function () {
            // Remove the modal backdrop if it persists
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());
            // Reset body class
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
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
    
    // Password toggle functionality
    document.querySelectorAll('.password-toggle').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });
    });
    
    // Show message when password field has content
    document.querySelectorAll('input[type="password"][name="new_password"]').forEach(input => {
        input.addEventListener('input', function() {
            const message = this.closest('.password-input-container').nextElementSibling;
            if (message && message.classList.contains('password-change-message')) {
                message.textContent = this.value.trim() !== '' 
                    ? 'Password will be updated' 
                    : 'Leave blank to keep current password';
            }
        });
    });
});
</script>
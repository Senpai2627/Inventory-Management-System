<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_operations.php';

if (!isLoggedIn()) {
    header("Location: /inventory_system/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    
    $updateStmt = $conn->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
    $updateStmt->bind_param("ssi", $username, $email, $user_id);
    
    if ($updateStmt->execute()) {
        $_SESSION['username'] = $username;
        $_SESSION['success'] = "Profile updated successfully";
        header("Refresh:0");
        exit();
    } else {
        $_SESSION['error'] = "Error updating profile";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (!password_verify($current_password, $user['password'])) {
        $_SESSION['error'] = "Current password is incorrect";
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = "New passwords don't match";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $passwordStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $passwordStmt->bind_param("si", $hashed_password, $user_id);
        
        if ($passwordStmt->execute()) {
            $_SESSION['success'] = "Password changed successfully";
            header("Refresh:0");
            exit();
        } else {
            $_SESSION['error'] = "Error changing password";
        }
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5>Admin Profile</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="profile.php">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" value="Admin" readonly>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-warning">
                    <h5>Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="profile.php">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                        </div>
                        <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
                    </form>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header bg-info text-white">
                    <h5>Staff Actions</h5>
                </div>
                <div class="card-body">
                    <a href="users.php" class="btn btn-info me-2">
                        <i class="fas fa-boxes me-1"></i> Inventory 
                    </a>
                    <a href="audit_logs.php" class="btn btn-secondary">
                        <i class="fas fa-exchange-alt me-1"></i> Transaction
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
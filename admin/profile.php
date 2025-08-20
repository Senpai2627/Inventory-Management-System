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

<style>
    :root {
        --primary-blue: #1a73e8;
        --light-blue: #e8f0fe;
        --dark-blue: #0d47a1;
        --accent-blue: #4285f4;
        --warning-orange: #ff6d00;
        --info-teal: #00acc1;
        --admin-purple: #6a1b9a;
    }
    
    .admin-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        transition: transform 0.3s ease;
        margin-bottom: 20px;
    }
    
    .admin-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
    }
    
    .card-header {
        background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
        color: white;
        padding: 1.25rem 1.5rem;
        border-bottom: none;
    }
    
    .password-header {
        background: linear-gradient(135deg, var(--warning-orange), #e65100);
    }
    
    .admin-actions-header {
        background: linear-gradient(135deg, var(--admin-purple), #4a148c);
    }
    
    .btn-primary {
        background-color: var(--primary-blue);
        border-color: var(--primary-blue);
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        background-color: var(--dark-blue);
        border-color: var(--dark-blue);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(26, 115, 232, 0.3);
    }
    
    .btn-warning {
        background-color: var(--warning-orange);
        border-color: var(--warning-orange);
        transition: all 0.3s ease;
    }
    
    .btn-warning:hover {
        background-color: #e65100;
        border-color: #e65100;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(255, 109, 0, 0.3);
    }
    
    .btn-admin {
        background-color: var(--admin-purple);
        border-color: var(--admin-purple);
        transition: all 0.3s ease;
    }
    
    .btn-admin:hover {
        background-color: #4a148c;
        border-color: #4a148c;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(106, 27, 154, 0.3);
    }
    
    .form-control:focus, .form-select:focus {
        border-color: var(--accent-blue);
        box-shadow: 0 0 0 0.25rem rgba(26, 115, 232, 0.25);
    }
    
    .admin-avatar {
        width: 100px;
        height: 100px;
        background-color: var(--light-blue);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        font-size: 2.5rem;
        color: var(--primary-blue);
        border: 3px solid var(--primary-blue);
    }
    
    .password-strength {
        height: 5px;
        margin-top: 5px;
        background-color: #e0e0e0;
        border-radius: 3px;
        overflow: hidden;
    }
    
    .password-strength-bar {
        height: 100%;
        width: 0%;
        transition: width 0.3s ease;
    }
    
    .admin-badge {
        background-color: var(--admin-purple);
        color: white;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: bold;
    }
    
    .admin-btn {
        border-radius: 50px;
        padding: 8px 20px;
        font-weight: 500;
        margin: 5px;
    }
    
    .admin-watermark {
        position: fixed;
        bottom: 20px;
        right: 20px;
        opacity: 0.05;
        z-index: -1;
    }
    
    .input-group-text {
        background-color: var(--light-blue);
        color: var(--primary-blue);
    }
</style>

<div class="container mt-4">
    <div class="admin-watermark">
        <svg width="200" height="200" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm1-13h-2v6h2V7zm0 8h-2v2h2v-2z" fill="var(--primary-blue)"/>
        </svg>
    </div>
    
    <div class="row">
        <div class="col-lg-4">
            <div class="admin-card">
                <div class="card-body text-center">
                    <div class="admin-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($user['username']); ?></h3>
                    <span class="admin-badge">Administrator</span>
                    <hr>
                    <p><i class="fas fa-envelope me-2 text-primary"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><i class="fas fa-calendar-alt me-2 text-primary"></i> Member since <?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                    <p><i class="fas fa-key me-2 text-primary"></i> Last updated: <?php echo date('M d, Y', strtotime($user['updated_at'])); ?></p>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="card-header admin-actions-header">
                    <h5><i class="fas fa-tools me-2"></i>Admin Tools</h5>
                </div>
                <div class="card-body text-center">
                    <a href="users.php" class="btn btn-admin admin-btn">
                        <i class="fas fa-users-cog me-1"></i> Manage Users
                    </a>
                    <a href="audit_logs.php" class="btn btn-secondary admin-btn">
                        <i class="fas fa-clipboard-list me-1"></i> Audit Logs
                    </a>
                   
                </div>
            </div>
        </div>
        
        <div class="col-lg-8">
            <div class="admin-card">
                <div class="card-header">
                    <h5><i class="fas fa-user-edit me-2"></i>Admin Profile Settings</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="profile.php">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user-tie"></i></span>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Account Type</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-shield-alt"></i></span>
                                <input type="text" class="form-control" value="Administrator" readonly>
                            </div>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary w-100 py-2">
                            <i class="fas fa-save me-2"></i>Update Admin Profile
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="admin-card">
                <div class="card-header password-header">
                    <h5><i class="fas fa-key me-2"></i>Change Admin Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="profile.php">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength">
                                <div class="password-strength-bar" id="password-strength-bar"></div>
                            </div>
                            <small class="text-muted">Minimum 8 characters with uppercase, lowercase, number, and special character</small>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-warning w-100 py-2">
                            <i class="fas fa-key me-2"></i>Change Admin Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.parentNode.querySelector('input');
            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
    
    // Password strength indicator
    document.getElementById('new_password').addEventListener('input', function() {
        const password = this.value;
        const strengthBar = document.getElementById('password-strength-bar');
        let strength = 0;
        
        // Length check
        if (password.length >= 8) strength += 1;
        // Contains uppercase and lowercase
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 1;
        // Contains number
        if (password.match(/[0-9]/)) strength += 1;
        // Contains special character
        if (password.match(/[^a-zA-Z0-9]/)) strength += 1;
        
        const width = strength * 25;
        strengthBar.style.width = width + '%';
        
        if (strength < 2) {
            strengthBar.style.backgroundColor = '#ff5252';
        } else if (strength < 4) {
            strengthBar.style.backgroundColor = '#ffab40';
        } else {
            strengthBar.style.backgroundColor = '#4caf50';
        }
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
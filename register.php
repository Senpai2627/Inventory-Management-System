<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header("Location: " . getDashboardLink());
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = sanitizeInput($_POST['password']);
    $confirmPassword = sanitizeInput($_POST['confirm_password']);
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $_SESSION['error'] = "All fields are required";
    } elseif ($password !== $confirmPassword) {
        $_SESSION['error'] = "Passwords do not match";
    } elseif (strlen($password) < 8) {
        $_SESSION['error'] = "Password must be at least 8 characters";
    } else {
        $conn = getDBConnection();
        
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $_SESSION['error'] = "Username or email already exists";
        } else {
            // Create new user (default role is staff)
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'staff')");
            $stmt->bind_param("sss", $username, $hashedPassword, $email);
            
            if ($stmt->execute()) {
                $userId = $conn->insert_id;
                
                // Log the registration action with IP information
                $ip_address = getClientIP();
                $location = getIPLocation($ip_address);
                
                logAction("user_registration", "users", $userId, null, json_encode([
                    'ip_address' => $ip_address,
                    'location' => $location,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT']
                ], JSON_PRETTY_PRINT));
                
                $_SESSION['success'] = "Registration successful. Please login.";
                header("Location: login.php");
                exit();
            } else {
                $_SESSION['error'] = "Registration failed. Please try again.";
            }
        }
        
        $stmt->close();
        $conn->close();
    }
    
    header("Location: register.php");
    exit();
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/register.css">
</head>
<body>
    <div class="register-container">
        <div class="register-card">
            <div class="card-header">
                <h4>Create Account</h4>
                <div class="system-title">Inventory Management System</div>
            </div>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <div class="card-body">
                <form method="POST" action="register.php" id="registerForm">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required autofocus>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                        </div>
                        <ul class="requirement-list">
                            <li id="length-req"><i class="fas fa-circle" style="font-size: 0.5rem; vertical-align: middle;"></i> Minimum 8 characters</li>
                            <li id="uppercase-req"><i class="fas fa-circle" style="font-size: 0.5rem; vertical-align: middle;"></i> At least one uppercase letter</li>
                            <li id="number-req"><i class="fas fa-circle" style="font-size: 0.5rem; vertical-align: middle;"></i> At least one number</li>
                            <li id="special-req"><i class="fas fa-circle" style="font-size: 0.5rem; vertical-align: middle;"></i> At least one special character</li>
                        </ul>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
                        <div class="password-match">
                            <div class="password-match-indicator" id="passwordMatchIndicator"></div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-register" id="registerButton">
                        Register
                    </button>
                </form>
            </div>
            <div class="card-footer">
                <p class="mb-0">Already have an account? <a href="login.php">Sign In</a></p>
            </div>
        </div>
    </div>
<?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('registerButton');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Registering...';
        });

        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('passwordStrengthBar');
        const requirements = {
            length: document.getElementById('length-req'),
            uppercase: document.getElementById('uppercase-req'),
            number: document.getElementById('number-req'),
            special: document.getElementById('special-req')
        };

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Check requirements
            const hasLength = password.length >= 8;
            const hasUppercase = /[A-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[^A-Za-z0-9]/.test(password);
            
            // Update requirement indicators
            updateRequirement(requirements.length, hasLength);
            updateRequirement(requirements.uppercase, hasUppercase);
            updateRequirement(requirements.number, hasNumber);
            updateRequirement(requirements.special, hasSpecial);
            
            // Calculate strength
            if (hasLength) strength += 25;
            if (hasUppercase) strength += 25;
            if (hasNumber) strength += 25;
            if (hasSpecial) strength += 25;
            
            // Update strength bar
            strengthBar.style.width = strength + '%';
            
            // Change color based on strength
            if (strength < 50) {
                strengthBar.style.backgroundColor = '#dc2626'; // Red
            } else if (strength < 75) {
                strengthBar.style.backgroundColor = '#ea580c'; // Orange
            } else {
                strengthBar.style.backgroundColor = '#16a34a'; // Green
            }
        });

        function updateRequirement(element, isValid) {
            if (isValid) {
                element.classList.add('valid');
                element.querySelector('i').style.color = '#16a34a';
            } else {
                element.classList.remove('valid');
                element.querySelector('i').style.color = '#6b7280';
            }
        }

        // Password match indicator
        const confirmPasswordInput = document.getElementById('confirm_password');
        const matchIndicator = document.getElementById('passwordMatchIndicator');

        confirmPasswordInput.addEventListener('input', function() {
            const password = passwordInput.value;
            const confirmPassword = this.value;
            
            if (confirmPassword.length === 0) {
                matchIndicator.style.width = '0%';
                return;
            }
            
            if (password === confirmPassword) {
                matchIndicator.style.width = '100%';
                matchIndicator.style.backgroundColor = '#16a34a'; // Green
            } else {
                const matchPercentage = Math.min(100, (confirmPassword.length / password.length) * 100);
                matchIndicator.style.width = matchPercentage + '%';
                matchIndicator.style.backgroundColor = '#dc2626'; // Red
            }
        });
    </script>
</body>
</html>
<?php 
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';


if (isLoggedIn()) {
    header("Location: " . getDashboardLink());
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recaptcha_secret = '6Ld-AH8rAAAAAKv1_ATKYESRqBINuq9ahYucigZm'; // Replace with your secret key
    $recaptcha_response = $_POST['g-recaptcha-response'];

    $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_data = [
        'secret' => $recaptcha_secret,
        'response' => $recaptcha_response,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];

    $recaptcha_options = [
        'http' => [
            'method' => 'POST',
            'content' => http_build_query($recaptcha_data),
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n"
        ]
    ];

    $recaptcha_context = stream_context_create($recaptcha_options);
    $recaptcha_result = file_get_contents($recaptcha_url, false, $recaptcha_context);
    $recaptcha_json = json_decode($recaptcha_result);

    if (!$recaptcha_json->success) {
        $_SESSION['error'] = "Please complete the reCAPTCHA verification";
        header("Location: login.php");
        exit();
    }

    $username = sanitizeInput($_POST['username']);
    $password = sanitizeInput($_POST['password']);
    $rememberMe = isset($_POST['rememberMe']) ? true : false;

    $conn = getDBConnection();

    $stmt = $conn->prepare("SELECT id, username, password, role, email FROM users WHERE username = ? AND is_active = 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];

            if ($rememberMe) {
                $session_token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
                $ip_address = getClientIP();
                $user_agent = $_SERVER['HTTP_USER_AGENT'];

                $stmt = $conn->prepare("INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $user['id'], $session_token, $ip_address, $user_agent, $expires_at);
                $stmt->execute();

                setcookie('remember_token', $session_token, [
                    'expires' => strtotime('+3 days'),
                    'path' => '/',
                    'httponly' => true,
                    'samesite' => 'Strict',
                    'secure' => isset($_SERVER['HTTPS'])
                ]);
            }

            $ip_address = getClientIP();
            $location = getIPLocation($ip_address);
            logAction("user_login", "users", $user['id'], null, json_encode([
                'ip_address' => $ip_address,
                'location' => $location,
                'user_agent' => $_SERVER['HTTP_USER_AGENT']
            ], JSON_PRETTY_PRINT));

            // ✅ OTP Logic Here
            $_SESSION['otp'] = rand(100000, 999999);
            $_SESSION['otp_verified'] = false;

            header("Location: verify-otp.php");
            exit();
        }
    }

    $ip_address = getClientIP();
    $location = getIPLocation($ip_address);

    logAction("failed_login_attempt", "users", null, null, json_encode([
        'username' => $username,
        'ip_address' => $ip_address,
        'location' => $location,
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ], JSON_PRETTY_PRINT));

    $_SESSION['error'] = "Invalid username or password";
    header("Location: login.php");
    exit();
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="card-header">
                <h4>Login</h4>
                <div class="system-title">Inventory Management System</div>
            </div>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <div class="card-body">
                <form method="POST" action="login.php" id="loginForm">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required autofocus>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="form-footer">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="rememberMe" name="rememberMe">
                            <label class="form-check-label" for="rememberMe">Remember me</label>
                        </div>
                        <a href="forgot-password.php" class="forgot-password">Forgot password?</a>
                    </div>
                    
                    <div class="g-recaptcha" data-sitekey="6Ld-AH8rAAAAAH2FVc10QCpVrzPSaa79KHjazFSo"></div>
                    
                    <button type="submit" class="btn btn-login">
                        Sign In
                    </button>
                </form>
            </div>
            <div class="card-footer">
                <p class="mb-0">Don't have an account? <a href="register.php">Sign Up</a></p>
            </div>
        </div>
    </div>
<?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const recaptchaResponse = grecaptcha.getResponse();
            if (recaptchaResponse.length === 0) {
                e.preventDefault();
                alert("Please complete the reCAPTCHA verification");
                return false;
            }
            
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Signing In...';
        });
    </script>
</body>

</html>
<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['otp'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredOtp = trim($_POST['otp']);

    if ($enteredOtp == $_SESSION['otp']) {
        $_SESSION['otp_verified'] = true;
        unset($_SESSION['otp']);
        header("Location: " . getDashboardLink());
        exit();
    } else {
        $error = "Invalid OTP. Try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            max-width: 400px;
            margin: 0 auto;
            padding: 2rem;
            color: #333;
        }
        h1 {
            font-weight: 500;
            text-align: center;
            margin-bottom: 2rem;
        }
        .otp-box {
            background: #f8f9fa;
            padding: 1rem;
            text-align: center;
            margin-bottom: 1.5rem;
            border-radius: 4px;
        }
        input {
            width: 100%;
            padding: 0.75rem;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 0.75rem;
            background: #000;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .error {
            color: #dc3545;
            text-align: center;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <h1>Verify OTP</h1>

    <div class="otp-box">
        Your OTP: <strong id="otpText"><?= $_SESSION['otp'] ?></strong>
    </div>

    <?php if (isset($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="otp" id="otp" placeholder="Enter OTP" required>
        <button type="submit">Continue</button>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const otp = document.getElementById('otpText').textContent;

            if (Notification.permission === "granted") {
                new Notification("Your One-Time Password", {
                    body: "OTP Code: " + otp,
                    icon: "https://cdn-icons-png.flaticon.com/512/2910/2910768.png",
                });
            } else if (Notification.permission !== "denied") {
                Notification.requestPermission().then(permission => {
                    if (permission === "granted") {
                        new Notification("Your One-Time Password", {
                            body: "OTP Code: " + otp,
                            icon: "https://cdn-icons-png.flaticon.com/512/2910/2910768.png",
                        });
                    }
                });
            }
        });
    </script>
</body>
</html>
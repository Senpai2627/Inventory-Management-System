<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header("Location: " . getDashboardLink());
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-blue: #1a73e8;
            --dark-blue: #0d47a1;
            --light-blue: #e8f0fe;
            --hover-blue: #4285f4;
            --white: #ffffff;
            --shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--white);
            color: #333;
            overflow-x: hidden;
        }
        
        /* Header Styles */
        header {
            background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
            color: var(--white);
            padding: 3rem 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path fill="rgba(255,255,255,0.05)" d="M0,0 L100,0 L100,100 L0,100 Z" /></svg>');
            background-size: cover;
            animation: wave 15s linear infinite;
        }
        
        @keyframes wave {
            0% { background-position: 0 0; }
            100% { background-position: 100% 0; }
        }
        
        header h1 {
            font-weight: 700;
            font-size: 3rem;
            margin-bottom: 1rem;
            position: relative;
            animation: fadeInDown 1s ease;
        }
        
        /* Hero Section */
        .hero {
            text-align: center;
            padding: 4rem 0;
            position: relative;
        }
        
        .hero h2 {
            font-size: 2.5rem;
            color: var(--dark-blue);
            margin-bottom: 1.5rem;
            animation: fadeIn 1.5s ease;
        }
        
        .hero p {
            font-size: 1.2rem;
            color: #555;
            max-width: 700px;
            margin: 0 auto 2rem;
            animation: fadeIn 2s ease;
        }
        
        /* Auth Buttons */
        .auth-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
            animation: fadeInUp 1s ease;
        }
        
        .btn {
            background-color: var(--primary-blue);
            color: var(--white);
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }
        
        .btn:hover {
            background-color: var(--hover-blue);
            color: var(--white);
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.15);
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-secondary {
            background-color: var(--white);
            color: var(--primary-blue);
            border: 2px solid var(--primary-blue);
        }
        
        .btn-secondary:hover {
            background-color: var(--light-blue);
            color: var(--primary-blue);
        }
        
        /* Features Section */
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            padding: 3rem 0;
        }
        
        .feature-card {
            background: var(--white);
            border-radius: 12px;
            padding: 2rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            border-top: 4px solid var(--primary-blue);
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.6s ease forwards;
        }
        
        .feature-card:nth-child(1) { animation-delay: 0.2s; }
        .feature-card:nth-child(2) { animation-delay: 0.4s; }
        .feature-card:nth-child(3) { animation-delay: 0.6s; }
        .feature-card:nth-child(4) { animation-delay: 0.8s; }
        .feature-card:nth-child(5) { animation-delay: 1.0s; }
        .feature-card:nth-child(6) { animation-delay: 1.2s; }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .feature-card h3 {
            color: var(--dark-blue);
            margin-bottom: 1rem;
            font-size: 1.4rem;
        }
        
        .feature-card p {
            color: #666;
            line-height: 1.6;
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            header h1 {
                font-size: 2.2rem;
            }
            
            .hero h2 {
                font-size: 1.8rem;
            }
            
            .auth-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 250px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1><i class="fas fa-box-open"></i> Inventory Management System</h1>
        </div>
    </header>
    
    <div class="container">
        <section class="hero">
            <h2>Streamline Your Inventory Management</h2>
            <p>Track, manage, and optimize your inventory with our powerful RBAC-enabled system</p>
            
            <div class="auth-buttons">
                <a href="login.php" class="btn"><i class="fas fa-sign-in-alt me-2"></i>Login</a>
                <a href="register.php" class="btn btn-secondary"><i class="fas fa-user-plus me-2"></i>Register</a>
            </div>
        </section>
        
        <section class="features">
            <div class="feature-card">
                <h3><i class="fas fa-user-shield me-2"></i>Role-Based Access</h3>
                <p>Secure system with different permission levels for admins, managers, and staff.</p>
            </div>
            
            <div class="feature-card">
                <h3><i class="fas fa-boxes me-2"></i>Inventory Tracking</h3>
                <p>Comprehensive tracking of all inventory items with quantities, prices, and locations.</p>
            </div>
            
            <div class="feature-card">
                <h3><i class="fas fa-exchange-alt me-2"></i>Transaction History</h3>
                <p>Full audit trail of all inventory movements and changes.</p>
            </div>
            
            <div class="feature-card">
                <h3><i class="fas fa-chart-bar me-2"></i>Reporting</h3>
                <p>Generate reports and export data for analysis and decision making.</p>
            </div>
            
            <div class="feature-card">
                <h3><i class="fas fa-truck me-2"></i>Supplier Management</h3>
                <p>Track supplier information and manage relationships.</p>
            </div>
            
            <div class="feature-card">
                <h3><i class="fas fa-bell me-2"></i>Low Stock Alerts</h3>
                <p>Get notified when inventory items reach reorder levels.</p>
            </div>
        </section>
    </div>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Animate elements when they come into view
        document.addEventListener('DOMContentLoaded', function() {
            const animateOnScroll = () => {
                const elements = document.querySelectorAll('.feature-card');
                elements.forEach(element => {
                    const elementPosition = element.getBoundingClientRect().top;
                    const windowHeight = window.innerHeight;
                    
                    if (elementPosition < windowHeight - 100) {
                        element.style.animationPlayState = 'running';
                    }
                });
            };
            
            // Run once on load
            animateOnScroll();
            
            // Run on scroll
            window.addEventListener('scroll', animateOnScroll);
        });
    </script>
</body>
</html>